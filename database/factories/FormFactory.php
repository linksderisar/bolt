<?php

namespace LaraZeus\Bolt\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use LaraZeus\Bolt\Models\Form;

class FormFactory extends Factory
{
    protected $model = Form::class;

    public function definition(): array
    {
        return [
            'category_id' => null,
            'name' => $this->faker->word(),
            'description' => ['en' => $this->faker->sentence()],
            'slug' => $this->faker->slug(),
            'ordering' => $this->faker->numberBetween(1, 100),
            'is_active' => $this->faker->boolean(),
            'details' => ['en' => null],
            'options' => [
                'confirmation-message' => null,
                'require-login' => false,
                'show-as' => 'page',
                'emails-notification' => null,
            ],
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
            'extensions' => null,
        ];
    }
}