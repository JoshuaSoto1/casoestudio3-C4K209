<?php

use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grupo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Curso fijo de las pruebas: IF6100, 4 créditos.
 */
function crearCursoIF6100(): Curso
{
    return Curso::factory()->create([
        'sigla' => 'IF6100',
        'nombreCurso' => 'Análisis y Diseño de Sistemas',
        'creditos' => 4,
    ]);
}

/**
 * Grupo 01 de IF6100, ciclo 2-2026, cerrado. Si no se pasa el curso, lo crea.
 *
 * @param  array<string, mixed>  $atributos  Valores que el caso necesite cambiar.
 */
function crearGrupo01(?Curso $curso = null, array $atributos = []): Grupo
{
    $curso ??= crearCursoIF6100();

    return Grupo::factory()->create([
        'curso_id' => $curso->id,
        'numeroCiclo' => 2,
        'ano' => 2026,
        'numeroGrupo' => 1,
        'abierto' => false,
        ...$atributos,
    ]);
}

function crearDocente1001(): Docente
{
    return Docente::factory()->create([
        'codEmpleado' => 1001,
        'nombre' => 'Ana',
        'apellidos' => 'Mora Vargas',
    ]);
}

function crearDocente1002(): Docente
{
    return Docente::factory()->create([
        'codEmpleado' => 1002,
        'nombre' => 'Luis',
        'apellidos' => 'Rojas Solano',
    ]);
}

function crearDocente1003(): Docente
{
    return Docente::factory()->create([
        'codEmpleado' => 1003,
        'nombre' => 'Carla',
        'apellidos' => 'Jiménez Araya',
    ]);
}

/**
 * Estudiante fijo C12345. El consejero solo se asigna cuando el caso lo pide.
 */
function crearEstudianteC12345(?Docente $consejero = null): Estudiante
{
    return Estudiante::factory()->create([
        'carne' => 'C12345',
        'nombre' => 'María',
        'apellidos' => 'Solís Quesada',
        'consejero_id' => $consejero?->id,
    ]);
}

/**
 * Matricula N "otros estudiantes" (generados con factory) en el grupo.
 *
 * @return Collection<int, Estudiante>
 */
function matricularEstudiantes(Grupo $grupo, int $cantidad): Collection
{
    $estudiantes = Estudiante::factory()->count($cantidad)->create();

    $grupo->estudiantes()->attach($estudiantes->modelKeys());

    return $estudiantes;
}
