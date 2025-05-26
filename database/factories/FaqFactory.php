<?php

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
final class FaqFactory extends Factory
{
    protected $model = Faq::class;

    public function definition(): array
    {
        return [
            'question' => $this->faker->sentence(),
            'answer_beginner' => $this->faker->paragraph(),
            'answer_advance' => $this->faker->paragraph(),
            'answer_tldr' => $this->faker->sentence(),
            'lang' => 'en',
            'categories' => 'general',
            'highlight' => false,
            'priority' => 100,
            'link' => null,
        ];
    }
}
