<?php

namespace App\Livewire\GestionVentas;

use App\Models\Caja as CajaModel;
use Illuminate\Support\Facades\DB;

class TransferenciaGratuita extends Caja
{
    public function mount(): void
    {
        abort_if(!auth()->user()->can('transferencia_gratuita.listar'), 403);

        $caja = (new CajaModel())->buscar_apertura_caja();
        if ($caja) {
            $this->validarCaja  = true;
            $this->idCaja       = (int) $caja->id_caja;
            $this->idCajaNumero = (int) $caja->id_caja_numero;

            $cn = DB::table('caja_numero')->where('id_caja_numero', $this->idCajaNumero)->first();
            $this->idTienda   = (int) ($cn->id_tienda ?? 0);
            $this->nombreCaja = $cn->caja_numero_nombre ?? '';

            if ($this->idTienda) {
                $tienda = DB::table('tiendas')->where('id_tienda', $this->idTienda)->first();
                $this->idEmpresa    = (int) ($tienda->id_empresa ?? 1);
                $this->nombreTienda = $tienda->tienda_nombre ?? '';
            }
        }

        $this->tiposPago = DB::table('tipo_pago')
            ->where('tipo_pago_estado', 1)
            ->orderBy('id_tipo_pago')
            ->get()
            ->toArray();

        $this->esGratuita = true;
    }

    protected function permisoCrear(): string
    {
        return 'transferencia_gratuita.crear';
    }

    // Mantiene el modo gratuita siempre activo en este módulo
    public function updatedEsGratuita(): void
    {
        $this->esGratuita   = true;
        $this->idFormasPago = 1;
        $this->pagos        = [];
    }
}
