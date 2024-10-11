<?php

namespace LaraZeus\Bolt\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use LaraZeus\Bolt\Models\Form;
use LaraZeus\Bolt\Models\Section;

class SectionFactory extends Factory
{
    protected $model = Section::class;

    public function definition(): array
    {
        return [
            'form_id' => Form::factory(),  // Or you can associate it with an existing Form
            'name' => ['en' => $this->faker->word()],
            'ordering' => $this->faker->numberBetween(1, 10),
            'columns' => $this->faker->numberBetween(1, 4),
            'description' => null,
            'icon' => null,
            'aside' => 0,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
            'compact' => 0,
            'options' => [
                'visibility' => [
                    'active' => 0,
                    'fieldID' => null,
                    'values' => null
                ]
            ],
        ];
    }
}