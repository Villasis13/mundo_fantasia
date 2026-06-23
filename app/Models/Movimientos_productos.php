<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Movimientos_productos extends Model
{
    use HasFactory;
    protected $table      = 'movimientos_productos';
    protected $primaryKey = 'id_movimientos_productos';

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_users', 'id_users');
    }

    public function detalle(): HasMany
    {
        return $this->hasMany(MovimientosProductosDetalle::class, 'id_movimientos_productos', 'id_movimientos_productos');
    }
}
