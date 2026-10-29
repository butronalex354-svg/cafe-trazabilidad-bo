<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Define el estado por defecto: un productor ya aprobado, para que los
     * tests no tengan que lidiar con el flujo de aprobacion salvo que lo
     * prueben explicitamente.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'rol' => 'productor',
            'estado' => 'aprobado',
        ];
    }

    public function productor(): static
    {
        return $this->state(fn () => ['rol' => 'productor']);
    }

    public function verificador(): static
    {
        return $this->state(fn () => ['rol' => 'verificador']);
    }

    public function administrador(): static
    {
        return $this->state(fn () => ['rol' => 'administrador']);
    }

    public function pendiente(): static
    {
        return $this->state(fn () => ['estado' => 'pendiente']);
    }

    public function rechazado(): static
    {
        return $this->state(fn () => ['estado' => 'rechazado']);
    }

    public function desactivado(): static
    {
        return $this->state(fn () => ['estado' => 'desactivado']);
    }
}
