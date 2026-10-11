<?php

namespace App\Services\Panel;

use App\Jobs\DatabaseBackupJob;
use App\Jobs\DeleteResourceJob;
use App\Models\ScheduledDatabaseBackup;
use Illuminate\Database\Eloquent\Model;

/**
 * ABao panel: database operations for the Vue frontend (baouit-panel-front).
 * 阶段 3.2 W3.
 *
 * Covers detail read, whitelisted updates (name/description/limits + per-type
 * credentials = password change), deletion (DeleteResourceJob), and backup
 * plans (list / create / execute now / delete). All queries are scoped to the
 * current team; write endpoints enforce policies in the controller.
 */
class PanelDatabaseService
{
    /** Password fields that may be updated per database type (blank = keep). */
    private const TYPE_PASSWORD_FIELDS = [
        'standalone-postgresql' => ['postgres_password'],
        'standalone-mysql' => ['mysql_root_password', 'mysql_password'],
        'standalone-mariadb' => ['mariadb_root_password', 'mariadb_password'],
        'standalone-redis' => ['redis_password'],
        'standalone-mongodb' => ['mongo_initdb_root_password'],
        'standalone-keydb' => ['keydb_password'],
        'standalone-dragonfly' => ['dragonfly_password'],
        'standalone-clickhouse' => ['clickhouse_admin_password'],
    ];

    private const UPDATABLE_FIELDS = ['name', 'description', 'limits_memory', 'limits_cpus'];

    public function show(string $uuid): array
    {
        $database = $this->resolveDatabase($uuid);
        if (! $database instanceof Model) {
            return $database;
        }

        $data = [
            'uuid' => $database->uuid,
            'name' => $database->name,
            'description' => $database->description,
            'type' => $database->type(),
            'status' => $database->status,
            'image' => $database->image,
            'is_public' => (bool) $database->is_public,
            'public_port' => $database->public_port,
            'limits_memory' => $database->limits_memory,
            'limits_cpus' => $database->limits_cpus,
        ];

        // Database-specific display fields (user/database names; passwords are
        // never revealed back to the UI).
        foreach (['postgres_user', 'postgres_db', 'mysql_user', 'mysql_database', 'mariadb_user', 'mariadb_database', 'mongo_initdb_root_username', 'mongo_initdb_database', 'clickhouse_admin_user', 'sqlite_databases'] as $field) {
            $value = data_get($database, $field);
            if ($value !== null) {
                $data[$field] = $value;
            }
        }

        return $data;
    }

    public function update(string $uuid, array $data): array
    {
        $database = $this->resolveDatabase($uuid);
        if (! $database instanceof Model) {
            return $database;
        }

        $payload = [];
        foreach (self::UPDATABLE_FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        $passwordFields = self::TYPE_PASSWORD_FIELDS[$database->type()] ?? [];
        foreach ($passwordFields as $passwordField) {
            if (array_key_exists($passwordField, $data) && filled($data[$passwordField])) {
                $payload[$passwordField] = $data[$passwordField];
            }
        }

        if ($payload === []) {
            return ['status' => 'ok'];
        }

        try {
            $database->update($payload);
        } catch (\Throwable $e) {
            report($e);

            return ['status' => 'error', 'message' => '保存失败：'.$e->getMessage()];
        }

        return ['status' => 'ok'];
    }

    public function destroy(string $uuid): array
    {
        $database = $this->resolveDatabase($uuid);
        if (! $database instanceof Model) {
            return $database;
        }

        $database->delete();
        DeleteResourceJob::dispatch(
            resource: $database,
            deleteVolumes: true,
            deleteConnectedNetworks: true,
            deleteConfigurations: true,
            dockerCleanup: true,
        );
        auditLog('panel.database.deleted', [
            'team_id' => $database->team()?->id,
            'database_uuid' => $database->uuid,
            'database_name' => $database->name,
            'database_type' => $database->type(),
        ]);

        return ['status' => 'ok'];
    }

    // ------------------------------------------------------------------ 备份

    public function backups(string $uuid): array
    {
        $database = $this->resolveDatabase($uuid);
        if (! $database instanceof Model) {
            return $database;
        }

        return ScheduledDatabaseBackup::where('database_id', $database->id)
            ->where('database_type', $database->getMorphClass())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (ScheduledDatabaseBackup $backup) => $this->serializeBackup($backup))
            ->values()
            ->all();
    }

    public function createBackup(string $uuid, array $data): array
    {
        $database = $this->resolveDatabase($uuid);
        if (! $database instanceof Model) {
            return $database;
        }

        if (! $database->isBackupSolutionAvailable()) {
            return ['status' => 'validation', 'message' => '该数据库类型不支持计划备份。'];
        }

        $frequency = (string) ($data['frequency'] ?? '');
        if (! validate_cron_expression($frequency)) {
            return ['status' => 'validation', 'message' => '备份频率必须是有效的 cron 表达式。'];
        }

        $backup = new ScheduledDatabaseBackup;
        $backup->team_id = $database->team()->id;
        $backup->database_type = $database->getMorphClass();
        $backup->database_id = $database->id;
        $backup->description = $data['description'] ?? null;
        $backup->enabled = (bool) ($data['enabled'] ?? true);
        $backup->frequency = $frequency;
        $backup->dump_all = (bool) ($data['dump_all'] ?? false);
        $backup->database_backup_retention_amount_locally = (int) ($data['database_backup_retention_amount_locally'] ?? 10);
        $backup->database_backup_retention_days_locally = (int) ($data['database_backup_retention_days_locally'] ?? 7);

        try {
            $backup->save();
        } catch (\Throwable $e) {
            report($e);

            return ['status' => 'error', 'message' => '创建备份计划失败：'.$e->getMessage()];
        }

        return ['status' => 'ok', 'backup' => $this->serializeBackup($backup)];
    }

    public function backupNow(string $uuid, string $backupUuid): array
    {
        $database = $this->resolveDatabase($uuid);
        if (! $database instanceof Model) {
            return $database;
        }

        $backup = $this->resolveBackup($database, $backupUuid);
        if (! $backup instanceof ScheduledDatabaseBackup) {
            return $backup;
        }

        $database = $backup->database->refresh();
        if ($database->id !== 0 && ! str($database->status)->startsWith('running')) {
            return ['status' => 'validation', 'message' => '数据库必须处于运行状态才能执行备份。'];
        }

        DatabaseBackupJob::dispatch($backup);
        auditLog('panel.database.backup_started', [
            'team_id' => $database->team()?->id,
            'database_uuid' => $database->uuid,
            'backup_uuid' => $backup->uuid,
        ]);

        return ['status' => 'ok'];
    }

    public function deleteBackup(string $uuid, string $backupUuid): array
    {
        $database = $this->resolveDatabase($uuid);
        if (! $database instanceof Model) {
            return $database;
        }

        $backup = $this->resolveBackup($database, $backupUuid);
        if (! $backup instanceof ScheduledDatabaseBackup) {
            return $backup;
        }

        $backup->delete();

        return ['status' => 'ok'];
    }

    // ------------------------------------------------------------------ 私有

    private function resolveDatabase(string $uuid): Model|array
    {
        $database = queryDatabaseByUuidWithinTeam($uuid, currentTeam()->id);
        if (! $database) {
            return ['status' => 'not_found', 'message' => 'Database not found.'];
        }

        return $database;
    }

    private function resolveBackup(Model $database, string $backupUuid): ScheduledDatabaseBackup|array
    {
        $backup = ScheduledDatabaseBackup::where('database_id', $database->id)
            ->where('database_type', $database->getMorphClass())
            ->where('uuid', $backupUuid)
            ->first();
        if (! $backup) {
            return ['status' => 'not_found', 'message' => 'Backup configuration not found.'];
        }

        return $backup;
    }

    private function serializeBackup(ScheduledDatabaseBackup $backup): array
    {
        $latest = $backup->executions()->orderByDesc('created_at')->first();

        return [
            'uuid' => $backup->uuid,
            'description' => $backup->description,
            'enabled' => (bool) $backup->enabled,
            'frequency' => $backup->frequency,
            'dump_all' => (bool) $backup->dump_all,
            'retention_locally' => $backup->database_backup_retention_amount_locally,
            'latest_status' => $latest?->status,
            'latest_finished_at' => $latest?->created_at,
        ];
    }
}
