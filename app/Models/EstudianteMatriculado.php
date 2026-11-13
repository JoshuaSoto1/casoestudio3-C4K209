<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class EstudianteMatriculado extends Pivot
{
    protected $table = 'estudiantes_matriculados';

    public $timestamps = false;

    /**
     * RN-04 / RN-06: la nota es null hasta que el curso finaliza.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nota' => 'float',
        ];
    }
}
