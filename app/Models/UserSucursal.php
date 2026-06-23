<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSucursal extends Model
{
    protected $table      = 'user_sucursal';
    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id_users',
        'id_sucursal',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_users', 'id_users');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'id_sucursal', 'id_sucursal');
    }
}
