<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Image Upload Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for image uploads including
    | file size limits, allowed types, and processing options.
    |
    */

    'max_file_size' => env('IMAGE_MAX_FILE_SIZE', 10485760), // 10MB in bytes

    'allowed_mime_types' => [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp'
    ],

    'allowed_extensions' => [
        'jpg', 'jpeg', 'png', 'gif', 'webp'
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Processing
    |--------------------------------------------------------------------------
    |
    | Configuration for image processing including quality, dimensions,
    | and thumbnail generation.
    |
    */

    'processing' => [
        'default_quality' => 85,
        'max_width' => 1920,
        'max_height' => 1080,
        'auto_orient' => true,
        'strip_exif' => true,
    ],

    'thumbnails' => [
        'small' => [
            'width' => 150,
            'height' => 150,
            'quality' => 80,
            'method' => 'fit' // fit, crop, resize
        ],
        'medium' => [
            'width' => 300,
            'height' => 300,
            'quality' => 85,
            'method' => 'fit'
        ],
        'large' => [
            'width' => 800,
            'height' => 600,
            'quality' => 90,
            'method' => 'resize'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configure where images are stored and how they are accessed.
    |
    */

    'storage' => [
        'disk' => env('IMAGE_STORAGE_DISK', 's3'),
        'path_prefix' => env('IMAGE_PATH_PREFIX', 'images'),
        'organize_by_date' => true,
        'organize_by_type' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | CDN and Optimization
    |--------------------------------------------------------------------------
    |
    | Configuration for CDN usage and image optimization services.
    |
    */

    'cdn' => [
        'enabled' => env('IMAGE_CDN_ENABLED', false),
        'base_url' => env('IMAGE_CDN_URL'),
        'transformations' => env('IMAGE_CDN_TRANSFORMATIONS', false),
    ],

    'optimization' => [
        'enabled' => env('IMAGE_OPTIMIZATION_ENABLED', true),
        'service' => env('IMAGE_OPTIMIZATION_SERVICE', 'intervention'), // intervention, imagemin, etc.
    ],

    /*
    |--------------------------------------------------------------------------
    | Watermark Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for watermarking images.
    |
    */

    'watermark' => [
        'enabled' => env('IMAGE_WATERMARK_ENABLED', false),
        'path' => env('IMAGE_WATERMARK_PATH', storage_path('app/watermarks/logo.png')),
        'position' => env('IMAGE_WATERMARK_POSITION', 'bottom-right'),
        'opacity' => env('IMAGE_WATERMARK_OPACITY', 50),
        'margin' => env('IMAGE_WATERMARK_MARGIN', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Type-specific Configuration
    |--------------------------------------------------------------------------
    |
    | Different settings for different image types.
    |
    */

    'types' => [
        'listing' => [
            'max_files' => 10,
            'required_primary' => true,
            'generate_thumbnails' => true,
            'max_width' => 1200,
            'max_height' => 900,
            'quality' => 85,
        ],
        'profile' => [
            'max_files' => 1,
            'required_primary' => true,
            'generate_thumbnails' => true,
            'max_width' => 800,
            'max_height' => 800,
            'quality' => 90,
        ],
        'user_avatar' => [
            'max_files' => 1,
            'required_primary' => true,
            'generate_thumbnails' => true,
            'max_width' => 400,
            'max_height' => 400,
            'quality' => 90,
            'force_square' => true,
        ],
        'seller_verification' => [
            'max_files' => 5,
            'required_primary' => false,
            'generate_thumbnails' => false,
            'max_width' => 1920,
            'max_height' => 1080,
            'quality' => 95,
            'strip_exif' => false, // Keep EXIF for verification
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security-related settings for image uploads.
    |
    */

    'security' => [
        'scan_for_malware' => env('IMAGE_MALWARE_SCAN', false),
        'check_image_headers' => true,
        'verify_file_signature' => true,
        'block_svg_uploads' => true,
        'sanitize_filename' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanup Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for cleaning up unused or old images.
    |
    */

    'cleanup' => [
        'enabled' => env('IMAGE_CLEANUP_ENABLED', true),
        'orphaned_images_ttl' => 7, // days
        'temp_files_ttl' => 1, // days
        'deleted_images_ttl' => 30, // days to keep in trash
    ],
];