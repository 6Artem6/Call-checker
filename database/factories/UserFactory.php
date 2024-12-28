<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\Models\User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_name' => $this->faker->userName, // Генерирует случайное имя пользователя
            'password' => bcrypt('password'), // Зашифрованный пароль
            'auth_key' => Str::random(32), // Генерация случайного ключа
            'access_token' => Str::random(64), // Генерация случайного токена доступа
            'status' => $this->faker->randomElement([0, 1]), // Статус (например, активен/неактивен)
        ];
    }
}
