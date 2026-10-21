<?php

namespace App\Console\Commands;

use App\Models\Image;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Removes the records that are older than 30 days.
 *
 * The files of the records go with them, otherwise the disk keeps the files
 * of the records that no longer exist.
 */
class DeleteOldImages extends Command
{
    /**
     * The disk the files of the gallery live on.
     */
    private const DISK = 'private';

    /**
     * The name and the signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:delete-old';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete the image records that are older than 30 days';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $deleted = 0;
        $files = 0;

        Image::where('created_at', '<', Carbon::now()->subDays(30))
            ->each(function (Image $image) use (&$deleted, &$files) {
                $path = $image->file;

                if ($image->delete() && $path) {
                    Storage::disk(self::DISK)->delete($path);
                    $files++;
                }

                $deleted++;
            });

        $this->info("Deleted {$deleted} image records older than 30 days and {$files} files.");

        return self::SUCCESS;
    }
}
