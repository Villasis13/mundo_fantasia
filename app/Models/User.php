<?php

namespace App\Models;

use App\Mail\RecuperarContrasena;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    private $log;

    public function __construct()
    {
        $this->log = new Logs();
    }

    use HasApiTokens, HasFactory, Notifiable;
    use HasRoles;

    protected $table      = 'users';
    protected $primaryKey = 'id_users';
    protected $fillable   = [
        'nombre_users',
        'email',
        'password',
        'username',
        'users_fotografia',
        'id_persona',
        'users_estado',
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function listar_datos_usuario($id)
    {
        try {
            $result = DB::table('users as u')
                ->join('persona as p', 'p.id_persona', '=', 'u.id_persona')
                ->join('model_has_roles as mr', 'mr.model_id', '=', 'u.id_users')
                ->join('roles as r', 'r.id', '=', 'mr.role_id')
                ->where('u.id_users', $id)
                ->first();
        } catch (\Exception $e) {
            $this->log->insertarLog($e);
            $result = null;
        }
        return $result;
    }

    public function sendPasswordResetNotification($token): void
    {
        $url = route('password.reset', ['token' => $token, 'email' => $this->email]);
        Mail::to($this->email)->send(new RecuperarContrasena($url));
    }

    public function sucursales(): HasMany
    {
        return $this->hasMany(UserSucursal::class, 'id_users', 'id_users');
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona');
    }
}
