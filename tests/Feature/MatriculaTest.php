<?php

use App\Models\Estudiante;
use App\Models\EstudianteMatriculado;
use App\Models\Grupo;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

mutates(Grupo::class);

test('CP-01 registra un grupo con toda su información y su curso', function () {
    $curso = crearCursoIF6100();

    $creado = crearGrupo01($curso, ['abierto' => true]);
    $grupo = Grupo::find($creado->id);

    expect($grupo->numeroCiclo)->toBe(2)
        ->and($grupo->ano)->toBe(2026)
        ->and($grupo->numeroGrupo)->toBe(1)
        ->and($grupo->abierto)->toBeTrue()
        ->and($grupo->curso->sigla)->toBe('IF6100')
        ->and($grupo->curso->creditos)->toBe(4);

    expect(Grupo::count())->toBe(1);
});

test('CP-02 un curso tiene varios grupos y distingue los abiertos', function () {
    $curso = crearCursoIF6100();

    crearGrupo01($curso, ['numeroGrupo' => 1, 'abierto' => true]);
    crearGrupo01($curso, ['numeroGrupo' => 2, 'abierto' => true]);
    crearGrupo01($curso, ['numeroGrupo' => 3, 'abierto' => false]);

    $grupos = $curso->grupos;
    $abiertos = $grupos->where('abierto', true);

    expect($grupos)->toHaveCount(3)
        ->and($abiertos)->toHaveCount(2)
        ->and($abiertos->pluck('numeroGrupo')->sort()->values()->all())->toBe([1, 2]);

    expect(Grupo::where('curso_id', $curso->id)->count())->toBe(3)
        ->and(Grupo::pluck('curso_id')->unique()->all())->toBe([$curso->id]);
});

test('CP-03 un grupo acepta dos docentes', function () {
    $grupo = crearGrupo01();
    $docente1001 = crearDocente1001();
    $docente1002 = crearDocente1002();

    $grupo->asignarDocente($docente1001);
    $grupo->asignarDocente($docente1002);

    expect($grupo->docentes)->toHaveCount(2)
        ->and($grupo->docentes->pluck('codEmpleado')->sort()->values()->all())->toBe([1001, 1002])
        ->and($docente1001->grupos->pluck('id')->all())->toContain($grupo->id);

    expect(DB::table('grupo_docente')->count())->toBe(2);
});

test('CP-04 rechaza un tercer docente', function () {
    $grupo = crearGrupo01();
    $grupo->asignarDocente(crearDocente1001());
    $grupo->asignarDocente(crearDocente1002());
    $docente1003 = crearDocente1003();

    expect(fn () => $grupo->asignarDocente($docente1003))->toThrow(DomainException::class);

    expect($grupo->docentes()->count())->toBe(2)
        ->and($grupo->docentes()->pluck('codEmpleado')->sort()->values()->all())->toBe([1001, 1002])
        ->and(DB::table('grupo_docente')->where('docente_id', $docente1003->id)->exists())->toBeFalse();
});

test('CP-05 un grupo con 14 estudiantes no puede abrirse', function () {
    $grupo = crearGrupo01();
    $grupo->asignarDocente(crearDocente1001());
    matricularEstudiantes($grupo, 14);

    expect($grupo->puedeAbrirse())->toBeFalse()
        ->and(fn () => $grupo->abrir())->toThrow(DomainException::class);

    expect($grupo->fresh()->abierto)->toBeFalse();
});

test('CP-06 un grupo con 15 estudiantes y un docente se abre', function () {
    $grupo = crearGrupo01();
    $grupo->asignarDocente(crearDocente1001());
    matricularEstudiantes($grupo, 15);

    expect($grupo->puedeAbrirse())->toBeTrue()
        ->and(fn () => $grupo->abrir())->not->toThrow(Throwable::class);

    expect($grupo->fresh()->abierto)->toBeTrue();
});

test('CP-07 un grupo sin docente no se abre aunque tenga cupo completo', function () {
    $grupo = crearGrupo01();
    matricularEstudiantes($grupo, 20);

    expect($grupo->puedeAbrirse())->toBeFalse()
        ->and(fn () => $grupo->abrir())->toThrow(DomainException::class);

    expect($grupo->fresh()->abierto)->toBeFalse();
});

test('CP-08 un estudiante queda ligado a su profesor consejero', function () {
    $docente1001 = crearDocente1001();
    expect($docente1001->estudiantes()->count())->toBe(0);

    $estudiante = crearEstudianteC12345($docente1001);

    expect($estudiante->consejero->codEmpleado)->toBe(1001)
        ->and($docente1001->estudiantes)->toHaveCount(1)
        ->and($docente1001->estudiantes->first()->carne)->toBe('C12345');

    expect($estudiante->fresh()->consejero_id)->toBe($docente1001->id);
});

test('CP-09 cambiar de consejero reemplaza al anterior', function () {
    $docente1001 = crearDocente1001();
    $docente1002 = crearDocente1002();
    $estudiante = crearEstudianteC12345($docente1001);

    $estudiante->consejero()->associate($docente1002);
    $estudiante->save();
    $docente1001 = $docente1001->fresh();
    $docente1002 = $docente1002->fresh();

    expect($estudiante->fresh()->consejero->codEmpleado)->toBe(1002)
        ->and($docente1001->estudiantes)->toHaveCount(0)
        ->and($docente1002->estudiantes)->toHaveCount(1);

    expect(Estudiante::where('carne', 'C12345')->pluck('consejero_id')->all())->toBe([$docente1002->id]);
});

test('CP-10 al matricular la nota queda vacía', function () {
    $grupo = crearGrupo01();
    $estudiante = crearEstudianteC12345();
    expect(EstudianteMatriculado::count())->toBe(0);

    $grupo->estudiantes()->attach($estudiante);
    $pivote = $grupo->estudiantes->first()->pivot;

    expect($pivote)->toBeInstanceOf(EstudianteMatriculado::class)
        ->and($pivote->nota)->toBeNull();

    expect(EstudianteMatriculado::count())->toBe(1)
        ->and(EstudianteMatriculado::where('grupo_id', $grupo->id)
            ->where('estudiante_id', $estudiante->id)
            ->count())->toBe(1);
});

test('CP-11 registra la nota al finalizar el curso', function () {
    $grupo = crearGrupo01();
    $estudiante = crearEstudianteC12345();
    $grupo->estudiantes()->attach($estudiante);
    $otro = matricularEstudiantes($grupo, 1)->first();

    $grupo->registrarNota($estudiante, 8.5);
    $matricula = EstudianteMatriculado::where('grupo_id', $grupo->id)
        ->where('estudiante_id', $estudiante->id)
        ->first();

    expect($matricula->nota)->toBeFloat()->toBe(8.5);

    $matriculaDelOtro = EstudianteMatriculado::where('grupo_id', $grupo->id)
        ->where('estudiante_id', $otro->id)
        ->first();

    expect($matriculaDelOtro->nota)->toBeNull()
        ->and($matricula->grupo_id)->toBe($grupo->id)
        ->and($matricula->estudiante_id)->toBe($estudiante->id)
        ->and(EstudianteMatriculado::count())->toBe(2);
});

test('CP-12 rechaza notas fuera del rango de 0 a 10', function (int|float $nota) {
    $grupo = crearGrupo01();
    $estudiante = crearEstudianteC12345();
    $grupo->estudiantes()->attach($estudiante);

    expect(fn () => $grupo->registrarNota($estudiante, $nota))->toThrow(InvalidArgumentException::class);

    $matricula = EstudianteMatriculado::where('grupo_id', $grupo->id)
        ->where('estudiante_id', $estudiante->id)
        ->first();

    expect($matricula->nota)->toBeNull();
})->with([-1, 10.5]);

test('CP-13 un estudiante no se matricula dos veces en el mismo grupo', function () {
    $grupo = crearGrupo01();
    $estudiante = crearEstudianteC12345();
    $grupo->estudiantes()->attach($estudiante);

    expect(fn () => $grupo->estudiantes()->attach($estudiante))->toThrow(QueryException::class);

    expect(EstudianteMatriculado::where('grupo_id', $grupo->id)
        ->where('estudiante_id', $estudiante->id)
        ->count())->toBe(1);
});
