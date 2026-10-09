<?php

namespace App\Services\Panel;

use App\Models\EmailNotificationSettings;
use App\Models\DiscordNotificationSettings;
use App\Models\SlackNotificationSettings;
use App\Models\TelegramNotificationSettings;
use App\Models\PushoverNotificationSettings;
use App\Models\WebhookNotificationSettings;
use App\Models\Team;
use Illuminate\Validation\ValidationException;

class PanelNotificationService
{
    private const CHANNELS = [
        'email' => ['model' => EmailNotificationSettings::class, 'label' => '邮件'],
        'discord' => ['model' => DiscordNotificationSettings::class, 'label' => 'Discord'],
        'slack' => ['model' => SlackNotificationSettings::class, 'label' => 'Slack'],
        'telegram' => ['model' => TelegramNotificationSettings::class, 'label' => 'Telegram'],
        'pushover' => ['model' => PushoverNotificationSettings::class, 'label' => 'Pushover'],
        'webhook' => ['model' => WebhookNotificationSettings::class, 'label' => 'Webhook'],
    ];

    /** 连接类（敏感）字段关键词——显示时一律打码 */
    private const SENSITIVE_KEYWORDS = ['token', 'secret', 'key', 'password', 'url', 'chat_id', 'user_key', 'api_token'];

    public function channels(): array
    {
        $out = [];
        foreach (self::CHANNELS as $channel => $cfg) {
            /** @var \Illuminate\Database\Eloquent\Model $model */
            $model = new $cfg['model'];
            $out[] = [
                'channel' => $channel,
                'label' => $cfg['label'],
                'fields' => $this->serializeFields($model->getFillable(), $channel),
            ];
        }

        return $out;
    }

    public function channel(string $channel, int $teamId): array
    {
        $cfg = $this->config($channel);
        $model = $cfg['model']::query()->firstOrCreate(['team_id' => $teamId]);

        return [
            'channel' => $channel,
            'label' => $cfg['label'],
            'fields' => $this->serializeFields($model->getFillable(), $channel, $model),
        ];
    }

    public function updateChannel(string $channel, array $data, int $teamId): array
    {
        $cfg = $this->config($channel);
        $allowed = $this->fillableWithoutTeam($cfg['model']);
        $extra = array_diff(array_keys($data), $allowed);
        if (! empty($extra)) {
            throw ValidationException::withMessages([implode(',', $extra) => 'This field is not allowed.']);
        }
        $model = $cfg['model']::query()->firstOrCreate(['team_id' => $teamId]);
        foreach ($data as $field => $value) {
            if ($field === 'team_id') {
                continue;
            }
            $model->{$field} = is_bool($value) ? $value : $value;
        }
        $model->save();

        return ['status' => 'ok', 'channel' => $channel];
    }

    /**
     * O10：发送测试通知（复用 Coolify 原生 Test 通知）。
     */
    public function test(string $channel, int $teamId): array
    {
        $cfg = $this->config($channel);
        $model = $cfg['model']::query()->firstOrCreate(['team_id' => $teamId]);
        if (! $model->getAttribute('enabled')) {
            // 未开启时仍允许测试（仅校验配置完整性由 Coolify 通知自身判定）
        }
        $team = Team::query()->find($teamId);
        if (! $team) {
            throw new \RuntimeException('Team not found.');
        }
        $team->notify(new \App\Notifications\Test(channel: $channel));

        return ['status' => 'ok'];
    }

    /**
     * 序列化渠道字段：布尔事件开关保留；连接/敏感字段打码；其余字符串原样。
     */
    private function serializeFields(array $fillable, string $channel, ?\Illuminate\Database\Eloquent\Model $model = null): array
    {
        $fields = [];
        foreach ($fillable as $field) {
            if ($field === 'team_id') {
                continue;
            }
            $value = $model ? $model->{$field} : null;
            $isEventSwitch = str_ends_with($field, '_notifications');
            $isSensitive = $this->isSensitive($field);
            $isBooleanField = is_bool($value) || str_contains($field, 'enabled');
            $fields[] = [
                'name' => $field,
                'kind' => $isEventSwitch ? 'event_switch' : ($isSensitive ? 'secret' : ($isBooleanField ? 'toggle' : 'text')),
                'value' => $isSensitive ? (filled($value) ? '••••••••' : null) : (is_bool($value) ? $value : $value),
            ];
        }

        return $fields;
    }

    private function isSensitive(string $field): bool
    {
        foreach (self::SENSITIVE_KEYWORDS as $kw) {
            if (str_contains(strtolower($field), $kw)) {
                return true;
            }
        }

        return false;
    }

    private function config(string $channel): array
    {
        if (! isset(self::CHANNELS[$channel])) {
            throw new \InvalidArgumentException("Unknown notification channel [{$channel}].");
        }

        return self::CHANNELS[$channel];
    }

    private function fillableWithoutTeam(string $modelClass): array
    {
        $model = new $modelClass;
        $fillable = $model->getFillable();
        if (is_array($fillable) && in_array('team_id', $fillable, true)) {
            return array_values(array_diff($fillable, ['team_id']));
        }

        // 部分模型 fillable 为 *：退化为常见字段集合
        $fallback = ['enabled', 'deployment_success_notifications', 'deployment_failure_notifications', 'status_change_notifications'];
        foreach ($model->getAttributes() as $attr => $_) {
            $fallback[] = $attr;
        }

        return array_values(array_unique($fallback));
    }
}
