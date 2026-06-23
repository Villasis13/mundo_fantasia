<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Opciones extends Model
{
    use HasFactory;
    protected $table      = 'opciones';
    protected $primaryKey = 'id_opciones';
    protected $fillable   = ['id_submenu', 'opciones_nombre', 'opciones_funcion', 'opciones_orden', 'opciones_mostrar', 'opciones_estado'];

    public function submenu(): BelongsTo
    {
        return $this->belongsTo(Submenu::class, 'id_submenu', 'id_submenu');
    }
}
