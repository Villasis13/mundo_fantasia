<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Caja extends Model
{
    use HasFactory;
    protected $table      = 'caja';
    protected $primaryKey = 'id_caja';

    public function buscar_apertura_caja()
    {
        return DB::table('caja_numero as cn')
            ->join('caja as c', 'c.id_caja_numero', '=', 'cn.id_caja_numero')
            ->join('users as u', 'c.id_users_apertura', '=', 'u.id_users')
            ->join('persona as p', 'p.id_persona', '=', 'u.id_persona')
            ->join('model_has_roles as m', 'm.model_id', '=', 'u.id_users')
            ->where('c.id_users_apertura', Auth::id())
            ->where('c.caja_fecha', now()->toDateString())
            ->where('c.caja_estado', 1)
            ->where(function ($query) {
                $query->whereNull('c.id_users_cierre')
                    ->orWhere('c.id_users_cierre', '');
            })
            ->first();
    }

    public function cajaNumero(): BelongsTo
    {
        return $this->belongsTo(Caja_numero::class, 'id_caja_numero', 'id_caja_numero');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_users_apertura', 'id_users');
    }
}
