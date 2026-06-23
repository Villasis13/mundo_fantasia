<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Orden_compra extends Model
{
    use HasFactory;
    protected $table      = 'orden_compra';
    protected $primaryKey = 'id_orden_compra';

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedores::class, 'id_proveedores', 'id_proveedores');
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_solicitante', 'id_users');
    }

    public function tipoPago(): BelongsTo
    {
        return $this->belongsTo(Tipo_pago::class, 'id_tipo_pago', 'id_tipo_pago');
    }

    public function detalle(): HasMany
    {
        return $this->hasMany(Detalle_compra::class, 'id_orden_compra', 'id_orden_compra');
    }
}
