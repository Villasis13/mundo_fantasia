<?php

namespace App\Livewire\Facturacion;

use App\Models\Logs;
use App\Models\Ventas_detalle_pago;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ComunicacionBaja extends Component
{
    // ── Rol y contexto ────────────────────────────────────────
    private int $cachedRoleId        = 0;
    public int  $empresaSeleccionada  = 0;
    public int  $sucursalSeleccionada = 0;
    public      $sucursalesDisponibles = [];

    // ── Servicios privados ────────────────────────────────────
    private $logs;

    public function boot(): void
    {
        $this->logs = new Logs();

        if (auth()->check()) {
            $this->cachedRoleId = (int) DB::table('model_has_roles')
                ->where('model_id', auth()->user()->id_users)
                ->value('role_id');
        }
    }

    private function esSuperAdmin(): bool { return $this->cachedRoleId === 1; }
    private function esAdmin(): bool      { return $this->cachedRoleId === 2; }

    private function empresaUsuario(): ?int
    {
        $id = DB::table('user_tienda as ut')
            ->join('tiendas as t', 't.id_tienda', '=', 'ut.id_tienda')
            ->where('ut.id_users', auth()->user()->id_users)
            ->value('t.id_empresa');
        return $id ? (int) $id : null;
    }

    private function resolverIdEmpresa(): ?int
    {
        return $this->empresaSeleccionada > 0 ? $this->empresaSeleccionada : $this->empresaUsuario();
    }

    private function aplicarFiltroUbicacion(\Illuminate\Database\Query\Builder $query): void
    {
        if ($this->esSuperAdmin() || $this->esAdmin()) {
            $empresaId = $this->empresaSeleccionada > 0
                ? $this->empresaSeleccionada
                : $this->empresaUsuario();
            if ($empresaId) {
                $query->where('v.id_empresa', $empresaId);
            }
        } else {
            $tiendaId = DB::table('user_tienda as ut')
                ->where('ut.id_users', auth()->user()->id_users)
                ->value('ut.id_tienda');
            if ($tiendaId) {
                $query->where('v.id_sucursal', (int) $tiendaId);
            } else {
                $query->whereRaw('0 = 1');
            }
        }
    }

    // ── Filtros ───────────────────────────────────────────────
    public string $fechaInicio = '';
    public string $fechaFinal  = '';
    public bool   $buscar      = false;

    // ── Lifecycle hooks ───────────────────────────────────────

    public function updatedEmpresaSeleccionada(): void
    {
        $this->sucursalSeleccionada = 0;
        $this->buscar = false;

        if ($this->empresaSeleccionada > 0) {
            $this->sucursalesDisponibles = DB::table('sucursals')
                ->where('id_empresa', $this->empresaSeleccionada)
                ->where('sucursal_estado', 1)
                ->whereNull('deleted_at')
                ->orderBy('sucursal_nombre')
                ->get();
        } else {
            $this->sucursalesDisponibles = [];
        }
    }

    public function updatedSucursalSeleccionada(): void
    {
        $this->buscar = false;
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('historial_bajas_facturas.listar'), 403);

        $this->fechaInicio = now()->format('Y-m-d');
        $this->fechaFinal  = now()->format('Y-m-d');

        $empresaId = $this->empresaUsuario();
        if ($empresaId) {
            $this->empresaSeleccionada = $empresaId;
            $this->buscar = true;
        }
    }

    public function listar(): void
    {
        $this->buscar = true;
    }

    // ── Render ────────────────────────────────────────────────

    public function render()
    {
        $esSuperAdmin    = $this->esSuperAdmin();
        $esAdmin         = $this->esAdmin();
        $idEmpresaActiva = $this->resolverIdEmpresa();

        $empresas = ($esSuperAdmin || $esAdmin)
            ? DB::table('empresa')->where('empresa_estado', '!=', '0')->orderBy('id_empresa')->get()
            : collect();

        $ventas = collect();
        if ($this->buscar && $idEmpresaActiva) {
            $query = DB::table('ventas as v')
                ->join('empresa as e', 'e.id_empresa', '=', 'v.id_empresa')
                ->join('ventas_anulados as va', 'v.id_venta', '=', 'va.id_venta')
                ->whereDate('va.venta_anulado_datetime', '>=', $this->fechaInicio)
                ->whereDate('va.venta_anulado_datetime', '<=', $this->fechaFinal);

            $this->aplicarFiltroUbicacion($query);

            $ventas = $query->get();

            foreach ($ventas as $v) {
                $v->tipo_pago = Ventas_detalle_pago::listar_formas_x_idventa($v->id_venta);
            }
        }

        return view('livewire.facturacion.comunicacion-baja', compact(
            'empresas', 'ventas',
            'esSuperAdmin', 'esAdmin', 'idEmpresaActiva'
        ));
    }
}
