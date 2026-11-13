<?php

namespace Database\Factories;

use App\Models\Curso;
use App\Models\Grupo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Grupo>
 */
class GrupoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'curso_id' => Curso::factory(),
            'numeroCiclo' => fake()->numberBetween(1, 3),
            'ano' => fake()->numberBetween(2020, 2030),
            'numeroGrupo' => fake()->numberBetween(1, 99),
            'abierto' => false,
        ];
    }
}
