<?php

namespace App\Services\Panel;

use App\Jobs\DeleteResourceJob;
use App\Models\Application;
use App\Models\EnvironmentVariable;
use App\Support\ValidationPatterns;
use Illuminate\Support\Str;

/**
 * ABao panel: application configuration operations for the Vue frontend
 * (baouit-panel-front). 阶段 3.2 W2.
 *
 * Covers the application configuration surface: detail read, field updates
 * (domains/ports/resource limits/name/description), environment variable CRUD,
 * and application deletion. Every query is scoped to the current team
 * (ownedByCurrentTeam); write endpoints additionally enforce the Application
 * policy in the controller.
 */
class PanelApplicationService
{
    /**
     * Fields the panel lets users edit. Kept deliberately small and safe;
     * the Application model normalizes fqdn on save (DomainPortOverrides).
     */
    private const UPDATABLE_FIELDS = [
        'name', 'description', 'fqdn',
        'ports_exposes', 'ports_mappings',
        'limits_memory', 'limits_cpus',
        'health_check_enabled', 'health_check_path',
        'health_check_port', 'health_check_host',
        'health_check_interval', 'health_check_timeout', 'health_check_retries',
    ];

    public function show(string $uuid): array
    {
        $application = $this->resolveApplication($uuid);
        if (! $application instanceof Application) {
            return $application;
        }

        return [
            'uuid' => $application->uuid,
            'name' => $application->name,
            'description' => $application->description,
            'status' => $application->status,
            'fqdn' => $application->fqdn,
            'ports_exposes' => $application->ports_exposes,
            'ports_mappings' => $application->ports_mappings,
            'limits_memory' => $application->limits_memory,
            'limits_cpus' => $application->limits_cpus,
            'build_pack' => $application->build_pack,
            'git_repository' => $application->git_repository,
            'git_branch' => $application->git_branch,
            'docker_registry_image_name' => $application->docker_registry_image_name,
            'docker_registry_image_tag' => $application->docker_registry_image_tag,
            'base_directory' => $application->base_directory,
            'publish_directory' => $application->publish_directory,
            'health_check_enabled' => (bool) $application->health_check_enabled,
            'health_check_path' => $application->health_check_path,
            'health_check_port' => $application->health_check_port,
            'health_check_host' => $application->health_check_host,
            'health_check_interval' => $application->health_check_interval,
            'health_check_timeout' => $application->health_check_timeout,
            'health_check_retries' => $application->health_check_retries,
        ];
    }

    /**
     * Update whitelisted fields. Unknown / non-editable fields are ignored.
     * Returns ['status' => 'ok'] or a validation error array.
     */
    public function update(string $uuid, array $data): array
    {
        $application = $this->resolveApplication($uuid);
        if (! $application instanceof Application) {
            return $application;
        }

        $payload = [];
        foreach (self::UPDATABLE_FIELDS as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }
            $value = $data[$field];
            if ($field === 'ports_exposes' && ! is_null($value)) {
                $ports = explode(',', (string) $value);
                foreach ($ports as $port) {
                    if (! is_numeric(trim($port)) || (int) trim($port) < 1 || (int) trim($port) > 65535) {
                        return ['status' => 'validation', 'message' => '端口必须是 1-65535 的数字，多个端口用英文逗号分隔。'];
                    }
                }
                $value = implode(',', array_map('trim', $ports));
            }
            if ($field === 'fqdn' && $value === '') {
                $value = null;
            }
            $payload[$field] = $value;
        }

        if ($payload === []) {
            return ['status' => 'ok'];
        }

        try {
            $application->update($payload);
        } catch (\Throwable $e) {
            report($e);

            return ['status' => 'error', 'message' => '保存失败：'.$e->getMessage()];
        }

        return ['status' => 'ok'];
    }

    // ------------------------------------------------------------------ 环境变量

    public function envs(string $uuid): array
    {
        $application = $this->resolveApplication($uuid);
        if (! $application instanceof Application) {
            return $application;
        }

        return EnvironmentVariable::where('resourceable_id', $application->id)
            ->where('resourceable_type', Application::class)
            ->orderBy('order')
            ->get()
            ->map(fn (EnvironmentVariable $env) => $this->serializeEnv($env))
            ->values()
            ->all();
    }

    /** 面板 API 专用：应用的全部环境变量（含 preview 变量与 buildpack 控制变量）。 */
    private function envQuery(Application $application)
    {
        return EnvironmentVariable::where('resourceable_id', $application->id)
            ->where('resourceable_type', Application::class);
    }

    public function createEnv(string $uuid, array $data): array
    {
        $application = $this->resolveApplication($uuid);
        if (! $application instanceof Application) {
            return $application;
        }

        try {
            $key = ValidationPatterns::validatedEnvironmentVariableKey((string) ($data['key'] ?? ''));
        } catch (\InvalidArgumentException $e) {
            return ['status' => 'validation', 'message' => '环境变量名不合法：'.($data['key'] ?? '')];
        }

        if ($this->envQuery($application)->where('key', $key)->exists()) {
            return ['status' => 'validation', 'message' => "环境变量 {$key} 已存在。"];
        }

        $maxOrder = $this->envQuery($application)->max('order') ?? 0;
        $env = new EnvironmentVariable;
        $env->key = $key;
        $env->value = (string) ($data['value'] ?? '');
        $env->comment = $data['comment'] ?? null;
        $env->is_multiline = (bool) ($data['is_multiline'] ?? false);
        $env->is_literal = (bool) ($data['is_literal'] ?? false);
        $env->is_runtime = (bool) ($data['is_runtime'] ?? true);
        $env->is_buildtime = (bool) ($data['is_buildtime'] ?? true);
        $env->is_preview = (bool) ($data['is_preview'] ?? false);
        $env->is_shown_once = false;
        $env->order = $maxOrder + 1;
        $env->resourceable_id = $application->id;
        $env->resourceable_type = Application::class;

        try {
            $env->save();
        } catch (\Throwable $e) {
            report($e);

            return ['status' => 'error', 'message' => '创建失败：'.$e->getMessage()];
        }

        return ['status' => 'ok', 'env' => $this->serializeEnv($env)];
    }

    public function updateEnv(string $uuid, string $envUuid, array $data): array
    {
        $application = $this->resolveApplication($uuid);
        if (! $application instanceof Application) {
            return $application;
        }

        $env = $this->envQuery($application)->where('uuid', $envUuid)->first();
        if (! $env) {
            return ['status' => 'not_found', 'message' => 'Environment variable not found.'];
        }

        try {
            $key = ValidationPatterns::validatedEnvironmentVariableKey((string) ($data['key'] ?? $env->key));
        } catch (\InvalidArgumentException $e) {
            return ['status' => 'validation', 'message' => '环境变量名不合法：'.($data['key'] ?? '')];
        }

        $duplicate = $this->envQuery($application)
            ->where('key', $key)
            ->where('uuid', '!=', $envUuid)
            ->exists();
        if ($duplicate) {
            return ['status' => 'validation', 'message' => "环境变量 {$key} 已存在。"];
        }

        try {
            $env->update([
                'key' => $key,
                'value' => array_key_exists('value', $data) ? (string) $data['value'] : $env->value,
                'comment' => array_key_exists('comment', $data) ? $data['comment'] : $env->comment,
                'is_multiline' => (bool) ($data['is_multiline'] ?? $env->is_multiline),
                'is_literal' => (bool) ($data['is_literal'] ?? $env->is_literal),
                'is_runtime' => (bool) ($data['is_runtime'] ?? $env->is_runtime),
                'is_buildtime' => (bool) ($data['is_buildtime'] ?? $env->is_buildtime),
                'is_preview' => (bool) ($data['is_preview'] ?? $env->is_preview),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return ['status' => 'error', 'message' => '保存失败：'.$e->getMessage()];
        }

        return ['status' => 'ok', 'env' => $this->serializeEnv($env)];
    }

    public function deleteEnv(string $uuid, string $envUuid): array
    {
        $application = $this->resolveApplication($uuid);
        if (! $application instanceof Application) {
            return $application;
        }

        $env = $this->envQuery($application)->where('uuid', $envUuid)->first();
        if (! $env) {
            return ['status' => 'not_found', 'message' => 'Environment variable not found.'];
        }

        $env->delete();

        return ['status' => 'ok'];
    }

    /**
     * O12：批量导入环境变量（.env 文本解析结果：envs 数组，replace 控制同名覆盖）。
     */
    public function bulkImportEnvs(string $uuid, array $data): array
    {
        $application = $this->resolveApplication($uuid);
        if (! $application instanceof Application) {
            return $application;
        }

        $envs = $data['envs'] ?? [];
        $replace = (bool) ($data['replace'] ?? false);
        if (! is_array($envs) || $envs === []) {
            return ['status' => 'validation', 'message' => 'envs 不能为空（至少一条 KEY=VALUE）'];
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $maxOrder = $this->envQuery($application)->max('order') ?? 0;
        $existing = $this->envQuery($application)->pluck('uuid', 'key');

        foreach ($envs as $item) {
            if (! is_array($item)) {
                $skipped++;

                continue;
            }
            try {
                $key = ValidationPatterns::validatedEnvironmentVariableKey((string) ($item['key'] ?? ''));
            } catch (\InvalidArgumentException $e) {
                $skipped++;

                continue;
            }
            $value = (string) ($item['value'] ?? '');
            $comment = $item['comment'] ?? null;
            $isPreview = (bool) ($item['is_preview'] ?? false);

            if ($existing->has($key)) {
                if (! $replace) {
                    $skipped++;

                    continue;
                }
                $env = $this->envQuery($application)->where('key', $key)->first();
                $env->value = $value;
                $env->comment = $comment;
                $env->is_preview = $isPreview;
                $env->save();
                $updated++;
            } else {
                $env = new EnvironmentVariable;
                $env->key = $key;
                $env->value = $value;
                $env->comment = $comment;
                $env->is_multiline = (bool) ($item['is_multiline'] ?? false);
                $env->is_literal = (bool) ($item['is_literal'] ?? false);
                $env->is_runtime = (bool) ($item['is_runtime'] ?? true);
                $env->is_buildtime = (bool) ($item['is_buildtime'] ?? true);
                $env->is_preview = $isPreview;
                $env->is_shown_once = false;
                $env->order = ++$maxOrder;
                $env->resourceable_id = $application->id;
                $env->resourceable_type = Application::class;
                $env->save();
                $existing->put($key, $env->uuid);
                $created++;
            }
        }

        return ['status' => 'ok', 'created' => $created, 'updated' => $updated, 'skipped' => $skipped];
    }

    // ------------------------------------------------------------------ 删除应用

    /**
     * Soft-delete the application and queue remote cleanup (containers,
     * volumes, networks, configurations) via the native DeleteResourceJob.
     */
    public function destroy(string $uuid): array
    {
        $application = $this->resolveApplication($uuid);
        if (! $application instanceof Application) {
            return $application;
        }

        $application->delete();
        DeleteResourceJob::dispatch(
            resource: $application,
            deleteVolumes: true,
            deleteConnectedNetworks: true,
            deleteConfigurations: true,
            dockerCleanup: true,
        );
        auditLog('panel.application.deleted', [
            'team_id' => $application->team()?->id,
            'application_uuid' => $application->uuid,
            'application_name' => $application->name,
        ]);

        return ['status' => 'ok'];
    }

    // ------------------------------------------------------------------ 私有

    private function resolveApplication(string $uuid): Application|array
    {
        $application = Application::ownedByCurrentTeam()->where('uuid', $uuid)->first();
        if (! $application) {
            return ['status' => 'not_found', 'message' => 'Application not found.'];
        }

        return $application;
    }

    private function serializeEnv(EnvironmentVariable $env): array
    {
        return [
            'uuid' => $env->uuid,
            'key' => $env->key,
            // is_shown_once values are never revealed back to the UI.
            'value' => $env->is_shown_once ? null : $env->value,
            'comment' => $env->comment,
            'is_multiline' => (bool) $env->is_multiline,
            'is_literal' => (bool) $env->is_literal,
            'is_runtime' => (bool) $env->is_runtime,
            'is_buildtime' => (bool) $env->is_buildtime,
            'is_preview' => (bool) $env->is_preview,
            'order' => $env->order,
        ];
    }
}
