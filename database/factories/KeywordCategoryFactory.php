<?php

namespace Database\Factories;

use App\Models\KeywordCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class KeywordCategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = KeywordCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => [
                'cs' => $this->faker->word(),
                'en' => $this->faker->word(),
            ],
        ];
    }
}
