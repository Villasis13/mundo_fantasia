<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sucursal extends Model
{
    use HasFactory;
    USE SoftDeletes;
    protected $table      = "sucursals";
    protected $primaryKey = "id_sucursal";

    public function usuarios(): HasMany
    {
        return $this->hasMany(UserSucursal::class, 'id_sucursal', 'id_sucursal');
    }

    public function productos(): HasMany
    {
        return $this->hasMany(ProductoSucursal::class, 'id_sucursal', 'id_sucursal');
    }
}
