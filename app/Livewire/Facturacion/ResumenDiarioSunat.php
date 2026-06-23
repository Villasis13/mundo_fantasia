<?php

namespace App\Livewire\Facturacion;

use App\Models\apiFacturacion;
use App\Models\Empresa;
use App\Models\Envio_resumen;
use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class ResumenDiarioSunat extends Component
{
    // ── Rol y contexto ────────────────────────────────────────
    private int $cachedRoleId       = 0;
    public int  $empresaSeleccionada = 0;

    // ── Servicios privados ────────────────────────────────────
    private $logs;
    private $empresas;

    public function boot(): void
    {
        $this->logs = new Logs();
        $this->empresas = new Empresa();

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

    private function aplicarFiltroEmpresa(\Illuminate\Database\Query\Builder $query): void
    {
        $idEmpresa = $this->resolverIdEmpresa();
        if ($idEmpresa) {
            $query->where('er.id_empresa', $idEmpresa);
        }
    }

    // ── Filtros ───────────────────────────────────────────────
    public string $fechaInicio = '';
    public string $fechaFinal  = '';
    public bool   $buscar      = false;

    // ── Confirmación ─────────────────────────────────────────
    public ?int   $idResumenConfirmacion = null;
    public string $mensajeConfirmacion   = '';

    // ── Lifecycle hooks ───────────────────────────────────────

    public function updatedEmpresaSeleccionada(): void
    {
        $this->buscar = false;
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('historial_resumenes_diarios.listar'), 403);

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

    // ── Confirmación consulta ticket ──────────────────────────
    public function confirmarConsultaTicket(int $idResumen): void
    {
        $this->idResumenConfirmacion = $idResumen;
        $this->mensajeConfirmacion   = '¿Está seguro que desea consultar este Resumen Diario?';
        $this->dispatch('abrirModalResumenDiario');
    }

    public function ejecutarConfirmacion(): void
    {
        if (!$this->idResumenConfirmacion) return;

        if (!auth()->user()->can('historial_resumenes_diarios.crear')) {
            $this->dispatch('cerrarModalResumenDiario');
            session()->flash('error', 'No tienes permiso para consultar tickets.');
            return;
        }

        $this->dispatch('cerrarModalResumenDiario');
        $this->consultarTicketResumen($this->idResumenConfirmacion);

        $this->idResumenConfirmacion = null;
        $this->mensajeConfirmacion   = '';
    }
    private function consultarTicketResumen(int $idResumen): void
    {
        try {
            $resumenDiario = Envio_resumen::find($idResumen);
            if (!$resumenDiario) {
                session()->flash('error', 'No se encontró la información del resumen.');
                return;
            }

            $emisor = $this->empresas->listar_datos_empresa_x_id($resumenDiario->id_empresa);
            if (!$emisor) {
                session()->flash('error', 'No se encontró información registrada de la empresa (emisor).');
                return;
            }

            $cabecera = [
                'tipocomp'      => 'RC',
                'serie'         => $resumenDiario->envio_resumen_serie,
                'correlativo'   => $resumenDiario->envio_resumen_correlativo,
                'fecha_emision' => date('Y-m-d'),
                'fecha_envio'   => date('Y-m-d'),
            ];

            $consulta = apiFacturacion::ConsultarTicket(
                $emisor,
                $cabecera,
                $resumenDiario->envio_resumen_ticket,
                'ApiFacturacion/cdr/',
                1,
            );

            if (($consulta['result'] ?? 0) == 1) {
                session()->flash('success', $consulta['mensaje'] ?? 'Ticket consultado correctamente.');
            } else {
                session()->flash('error', $consulta['mensaje'] ?? 'No se obtuvo respuesta al consultar el ticket.');
            }

            $this->listar();

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error interno. Inténtelo nuevamente o contacte al administrador.');
        }
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

        $resumenes = collect();
        if ($this->buscar && $idEmpresaActiva) {
            $query = DB::table('envio_resumen as er')
                ->whereDate('er.envio_sunat_datetime', '>=', $this->fechaInicio)
                ->whereDate('er.envio_sunat_datetime', '<=', $this->fechaFinal);

            $this->aplicarFiltroEmpresa($query);

            $resumenes = $query->get();
        }

        return view('livewire.facturacion.resumen-diario-sunat', compact(
            'empresas', 'resumenes',
            'esSuperAdmin', 'esAdmin', 'idEmpresaActiva'
        ));
    }
}
