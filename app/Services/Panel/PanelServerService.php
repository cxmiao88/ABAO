<?php

namespace App\Services\Panel;

use App\Models\Server;
use App\Rules\ValidServerIp;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Jobs\ValidateAndInstallServerJob;
use Illuminate\Support\Str;

class PanelServerService
{
    /**
     * 详情（脱敏）：只暴露常规字段 + settings 子集。
     */
    public function show(string $uuid): Server
    {
        $server = Server::whereUuid($uuid)->firstOrFail();
        $server->load('settings');

        return $server;
    }

    /**
     * 更新服务器设置。
     * 简化决策：单机部署（本地服务器）不开放 private_key_uuid / server_role / is_build_server 流转；
     * 其余白名单与官方 API update_server 一致。
     */
    public function update(string $uuid, array $data): Server
    {
        $server = Server::whereUuid($uuid)->firstOrFail();

        $allowedFields = [
            'name', 'description', 'ip', 'port', 'user',
            'instant_validate', 'proxy_type',
            'concurrent_builds', 'dynamic_timeout', 'deployment_queue_limit',
            'server_disk_usage_notification_threshold',
            'server_disk_usage_check_frequency',
            'server_disk_usage_notification_interval_hours',
            'connection_timeout', 'is_terminal_enabled',
        ];

        $extraFields = array_diff(array_keys($data), $allowedFields);
        if (! empty($extraFields)) {
            throw ValidationException::withMessages([
                implode(',', $extraFields) => 'This field is not allowed.',
            ]);
        }

        $validator = Validator::make($data, [
            'name' => 'string|max:255|nullable',
            'description' => 'string|nullable',
            'ip' => ['string', 'nullable', new ValidServerIp],
            'port' => 'integer|nullable|between:1,65535',
            'user' => 'string|nullable|max:255',
            'instant_validate' => 'boolean|nullable',
            'proxy_type' => 'string|nullable',
            'concurrent_builds' => 'integer|min:1',
            'dynamic_timeout' => 'integer|min:1',
            'deployment_queue_limit' => 'integer|min:1',
            'server_disk_usage_notification_threshold' => 'integer|min:1|max:100',
            'server_disk_usage_check_frequency' => 'string',
            'server_disk_usage_notification_interval_hours' => 'integer|min:1|max:720',
            'connection_timeout' => 'integer|min:1|max:300',
            'is_terminal_enabled' => 'boolean|nullable',
        ]);
        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        if (! empty($data['proxy_type'])) {
            $validProxyTypes = collect(\App\Enums\ProxyTypes::cases())
                ->map(fn ($t) => str($t->value)->lower());
            if (! $validProxyTypes->contains(str($data['proxy_type'])->lower())) {
                throw ValidationException::withMessages(['proxy_type' => 'Invalid proxy type.']);
            }
        }

        if (array_key_exists('server_disk_usage_check_frequency', $data) && ! is_null($data['server_disk_usage_check_frequency']) && ! validate_cron_expression($data['server_disk_usage_check_frequency'])) {
            throw ValidationException::withMessages(['server_disk_usage_check_frequency' => 'Invalid Cron / Human expression for Disk Usage Check Frequency.']);
        }

        $updateFields = array_intersect_key($data, array_flip(['name', 'description', 'ip', 'port', 'user']));
        $updateFields = array_filter($updateFields, fn ($v) => ! is_null($v));
        if (! empty($updateFields)) {
            $server->update($updateFields);
        }

        $advancedSettings = array_intersect_key($data, array_flip([
            'concurrent_builds', 'dynamic_timeout', 'deployment_queue_limit',
            'server_disk_usage_notification_threshold',
            'server_disk_usage_check_frequency',
            'server_disk_usage_notification_interval_hours',
            'connection_timeout',
        ]));
        $advancedSettings = array_filter($advancedSettings, fn ($v) => ! is_null($v));
        if (! empty($advancedSettings)) {
            $server->settings()->update($advancedSettings);
        }

        if (array_key_exists('is_terminal_enabled', $data) && ! is_null($data['is_terminal_enabled'])) {
            $server->settings()->update(['is_terminal_enabled' => filter_var($data['is_terminal_enabled'], FILTER_VALIDATE_BOOLEAN)]);
        }

        if (! empty($data['proxy_type'])) {
            $server->changeProxy($data['proxy_type'], async: true);
        }

        if (! empty($data['instant_validate']) && filter_var($data['instant_validate'], FILTER_VALIDATE_BOOLEAN)) {
            ValidateAndInstallServerJob::dispatch($server);
        }

        $server->refresh();

        return $server;
    }

    /**
     * 触发连接校验（异步 job，install=false 语义）。
     * 官方 ValidateServer 类在当前版本已不存在（官方 API 该分支会 500）；
     * 改用 ValidateAndInstallServerJob：环境依赖齐全时只校验连接/OS/Docker，
     * 不会触发任何安装。
     */
    public function validate(string $uuid): Server
    {
        $server = Server::whereUuid($uuid)->firstOrFail();

        if (! $server->canBeValidated()) {
            throw ValidationException::withMessages(['server' => 'This server was transferred to another Coolify instance and cannot be revalidated here.']);
        }

        ValidateAndInstallServerJob::dispatch($server);

        return $server;
    }
}
