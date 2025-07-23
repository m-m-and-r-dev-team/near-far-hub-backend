<?php

declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\CleanupOrphanedImages::class,
        Commands\GenerateMissingThumbnails::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Clean up orphaned images daily
        $schedule->command('images:cleanup-orphaned')->daily();

        // Generate missing thumbnails weekly
        $schedule->command('images:generate-thumbnails')->weekly();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}