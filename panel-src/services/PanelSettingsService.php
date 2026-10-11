<?php

namespace App\Services\Panel;

use App\Models\SharedEnvironmentVariable;
use App\Models\Tag;
use App\Models\S3Storage;
use App\Models\PrivateKey;
use Illuminate\Validation\ValidationException;

class PanelSettingsService
{
    // ---------------------------------------------------------------- 共享环境变量（团队级 / 环境级多级作用域）

    /**
     * 共享变量列表（多级作用域：type=team 团队级 / type=environment 环境级）。
     * 脱敏：is_shown_once 不回显 value。
     */
    public function sharedEnvs(int $teamId, ?string $type = null, int|string|null $environmentId = null): array
    {
        $query = SharedEnvironmentVariable::where('team_id', $teamId);
        if ($type !== null && in_array($type, ['team', 'environment'])) {
            $query->where('type', $type);
        }
        if ($environmentId !== null) {
            // 兼容数字 id 或 uuid 字符串
            if (is_numeric($environmentId)) {
                $query->where('environment_id', (int) $environmentId);
            } else {
                $query->whereHas('environment', fn ($q) => $q->where('uuid', $environmentId));
            }
        }

        return $query->orderBy('id', 'desc')
            ->get()
            ->map(fn (SharedEnvironmentVariable $e) => [
                'id' => $e->id,
                'key' => $e->key,
                'value' => $e->is_shown_once ? null : $e->value,
                'is_literal' => (bool) $e->is_literal,
                'is_multiline' => (bool) $e->is_multiline,
                'is_shown_once' => (bool) $e->is_shown_once,
                'comment' => $e->comment,
                'type' => $e->type,
                'environment_id' => $e->environment_id,
                'environment_name' => $e->environment_id ? optional($e->environment)->name : null,
            ])
            ->values()
            ->all();
    }

    /**
     * 创建共享变量（重名 409，与官方一致）。
     * 支持多级作用域：type=team（默认）或 type=environment + environment_id。
     */
    public function createSharedEnv(array $data, int $teamId): array
    {
        $this->assertAllowed($data, ['key', 'value', 'is_literal', 'is_multiline', 'is_shown_once', 'comment', 'type', 'environment_id']);
        if (blank(data_get($data, 'key'))) {
            throw ValidationException::withMessages(['key' => 'key is required.']);
        }
        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $data['key'])) {
            throw ValidationException::withMessages(['key' => 'Invalid environment variable key.']);
        }
        $type = data_get($data, 'type', 'team');
        if (! in_array($type, ['team', 'environment'])) {
            throw ValidationException::withMessages(['type' => 'type must be team or environment.']);
        }
        $environmentId = null;
        if ($type === 'environment') {
            $envRef = data_get($data, 'environment_id');
            if (blank($envRef)) {
                throw ValidationException::withMessages(['environment_id' => 'environment_id is required for environment scope.']);
            }
            // 兼容数字 id 或 uuid 字符串（团队归属经 project.team 间接校验）
            $environmentQuery = \App\Models\Environment::whereRelation('project.team', 'id', $teamId);
            if (is_numeric($envRef)) {
                $environmentQuery->where('id', (int) $envRef);
            } else {
                $environmentQuery->where('uuid', $envRef);
            }
            $env = $environmentQuery->first();
            if (! $env) {
                throw ValidationException::withMessages(['environment_id' => 'Environment not found in this team.']);
            }
            $environmentId = $env->id;
        }
        $exists = SharedEnvironmentVariable::where('team_id', $teamId)
            ->where('key', $data['key'])
            ->where('type', $type)
            ->when($type === 'environment', fn ($q) => $q->where('environment_id', $environmentId))
            ->exists();
        if ($exists) {
            return ['status' => 'error', 'message' => 'Environment variable already exists. Use PATCH request to update it.', 'http' => 409];
        }

        $env = SharedEnvironmentVariable::create([
            'key' => $data['key'],
            'value' => data_get($data, 'value'),
            'is_literal' => (bool) data_get($data, 'is_literal', false),
            'is_multiline' => (bool) data_get($data, 'is_multiline', false),
            'is_shown_once' => (bool) data_get($data, 'is_shown_once', false),
            'comment' => data_get($data, 'comment'),
            'type' => $type,
            'environment_id' => $environmentId,
            'team_id' => $teamId,
        ]);

        return ['status' => 'ok', 'id' => $env->id];
    }

    /**
     * 更新共享变量（团队范围内任意作用域；key 重名 409）。
     */
    public function updateSharedEnv(int $envId, array $data, int $teamId): array
    {
        $this->assertAllowed($data, ['key', 'value', 'is_literal', 'is_multiline', 'is_shown_once', 'comment', 'environment_id']);
        $env = SharedEnvironmentVariable::where('team_id', $teamId)
            ->where('id', $envId)
            ->firstOrFail();
        if (array_key_exists('key', $data)) {
            if (blank($data['key'])) {
                throw ValidationException::withMessages(['key' => 'key is required.']);
            }
            $exists = SharedEnvironmentVariable::where('team_id', $teamId)
                ->where('key', $data['key'])
                ->where('type', $env->type)
                ->when($env->type === 'environment', fn ($q) => $q->where('environment_id', $env->environment_id))
                ->where('id', '!=', $env->id)
                ->exists();
            if ($exists) {
                return ['status' => 'error', 'message' => 'Environment variable already exists.', 'http' => 409];
            }
            $env->key = $data['key'];
        }
        if (array_key_exists('environment_id', $data)) {
            $envRef = $data['environment_id'];
            if (blank($envRef)) {
                $env->environment_id = null;
            } elseif ($env->type === 'environment') {
                $environmentQuery = \App\Models\Environment::whereRelation('project.team', 'id', $teamId);
                if (is_numeric($envRef)) {
                    $environmentQuery->where('id', (int) $envRef);
                } else {
                    $environmentQuery->where('uuid', $envRef);
                }
                $newEnv = $environmentQuery->first();
                if (! $newEnv) {
                    throw ValidationException::withMessages(['environment_id' => 'Environment not found in this team.']);
                }
                $env->environment_id = $newEnv->id;
            }
        }
        foreach (['value', 'comment'] as $field) {
            if (array_key_exists($field, $data)) {
                $env->{$field} = $data[$field];
            }
        }
        foreach (['is_literal', 'is_multiline', 'is_shown_once'] as $field) {
            if (array_key_exists($field, $data)) {
                $env->{$field} = (bool) $data[$field];
            }
        }
        $env->save();

        return ['status' => 'ok', 'id' => $env->id];
    }

    /**
     * 删除共享变量（团队范围内任意作用域）。
     */
    public function deleteSharedEnv(int $envId, int $teamId): array
    {
        $env = SharedEnvironmentVariable::where('team_id', $teamId)
            ->where('id', $envId)
            ->firstOrFail();
        $env->delete();

        return ['status' => 'ok', 'message' => 'Shared environment variable deleted.'];
    }

    // ---------------------------------------------------------------- 标签

    /**
     * 标签列表（含关联资源数）。
     */
    public function tags(int $teamId): array
    {
        return Tag::where('team_id', $teamId)
            ->orderBy('name')
            ->get()
            ->map(fn (Tag $tag) => [
                'uuid' => $tag->uuid,
                'name' => $tag->name,
                'resources_count' => $tag->applications()->count() + $tag->services()->count(),
            ])
            ->values()
            ->all();
    }

    /**
     * 创建标签（重名 409）。
     */
    public function createTag(array $data, int $teamId): array
    {
        $this->assertAllowed($data, ['name']);
        if (blank(data_get($data, 'name'))) {
            throw ValidationException::withMessages(['name' => 'name is required.']);
        }
        if (Tag::where('team_id', $teamId)->where('name', $data['name'])->exists()) {
            return ['status' => 'error', 'message' => 'Tag with this name already exists.', 'http' => 409];
        }

        $tag = Tag::create([
            'name' => $data['name'],
            'team_id' => $teamId,
        ]);

        return ['status' => 'ok', 'uuid' => $tag->uuid];
    }

    /**
     * 更新标签。
     */
    public function updateTag(string $uuid, array $data, int $teamId): array
    {
        $this->assertAllowed($data, ['name']);
        $tag = Tag::where('team_id', $teamId)->where('uuid', $uuid)->firstOrFail();
        if (array_key_exists('name', $data)) {
            $exists = Tag::where('team_id', $teamId)->where('name', $data['name'])->where('id', '!=', $tag->id)->exists();
            if ($exists) {
                return ['status' => 'error', 'message' => 'Tag with this name already exists.', 'http' => 409];
            }
            $tag->name = $data['name'];
        }
        $tag->save();

        return ['status' => 'ok', 'uuid' => $tag->uuid];
    }

    /**
     * 删除标签。
     */
    public function deleteTag(string $uuid, int $teamId): array
    {
        $tag = Tag::where('team_id', $teamId)->where('uuid', $uuid)->firstOrFail();
        $tag->delete();

        return ['status' => 'ok', 'message' => 'Tag deleted.'];
    }

    // ---------------------------------------------------------------- S3 存储

    /**
     * S3 存储列表（secret 脱敏）。
     */
    public function s3Storages(int $teamId): array
    {
        return S3Storage::where('team_id', $teamId)
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn (S3Storage $s) => [
                'uuid' => $s->uuid,
                'name' => $s->name,
                'description' => $s->description,
                'endpoint' => $s->endpoint,
                'bucket' => $s->bucket,
                'region' => $s->region,
                'key' => $s->key,
                'secret' => filled($s->secret) ? '••••••••' : null,
                'is_usable' => (bool) $s->is_usable,
            ])
            ->values()
            ->all();
    }

    public function createS3Storage(array $data, int $teamId): array
    {
        $this->assertAllowed($data, ['name', 'description', 'endpoint', 'bucket', 'region', 'key', 'secret', 'is_usable']);
        foreach (['name', 'endpoint', 'bucket', 'region', 'key', 'secret'] as $req) {
            if (blank(data_get($data, $req))) {
                throw ValidationException::withMessages([$req => "{$req} is required."]);
            }
        }

        $storage = S3Storage::create([
            'team_id' => $teamId,
            'name' => $data['name'],
            'description' => data_get($data, 'description'),
            'endpoint' => $data['endpoint'],
            'bucket' => $data['bucket'],
            'region' => $data['region'],
            'key' => $data['key'],
            'secret' => $data['secret'],
            'is_usable' => (bool) data_get($data, 'is_usable', false),
        ]);

        return ['status' => 'ok', 'uuid' => $storage->uuid];
    }

    public function updateS3Storage(string $uuid, array $data, int $teamId): array
    {
        $this->assertAllowed($data, ['name', 'description', 'endpoint', 'bucket', 'region', 'key', 'secret', 'is_usable']);
        $storage = S3Storage::where('team_id', $teamId)->where('uuid', $uuid)->firstOrFail();
        foreach (['name', 'description', 'endpoint', 'bucket', 'region', 'key'] as $field) {
            if (array_key_exists($field, $data)) {
                $storage->{$field} = $data[$field];
            }
        }
        // secret 仅在提供新值时更新（不回写打码值）
        if (array_key_exists('secret', $data) && filled($data['secret']) && $data['secret'] !== '••••••••') {
            $storage->secret = $data['secret'];
        }
        if (array_key_exists('is_usable', $data)) {
            $storage->is_usable = (bool) $data['is_usable'];
        }
        $storage->save();

        return ['status' => 'ok', 'uuid' => $storage->uuid];
    }

    public function deleteS3Storage(string $uuid, int $teamId): array
    {
        $storage = S3Storage::where('team_id', $teamId)->where('uuid', $uuid)->firstOrFail();
        $storage->delete();

        return ['status' => 'ok', 'message' => 'S3 storage deleted.'];
    }

    /**
     * O10：S3 存储连通性测试（复用 S3Storage::testConnection）。
     */
    public function testS3Storage(string $uuid, int $teamId): array
    {
        $storage = S3Storage::where('team_id', $teamId)->where('uuid', $uuid)->firstOrFail();
        $storage->testConnection(shouldSave: true);

        return ['status' => 'ok', 'message' => 'S3 连接正常，可用'];
    }

    // ---------------------------------------------------------------- SSH 密钥

    /**
     * SSH 密钥列表（脱敏 + 指纹）。
     */
    public function sshKeys(int $teamId): array
    {
        return PrivateKey::where('team_id', $teamId)
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn (PrivateKey $k) => [
                'uuid' => $k->uuid,
                'name' => $k->name,
                'description' => $k->description,
                'fingerprint' => $k->fingerprint,
                'is_git_related' => (bool) $k->is_git_related,
                'is_build_server' => (bool) $k->is_build_server,
                'private_key' => filled($k->private_key) ? '••••••••' : null,
            ])
            ->values()
            ->all();
    }

    public function createSshKey(array $data, int $teamId): array
    {
        $this->assertAllowed($data, ['name', 'description', 'private_key', 'is_git_related', 'is_build_server']);
        if (blank(data_get($data, 'private_key'))) {
            throw ValidationException::withMessages(['private_key' => 'private_key is required.']);
        }
        $privateKey = $data['private_key'];
        if (! str_starts_with($privateKey, '-----BEGIN')) {
            $decoded = base64_decode($privateKey, true);
            if ($decoded !== false) {
                $privateKey = $decoded;
            }
        }
        if (! PrivateKey::validatePrivateKey($privateKey)) {
            throw ValidationException::withMessages(['private_key' => 'Invalid private key.']);
        }
        $fingerprint = PrivateKey::generateFingerprint($privateKey);
        if (PrivateKey::fingerprintExists($fingerprint, teamId: $teamId)) {
            throw ValidationException::withMessages(['private_key' => 'This private key already exists.']);
        }

        $key = PrivateKey::create([
            'team_id' => $teamId,
            'name' => data_get($data, 'name') ?: 'server-key',
            'description' => data_get($data, 'description'),
            'private_key' => $privateKey,
            'is_git_related' => (bool) data_get($data, 'is_git_related', false),
            'is_build_server' => (bool) data_get($data, 'is_build_server', false),
        ]);

        return ['status' => 'ok', 'uuid' => $key->uuid, 'fingerprint' => $key->fingerprint];
    }

    public function deleteSshKey(string $uuid, int $teamId): array
    {
        $key = PrivateKey::where('team_id', $teamId)->where('uuid', $uuid)->firstOrFail();
        $key->delete();

        return ['status' => 'ok', 'message' => 'SSH key deleted.'];
    }

    /**
     * 更新 SSH 密钥：name/description/is_git_related/is_build_server/private_key（换私钥时重算指纹 + 重复校验）。
     */
    public function updateSshKey(string $uuid, array $data, int $teamId): array
    {
        $this->assertAllowed($data, ['name', 'description', 'private_key', 'is_git_related', 'is_build_server']);
        $key = PrivateKey::where('team_id', $teamId)->where('uuid', $uuid)->firstOrFail();

        if (array_key_exists('private_key', $data) && filled($data['private_key'])) {
            $privateKey = $data['private_key'];
            if (! str_starts_with($privateKey, '-----BEGIN')) {
                $decoded = base64_decode($privateKey, true);
                if ($decoded !== false) {
                    $privateKey = $decoded;
                }
            }
            if (! PrivateKey::validatePrivateKey($privateKey)) {
                throw ValidationException::withMessages(['private_key' => 'Invalid private key.']);
            }
            $fingerprint = PrivateKey::generateFingerprint($privateKey);
            if (PrivateKey::fingerprintExists($fingerprint, teamId: $teamId)) {
                throw ValidationException::withMessages(['private_key' => 'This private key already exists.']);
            }
            $key->private_key = $privateKey;
            $key->fingerprint = $fingerprint;
        }

        if (array_key_exists('name', $data)) {
            $key->name = $data['name'];
        }
        if (array_key_exists('description', $data)) {
            $key->description = $data['description'];
        }
        if (array_key_exists('is_git_related', $data)) {
            $key->is_git_related = (bool) $data['is_git_related'];
        }
        if (array_key_exists('is_build_server', $data)) {
            $key->is_build_server = (bool) $data['is_build_server'];
        }
        $key->save();

        return ['status' => 'ok', 'uuid' => $key->uuid, 'fingerprint' => $key->fingerprint];
    }

    private function assertAllowed(array $data, array $allowed): void
    {
        $extra = array_diff(array_keys($data), $allowed);
        if (! empty($extra)) {
            throw ValidationException::withMessages([implode(',', $extra) => 'This field is not allowed.']);
        }
    }
}
