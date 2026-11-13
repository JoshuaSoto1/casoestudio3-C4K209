<?php

namespace App\Models;

use Database\Factories\GrupoFactory;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use InvalidArgumentException;

class Grupo extends Model
{
    /** @use HasFactory<GrupoFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'abierto' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Curso, $this>
     */
    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    /**
     * RN-02: cada grupo lo imparten 1 o 2 docentes.
     *
     * @return BelongsToMany<Docente, $this>
     */
    public function docentes(): BelongsToMany
    {
        return $this->belongsToMany(Docente::class, 'grupo_docente', 'grupo_id', 'docente_id');
    }

    /**
     * RN-04 / RN-05: estudiantes matriculados, con su nota en el pivote.
     *
     * @return BelongsToMany<Estudiante, $this, EstudianteMatriculado>
     */
    public function estudiantes(): BelongsToMany
    {
        return $this->belongsToMany(Estudiante::class, 'estudiantes_matriculados', 'grupo_id', 'estudiante_id')
            ->using(EstudianteMatriculado::class)
            ->withPivot('nota');
    }

    /**
     * RN-02: asocia un docente al grupo; como máximo 2.
     *
     * @throws DomainException
     */
    public function asignarDocente(Docente $d): void
    {
        if ($this->docentes()->count() >= 2) {
            throw new DomainException('Un grupo no puede tener más de 2 docentes.');
        }

        $this->docentes()->attach($d->id);
    }

    /**
     * RN-02 / RN-05: al menos 1 docente y al menos 15 estudiantes.
     */
    public function puedeAbrirse(): bool
    {
        return $this->docentes()->count() >= 1
            && $this->estudiantes()->count() >= 15;
    }

    /**
     * @throws DomainException
     */
    public function abrir(): void
    {
        if (! $this->puedeAbrirse()) {
            throw new DomainException('El grupo necesita al menos 1 docente y 15 estudiantes para abrirse.');
        }

        $this->abierto = true;
        $this->save();
    }

    /**
     * RN-04 / RN-06: registra la nota (0 a 10) en la matrícula del estudiante.
     *
     * @throws InvalidArgumentException
     */
    public function registrarNota(Estudiante $e, float $nota): void
    {
        if ($nota < 0 || $nota > 10) {
            throw new InvalidArgumentException('La nota debe estar entre 0 y 10.');
        }

        $this->estudiantes()->updateExistingPivot($e->id, ['nota' => $nota]);
    }
}
