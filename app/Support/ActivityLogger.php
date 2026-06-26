<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

class ActivityLogger
{
    /**
     * @var array<int, string>
     */
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'remember_token',
        'current_password',
        'new_password',
        'token',
        'api_token',
        'session',
        'cookie',
    ];

    public static function batchId(): string
    {
        return (string) Str::uuid();
    }

    public static function log(array $data): ?ActivityLog
    {
        try {
            $request = request();
            $user = $request?->user();

            $payload = [
                'id' => 'file_'.(string) Str::uuid(),
                'user_id' => $data['user_id'] ?? $user?->getKey(),
                'user_name' => $data['user_name'] ?? ($user?->name ?? $user?->username),
                'action' => (string) ($data['action'] ?? ''),
                'module' => (string) ($data['module'] ?? ''),
                'model_type' => $data['model_type'] ?? null,
                'model_id' => $data['model_id'] ?? null,
                'description' => $data['description'] ?? null,
                'old_values' => self::sanitize($data['old_values'] ?? null),
                'new_values' => self::sanitize($data['new_values'] ?? null),
                'ip_address' => $data['ip_address'] ?? $request?->ip(),
                'user_agent' => $data['user_agent'] ?? $request?->userAgent(),
                'route_name' => $data['route_name'] ?? $request?->route()?->getName(),
                'url' => $data['url'] ?? $request?->fullUrl(),
                'method' => $data['method'] ?? $request?->method(),
                'batch_id' => $data['batch_id'] ?? null,
                'created_at' => now()->format('Y-m-d H:i:s'),
                'updated_at' => now()->format('Y-m-d H:i:s'),
            ];

            $writeLog = static function () use ($payload): void {
                try {
                    ActivityLogFileStore::append($payload);
                } catch (Throwable) {
                    //
                }
            };

            if (app()->runningInConsole()) {
                $writeLog();
            } else {
                app()->terminating($writeLog);
            }

            return null;
        } catch (Throwable) {
            return null;
        }
    }

    public static function modelValues(Model|array|null $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Model) {
            return $value->getAttributes();
        }

        return $value;
    }

    public static function changedValues(Model $model): array
    {
        $keys = collect(array_keys($model->getChanges()))
            ->reject(fn (string $key): bool => in_array($key, ['updated_at'], true))
            ->values();

        return [
            'old' => $keys->mapWithKeys(fn (string $key): array => [$key => $model->getOriginal($key)])->all(),
            'new' => $keys->mapWithKeys(fn (string $key): array => [$key => $model->getAttribute($key)])->all(),
        ];
    }

    private static function sanitize(mixed $value): mixed
    {
        if ($value instanceof Model) {
            $value = $value->getAttributes();
        }

        if (! is_array($value)) {
            return $value;
        }

        return collect($value)
            ->reject(fn (mixed $item, string|int $key): bool => is_string($key) && self::isSensitiveKey($key))
            ->map(fn (mixed $item): mixed => is_array($item) ? self::sanitize($item) : $item)
            ->all();
    }

    private static function isSensitiveKey(string $key): bool
    {
        $key = Str::lower($key);

        return Arr::first(self::SENSITIVE_KEYS, fn (string $sensitive): bool => Str::contains($key, $sensitive)) !== null;
    }
}
