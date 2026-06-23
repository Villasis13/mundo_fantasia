<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedores extends Model
{
    use HasFactory;
    protected $table      = 'proveedores';
    protected $primaryKey = 'id_proveedores';

    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(Tipo_documento::class, 'id_tipo_documento', 'id_tipo_documento');
    }

    public function ordenesCompra(): HasMany
    {
        return $this->hasMany(Orden_compra::class, 'id_proveedores', 'id_proveedores');
    }
}
