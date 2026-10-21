<?php

namespace Database\Factories;

use App\Models\Image;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * @extends Factory<Image>
 */
class ImageFactory extends Factory
{
    /**
     * The name of the factory.
     *
     * @var string
     */
    protected $model = Image::class;

    /**
     * The disk the files of the gallery live on.
     */
    private const DISK = 'private';

    /**
     * The default state of an image.
     *
     * The file of the record is a real file on the private disk, so a
     * record of a seeded database can be read like a record of a real one.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'description' => $this->faker->sentence(6),
            'type' => $this->faker->randomElement(['1', '2', '3']),
            'file' => Storage::disk(self::DISK)->putFile(
                'images',
                UploadedFile::fake()->image('photo.jpg', 64, 64)
            ),
        ];
    }
}
