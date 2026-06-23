<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caja_numero extends Model
{
    use HasFactory;
    protected $table      = 'caja_numero';
    protected $primaryKey = 'id_caja_numero';

    public function series(): HasMany
    {
        return $this->hasMany(Serie::class, 'id_caja_numero', 'id_caja_numero');
    }

    public function cajas(): HasMany
    {
        return $this->hasMany(Caja::class, 'id_caja_numero', 'id_caja_numero');
    }
}
