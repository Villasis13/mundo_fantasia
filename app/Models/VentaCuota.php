<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VentaCuota extends Model
{
    use HasFactory;
    protected $table      = 'ventas_cuotas';
    protected $primaryKey = 'id_ventas_cuotas';

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Ventas::class, 'id_venta', 'id_venta');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(PagosCuota::class, 'id_ventas_cuotas', 'id_ventas_cuotas');
    }
}
