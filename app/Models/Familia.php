<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Familia extends Model
{
    use HasFactory;
    protected $table      = 'familias';
    protected $primaryKey = 'id_fa';

    public function categorias(): HasMany
    {
        return $this->hasMany(Categoria::class, 'id_fa', 'id_fa');
    }
}
