<?php

use App\Models\EstudianteMatriculado;
use App\Models\Grupo;

mutates(Grupo::class);

test('C3 registrarNota acepta el límite inferior 0', function () {
    $grupo = crearGrupo01();
    $estudiante = crearEstudianteC12345();
    $grupo->estudiantes()->attach($estudiante);

    expect(fn () => $grupo->registrarNota($estudiante, 0))->not->toThrow(Throwable::class);

    $matricula = EstudianteMatriculado::where('grupo_id', $grupo->id)
        ->where('estudiante_id', $estudiante->id)
        ->first();

    expect($matricula->nota)->toBe(0.0);
});

test('C3 registrarNota acepta el límite superior 10', function () {
    $grupo = crearGrupo01();
    $estudiante = crearEstudianteC12345();
    $grupo->estudiantes()->attach($estudiante);

    expect(fn () => $grupo->registrarNota($estudiante, 10))->not->toThrow(Throwable::class);

    $matricula = EstudianteMatriculado::where('grupo_id', $grupo->id)
        ->where('estudiante_id', $estudiante->id)
        ->first();

    expect($matricula->nota)->toBe(10.0);
});
