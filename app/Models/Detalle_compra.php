<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Detalle_compra extends Model
{
    use HasFactory;
    protected $table      = 'detalle_compra';
    protected $primaryKey = 'id_detalle_compra';

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(Orden_compra::class, 'id_orden_compra', 'id_orden_compra');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Productos::class, 'id_pro', 'id_pro');
    }
}
