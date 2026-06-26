<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Ventas extends Model
{
    use HasFactory;
    protected $table      = 'ventas';
    protected $primaryKey = 'id_venta';
    private $logs;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->logs = new Logs();
    }

    public function listar_ventas_facturas($id_empresa)
    {
        try {
            $datos = DB::table('ventas as v')
                ->join('empresa as e', 'e.id_empresa', '=', 'v.id_empresa')
                ->join('clientes as c', 'v.id_clientes', '=', 'c.id_clientes')
                ->join('monedas as mo', 'v.id_moneda', '=', 'mo.id_moneda')
                ->join('users as u', 'v.id_users', '=', 'u.id_users')
                ->where('v.venta_estado_sunat', 0)
                ->where('e.id_empresa', $id_empresa)
                ->where('v.venta_tipo', '01')
                ->limit(50)
                ->get();
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            $datos = [];
        }
        return $datos;
    }

    public function listar_venta_x_id_pdf($id_venta)
    {
        try {
            $datos = DB::table('ventas as v')
                ->join('empresa as e', 'e.id_empresa', '=', 'v.id_empresa')
                ->join('ubigeo as ub', 'ub.id_ubigeo', '=', 'e.id_ubigeo')
                ->join('clientes as c', 'v.id_clientes', '=', 'c.id_clientes')
                ->join('monedas as mo', 'v.id_moneda', '=', 'mo.id_moneda')
                ->leftJoin('users as u', 'v.id_users', '=', 'u.id_users')
                ->join('tipo_documento as ti', 'ti.id_tipo_documento', '=', 'c.id_tipo_documento')
                ->leftJoin('caja_numero as cn', 'cn.id_caja_numero', '=', 'v.id_caja_numero')
                ->leftJoin('tiendas as t', 't.id_tienda', '=', 'cn.id_tienda')
                ->where('v.id_venta', $id_venta)
                ->select('v.*', 'c.*', 'mo.*', 'u.*', 'ti.*', 'e.*', 'ub.*', 't.tienda_nombre')
                ->first();
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            $datos = null;
        }
        return $datos;
    }

    public function listar_venta_detalle_x_id_venta_pdf($id_venta)
    {
        try {
            $idSucursal = DB::table('ventas')->select('id_sucursal')->where('id_venta', $id_venta)->value('id_sucursal');

            $query = DB::table('ventas_detalle as vd')
                ->leftJoin('productos as p', 'vd.id_pro', '=', 'p.id_pro')
                ->leftJoin('medida as m', 'm.id_medida', '=', 'p.id_medida')
                ->where('vd.id_venta', $id_venta)
                // Conservar las líneas de servicio (sin id_pro) además de los productos activos
                ->where(function ($q) {
                    $q->where('p.pro_estado', 1)->orWhereNull('vd.id_pro');
                });

            if ($idSucursal) {
                $query->leftJoin('producto_sucursal as ps', function ($join) use ($idSucursal) {
                        $join->on('ps.id_pro', '=', 'p.id_pro')->where('ps.id_sucursal', $idSucursal);
                    })
                    ->leftJoin('tipo_afectacion as ta', 'ta.id_tipo_afectacion', '=', 'ps.id_tipo_afectacion')
                    ->select('vd.*', 'p.*', 'ta.*', 'ps.id_tipo_afectacion', 'm.medida_nombre', 'm.medida_codigo_unidad');
            } else {
                $query->leftJoin('tipo_afectacion as ta', 'ta.id_tipo_afectacion', '=', 'p.id_tipo_afectacion')
                    ->select('vd.*', 'p.*', 'ta.*', 'm.medida_nombre', 'm.medida_codigo_unidad');
            }

            $datos = $query->get();
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            $datos = [];
        }
        return $datos;
    }

    public function listar_soloventa_x_id($ID)
    {
        try {
            $datos = DB::table('ventas as v')
                ->select(
                    'v.*',
                    'c.cliente_numero', 'c.cliente_razonsocial', 'c.id_tipo_documento',
                    'c.cliente_correo', 'c.cliente_direccion', 'c.cliente_nombre', 'c.cliente_telefono',
                    'mo.simbolo', 'mo.moneda', 'mo.abreviado', 'mo.abrstandar',
                    'u.nombre_users', 'u.id_persona'
                )
                ->join('clientes as c', 'v.id_clientes', '=', 'c.id_clientes')
                ->join('monedas as mo', 'v.id_moneda', '=', 'mo.id_moneda')
                ->join('users as u', 'v.id_users', '=', 'u.id_users')
                ->where('v.id_venta', $ID)
                ->first();
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            $datos = null;
        }
        return $datos;
    }

    public function listar_cuotas($id_venta)
    {
        try {
            $datos = DB::table('ventas_cuotas')->where('id_venta', $id_venta)->get();
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            $datos = [];
        }
        return $datos;
    }

    public function listar_venta_x_id($ID)
    {
        try {
            $datos = DB::table('ventas as v')
                ->select(
                    'v.*',
                    'c.cliente_numero', 'c.cliente_razonsocial', 'c.id_tipo_documento',
                    'c.cliente_correo', 'c.cliente_direccion', 'c.cliente_nombre', 'c.cliente_telefono',
                    'mo.simbolo', 'mo.moneda', 'mo.abreviado', 'mo.abrstandar',
                    'u.nombre_users', 'u.id_persona'
                )
                ->join('clientes as c', 'v.id_clientes', '=', 'c.id_clientes')
                ->join('monedas as mo', 'v.id_moneda', '=', 'mo.id_moneda')
                ->leftJoin('users as u', 'v.id_users', '=', 'u.id_users')
                ->where('v.id_venta', $ID)
                ->first();
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            $datos = null;
        }
        return $datos;
    }

    public function listar_x_filtro_para_ventas($select_cliente, $fecha_inicio, $fecha_cierre, $estado)
    {
        try {
            $datos = DB::table('ventas as v')
                ->join('ventas_cuotas as vc', 'v.id_venta', '=', 'vc.id_venta')
                ->join('monedas as m', 'v.id_moneda', '=', 'm.id_moneda')
                ->join('clientes as c', 'v.id_clientes', '=', 'c.id_clientes')
                ->join('tipo_documento as tp', 'c.id_tipo_documento', '=', 'tp.id_tipo_documento');

            if (!empty($select_cliente)) {
                $datos->where('c.id_clientes', $select_cliente);
            }
            if ($estado == 1) {
                $datos->whereDate('vc.venta_cuota_fecha', '>=', $fecha_inicio)
                    ->whereDate('vc.venta_cuota_fecha', '<=', $fecha_cierre);
            } elseif ($estado == 0) {
                $datos->whereDate('vc.venta_cuota_fecha', '<=', $fecha_cierre);
            }

            $query = $datos->where([['v.venta_tipo', '<>', '07'], ['v.venta_tipo', '<>', '08']])
                ->where('v.venta_tipo', '<>', '20')
                ->get();
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            $query = [];
        }
        return $query;
    }

    public function datos_para_tabla($select_cliente, $fecha_inicio, $fecha_cierre, $estado)
    {
        try {
            $datos = DB::table('pagos as p')
                ->join('monedas as m', 'p.id_moneda', '=', 'm.id_moneda')
                ->join('ventas_cuotas as vc', 'p.id_ventas_cuotas', '=', 'vc.id_ventas_cuotas')
                ->join('ventas as v', 'vc.id_venta', '=', 'v.id_venta')
                ->join('clientes as c', 'v.id_clientes', '=', 'c.id_clientes');

            if (!empty($select_cliente)) {
                $datos->where('c.id_clientes', $select_cliente);
            }
            if ($estado == 1) {
                $datos->whereDate('vc.venta_cuota_fecha', '>=', $fecha_inicio)
                    ->whereDate('vc.venta_cuota_fecha', '<=', $fecha_cierre);
            } elseif ($estado == 0) {
                $datos->whereDate('vc.venta_cuota_fecha', '<=', $fecha_cierre);
            }

            $query = $datos->where([['v.venta_tipo', '<>', '07'], ['v.venta_tipo', '<>', '08']])
                ->where('v.venta_tipo', '<>', '20')
                ->get();
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            $query = [];
        }
        return $query;
    }

    public function listar_venta_x_fecha($fecha, $tipo, ?int $idEmpresa = null, $idSucursal = null)
    {
        try {
            $query = DB::table('ventas as v')
                ->select(
                    'v.*',
                    'c.id_tipo_documento', 'c.cliente_numero', 'c.cliente_razonsocial',
                    'c.cliente_nombre', 'c.cliente_direccion', 'c.cliente_telefono', 'c.cliente_correo', 'c.cliente_estado',
                    'mo.id_moneda', 'mo.moneda', 'mo.abreviado', 'mo.abrstandar', 'mo.simbolo', 'mo.activo',
                    'u.id_users', 'u.nombre_users',
                    'td.tipodocumento_codigo', 'td.tipo_documento_identidad', 'td.tipo_documento_identidad_abr', 'td.tipo_documento_estado'
                )
                ->join('clientes as c', 'v.id_clientes', '=', 'c.id_clientes')
                ->join('monedas as mo', 'v.id_moneda', '=', 'mo.id_moneda')
                ->join('users as u', 'v.id_users', '=', 'u.id_users')
                ->join('tipo_documento as td', 'c.id_tipo_documento', '=', 'td.id_tipo_documento')
                ->whereDate('v.venta_fecha', $fecha)
                ->whereNotIn('v.venta_tipo', [$tipo, '20'])
                ->where('v.venta_estado_sunat', 0)
                ->where('v.tipo_documento_modificar', '<>', '01')
                ->where('v.venta_tipo_envio', '<>', 1)
                ->orderBy('v.id_venta', 'ASC')
                ->limit(350);

            if ($idEmpresa) {
                $query->where('v.id_empresa', $idEmpresa);
            }
            if ($idSucursal) {
                $query->where('v.id_sucursal', $idSucursal);
            }

            $datos = $query->get();
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            $datos = [];
        }
        return $datos;
    }

    public function listar_venta_detalle_x_id_venta($id_venta, $id_sucursal = null)
    {
        try {
            if (!$id_sucursal) {
                $id_sucursal = DB::table('ventas')->select('id_sucursal')->where('id_venta', $id_venta)->value('id_sucursal');
            }
            $datos = DB::table('ventas_detalle as vd')
                ->select('vd.*', 'ta.*', 'm.*')
                ->join('productos as p', 'vd.id_pro', '=', 'p.id_pro')
                ->join('producto_sucursal as ps', 'ps.id_pro', '=', 'p.id_pro')
                ->join('tipo_afectacion as ta', 'ta.id_tipo_afectacion', '=', 'ps.id_tipo_afectacion')
                ->join('medida as m', 'm.id_medida', '=', 'p.id_medida')
                ->where('vd.id_venta', $id_venta)
                ->where('ps.id_sucursal', $id_sucursal)
                ->where('p.pro_estado', 1)
                ->get();
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            $datos = [];
        }
        return $datos;
    }

    public function listarVentasPorTipo($tipo, $desde, $hasta, $tipoReturn, $idEmpresaActiva, $idSucursal, $buscarComprobanteRelacionado = null)
    {
        try {
            $ventas = DB::table('ventas as v')
                ->select('v.*', 'c.id_tipo_documento', 'c.cliente_numero', 'c.cliente_razonsocial', 'm.simbolo', 'u.nombre_users')
                ->join('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
                ->join('monedas as m', 'm.id_moneda', '=', 'v.id_moneda')
                ->join('users as u', 'v.id_users', '=', 'u.id_users')
                ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')->whereNull('va.id_venta')
                ->where('v.id_formas_pago', 1)
                ->where('v.id_empresa', $idEmpresaActiva);

            if ($idSucursal) {
                $ventas->where('v.id_sucursal', $idSucursal);
            }
            if ($tipo) {
                $ventas->whereIn('v.venta_tipo', $tipo);
            }
            if ($desde && $hasta) {
                $ventas->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$desde, $hasta]);
            }

            return $tipoReturn == 1 ? $ventas->get() : $ventas->sum('v.venta_total');
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            return $tipoReturn == 1 ? [] : 0;
        }
    }

    public function listarVentasNotasVentas($desde, $hasta, $tipoReturn, $idEmpresaActiva, $idSucursal)
    {
        try {
            $ventas = DB::table('ventas as v')
                ->select('v.*', 'c.id_tipo_documento', 'c.cliente_numero', 'c.cliente_razonsocial', 'm.simbolo', 'u.nombre_users')
                ->join('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
                ->join('monedas as m', 'm.id_moneda', '=', 'v.id_moneda')
                ->join('users as u', 'v.id_users', '=', 'u.id_users')
                ->where('v.anulado_sunat', 0)
                ->where('v.venta_cancelar', 1)
                ->where('v.id_empresa', $idEmpresaActiva)
                ->where('v.venta_tipo', '20');

            if ($idSucursal) {
                $ventas->where('v.id_sucursal', $idSucursal);
            }
            if ($desde && $hasta) {
                $ventas->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$desde, $hasta]);
            }

            return $tipoReturn == 1 ? $ventas->get() : $ventas->sum('v.venta_total');
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            return $tipoReturn == 1 ? collect() : 0;
        }
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_clientes', 'id_clientes');
    }

    public function moneda(): BelongsTo
    {
        return $this->belongsTo(Moneda::class, 'id_moneda', 'id_moneda');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_users', 'id_users');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'id_sucursal', 'id_sucursal');
    }

    public function detalle(): HasMany
    {
        return $this->hasMany(Venta_detalle::class, 'id_venta', 'id_venta');
    }

    public function cuotas(): HasMany
    {
        return $this->hasMany(VentaCuota::class, 'id_venta', 'id_venta');
    }
}
