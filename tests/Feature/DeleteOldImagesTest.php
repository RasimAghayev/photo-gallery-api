<?php

namespace Tests\Feature;

use App\Models\Image;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeleteOldImagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
    }

    public function test_the_records_that_are_older_than_thirty_days_are_removed(): void
    {
        $old = Image::factory()->create(['created_at' => now()->subDays(31)]);
        $fresh = Image::factory()->create(['created_at' => now()->subDays(29)]);

        $this->artisan('images:delete-old')->assertSuccessful();

        $this->assertDatabaseMissing('images', ['id' => $old->id]);
        $this->assertDatabaseHas('images', ['id' => $fresh->id]);
    }

    public function test_the_files_of_the_removed_records_go_with_them(): void
    {
        $old = Image::factory()->create(['created_at' => now()->subDays(31)]);
        $path = $old->file;

        $this->artisan('images:delete-old')->assertSuccessful();

        // The command deleted the row before, so the file of the row was left
        // behind on the disk.
        Storage::disk('private')->assertMissing($path);
    }
}
