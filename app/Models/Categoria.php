<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    use HasFactory;
    protected $table      = 'categorias';
    protected $primaryKey = 'id_ca';

    public function familia(): BelongsTo
    {
        return $this->belongsTo(Familia::class, 'id_fa', 'id_fa');
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Productos::class, 'id_ca', 'id_ca');
    }
}
