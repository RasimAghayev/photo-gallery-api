<?php

namespace App\Console\Commands;

use App\Models\Image;
use Illuminate\Console\Command;
use Carbon\Carbon;

class DeleteOldImages extends Command
{
    protected $signature = 'images:delete-old';
    protected $description = 'Delete image records older than 30 days';

    public function handle()
    {
        $deleted = Image::where('created_at', '<', Carbon::now()->subDays(30))->delete();
        $this->info("Deleted {$deleted} image records older than 30 days.");
    }
}
