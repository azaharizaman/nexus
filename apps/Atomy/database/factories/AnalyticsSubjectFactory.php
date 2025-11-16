<?php

namespace Database\Factories;

use App\Models\AnalyticsSubject;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnalyticsSubjectFactory extends Factory
{
    protected $model = AnalyticsSubject::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'tenant_id' => null,
        ];
    }
}
