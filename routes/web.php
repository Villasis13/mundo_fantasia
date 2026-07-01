<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\adminController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\GestionController;
use App\Http\Controllers\LogisticaController;
use App\Http\Controllers\GestionventasController;
use App\Http\Controllers\FacturacionController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\CxCController;
use App\Http\Controllers\CxPController;



/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/



route::get('/',[InicioController::class ,'inicio'])->name('inicio.inicio');

/* ======================== LOGIN - INICIO ========================*/
route::get('login',[LoginController::class ,'login'])->name('login');
route::get('forgot-password',[LoginController::class ,'forgotPassword'])->name('password.request');
route::get('reset-password/{token}',[LoginController::class ,'resetPassword'])->name('password.reset');
route::get('Sign-off',[LoginController::class ,'cerrar_session'])->name('cerrar_session')->middleware('auth');
/* ======================== LOGIN - LOGIN ========================*/

/* ======================== ADMIN - INICIO ========================*/
route::get('admin',[adminController::class ,'inicio'])->name('admin')->middleware('auth');
route::get('admin/perfil',[adminController::class ,'perfil'])->name('admin.perfil')->middleware('auth');
/* ======================== ADMIN - LOGIN ========================*/


/* ======================== CONFIGURACIÓN - INICIO ========================*/
Route::prefix('configuracion')->middleware(['auth', 'verifyUserStatus'])->group(function () {
    route::get('/menus',[ConfiguracionController::class ,'menus'])->name('configuracion.menus')->middleware('role:Superadmin|Desarrollador')->middleware('can:menus.submenu');
    route::get('/submenu/{menu}',[ConfiguracionController::class ,'submenus'])->name('configuracion.submenu')->middleware('role:Superadmin|Desarrollador')->middleware('can:submenu.submenu');
    route::get('/opciones/{menu}',[ConfiguracionController::class ,'opciones'])->name('configuracion.opciones')->middleware('role:Superadmin|Desarrollador')->middleware('can:opciones.submenu');
    route::get('/usuarios',[ConfiguracionController::class ,'usuarios'])->name('configuracion.usuarios')->middleware('can:usuarios.submenu');
    route::get('/roles',[ConfiguracionController::class ,'roles'])->name('configuracion.roles')->middleware('role:Superadmin|Desarrollador')->middleware('can:roles.submenu');
    route::get('/iconos',[ConfiguracionController::class ,'iconos'])->name('configuracion.iconos')->middleware('can:iconos.submenu');
    route::get('/cajas/{sucursal}',[ConfiguracionController::class ,'cajasPorSucursal'])->name('configuracion.cajas.sucursal')->middleware('can:cajas.submenu');
    route::get('/sucursal/{empresa}',[ConfiguracionController::class ,'sucursales'])->name('configuracion.sucursales')->middleware('can:sucursal.submenu');
    route::get('/empresas',[ConfiguracionController::class ,'empresas'])->name('configuracion.empresas')->middleware('can:empresas.submenu');
    route::get('/empresas/{grupo}',[ConfiguracionController::class ,'empresasPorGrupo'])->name('configuracion.empresas.grupo')->middleware('can:empresas.submenu');
    route::get('/plan',[ConfiguracionController::class ,'plan'])->name('configuracion.plan')->middleware('can:plan.submenu');
    route::get('/grupos',[ConfiguracionController::class ,'grupos'])->name('configuracion.grupos')->middleware('can:grupos.submenu');
    route::get('/tiendas/{empresa}',[ConfiguracionController::class ,'tiendas'])->name('configuracion.tiendas')->middleware('can:tiendas.submenu');
    route::get('/almacenes/{tienda}',[ConfiguracionController::class ,'almacenes'])->name('configuracion.almacenes')->middleware('can:tiendas.submenu');
    route::get('/cajas-tienda/{tienda}',[ConfiguracionController::class ,'cajasPorTienda'])->name('configuracion.cajas.tienda')->middleware('can:cajas.submenu');
    Route::post('/empresa-activa', function () {
        $id = (int) request('id_empresa');
        if ($id > 0) {
            session(['empresa_activa_global_id' => $id]);
        } else {
            session()->forget('empresa_activa_global_id');
        }
        return redirect()->back();
    })->name('configuracion.empresa-activa')->middleware('role:superadmin');
});
/* ======================== CONFIGURACIÓN - FINAL ========================*/


/* ======================== GESTIÓN DE NEGOCIO - INICIO ========================*/
Route::prefix('Gestion')->middleware(['auth', 'verifyUserStatus'])->group(function () {
    route::get('/proveedores',[GestionController::class ,'proveedores'])->name('Gestion.proveedores')->middleware('can:proveedores.submenu');
    route::get('/familias',[GestionController::class ,'familias'])->name('Gestion.familias')->middleware('can:familias.submenu');
    route::get('/categorias/{familia}',[GestionController::class,'categorias'])->name('Gestion.categorias')->middleware('can:categoria.submenu');
});
/* ======================== GESTIÓN DE NEGOCIO - FINAL ========================*/

/* ======================== LOGÍSTICA - INICIO ========================*/
Route::prefix('logistica')->middleware(['auth', 'verifyUserStatus'])->group(function () {
    route::get('/gestionar_productos',[LogisticaController::class ,'gestionar_productos'])->name('logistica.gestionar_productos')->middleware('can:gestionar_productos.submenu');
    route::get('/adquisiciones_recientes_excel',[LogisticaController::class ,'adquisicionesRecientesExcel'])->name('logistica.adquisiciones_recientes_excel')->middleware('can:gestionar_productos.submenu');
    route::get('/compras',[LogisticaController::class ,'compras'])->name('logistica.compras')->middleware('can:compras.submenu');
    route::get('/ordenCompraDetalle',[LogisticaController::class ,'ordenCompraDetalle'])->name('logistica.ordenCompraDetalle')->middleware('can:ordenCompraDetalle.submenu');
    route::get('/compras_pdf',[LogisticaController::class ,'compras_pdf'])->name('logistica.compras_pdf')->middleware('can:registro_compras.exportar');
    route::get('/ingreso_compra_pdf',[LogisticaController::class ,'ingreso_compra_pdf'])->name('logistica.ingreso_compra_pdf')->middleware('can:historial_compras.exportar');
    route::get('/stock_establecimiento',[LogisticaController::class ,'stock_establecimiento'])->name('logistica.stock_establecimiento')->middleware('can:stock_establecimiento.submenu');
    route::get('/stock_consolidado',[LogisticaController::class ,'stock_consolidado'])->name('logistica.stock_consolidado')->middleware('can:stock_consolidado.submenu');
    route::get('/transferencias_stock',[LogisticaController::class ,'transferencias_stock'])->name('logistica.transferencias_stock')->middleware('can:transferencias_stock.submenu');
    route::get('/transferencia_pdf',[LogisticaController::class ,'transferencia_pdf'])->name('logistica.transferencia_pdf')->middleware('can:transferencias_stock.listar');
    route::get('/mercaderia_transito',[LogisticaController::class ,'mercaderia_transito'])->name('logistica.mercaderia_transito')->middleware('can:mercaderia_transito.submenu');
    route::get('/mercaderia_transito/pdf',[LogisticaController::class,'mercaderiaTransitoPdf'])->name('logistica.mercaderia_transito_pdf')->middleware('can:mercaderia_transito.exportar');
    route::get('/mercaderia_transito/excel',[LogisticaController::class,'mercaderiaTransitoExcel'])->name('logistica.mercaderia_transito_excel')->middleware('can:mercaderia_transito.exportar');
    route::get('/reporte_compras',[LogisticaController::class ,'reporte_compras'])->name('logistica.reporte_compras')->middleware('can:reporte_compras.submenu');
    route::get('/reporte_compras/pdf',[LogisticaController::class ,'reporte_compras_pdf'])->name('logistica.reporte_compras_pdf')->middleware('can:reporte_compras.exportar');
    route::get('/reporte_compras/excel',[LogisticaController::class ,'reporte_compras_excel'])->name('logistica.reporte_compras_excel')->middleware('can:reporte_compras.exportar');
    Route::get('/kardex_valorizado',[LogisticaController::class ,'kardex_valorizado'])->name('logistica.kardex_valorizado')->middleware('can:kardex_valorizado.submenu');
    Route::get('/kardex_valorizado/pdf',[LogisticaController::class ,'kardex_valorizado_pdf'])->name('logistica.kardex_valorizado_pdf')->middleware('can:kardex_valorizado.exportar');
    Route::get('/kardex_valorizado/excel',[LogisticaController::class ,'kardex_valorizado_excel'])->name('logistica.kardex_valorizado_excel')->middleware('can:kardex_valorizado.exportar');
    Route::get('/gastos',[LogisticaController::class ,'gastos'])->name('logistica.gastos')->middleware('can:gastos.submenu');
    Route::get('/autoconsumo',[LogisticaController::class ,'autoconsumo'])->name('logistica.autoconsumo')->middleware('can:autoconsumo.submenu');
    Route::get('/autoconsumo-pdf',[LogisticaController::class ,'autoconsumo_pdf'])->name('logistica.autoconsumo_pdf')->middleware('can:autoconsumo.submenu');
    Route::get('/autoconsumo-ticket',[LogisticaController::class ,'autoconsumo_ticket'])->name('logistica.autoconsumo_ticket')->middleware('can:autoconsumo.submenu');
    Route::get('/notas_compra',[LogisticaController::class ,'notas_compra'])->name('logistica.notas_compra')->middleware('can:notas_compra.submenu');
    Route::get('/distribucion_flete',[LogisticaController::class ,'distribucionFlete'])->name('logistica.distribucion_flete')->middleware('can:distribucion_flete.submenu');
});
/* ======================== LOGÍSTICA - FINAL ========================*/

/* ======================== GESTIÓN DE VENTAS - INICIO ========================*/
Route::prefix('Gestionventas')->middleware(['auth', 'verifyUserStatus'])->group(function () {
    route::get('/movimientos',[GestionventasController::class ,'movimientos'])->name('Gestionventas.movimientos')->middleware('can:movimientos.submenu');
    route::get('/realizar_ventas',[GestionventasController::class ,'realizar_ventas'])->name('Gestionventas.realizar_ventas')->middleware('can:realizar_ventas.submenu');
    route::get('/ventas_servicios',[GestionventasController::class ,'ventas_servicios'])->name('Gestionventas.ventas_servicios')->middleware('can:ventas_servicios.submenu');
    route::get('/registro_ventas',[GestionventasController::class ,'registro_ventas'])->name('Gestionventas.registro_ventas')->middleware('can:registro_ventas.submenu');
    route::get('/guias_remision',[GestionventasController::class ,'guias_remision'])->name('Gestionventas.guias_remision')->middleware('can:guias_remision.submenu');
    route::get('/generar_guia',[GestionventasController::class ,'generar_guia'])->name('Gestionventas.generar_guia')->middleware('can:guias_remision.submenu');
    route::get('/pendientes_guia',[GestionventasController::class ,'pendientes_guia'])->name('Gestionventas.pendientes_guia')->middleware('can:guias_remision.submenu');
    route::get('/imprimir_guia_pdf',[GestionventasController::class ,'imprimir_guia_pdf'])->name('Gestionventas.imprimir_guia_pdf')->middleware('can:guias_remision.listar');
    route::get('/clientes',[GestionventasController::class ,'clientes'])->name('Gestionventas.clientes')->middleware('can:clientes.submenu');
    route::get('/exportar_clientes_excel',[GestionventasController::class ,'exportarClientesExcel'])->name('Gestionventas.exportar_clientes_excel')->middleware('can:clientes.submenu');
    route::get('/registro_pagos',[GestionventasController::class ,'registro_pagos'])->name('Gestionventas.registro_pagos')->middleware('can:registro_pagos.submenu');
    route::get('/notas_de_venta',[GestionventasController::class ,'notas_de_venta'])->name('Gestionventas.notas_de_venta')->middleware('can:notas_de_venta.submenu');
    route::get('/venta_detalle',[GestionventasController::class ,'venta_detalle'])->name('Gestionventas.venta_detalle')->middleware('can:venta_detalle.submenu');
    route::get('/proformas',[GestionventasController::class ,'proformas'])->name('Gestionventas.proformas')->middleware('can:proformas.submenu');
    route::get('/imprimir_proforma',[GestionventasController::class ,'imprimir_proforma'])->name('Gestionventas.imprimir_proforma');
    route::get('/imprimir_ticket_pdf',[GestionventasController::class ,'imprimir_ticket_pdf'])->name('Gestionventas.imprimir_ticket_pdf')->middleware('can:detalle_venta.exportar');
    route::get('/imprimir_ticketera_venta',[GestionventasController::class ,'imprimir_ticketera_venta'])->name('Gestionventas.imprimir_ticketera_venta')->middleware('can:detalle_venta.exportar');
    route::get('/imprimir_ticketera_escpos',[GestionventasController::class,'imprimir_ticketera_escpos'])->name('Gestionventas.imprimir_ticketera_escpos');
    route::get('/imprimir_resumen_caja',[GestionventasController::class,'imprimir_resumen_caja'])->name('Gestionventas.imprimir_resumen_caja');
    route::post('/enviarComprobanteporCorreo',[GestionventasController::class ,'enviarComprobanteporCorreo'])->name('Gestionventas.enviarComprobanteporCorreo')->middleware('can:detalle_venta.exportar');
    route::get('/imprimir_pdf_reporte_historial_notas_venta',[GestionventasController::class ,'imprimirPdfReporteHistorialNotasVenta'])->name('Gestionventas.imprimir_pdf_reporte_historial_notas_venta')->middleware('can:historial_notas_venta.exportar');
    route::get('/imprimir_excel_historial_notas_de_venta',[GestionventasController::class ,'imprimirExcelHistorialNotasDeVenta'])->name('Gestionventas.imprimir_excel_historial_notas_de_venta')->middleware('can:historial_notas_venta.exportar');
    route::get('/pedidos',[GestionventasController::class,'pedidos'])->name('Gestionventas.pedidos')->middleware('can:pedidos.submenu');
    route::get('/caja_pedidos',[GestionventasController::class,'caja_pedidos'])->name('Gestionventas.caja_pedidos')->middleware('can:caja_pedidos.submenu');
    route::get('/despacho',[GestionventasController::class,'despacho'])->name('Gestionventas.despacho')->middleware('can:despacho.submenu');
    route::get('/imprimir_ticket_pedido',[GestionventasController::class,'imprimir_ticket_pedido'])->name('Gestionventas.imprimir_ticket_pedido');
    route::get('/transferencia_gratuita',[GestionventasController::class,'transferencia_gratuita'])->name('Gestionventas.transferencia_gratuita')->middleware('can:transferencia_gratuita.submenu');
});
/* ======================== GESTIÓN DE VENTAS - FINAL ========================*/


/* ======================== FACTURACIÓN - INICIO ========================*/
Route::prefix('facturacion')->middleware(['auth', 'verifyUserStatus'])->group(function () {
    route::get('/pendiente_declarar',[FacturacionController::class ,'pendiente_declarar'])->name('facturacion.pendiente_declarar')->middleware('can:pendiente_declarar.submenu');
    route::get('/historial_envios',[FacturacionController::class ,'historial_envios'])->name('facturacion.historial_envios')->middleware('can:historial_envios.submenu');
    route::get('/detalle_resumen/{id}',[FacturacionController::class ,'detalle_resumen'])->name('facturacion.detalle_resumen')->middleware('can:detalle_resumen.submenu');
    route::get('/generar_nota/{id}',[FacturacionController::class ,'generar_nota'])->name('facturacion.generar_nota')->middleware('can:generar_nota.submenu');
    route::post('/crear_xml_enviar_sunat',[FacturacionController::class ,'crear_xml_enviar_sunat'])->name('facturacion.crear_xml_enviar_sunat')->middleware('can:pendientes_declarar.crear');
    route::post('/crear_enviar_resumen_sunat',[FacturacionController::class ,'crear_enviar_resumen_sunat'])->name('facturacion.crear_enviar_resumen_sunat')->middleware('can:resumen_diario.crear');
    route::get('/imprimir_pdf_ventas_declaras',[FacturacionController::class ,'imprimirPdfHistorialEnvios'])->name('facturacion.imprimir_pdf_ventas_declaras')->middleware('can:historial_ventas_sunat.exportar');
    route::get('/imprimir_excel_ventas_declaras',[FacturacionController::class ,'imprimirExcelHistorialEnvios'])->name('facturacion.imprimir_excel_ventas_declaras')->middleware('can:historial_ventas_sunat.exportar');
    route::get('/alertas_sunat',[FacturacionController::class ,'alertas_sunat'])->name('facturacion.alertas_sunat')->middleware('can:alertas_sunat.submenu');
    route::get('/guias_remision',[FacturacionController::class ,'guias_remision'])->name('facturacion.guias_remision')->middleware('can:guias_remision.submenu');
    route::get('/envio_guias_sunat',[FacturacionController::class ,'envio_guias_sunat'])->name('facturacion.envio_guias_sunat')->middleware('can:envio_guias_sunat.submenu');
    route::get('/conciliacion_sunat',[FacturacionController::class ,'conciliacion_sunat'])->name('facturacion.conciliacion_sunat')->middleware('can:conciliacion_sunat.submenu');
    route::get('/conciliacion_sunat/pdf',[FacturacionController::class ,'conciliacion_sunat_pdf'])->name('facturacion.conciliacion_sunat_pdf')->middleware('can:conciliacion_sunat.exportar');
    route::get('/conciliacion_sunat/excel',[FacturacionController::class ,'conciliacion_sunat_excel'])->name('facturacion.conciliacion_sunat_excel')->middleware('can:conciliacion_sunat.exportar');
});
/* ======================== FACTURACIÓN - FINAL ========================*/

/* ======================== CAJA - INICIO ========================*/
Route::prefix('caja_tesoreria')->middleware(['auth', 'verifyUserStatus'])->group(function () {
    route::get('/movimientos_caja', [CajaController::class,  'movimientosCaja'])->name('caja_tesoreria.movimientos_caja')->middleware('can:movimientos_caja.submenu');
    route::get('/arqueo_caja',      [CajaController::class,  'arqueoCaja'])->name('caja_tesoreria.arqueo_caja')->middleware('can:arqueo_caja.submenu');
    route::get('/arqueo_caja/pdf',    [CajaController::class,  'arqueoCajaPdf'])->name('caja_tesoreria.arqueo_caja_pdf')->middleware('can:arqueo_caja.exportar');
    route::get('/arqueo_caja/excel',  [CajaController::class,  'arqueoCajaExcel'])->name('caja_tesoreria.arqueo_caja_excel')->middleware('can:arqueo_caja.exportar');
    route::get('/arqueo_caja/ticket', [CajaController::class,  'arqueoCajaTicket'])->name('caja_tesoreria.arqueo_caja_ticket')->middleware('can:arqueo_caja.exportar');
    route::get('/cuentas_cobrar',   [CxCController::class,   'cuentasCobrar'])->name('caja_tesoreria.cuentas_cobrar')->middleware('can:cuentas_cobrar.submenu');
    route::get('/cuentas_pagar',    [CxPController::class,   'cuentasPagar'])->name('caja_tesoreria.cuentas_pagar')->middleware('can:cuentas_pagar.submenu');
});
/* ======================== CAJA - FINAL ========================*/

/* ======================== CxC / CxP exports (route names used in Livewire) ========================*/
Route::prefix('cxc')->middleware(['auth', 'verifyUserStatus'])->group(function () {
    Route::get('/export/cxc/pdf',  [ReporteController::class, 'reporteCxCPdf'])->name('cxc.export_pdf')->middleware('can:cuentas_cobrar.exportar');
    Route::get('/export/cxc/excel',[ReporteController::class, 'reporteCxCExcel'])->name('cxc.export_excel')->middleware('can:cuentas_cobrar.exportar');
    Route::get('/export/cxp/pdf',  [ReporteController::class, 'reporteCxPPdf'])->name('cxp.export_pdf')->middleware('can:cuentas_pagar.exportar');
    Route::get('/export/cxp/excel',[ReporteController::class, 'reporteCxPExcel'])->name('cxp.export_excel')->middleware('can:cuentas_pagar.exportar');
});

/* ======================== REPORTE - INICIO ========================*/
Route::prefix('reporte')->middleware(['auth', 'verifyUserStatus'])->group(function () {
    route::get('/reporte_de_ventas',[ReporteController::class ,'reporte_de_ventas'])->name('reporte.reporte_de_ventas')->middleware('can:reporte_de_ventas.submenu');
    route::get('/reporte_ventas_tipo_pago',[ReporteController::class ,'reporteVentasTipoPago'])->name('reporte.reporte_ventas_tipo_pago')->middleware('can:reporte_ventas_tipo_pago.submenu');
    route::get('/reporte_utilidad',[ReporteController::class ,'reporteUtilidad'])->name('reporte.reporte_utilidad')->middleware('can:reporte_utilidad.submenu');
    route::get('/reporte_movimientos',[ReporteController::class ,'reporteMovimientos'])->name('reporte.reporte_movimientos')->middleware('can:reporte_movimientos.submenu');
    route::get('/reporte_lista_precios',[ReporteController::class ,'reporteListaPrecios'])->name('reporte.reporte_lista_precios')->middleware('can:reporte_lista_precios.submenu');
    route::get('/reporte_stock_minimo',[ReporteController::class ,'reporteStockMinimo'])->name('reporte.reporte_stock_minimo')->middleware('can:reporte_stock_minimo.submenu');
    route::get('/reporte_series_productos',[ReporteController::class ,'reporteSeriesProductos'])->name('reporte.reporte_series_productos')->middleware('can:reporte_series_productos.submenu');
    route::get('/formato_14_excel',[ReporteController::class ,'formato14Excel'])->name('reporte.formato_14_excel')->middleware('can:reporte_ventas.exportar');
    // Exportaciones de los reportes nuevos (PDF / Excel)
    route::get('/reporte_ventas_tipo_pago/pdf',  [ReporteController::class ,'tipoPagoPdf'])->name('reporte.tipo_pago_pdf')->middleware('can:reporte_ventas_tipo_pago.exportar');
    route::get('/reporte_ventas_tipo_pago/excel',[ReporteController::class ,'tipoPagoExcel'])->name('reporte.tipo_pago_excel')->middleware('can:reporte_ventas_tipo_pago.exportar');
    route::get('/reporte_utilidad/pdf',  [ReporteController::class ,'utilidadPdf'])->name('reporte.utilidad_pdf')->middleware('can:reporte_utilidad.exportar');
    route::get('/reporte_utilidad/excel',[ReporteController::class ,'utilidadExcel'])->name('reporte.utilidad_excel')->middleware('can:reporte_utilidad.exportar');
    route::get('/reporte_movimientos/pdf',  [ReporteController::class ,'movimientosPdf'])->name('reporte.movimientos_pdf')->middleware('can:reporte_movimientos.exportar');
    route::get('/reporte_movimientos/excel',[ReporteController::class ,'movimientosExcel'])->name('reporte.movimientos_excel')->middleware('can:reporte_movimientos.exportar');
    route::get('/reporte_lista_precios/pdf',  [ReporteController::class ,'listaPreciosPdf'])->name('reporte.lista_precios_pdf')->middleware('can:reporte_lista_precios.exportar');
    route::get('/reporte_lista_precios/excel',[ReporteController::class ,'listaPreciosExcel'])->name('reporte.lista_precios_excel')->middleware('can:reporte_lista_precios.exportar');
    route::get('/reporte_stock_minimo/pdf',  [ReporteController::class ,'stockMinimoPdf'])->name('reporte.stock_minimo_pdf')->middleware('can:reporte_stock_minimo.exportar');
    route::get('/reporte_stock_minimo/excel',[ReporteController::class ,'stockMinimoExcel'])->name('reporte.stock_minimo_excel')->middleware('can:reporte_stock_minimo.exportar');
    route::get('/reporte_series_productos/pdf',  [ReporteController::class ,'seriesPdf'])->name('reporte.series_pdf')->middleware('can:reporte_series_productos.exportar');
    route::get('/reporte_series_productos/excel',[ReporteController::class ,'seriesExcel'])->name('reporte.series_excel')->middleware('can:reporte_series_productos.exportar');
    route::get('/control_pagos_de_cuotas',[ReporteController::class ,'control_pagos_de_cuotas'])->name('reporte.control_pagos_de_cuotas')->middleware('can:control_pagos_de_cuotas.submenu');
    route::get('/ventas_por_vendedor',[ReporteController::class ,'ventas_por_vendedor'])->name('reporte.ventas_por_vendedor')->middleware('can:ventas_por_vendedor.submenu');
    route::get('/ventas_por_cliente',[ReporteController::class ,'ventas_por_cliente'])->name('reporte.ventas_por_cliente')->middleware('can:ventas_por_cliente.submenu');
    route::get('/productos_mas_vendidos',[ReporteController::class ,'productos_mas_vendidos'])->name('reporte.productos_mas_vendidos')->middleware('can:productos_mas_vendidos.submenu');

    route::get('/imprimir_pdf_reporte_ventas',[ReporteController::class ,'imprimirPdfReporteVentas'])->name('reporte.imprimir_pdf_reporte_ventas')->middleware('can:reporte_ventas.exportar');
    route::get('/imprimir_excel_reporte_ventas',[ReporteController::class ,'imprimirExcelReporteVentas'])->name('reporte.imprimir_excel_reporte_ventas')->middleware('can:reporte_ventas.exportar');
    Route::get('/imprimir_excel_para_estudio', [ReporteController::class, 'imprimirExcelParaEstudio'])->name('reporte.imprimir_excel_para_estudio')->middleware('can:reporte_ventas.exportar');
    Route::get('/pagos-cuotas/pdf',   [ReporteController::class, 'imprimirPdfPagosCuotas'])->name('reporte.imprimir_pdf_pagos_cuotas')->middleware('can:control_pagos_de_cuotas.exportar');
    Route::get('/pagos-cuotas/excel', [ReporteController::class, 'imprimirExcelPagosCuotas'])->name('reporte.imprimir_excel_pagos_cuotas')->middleware('can:control_pagos_de_cuotas.exportar');
    Route::get('/ventas-vendedor/pdf',   [ReporteController::class, 'imprimirPdfVentasVendedor'])->name('reporte.imprimir_pdf_ventas_vendedor')->middleware('can:reporte_ventas.exportar');
    Route::get('/ventas-vendedor/excel', [ReporteController::class, 'imprimirExcelVentasVendedor'])->name('reporte.imprimir_excel_ventas_vendedor')->middleware('can:reporte_ventas.exportar');
    Route::get('/ventas-cliente/pdf',   [ReporteController::class, 'imprimirPdfVentasCliente'])->name('reporte.imprimir_pdf_ventas_cliente')->middleware('can:reporte_ventas.exportar');
    Route::get('/ventas-cliente/excel', [ReporteController::class, 'imprimirExcelVentasCliente'])->name('reporte.imprimir_excel_ventas_cliente')->middleware('can:reporte_ventas.exportar');
    Route::get('/ventas-productos/pdf',   [ReporteController::class, 'imprimirPdfVentasProductos'])->name('reporte.imprimir_pdf_ventas_productos')->middleware('can:productos_mas_vendidos.exportar');
    Route::get('/ventas-productos/excel', [ReporteController::class, 'imprimirExcelVentasProductos'])->name('reporte.imprimir_excel_ventas_productos')->middleware('can:productos_mas_vendidos.exportar');

    // Nuevos reportes
    Route::get('/reporte_caja',       [ReporteController::class, 'reporteCaja'])->name('reporte.reporte_caja')->middleware('can:reporte_caja.submenu');
    Route::get('/reporte_caja/pdf',   [ReporteController::class, 'reporteCajaPdf'])->name('reporte.reporte_caja_pdf')->middleware('can:reporte_caja.exportar');
    Route::get('/reporte_caja/excel', [ReporteController::class, 'reporteCajaExcel'])->name('reporte.reporte_caja_excel')->middleware('can:reporte_caja.exportar');

    Route::get('/reporte_cxc',        [ReporteController::class, 'reporteCxC'])->name('reporte.reporte_cxc')->middleware('can:reporte_cxc.submenu');
    Route::get('/reporte_cxc/pdf',    [ReporteController::class, 'reporteCxCPdf'])->name('reporte.reporte_cxc_pdf')->middleware('can:reporte_cxc.exportar');
    Route::get('/reporte_cxc/excel',  [ReporteController::class, 'reporteCxCExcel'])->name('reporte.reporte_cxc_excel')->middleware('can:reporte_cxc.exportar');

    Route::get('/reporte_cxp',        [ReporteController::class, 'reporteCxP'])->name('reporte.reporte_cxp')->middleware('can:reporte_cxp.submenu');
    Route::get('/reporte_cxp/pdf',    [ReporteController::class, 'reporteCxPPdf'])->name('reporte.reporte_cxp_pdf')->middleware('can:reporte_cxp.exportar');
    Route::get('/reporte_cxp/excel',  [ReporteController::class, 'reporteCxPExcel'])->name('reporte.reporte_cxp_excel')->middleware('can:reporte_cxp.exportar');

    Route::get('/reporte_stock',        [ReporteController::class, 'reporteStock'])->name('reporte.reporte_stock')->middleware('can:reporte_stock.submenu');
    Route::get('/reporte_stock/pdf',    [ReporteController::class, 'reporteStockPdf'])->name('reporte.reporte_stock_pdf')->middleware('can:reporte_stock.exportar');
    Route::get('/reporte_stock/excel',  [ReporteController::class, 'reporteStockExcel'])->name('reporte.reporte_stock_excel')->middleware('can:reporte_stock.exportar');

    Route::get('/reporte_compras',        [ReporteController::class, 'reporteCompras'])->name('reporte.reporte_compras')->middleware('can:reporte_compras.submenu');
    Route::get('/reporte_compras/pdf',    [ReporteController::class, 'reporteComprasPdf'])->name('reporte.reporte_compras_pdf')->middleware('can:reporte_compras.exportar');
    Route::get('/reporte_compras/excel',  [ReporteController::class, 'reporteComprasExcel'])->name('reporte.reporte_compras_excel')->middleware('can:reporte_compras.exportar');

    Route::get('/reporte_transferencias',        [ReporteController::class, 'reporteTransferencias'])->name('reporte.reporte_transferencias')->middleware('can:reporte_transferencias.submenu');
    Route::get('/reporte_transferencias/pdf',    [ReporteController::class, 'reporteTransferenciasPdf'])->name('reporte.reporte_transferencias_pdf')->middleware('can:reporte_transferencias.exportar');
    Route::get('/reporte_transferencias/excel',  [ReporteController::class, 'reporteTransferenciasExcel'])->name('reporte.reporte_transferencias_excel')->middleware('can:reporte_transferencias.exportar');

    Route::get('/reporte_corte_mensual',        [ReporteController::class, 'reporteCorteMensual'])->name('reporte.reporte_corte_mensual')->middleware('can:reporte_corte_mensual.submenu');
    Route::get('/reporte_corte_mensual/pdf',    [ReporteController::class, 'reporteCorteMensualPdf'])->name('reporte.reporte_corte_mensual_pdf')->middleware('can:reporte_corte_mensual.exportar');
    Route::get('/reporte_corte_mensual/excel',  [ReporteController::class, 'reporteCorteMensualExcel'])->name('reporte.reporte_corte_mensual_excel')->middleware('can:reporte_corte_mensual.exportar');
    Route::get('/ventas-mes/excel', [ReporteController::class, 'excelVentasMes'])->name('reporte.excel_ventas_mes');
});
/* ======================== REPORTE - FINAL ========================*/
