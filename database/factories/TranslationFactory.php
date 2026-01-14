<?php

namespace Database\Factories;
use App\Models\Locale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Translation>
 */
class TranslationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'key' => 'app.' . $this->faker->unique()->slug(3),
            'content' => $this->faker->sentence(),
            'locale_id' => Locale::inRandomOrder()->value('id'),
        ];
    }
}
