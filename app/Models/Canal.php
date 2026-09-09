<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Canal extends Model
{
    use HasFactory;

    // Indicamos explícitamente el nombre de la tabla en español
    protected $table = 'canales';

    // Campos permitidos para guardar información
    protected $fillable = [
        'nombre',
        'enlace_streaming',
        'logo',
        'estado',
    ];
}
