<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Fluent;

class ActivityLogFileStore
{
    private const DIRECTORY = 'logs/activity';
    private const RETENTION_DAYS = 30;

    public static function append(array $payload): void
    {
        $directory = self::directory();

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::append(
            self::pathFor(Carbon::parse($payload['created_at'] ?? now())),
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL
        );

        self::cleanupOldFiles();
    }

    public static function all(array $filters = []): Collection
    {
        return collect(self::files())
            ->flatMap(fn (string $file): Collection => self::readFile($file))
            ->filter(fn (Fluent $log): bool => self::passesFilters($log, $filters))
            ->values();
    }

    public static function find(string $id): ?Fluent
    {
        foreach (self::files() as $file) {
            foreach (self::readFile($file) as $log) {
                if ((string) $log->id === $id) {
                    return $log;
                }
            }
        }

        return null;
    }

    public static function modules(): Collection
    {
        return self::all()
            ->pluck('module')
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    public static function actions(): Collection
    {
        return self::all()
            ->pluck('action')
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    public static function cleanupOldFiles(int $retentionDays = self::RETENTION_DAYS): int
    {
        $cutoff = now()
            ->subDays($retentionDays)
            ->startOfDay();

        return collect(self::files())
            ->filter(function (string $file) use ($cutoff): bool {
                $date = self::dateFromPath($file);

                return $date !== null && $date->lt($cutoff);
            })
            ->reduce(function (int $deleted, string $file): int {
                return File::delete($file) ? $deleted + 1 : $deleted;
            }, 0);
    }

    private static function readFile(string $file): Collection
    {
        if (! File::exists($file)) {
            return collect();
        }

        return collect(File::lines($file))
            ->map(function (string $line): ?Fluent {
                $data = json_decode($line, true);

                if (! is_array($data)) {
                    return null;
                }

                return self::toEntry($data);
            })
            ->filter()
            ->values();
    }

    private static function toEntry(array $data): Fluent
    {
        $createdAt = Carbon::parse($data['created_at'] ?? now())
            ->timezone(config('app.timezone'));

        return new Fluent([
            'id' => (string) ($data['id'] ?? ''),
            'source_key' => (string) ($data['id'] ?? ''),
            'source_type' => 'file',
            'user_id' => $data['user_id'] ?? null,
            'user_name' => $data['user_name'] ?? null,
            'action' => (string) ($data['action'] ?? ''),
            'module' => (string) ($data['module'] ?? ''),
            'model_type' => $data['model_type'] ?? null,
            'model_id' => $data['model_id'] ?? null,
            'description' => $data['description'] ?? null,
            'old_values' => $data['old_values'] ?? null,
            'new_values' => $data['new_values'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
            'route_name' => $data['route_name'] ?? null,
            'url' => $data['url'] ?? null,
            'method' => $data['method'] ?? null,
            'batch_id' => $data['batch_id'] ?? null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private static function passesFilters(Fluent $log, array $filters): bool
    {
        if (($filters['date_from'] ?? '') !== '' && $log->created_at->lt(Carbon::parse($filters['date_from'])->startOfDay())) {
            return false;
        }

        if (($filters['date_to'] ?? '') !== '' && $log->created_at->gt(Carbon::parse($filters['date_to'])->endOfDay())) {
            return false;
        }

        if (! empty($filters['user_id']) && (string) $log->user_id !== (string) $filters['user_id']) {
            return false;
        }

        if (($filters['module'] ?? '') !== '' && (string) $log->module !== (string) $filters['module']) {
            return false;
        }

        if (($filters['action'] ?? '') !== '' && (string) $log->action !== (string) $filters['action']) {
            return false;
        }

        $keyword = trim((string) ($filters['q'] ?? ''));

        if ($keyword === '') {
            return true;
        }

        $haystack = implode(' ', [
            $log->description,
            $log->user_name,
            $log->ip_address,
            $log->route_name,
            $log->url,
        ]);

        return mb_stripos($haystack, $keyword) !== false;
    }

    private static function pathFor(Carbon $date): string
    {
        return self::directory().DIRECTORY_SEPARATOR.'activity-'.$date->format('Y-m-d').'.jsonl';
    }

    private static function dateFromPath(string $path): ?Carbon
    {
        if (! preg_match('/activity-(\d{4}-\d{2}-\d{2})\.jsonl$/', $path, $matches)) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d', $matches[1], config('app.timezone'))->startOfDay();
    }

    /**
     * @return array<int, string>
     */
    private static function files(): array
    {
        $directory = self::directory();

        if (! File::isDirectory($directory)) {
            return [];
        }

        return collect(File::files($directory))
            ->map(fn ($file): string => $file->getPathname())
            ->filter(fn (string $path): bool => str_ends_with($path, '.jsonl'))
            ->sortDesc()
            ->values()
            ->all();
    }

    private static function directory(): string
    {
        return storage_path(self::DIRECTORY);
    }
}
