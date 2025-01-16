<?php

return [
    /**
     * Default disk used by Spatie Media Library.
     */
    'disk_name' => env('MEDIA_DISK', 'public'),

    /**
     * Maximum file size in bytes.
     */
    'max_file_size' => 1024 * 1024 * 10, // 10MB

    /**
     * (Optional) Queue name for background jobs.
     */
    'queue_name' => '',

    /**
     * Custom Media model class that handles tenant table prefixes.
     */
    'media_model' => App\Models\Media::class,

    /**
     * Configuration for S3 (if used).
     */
    's3' => [
        'domain' => 'https://' . env('AWS_BUCKET') . '.s3.amazonaws.com',
    ],

    /**
     * Extra headers for remote disks.
     */
    'remote' => [
        'extra_headers' => [
            'CacheControl' => 'max-age=604800',
        ],
    ],

    /**
     * Settings for responsive images.
     */
    'responsive_images' => [
        'use_tiny_placeholders' => true,
        'tiny_placeholder_generator' => Spatie\MediaLibrary\ResponsiveImages\TinyPlaceholderGenerator\Blurred::class,
    ],

    /**
     * Custom URL generator for tenant-based paths.
     */
    'url_generator' => App\MediaLibrary\TenancyUrlGenerator::class,

    /**
     * Whether to use version URLs.
     */
    'version_urls' => false,

    /**
     * (Optional) Custom path generator class.
     */
    'path_generator' => null,

    /**
     * Image optimizers (for performance).
     */
    'image_optimizers' => [
        Spatie\ImageOptimizer\Optimizers\Jpegoptim::class => [
            '--strip-all',
            '--all-progressive',
        ],
        Spatie\ImageOptimizer\Optimizers\Pngquant::class => [
            '--force',
        ],
        Spatie\ImageOptimizer\Optimizers\Optipng::class => [
            '-i0',
            '-o2',
            '-quiet',
        ],
        Spatie\ImageOptimizer\Optimizers\Svgo::class => [
            '--disable=cleanupIDs',
        ],
        Spatie\ImageOptimizer\Optimizers\Gifsicle::class => [
            '-b',
            '-O3',
        ],
    ],

    /**
     * Image generators for different file types.
     */
    'image_generators' => [
        Spatie\MediaLibrary\ImageGenerators\FileTypes\Image::class,
        Spatie\MediaLibrary\ImageGenerators\FileTypes\Webp::class,
        Spatie\MediaLibrary\ImageGenerators\FileTypes\Pdf::class,
        Spatie\MediaLibrary\ImageGenerators\FileTypes\Svg::class,
        Spatie\MediaLibrary\ImageGenerators\FileTypes\Video::class,
    ],

    /**
     * Image processing driver (gd or imagick).
     */
    'image_driver' => env('IMAGE_DRIVER', 'gd'),

    /**
     * Paths for FFMPEG (if used for video).
     */
    'ffmpeg_path' => env('FFMPEG_PATH', '/usr/bin/ffmpeg'),
    'ffprobe_path' => env('FFPROBE_PATH', '/usr/bin/ffprobe'),

    /**
     * (Optional) Temporary directory path for conversions.
     */
    'temporary_directory_path' => null,

    /**
     * Custom conversion jobs.
     */
    'jobs' => [
        'perform_conversions' => Spatie\MediaLibrary\Conversions\Jobs\PerformConversions::class,
        'generate_responsive_images' => Spatie\MediaLibrary\ResponsiveImages\Jobs\GenerateResponsiveImages::class,
    ],
];
