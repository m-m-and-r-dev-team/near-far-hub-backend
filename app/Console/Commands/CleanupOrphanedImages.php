<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Images\Image;
use App\Services\Images\AwsImageUploadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CleanupOrphanedImages extends Command
{
    protected $signature = 'images:cleanup-orphaned {--dry-run : Show what would be deleted without actually deleting}';
    protected $description = 'Clean up orphaned images that are no longer associated with any model';

    public function __construct(
        private readonly AwsImageUploadService $imageService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting orphaned images cleanup...');

        $isDryRun = $this->option('dry-run');
        $ttl = config('images.cleanup.orphaned_images_ttl', 7);
        $cutoffDate = Carbon::now()->subDays($ttl);

        // Find orphaned images (images where the related model no longer exists)
        $orphanedImages = Image::whereDoesntHave('imageable')
            ->where('created_at', '<', $cutoffDate)
            ->get();

        if ($orphanedImages->isEmpty()) {
            $this->info('No orphaned images found.');
            return 0;
        }

        $this->info("Found {$orphanedImages->count()} orphaned images older than {$ttl} days.");

        if ($isDryRun) {
            $this->table(
                ['ID', 'Type', 'Filename', 'Size', 'Created'],
                $orphanedImages->map(fn($img) => [
                    $img->getId(),
                    $img->getType()->value,
                    $img->getFilename(),
                    $img->getFormattedSize(),
                    $img->getCreatedAt()->format('Y-m-d H:i:s')
                ])->toArray()
            );
            $this->warn('This was a dry run. Use without --dry-run to actually delete the images.');
            return 0;
        }

        $deletedCount = 0;
        $failedCount = 0;

        foreach ($orphanedImages as $image) {
            if ($this->imageService->deleteImage($image)) {
                $deletedCount++;
                $this->line("✓ Deleted: {$image->getFilename()}");
            } else {
                $failedCount++;
                $this->error("✗ Failed to delete: {$image->getFilename()}");
            }
        }

        $this->info("Cleanup complete. Deleted: {$deletedCount}, Failed: {$failedCount}");
        return 0;
    }
}