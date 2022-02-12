<?php

namespace Database\Factories;

use App\Models\Link;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $link = Link::inRandomOrder()->first();
        return [
            'code'  =>  $link->code,
            'ambassador_email'  =>  $link->user->email,
            'first_name'    =>  $this->faker->firstName,
            'last_name' =>  $this->faker->lastName,
            'email' =>  $this->faker->email,
            'complete'  =>  1,
            'user_id'   =>  $link->user->id
        ];
    }
}
