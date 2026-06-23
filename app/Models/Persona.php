<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Persona extends Model
{
    use HasFactory;
    protected $table      = 'persona';
    protected $primaryKey = 'id_persona';
    protected $fillable   = [
        'id_empresa', 'persona_nombre', 'persona_apellido_paterno', 'persona_apellido_materno',
        'persona_email', 'persona_tipo_documento', 'persona_dni', 'persona_nacionalidad',
        'persona_estado_civil', 'persona_direccion', 'persona_discapacidad', 'persona_job',
        'persona_nacimiento', 'persona_sexo', 'persona_telefono', 'persona_telefono_2',
        'persona_foto', 'persona_hijos', 'persona_departamento', 'persona_provincia',
        'persona_distrito', 'persona_adicional', 'persona_afp', 'persona_cuspp',
        'persona_blacklist', 'persona_bank', 'persona_number_account', 'persona_bank_alt',
        'persona_number_account_alt', 'persona_bank_cts', 'persona_account_cts', 'persona_cv',
        'persona_empleado', 'person_codigo', 'persona_estado',
    ];

    public function usuario(): HasOne
    {
        return $this->hasOne(User::class, 'id_persona', 'id_persona');
    }
}
