<?php

namespace App\Models;

use Database\Factories\EstudianteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Estudiante extends Model
{
    /** @use HasFactory<EstudianteFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * RN-03: cada estudiante tiene un solo profesor consejero.
     *
     * @return BelongsTo<Docente, $this>
     */
    public function consejero(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'consejero_id');
    }

    /**
     * Grupos en los que el estudiante está matriculado.
     *
     * @return BelongsToMany<Grupo, $this, EstudianteMatriculado>
     */
    public function grupos(): BelongsToMany
    {
        return $this->belongsToMany(Grupo::class, 'estudiantes_matriculados', 'estudiante_id', 'grupo_id')
            ->using(EstudianteMatriculado::class)
            ->withPivot('nota');
    }
}
