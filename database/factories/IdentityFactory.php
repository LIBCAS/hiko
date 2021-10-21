<?php

namespace Database\Factories;

use App\Models\Identity;
use Illuminate\Database\Eloquent\Factories\Factory;

class IdentityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Identity::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $type = $this->faker->randomElement(['person', 'institution',]);

        $surname = $type === 'person' ? $this->faker->lastName() : null;
        $firstname = $type === 'person' ? $this->faker->firstName() : null;

        return [
            'name' => $this->faker->name(),
            'type' => $type,
            'surname' => $surname,
            'forename' => $firstname,
            'gender' => $this->faker->randomElement(['M', 'F',]),
            'nationality' => $this->faker->word(),
        ];
    }
}
