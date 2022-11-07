<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Image>
 */
class ImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type=$this->faker->randomElement(['1','2','3']);
        return [
            'name'=>$this->faker->name,
            'description'=>$this->faker->sentence(6),
            'type'=>$type,
            'file'=>$this->faker->image('public/storage/images',360, 360, 'animals', true, true, 'cats', true, 'jpg'),
        ];
    }
}
