<?php

namespace Database\Factories;

use App\Models\Keyword;
use Illuminate\Database\Eloquent\Factories\Factory;

class KeywordFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Keyword::class;

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
