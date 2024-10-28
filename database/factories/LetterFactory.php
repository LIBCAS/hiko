<?php

namespace Database\Factories;

use App\Models\Letter;
use Illuminate\Database\Eloquent\Factories\Factory;

class LetterFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Letter::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        $date = $this->faker->date('Y n j');
        $date2 = $this->faker->date('Y n j');
        $is_range = $this->faker->boolean();

        return [
            'date_year' => explode(' ', $date)[0],
            'date_month' => explode(' ', $date)[1],
            'date_day' => explode(' ', $date)[2],
            'date_marked' => $date,
            'date_uncertain' => $this->faker->boolean(),
            'date_approximate' => $this->faker->boolean(),
            'date_inferred' => $this->faker->boolean(),
            'date_is_range' => $is_range,
            'range_year' => $is_range ? explode(' ', $date2)[0] : null,
            'range_month' => $is_range ? explode(' ', $date2)[1] : null,
            'range_day' => $is_range ? explode(' ', $date2)[2] : null,
            'date_note' => $this->faker->sentence(),
            'author_inferred' => $this->faker->boolean(),
            'author_uncertain' => $this->faker->boolean(),
            'author_note' => $this->faker->sentence(),
            'recipient_inferred' => $this->faker->boolean(),
            'recipient_uncertain' => $this->faker->boolean(),
            'recipient_note' => $this->faker->sentence(),
            'destination_inferred' => $this->faker->boolean(),
            'destination_uncertain' => $this->faker->boolean(),
            'destination_note' => $this->faker->sentence(),
            'origin_inferred' => $this->faker->boolean(),
            'origin_uncertain' => $this->faker->boolean(),
            'origin_note' => $this->faker->sentence(),
            'people_mentioned_note' => $this->faker->sentence(),
            'copies' => [
                [
                    'archive' => 'Archiv ' . $this->faker->word(),
                    'collection' => $this->faker->word(),
                    'copy' => 'handwritten',
                    'l_number' => $this->faker->randomNumber(),
                    'location_note' => $this->faker->sentence(),
                    'manifestation_notes' => $this->faker->sentence(),
                    'ms_manifestation' => 'ALS',
                    'preservation' => 'original',
                    'repository' => $this->faker->words(3, true),
                    'signature' => $this->faker->word() . ' ' . $this->faker->randomNumber(),
                    'type' => 'letter',
                ],
            ],
            'related_resources' => [
                [
                    'link' => $this->faker->url(),
                    'title' => $this->faker->words(3, true),
                ],
            ],
            'explicit' => $this->faker->sentence(),
            'incipit' => $this->faker->sentence(),
            'copyright' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['draft', 'publish',]),
            'abstract' => [
                'cs' => $this->faker->paragraph(),
                'en' => $this->faker->paragraph(),
            ],
            'languages' => 'Czech;Latin',
            'notes_private' => $this->faker->paragraph(),
            'notes_public' => $this->faker->paragraph(),
            'content' => $this->faker->paragraphs(3, true),
        ];
    }
}
