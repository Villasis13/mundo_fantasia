<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('persona', function (Blueprint $table) {
            $table->id('id_persona');
            $table->foreignId('id_empresa')->constrained('empresa', 'id_empresa');
            $table->string('persona_nombre', 120);
            $table->string('persona_apellido_paterno', 30);
            $table->string('persona_apellido_materno', 30)->nullable();
            $table->string('persona_email')->nullable();
            $table->string('persona_tipo_documento', 50)->nullable();
            $table->char('persona_dni', 15);
            $table->string('persona_nacionalidad', 120)->nullable();
            $table->string('persona_estado_civil', 60)->nullable();
            $table->string('persona_direccion', 300)->nullable();
            $table->string('persona_discapacidad', 50)->nullable();
            $table->string('persona_job', 200)->nullable();
            $table->date('persona_nacimiento')->nullable();
            $table->char('persona_sexo', 2)->nullable();
            $table->char('persona_telefono', 15)->nullable();
            $table->string('persona_telefono_2', 50)->nullable();
            $table->string('persona_foto')->nullable();
            $table->string('persona_hijos', 50)->nullable();
            $table->string('persona_departamento', 120)->nullable();
            $table->string('persona_provincia', 120)->nullable();
            $table->string('persona_distrito', 120)->nullable();
            $table->string('persona_adicional', 500)->nullable();
            $table->string('persona_afp', 50)->nullable();
            $table->string('persona_cuspp', 50)->nullable();
            $table->date('persona_afiliac')->nullable();
            $table->string('persona_blacklist')->nullable();
            $table->string('persona_bank')->nullable();
            $table->string('persona_number_account', 50)->nullable();
            $table->string('persona_bank_alt', 70)->nullable();
            $table->string('persona_number_account_alt', 50)->nullable();
            $table->string('persona_bank_cts', 50)->nullable();
            $table->string('persona_account_cts', 50)->nullable();
            $table->string('persona_cv', 300)->nullable();
            $table->tinyInteger('persona_empleado')->nullable();
            $table->string('person_codigo');
            $table->tinyInteger('persona_estado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('persona');
    }
};
