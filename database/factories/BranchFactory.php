<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->city() . ' Branch',
            'code' => strtoupper($this->faker->unique()->lexify('BR???')),
            'address' => $this->faker->address(),
            'phone' => '+63 917 ' . $this->faker->numerify('### ####'),
            'email' => $this->faker->unique()->safeEmail(),
            'opening_hours' => [
                'mon_fri' => '06:00 - 22:00',
                'sat' => '07:00 - 20:00',
                'sun' => '08:00 - 18:00',
            ],
            'allow_self_registration' => true,
            'is_active' => true,
        ];
    }
}
