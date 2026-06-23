<?php

namespace App\Livewire\CxP;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class CuentasPagar extends Component
{
    use WithPagination;

    private int $cachedRoleId         = 0;
    public int  $empresaSeleccionada  = 0;
    public int  $sucursalSeleccionada = 0;
    public      $sucursalesDisponibles = [];

    public string $filtroProveedor   = '';
    public string $filtroEstado      = '';
    public string $filtroDesde       = '';
    public string $filtroHasta       = '';
    public string $filtroVinculada   = '';
    public int    $porPagina         = 15;
    public string $ordenColumna      = 'cp_fecha_vencimiento';
    public string $ordenDireccion    = 'asc';

    public bool   $mostrarFormCrear   = false;
    public string $buscarOrden        = '';
    public        $ordenEncontrada    = null;
    public int    $idOrdenCompra      = 0;
    public int    $idProveedores      = 0;
    public string $cpNumeroDoc        = '';
    public string $cpTipoDoc          = 'Factura';
    public string $cpFechaEmision     = '';
    public string $cpFechaVencimiento = '';
    public string $cpMontoTotal       = '';
    public string $cpObservacion      = '';

    public ?int   $idCuentaPagarPago  = null;
    public string $pcpMonto           = '';
    public string $pcpFecha           = '';
    public int    $pcpIdTipoPago      = 0;
    public string $pcpNumeroOperacion = '';
    public string $pcpVoucher         = '';
    public string $pcpObservacion     = '';
    public        $cuentaActual       = null;

    public ?int $idAnular = null;

    public ?int  $idHistorialCuentaPagar = null;
    public array $historialPagos         = [];
    public       $cuentaHistorial        = null;

    public ?int   $idVincularCuentaPagar = null;
    public        $cuentaVincular        = null;
    public array  $cuotasDisponibles     = [];
    public ?int   $idCuotaSeleccionada   = null;
    public        $cuotaVinculadaInfo    = null;

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
        if ($this->esSuperAdmin()) {
            return $this->empresaSeleccionada > 0 ? $this->empresaSeleccionada : null;
        }
        return $this->empresaSeleccionada > 0 ? $this->empresaSeleccionada : $this->empresaUsuario();
    }

    private function resolverIdSucursal(): int
    {
        if ($this->esSuperAdmin() || $this->esAdmin()) return $this->sucursalSeleccionada;
        $id = DB::table('user_tienda as ut')
            ->where('ut.id_users', auth()->user()->id_users)
            ->value('ut.id_tienda');
        return $id ? (int) $id : 0;
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('cuentas_pagar.listar'), 403);

        $this->cpFechaEmision     = now()->format('Y-m-d');
        $this->cpFechaVencimiento = now()->addDays(30)->format('Y-m-d');
        $this->pcpFecha           = now()->format('Y-m-d');
        $this->filtroDesde        = now()->startOfMonth()->format('Y-m-d');
        $this->filtroHasta        = now()->endOfMonth()->format('Y-m-d');

        $empresaId = $this->empresaUsuario();
        if ($empresaId) {
            $this->sucursalesDisponibles = DB::table('tiendas')
                ->where('id_empresa', $empresaId)
                ->where('tienda_estado', 1)
                ->orderBy('tienda_nombre')
                ->get();
        }

        if (!$this->esSuperAdmin() && !$this->esAdmin()) {
            $idTienda = DB::table('user_tienda as ut')
                ->where('ut.id_users', auth()->user()->id_users)
                ->value('ut.id_tienda');
            if ($idTienda) {
                $this->sucursalSeleccionada = (int) $idTienda;
            }
        } elseif ($this->esAdmin() && $empresaId) {
            $sucursales = DB::table('tiendas')
                ->where('id_empresa', $empresaId)
                ->where('tienda_estado', 1)
                ->get();
            if ($sucursales->count() === 1) {
                $this->sucursalSeleccionada = $sucursales->first()->id_tienda;
            }
        }
    }

    public function updatedEmpresaSeleccionada(): void
    {
        $this->sucursalSeleccionada  = 0;
        $this->resetPage();

        $this->sucursalesDisponibles = $this->empresaSeleccionada > 0
            ? DB::table('tiendas')
                ->where('id_empresa', $this->empresaSeleccionada)
                ->where('tienda_estado', 1)
                ->orderBy('tienda_nombre')
                ->get()
            : collect();
    }

    public function updatedSucursalSeleccionada(): void { $this->resetPage(); }
    public function updatedFiltroProveedor(): void      { $this->resetPage(); }
    public function updatedFiltroEstado(): void         { $this->resetPage(); }
    public function updatedFiltroDesde(): void          { $this->resetPage(); }
    public function updatedFiltroHasta(): void          { $this->resetPage(); }
    public function updatedFiltroVinculada(): void      { $this->resetPage(); }
    public function updatingPorPagina(): void           { $this->resetPage(); }

    public function ordenar(string $columna): void
    {
        $this->ordenDireccion = $this->ordenColumna === $columna
            ? ($this->ordenDireccion === 'asc' ? 'desc' : 'asc')
            : 'asc';
        $this->ordenColumna = $columna;
        $this->resetPage();
    }

    public function buscarOrdenCompra(): void
    {
        $this->ordenEncontrada = null;
        $this->idOrdenCompra   = 0;
        $this->idProveedores   = 0;

        if (trim($this->buscarOrden) === '') return;

        $orden = DB::table('orden_compra as oc')
            ->select('oc.*', 'p.proveedores_nombre', 'p.proveedores_numero_documento')
            ->join('proveedores as p', 'p.id_proveedores', '=', 'oc.id_proveedores')
            ->where(function ($q) {
                $q->where('oc.orden_compra_numero', 'like', '%' . $this->buscarOrden . '%')
                  ->orWhere('oc.orden_compra_codigo', 'like', '%' . $this->buscarOrden . '%');
            })
            ->where('oc.orden_compra_activo', 1)
            ->first();

        if ($orden) {
            $this->ordenEncontrada = $orden;
            $this->idOrdenCompra   = (int) $orden->id_orden_compra;
            $this->idProveedores   = (int) $orden->id_proveedores;
            $this->cpMontoTotal    = (string) ($orden->orden_compra_total ?? '');
        } else {
            session()->flash('error', 'No se encontró ninguna orden de compra con ese número.');
        }
    }

    public function abrirFormCrear(): void
    {
        abort_if(!auth()->user()->can('cuentas_pagar.crear'), 403);
        $this->limpiarFormCrear();
        $this->mostrarFormCrear = true;
        $this->dispatch('abrirModalCrear');
    }

    public function cerrarFormCrear(): void
    {
        $this->mostrarFormCrear = false;
        $this->limpiarFormCrear();
        $this->dispatch('cerrarModalCrear');
    }

    public function limpiarFormCrear(): void
    {
        $this->buscarOrden        = '';
        $this->ordenEncontrada    = null;
        $this->idOrdenCompra      = 0;
        $this->idProveedores      = 0;
        $this->cpNumeroDoc        = '';
        $this->cpTipoDoc          = 'Factura';
        $this->cpFechaEmision     = now()->format('Y-m-d');
        $this->cpFechaVencimiento = now()->addDays(30)->format('Y-m-d');
        $this->cpMontoTotal       = '';
        $this->cpObservacion      = '';
        $this->resetErrorBag();
    }

    public function guardar(): void
    {
        abort_if(!auth()->user()->can('cuentas_pagar.crear'), 403);

        $this->validate([
            'idProveedores'      => 'required|integer|min:1',
            'cpNumeroDoc'        => 'required|string|max:100',
            'cpTipoDoc'          => 'required|string|max:20',
            'cpFechaEmision'     => 'required|date',
            'cpFechaVencimiento' => 'required|date|after_or_equal:cpFechaEmision',
            'cpMontoTotal'       => 'required|numeric|min:0.01',
        ], [
            'idProveedores.min'                 => 'Seleccione un proveedor (busque una orden de compra).',
            'cpNumeroDoc.required'              => 'El número de documento es obligatorio.',
            'cpTipoDoc.required'                => 'El tipo de documento es obligatorio.',
            'cpFechaEmision.required'           => 'La fecha de emisión es obligatoria.',
            'cpFechaVencimiento.required'       => 'La fecha de vencimiento es obligatoria.',
            'cpFechaVencimiento.after_or_equal' => 'La fecha de vencimiento debe ser igual o posterior a la emisión.',
            'cpMontoTotal.required'             => 'El monto total es obligatorio.',
            'cpMontoTotal.min'                  => 'El monto debe ser mayor a cero.',
        ]);

        DB::beginTransaction();
        try {
            $monto = (float) $this->cpMontoTotal;

            DB::table('cuentas_pagar')->insert([
                'id_orden_compra'      => $this->idOrdenCompra ?: null,
                'id_proveedores'       => $this->idProveedores,
                'id_empresa'           => $this->resolverIdEmpresa(),
                'id_sucursal'          => $this->resolverIdSucursal() ?: null,
                'id_users_registro'    => auth()->user()->id_users,
                'cp_numero_doc'        => trim($this->cpNumeroDoc),
                'cp_tipo_doc'          => $this->cpTipoDoc,
                'cp_fecha_emision'     => $this->cpFechaEmision,
                'cp_fecha_vencimiento' => $this->cpFechaVencimiento,
                'cp_monto_total'       => $monto,
                'cp_monto_pagado'      => 0,
                'cp_saldo'             => $monto,
                'cp_estado'            => 1,
                'cp_observacion'       => trim($this->cpObservacion) ?: null,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            DB::commit();
            $this->cerrarFormCrear();
            session()->flash('success', 'Cuenta por pagar registrada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al registrar la cuenta.');
        }
    }

    public function abrirModalPago(int $idCuentaPagar): void
    {
        abort_if(!auth()->user()->can('cuentas_pagar.actualizar'), 403);

        $cuenta = DB::table('cuentas_pagar')->where('id_cuenta_pagar', $idCuentaPagar)->first();
        if (!$cuenta || $cuenta->cp_estado == 0 || $cuenta->cp_estado == 3) {
            session()->flash('error', 'Esta cuenta no puede recibir pagos.');
            return;
        }

        $this->idCuentaPagarPago  = $idCuentaPagar;
        $this->cuentaActual       = $cuenta;
        $this->pcpMonto           = (string) $cuenta->cp_saldo;
        $this->pcpFecha           = now()->format('Y-m-d');
        $this->pcpIdTipoPago      = 0;
        $this->pcpNumeroOperacion = '';
        $this->pcpVoucher         = '';
        $this->pcpObservacion     = '';
        $this->resetErrorBag();

        $this->cuotaVinculadaInfo = null;
        if ($cuenta->id_vinculado_cxc) {
            $this->cuotaVinculadaInfo = DB::table('ventas_cuotas as vc')
                ->select('vc.venta_cuota_numero', 'v.venta_serie', 'v.venta_correlativo')
                ->join('ventas as v', 'v.id_venta', '=', 'vc.id_venta')
                ->where('vc.id_ventas_cuotas', $cuenta->id_vinculado_cxc)
                ->first();
        }

        $this->dispatch('abrirModalPago');
    }

    public function cerrarModalPago(): void
    {
        $this->idCuentaPagarPago  = null;
        $this->cuentaActual       = null;
        $this->cuotaVinculadaInfo = null;
        $this->dispatch('cerrarModalPago');
    }

    public function registrarPago(): void
    {
        abort_if(!auth()->user()->can('cuentas_pagar.actualizar'), 403);

        $cuenta = DB::table('cuentas_pagar')->where('id_cuenta_pagar', $this->idCuentaPagarPago)->first();
        if (!$cuenta) {
            session()->flash('error', 'Cuenta no encontrada.');
            return;
        }

        $rules = [
            'pcpMonto'      => 'required|numeric|min:0.01|max:' . $cuenta->cp_saldo,
            'pcpFecha'      => 'required|date',
            'pcpIdTipoPago' => 'required|integer|min:1',
        ];
        $messages = [
            'pcpMonto.required'   => 'El monto es obligatorio.',
            'pcpMonto.max'        => 'El monto no puede superar el saldo pendiente (S/ ' . number_format($cuenta->cp_saldo, 2) . ').',
            'pcpMonto.min'        => 'El monto debe ser mayor a cero.',
            'pcpFecha.required'   => 'La fecha es obligatoria.',
            'pcpIdTipoPago.min'   => 'Seleccione el medio de pago.',
        ];
        if ($this->pcpIdTipoPago && $this->pcpIdTipoPago != 1) {
            $rules['pcpNumeroOperacion']                    = 'required|string|max:100';
            $messages['pcpNumeroOperacion.required'] = 'El número de operación es obligatorio para este medio de pago.';
        }

        $this->validate($rules, $messages);

        DB::beginTransaction();
        try {
            $monto         = (float) $this->pcpMonto;
            $nuevoMontoPag = round($cuenta->cp_monto_pagado + $monto, 2);
            $nuevoSaldo    = round($cuenta->cp_monto_total - $nuevoMontoPag, 2);
            $nuevoEstado   = $nuevoSaldo <= 0 ? 3 : 2;

            DB::table('pagos_cuentas_pagar')->insert([
                'id_cuenta_pagar'      => $this->idCuentaPagarPago,
                'id_users'             => auth()->user()->id_users,
                'id_tipo_pago'         => $this->pcpIdTipoPago,
                'pcp_monto'            => $monto,
                'pcp_fecha'            => $this->pcpFecha,
                'pcp_numero_operacion' => trim($this->pcpNumeroOperacion) ?: null,
                'pcp_voucher'          => trim($this->pcpVoucher) ?: null,
                'pcp_observacion'      => trim($this->pcpObservacion) ?: null,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            DB::table('cuentas_pagar')->where('id_cuenta_pagar', $this->idCuentaPagarPago)->update([
                'cp_monto_pagado' => $nuevoMontoPag,
                'cp_saldo'        => max(0, $nuevoSaldo),
                'cp_estado'       => $nuevoEstado,
                'updated_at'      => now(),
            ]);

            // Auto-sync con CxC vinculada
            if ($cuenta->id_vinculado_cxc) {
                $cuota = DB::table('ventas_cuotas')
                    ->where('id_ventas_cuotas', $cuenta->id_vinculado_cxc)
                    ->first();

                if ($cuota) {
                    $totalPagadoCxC = (float) DB::table('pagos_cuotas')
                        ->where('id_ventas_cuotas', $cuenta->id_vinculado_cxc)
                        ->whereNull('deleted_at')
                        ->sum('pagos_cuota_monto');

                    $saldoCxC = max(0.0, (float) $cuota->venta_cuota_importe - $totalPagadoCxC);
                    $montoCxC = min($monto, $saldoCxC);

                    if ($montoCxC > 0) {
                        DB::table('pagos_cuotas')->insert([
                            'id_users'            => auth()->user()->id_users,
                            'id_ventas_cuotas'    => $cuenta->id_vinculado_cxc,
                            'id_tipo_pago'        => $this->pcpIdTipoPago,
                            'pagos_cuota_monto'   => $montoCxC,
                            'pagos_cuota_fecha'   => $this->pcpFecha,
                            'pagos_cuota_voucher' => trim($this->pcpVoucher) ?: null,
                            'created_at'          => now(),
                            'updated_at'          => now(),
                        ]);

                        if (round($totalPagadoCxC + $montoCxC, 2) >= (float) $cuota->venta_cuota_importe) {
                            DB::table('ventas_cuotas')
                                ->where('id_ventas_cuotas', $cuenta->id_vinculado_cxc)
                                ->update(['venta_cuota_pago' => 1, 'updated_at' => now()]);
                        }
                    }
                }
            }

            DB::commit();
            $this->cerrarModalPago();
            session()->flash('success', 'Pago registrado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al registrar el pago.');
        }
    }

    public function abrirModalHistorial(int $idCuentaPagar): void
    {
        $this->cuentaHistorial = DB::table('cuentas_pagar as cp')
            ->select('cp.*', 'p.proveedores_nombre')
            ->join('proveedores as p', 'p.id_proveedores', '=', 'cp.id_proveedores')
            ->where('cp.id_cuenta_pagar', $idCuentaPagar)
            ->first();

        $this->historialPagos = DB::table('pagos_cuentas_pagar as pcp')
            ->select('pcp.*', 'tp.tipo_pago_nombre', 'u.nombre_users')
            ->join('tipo_pago as tp', 'tp.id_tipo_pago', '=', 'pcp.id_tipo_pago')
            ->join('users as u', 'u.id_users', '=', 'pcp.id_users')
            ->where('pcp.id_cuenta_pagar', $idCuentaPagar)
            ->whereNull('pcp.deleted_at')
            ->orderBy('pcp.pcp_fecha')
            ->get()
            ->toArray();

        $this->idHistorialCuentaPagar = $idCuentaPagar;
        $this->dispatch('abrirModalHistorial');
    }

    public function cerrarModalHistorial(): void
    {
        $this->idHistorialCuentaPagar = null;
        $this->historialPagos         = [];
        $this->cuentaHistorial        = null;
        $this->dispatch('cerrarModalHistorial');
    }

    public function abrirModalVincular(int $idCuentaPagar): void
    {
        abort_if(!auth()->user()->can('cuentas_pagar.actualizar'), 403);

        $cuenta = DB::table('cuentas_pagar as cp')
            ->select('cp.*', 'p.proveedores_nombre', 'p.proveedores_numero_documento', 'e.empresa_ruc as empresa_ruc_propia')
            ->join('proveedores as p', 'p.id_proveedores', '=', 'cp.id_proveedores')
            ->join('empresa as e', 'e.id_empresa', '=', 'cp.id_empresa')
            ->where('cp.id_cuenta_pagar', $idCuentaPagar)
            ->first();

        if (!$cuenta) return;

        $empresaProveedor = DB::table('empresa')
            ->where('empresa_ruc', $cuenta->proveedores_numero_documento)
            ->first();

        if (!$empresaProveedor) {
            session()->flash('error', 'No se encontró la empresa vinculada del proveedor en el sistema.');
            return;
        }

        $subPagado = DB::table('pagos_cuotas as pc2')
            ->selectRaw('pc2.id_ventas_cuotas, SUM(pc2.pagos_cuota_monto) as total_pagado')
            ->whereNull('pc2.deleted_at')
            ->groupBy('pc2.id_ventas_cuotas');

        $this->cuotasDisponibles = DB::table('ventas_cuotas as vc')
            ->select(
                'vc.id_ventas_cuotas', 'vc.venta_cuota_numero',
                'vc.venta_cuota_importe', 'vc.venta_cuota_fecha',
                'v.venta_serie', 'v.venta_correlativo', 'v.venta_tipo',
                DB::raw('COALESCE(pag.total_pagado, 0) as total_pagado'),
                DB::raw('GREATEST(vc.venta_cuota_importe - COALESCE(pag.total_pagado, 0), 0) as saldo')
            )
            ->join('ventas as v', 'v.id_venta', '=', 'vc.id_venta')
            ->join('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
            ->leftJoinSub($subPagado, 'pag', 'pag.id_ventas_cuotas', '=', 'vc.id_ventas_cuotas')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->where('v.id_empresa', $empresaProveedor->id_empresa)
            ->where('v.id_formas_pago', 2)
            ->where('c.cliente_numero', $cuenta->empresa_ruc_propia)
            ->whereRaw('GREATEST(vc.venta_cuota_importe - COALESCE(pag.total_pagado, 0), 0) > 0')
            ->whereNotIn('vc.id_ventas_cuotas', function ($q) use ($idCuentaPagar) {
                $q->select('id_vinculado_cxc')
                  ->from('cuentas_pagar')
                  ->whereNotNull('id_vinculado_cxc')
                  ->where('id_cuenta_pagar', '!=', $idCuentaPagar);
            })
            ->orderBy('vc.venta_cuota_fecha')
            ->get()
            ->toArray();

        $this->idVincularCuentaPagar = $idCuentaPagar;
        $this->cuentaVincular        = $cuenta;
        $this->idCuotaSeleccionada   = null;

        $this->dispatch('abrirModalVincular');
    }

    public function cerrarModalVincular(): void
    {
        $this->idVincularCuentaPagar = null;
        $this->cuentaVincular        = null;
        $this->cuotasDisponibles     = [];
        $this->idCuotaSeleccionada   = null;
        $this->dispatch('cerrarModalVincular');
    }

    public function vincularCuota(): void
    {
        if (!$this->idVincularCuentaPagar || !$this->idCuotaSeleccionada) return;

        DB::table('cuentas_pagar')
            ->where('id_cuenta_pagar', $this->idVincularCuentaPagar)
            ->update(['id_vinculado_cxc' => $this->idCuotaSeleccionada, 'updated_at' => now()]);

        $this->cerrarModalVincular();
        session()->flash('success', 'Cuenta vinculada. Los pagos se sincronizarán automáticamente con Cuentas por Cobrar.');
    }

    public function desvincular(int $idCuentaPagar): void
    {
        abort_if(!auth()->user()->can('cuentas_pagar.actualizar'), 403);

        DB::table('cuentas_pagar')
            ->where('id_cuenta_pagar', $idCuentaPagar)
            ->update(['id_vinculado_cxc' => null, 'updated_at' => now()]);

        session()->flash('success', 'Cuenta desvinculada correctamente.');
    }

    public function confirmarAnular(int $id): void
    {
        abort_if(!auth()->user()->can('cuentas_pagar.cambiar_estado'), 403);
        $this->idAnular = $id;
        $this->dispatch('abrirModalAnular');
    }

    public function anular(): void
    {
        abort_if(!auth()->user()->can('cuentas_pagar.cambiar_estado'), 403);

        DB::beginTransaction();
        try {
            DB::table('cuentas_pagar')->where('id_cuenta_pagar', $this->idAnular)->update([
                'cp_estado'  => 0,
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            $this->idAnular = null;
            $this->dispatch('cerrarModalAnular');
            session()->flash('success', 'Cuenta anulada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al anular la cuenta.');
        }
    }

    public function exportarPdf(): void
    {
        if (!auth()->user()->can('cuentas_pagar.exportar')) {
            session()->flash('error', 'No tienes permiso para exportar.');
            return;
        }
        $url = route('cxp.export_pdf') . '?' . http_build_query([
            'empresa'   => $this->empresaSeleccionada,
            'sucursal'  => $this->sucursalSeleccionada,
            'proveedor' => $this->filtroProveedor,
            'desde'     => $this->filtroDesde,
            'hasta'     => $this->filtroHasta,
            'vinculada' => $this->filtroVinculada,
            'estado'    => $this->filtroEstado,
        ]);
        $this->dispatch('abrirEnlaces', url: $url);
    }

    public function exportarExcel(): void
    {
        if (!auth()->user()->can('cuentas_pagar.exportar')) {
            session()->flash('error', 'No tienes permiso para exportar.');
            return;
        }
        $url = route('cxp.export_excel') . '?' . http_build_query([
            'empresa'   => $this->empresaSeleccionada,
            'sucursal'  => $this->sucursalSeleccionada,
            'proveedor' => $this->filtroProveedor,
            'desde'     => $this->filtroDesde,
            'hasta'     => $this->filtroHasta,
            'vinculada' => $this->filtroVinculada,
            'estado'    => $this->filtroEstado,
        ]);
        $this->dispatch('abrirEnlaces', url: $url);
    }

    public function render()
    {
        $esSuperAdmin = $this->esSuperAdmin();
        $esAdmin      = $this->esAdmin();
        $hoy          = now()->toDateString();

        $empresas = ($esSuperAdmin || $esAdmin)
            ? DB::table('empresa')->where('empresa_estado', '!=', '0')->orderBy('empresa_nombrecomercial')->get()
            : collect();

        $tiposPago = DB::table('tipo_pago')->where('tipo_pago_estado', 1)->orderBy('tipo_pago_nombre')->get();

        $idEmpresa  = $this->resolverIdEmpresa();
        $idSucursal = $this->resolverIdSucursal();

        $columnasPermitidas = ['cp_fecha_vencimiento', 'cp_fecha_emision', 'cp_monto_total', 'cp_saldo', 'proveedores_nombre'];
        $columna   = in_array($this->ordenColumna, $columnasPermitidas) ? $this->ordenColumna : 'cp_fecha_vencimiento';
        $direccion = $this->ordenDireccion === 'desc' ? 'desc' : 'asc';

        $query = DB::table('cuentas_pagar as cp')
            ->select(
                'cp.*', 'p.proveedores_nombre', 'p.proveedores_numero_documento', 'u.nombre_users',
                DB::raw("CASE WHEN ev.id_empresa IS NOT NULL AND eo.id_grupo IS NOT NULL AND ev.id_grupo = eo.id_grupo THEN 1 ELSE 0 END as es_vinculada"),
                'vc_lnk.venta_cuota_numero as cxc_cuota_num',
                'v_lnk.venta_serie as cxc_serie',
                'v_lnk.venta_correlativo as cxc_correlativo'
            )
            ->join('proveedores as p', 'p.id_proveedores', '=', 'cp.id_proveedores')
            ->join('users as u', 'u.id_users', '=', 'cp.id_users_registro')
            ->leftJoin('empresa as ev', 'ev.empresa_ruc', '=', 'p.proveedores_numero_documento')
            ->leftJoin('empresa as eo', 'eo.id_empresa', '=', 'cp.id_empresa')
            ->leftJoin('ventas_cuotas as vc_lnk', 'vc_lnk.id_ventas_cuotas', '=', 'cp.id_vinculado_cxc')
            ->leftJoin('ventas as v_lnk', 'v_lnk.id_venta', '=', 'vc_lnk.id_venta')
            ->whereNull('cp.deleted_at');

        if ($idSucursal > 0) {
            $query->where('cp.id_sucursal', $idSucursal);
        } elseif ($idEmpresa) {
            $query->where('cp.id_empresa', $idEmpresa);
        }

        if ($this->filtroProveedor !== '') {
            $like = '%' . $this->filtroProveedor . '%';
            $query->where(function ($q) use ($like) {
                $q->where('p.proveedores_nombre', 'like', $like)
                  ->orWhere('p.proveedores_numero_documento', 'like', $like);
            });
        }

        if ($this->filtroEstado !== '') {
            $query->where('cp.cp_estado', (int) $this->filtroEstado);
        }

        if ($this->filtroDesde) {
            $query->whereDate('cp.cp_fecha_vencimiento', '>=', $this->filtroDesde);
        }
        if ($this->filtroHasta) {
            $query->whereDate('cp.cp_fecha_vencimiento', '<=', $this->filtroHasta);
        }

        if ($this->filtroVinculada === '1') {
            $query->whereRaw('ev.id_empresa IS NOT NULL AND eo.id_grupo IS NOT NULL AND ev.id_grupo = eo.id_grupo');
        } elseif ($this->filtroVinculada === '0') {
            $query->whereRaw('NOT (ev.id_empresa IS NOT NULL AND eo.id_grupo IS NOT NULL AND ev.id_grupo = eo.id_grupo)');
        }

        $cuentas = $query->orderBy($columna, $direccion)->paginate($this->porPagina);

        $baseIndicadores = DB::table('cuentas_pagar as cp')
            ->whereNull('cp.deleted_at')
            ->where('cp.cp_estado', '!=', 0)
            ->where('cp.cp_estado', '!=', 3);

        if ($idSucursal > 0) {
            $baseIndicadores->where('cp.id_sucursal', $idSucursal);
        } elseif ($idEmpresa) {
            $baseIndicadores->where('cp.id_empresa', $idEmpresa);
        }

        $indicadores = [
            'total_pendiente' => (clone $baseIndicadores)->sum('cp_saldo'),
            'vencido'         => (clone $baseIndicadores)->whereDate('cp_fecha_vencimiento', '<', $hoy)->sum('cp_saldo'),
            'por_vencer_7d'   => (clone $baseIndicadores)->whereDate('cp_fecha_vencimiento', '>=', $hoy)
                                    ->whereDate('cp_fecha_vencimiento', '<=', now()->addDays(7)->toDateString())->sum('cp_saldo'),
        ];

        return view('livewire.cxp.cuentas-pagar', compact(
            'esSuperAdmin', 'esAdmin',
            'empresas', 'tiposPago',
            'cuentas', 'indicadores'
        ));
    }
}
