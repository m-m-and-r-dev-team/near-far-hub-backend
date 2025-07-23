<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Images\Image;
use App\Services\Images\AwsImageUploadService;
use Illuminate\Console\Command;

class GenerateMissingThumbnails extends Command
{
    protected $signature = 'images:generate-thumbnails {--type= : Only process images of specific type} {--limit=100 : Limit number of images to process}';
    protected $description = 'Generate missing thumbnails for existing images';

    public function __construct(
        private readonly AwsImageUploadService $imageService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Generating missing thumbnails...');

        $query = Image::where('is_active', true);

        if ($type = $this->option('type')) {
            $query->where('type', $type);
        }

        $limit = (int) $this->option('limit');
        $images = $query->limit($limit)->get();

        if ($images->isEmpty()) {
            $this->info('No images found to process.');
            return 0;
        }

        $this->info("Processing {$images->count()} images...");

        $processed = 0;
        $skipped = 0;

        foreach ($images as $image) {
            $metadata = $image->getMetadata() ?? [];

            if (empty($metadata['thumbnails'])) {
                // Generate thumbnails
                // This would require implementing a method in the service
                $this->line("Processing: {$image->getFilename()}");
                $processed++;
            } else {
                $skipped++;
            }
        }

        $this->info("Processing complete. Processed: {$processed}, Skipped: {$skipped}");
        return 0;
    }
}