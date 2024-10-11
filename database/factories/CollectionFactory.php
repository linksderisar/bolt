<?php

namespace LaraZeus\Bolt\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use LaraZeus\Bolt\Models\Collection;

class CollectionFactory extends Factory
{
    protected $model = Collection::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->domainWord(),
            'values' => [
                [
                    'itemValue' => $this->faker->domainWord(),
                    'itemKey' => $this->faker->unique()->domainWord(),
                    'itemIsDefault' => $this->faker->boolean(),
                ],
                [
                    'itemValue' => $this->faker->domainWord(),
                    'itemKey' => $this->faker->unique()->domainWord(),
                    'itemIsDefault' => $this->faker->boolean(),
                ],
                [
                    'itemValue' => $this->faker->domainWord(),
                    'itemKey' => $this->faker->unique()->domainWord(),
                    'itemIsDefault' => $this->faker->boolean(false),
                ]
            ],
        ];
    }
}