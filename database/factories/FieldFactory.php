<?php

namespace LaraZeus\Bolt\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use LaraZeus\Bolt\Models\Section;

class FieldFactory extends Factory
{
    protected $model = \LaraZeus\Bolt\Models\Field::class;

    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'name' => ['en' => $this->faker->word()],
            'description' => null,
            'type' => $this->faker->randomElement([
                '\\LaraZeus\\Bolt\\Fields\\Classes\\TextInput',
                '\\LaraZeus\\Bolt\\Fields\\Classes\\Select',
                '\\LaraZeus\\Bolt\\Fields\\Classes\\Toggle',
                '\\LaraZeus\\Bolt\\Fields\\Classes\\Textarea',
            ]),
            'ordering' => $this->faker->numberBetween(1, 10),
            'options' => [
                'visibility' => [
                    'active' => true,
                    'fieldID' => null,
                    'values' => null,
                ],
                'maxLength'=> null,
                'rows'=> 6,
                'cols'=> 2,
                'htmlId' => $this->faker->regexify('[A-Za-z0-9]{7}'),
                'hint' => [
                    'text' => $this->faker->optional()->word(),
                    'icon' => null,
                    'color' => null,
                    'icon-tooltip' => $this->faker->optional()->word(),
                ],
                'is_required' => $this->faker->boolean(),
                'column_span_full' => false,
            ],
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ];
    }
}