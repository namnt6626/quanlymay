<?php

use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Support\ActivityLogFileStore;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('activity-logs:cleanup {--days=30}', function () {
    /** @var ClosureCommand $this */
    $days = max(1, (int) $this->option('days'));
    $deleted = ActivityLogFileStore::cleanupOldFiles($days);

    $this->info("Đã xóa {$deleted} file nhật ký thao tác cũ hơn {$days} ngày.");
})->purpose('Xóa file nhật ký thao tác cũ theo thời gian lưu trữ.');
