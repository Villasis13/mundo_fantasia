<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpresaPlane extends Model
{
    use HasFactory;
    protected $table = "empresa_planes";
    protected $primaryKey = "id_empresa_plan";
}
