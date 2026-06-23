<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        DB::table('menus')->truncate();
        DB::table('menus')->insert([
            ['id_menu' => 1,  'menu_nombre' => 'Configuración',      'menu_controlador' => 'configuracion',  'menu_icono' => 'bx bx-cog',           'menu_orden' => '1', 'menu_mostrar' => 1, 'menu_estado' => 1],
            ['id_menu' => 13, 'menu_nombre' => 'Gestión de Negocio', 'menu_controlador' => 'Gestion',        'menu_icono' => 'fa fa-chart-simple',   'menu_orden' => '2', 'menu_mostrar' => 1, 'menu_estado' => 1],
            ['id_menu' => 14, 'menu_nombre' => 'Logística',          'menu_controlador' => 'logistica',      'menu_icono' => 'fa fa-chart-line',     'menu_orden' => '3', 'menu_mostrar' => 1, 'menu_estado' => 1],
            ['id_menu' => 15, 'menu_nombre' => 'Gestión de Ventas',  'menu_controlador' => 'Gestionventas',  'menu_icono' => 'bx bx-cart',           'menu_orden' => '4', 'menu_mostrar' => 1, 'menu_estado' => 1],
            ['id_menu' => 16, 'menu_nombre' => 'Facturación',        'menu_controlador' => 'facturacion',    'menu_icono' => 'bx bx-coin-stack',     'menu_orden' => '5', 'menu_mostrar' => 1, 'menu_estado' => 1],
            ['id_menu' => 17, 'menu_nombre' => 'Reporte',            'menu_controlador' => 'reporte',        'menu_icono' => 'bx bx-pie-chart',      'menu_orden' => '6', 'menu_mostrar' => 1, 'menu_estado' => 1],
            ['id_menu' => 18, 'menu_nombre' => 'Caja y Tesorería',  'menu_controlador' => 'caja_tesoreria', 'menu_icono' => 'fa fa-cash-register',  'menu_orden' => '7', 'menu_mostrar' => 1, 'menu_estado' => 1],
            ['id_menu' => 19, 'menu_nombre' => 'Cuentas',           'menu_controlador' => 'cxc',            'menu_icono' => 'fa fa-file-invoice-dollar', 'menu_orden' => '8', 'menu_mostrar' => 1, 'menu_estado' => 1],
        ]);

        DB::table('submenu')->truncate();
        DB::table('submenu')->insert([
            ['id_submenu' =>  1, 'id_menu' =>  1, 'submenu_nombre' => 'Menus',                     'submenu_funcion' => 'menus',                'submenu_mostrar' => 1, 'submenu_orden' => 2, 'submenu_estado' => 1],
            ['id_submenu' =>  4, 'id_menu' =>  1, 'submenu_nombre' => 'Usuarios',                  'submenu_funcion' => 'usuarios',             'submenu_mostrar' => 1, 'submenu_orden' => 6, 'submenu_estado' => 1],
            ['id_submenu' =>  5, 'id_menu' =>  1, 'submenu_nombre' => 'Roles',                     'submenu_funcion' => 'roles',                'submenu_mostrar' => 1, 'submenu_orden' => 5, 'submenu_estado' => 1],
            ['id_submenu' =>  6, 'id_menu' =>  1, 'submenu_nombre' => 'Permisos',                  'submenu_funcion' => 'permisos',             'submenu_mostrar' => 0, 'submenu_orden' => 4, 'submenu_estado' => 1],
            ['id_submenu' =>  7, 'id_menu' =>  1, 'submenu_nombre' => 'Submenús',                  'submenu_funcion' => 'submenu',              'submenu_mostrar' => 0, 'submenu_orden' => 4, 'submenu_estado' => 1],
            ['id_submenu' =>  8, 'id_menu' =>  1, 'submenu_nombre' => 'Opciones',                  'submenu_funcion' => 'opciones',             'submenu_mostrar' => 0, 'submenu_orden' => 6, 'submenu_estado' => 1],
            ['id_submenu' => 48, 'id_menu' =>  1, 'submenu_nombre' => 'Iconos',                    'submenu_funcion' => 'iconos',               'submenu_mostrar' => 1, 'submenu_orden' => 1, 'submenu_estado' => 1],
            ['id_submenu' => 49, 'id_menu' => 13, 'submenu_nombre' => 'Proveedores',               'submenu_funcion' => 'proveedores',          'submenu_mostrar' => 1, 'submenu_orden' => 1, 'submenu_estado' => 1],
            ['id_submenu' => 50, 'id_menu' => 13, 'submenu_nombre' => 'Familias',                  'submenu_funcion' => 'familias',             'submenu_mostrar' => 1, 'submenu_orden' => 2, 'submenu_estado' => 1],
            ['id_submenu' => 51, 'id_menu' => 13, 'submenu_nombre' => 'Categorías',                'submenu_funcion' => 'categoria',            'submenu_mostrar' => 0, 'submenu_orden' => 3, 'submenu_estado' => 1],
            ['id_submenu' => 52, 'id_menu' => 14, 'submenu_nombre' => 'Gestionar Productos',       'submenu_funcion' => 'gestionar_productos',  'submenu_mostrar' => 1, 'submenu_orden' => 1, 'submenu_estado' => 1],
            ['id_submenu' => 53, 'id_menu' => 14, 'submenu_nombre' => 'Compras',                   'submenu_funcion' => 'compras',              'submenu_mostrar' => 1, 'submenu_orden' => 1, 'submenu_estado' => 1],
            ['id_submenu' => 54, 'id_menu' => 14, 'submenu_nombre' => 'Detalle de Orden de Compra','submenu_funcion' => 'ordenCompraDetalle',   'submenu_mostrar' => 0, 'submenu_orden' => 3, 'submenu_estado' => 1],
            ['id_submenu' => 55, 'id_menu' => 15, 'submenu_nombre' => 'Movimientos',               'submenu_funcion' => 'movimientos',          'submenu_mostrar' => 1, 'submenu_orden' => 1, 'submenu_estado' => 1],
            ['id_submenu' => 56, 'id_menu' => 15, 'submenu_nombre' => 'Realizar ventas',           'submenu_funcion' => 'realizar_ventas',      'submenu_mostrar' => 1, 'submenu_orden' => 3, 'submenu_estado' => 1],
            ['id_submenu' => 57, 'id_menu' => 15, 'submenu_nombre' => 'Venta Detalle',             'submenu_funcion' => 'venta_detalle',        'submenu_mostrar' => 0, 'submenu_orden' => 3, 'submenu_estado' => 1],
            ['id_submenu' => 58, 'id_menu' => 16, 'submenu_nombre' => 'Pendientes de Declarar',    'submenu_funcion' => 'pendiente_declarar',   'submenu_mostrar' => 1, 'submenu_orden' => 1, 'submenu_estado' => 1],
            ['id_submenu' => 59, 'id_menu' => 16, 'submenu_nombre' => 'Historial de Envíos',       'submenu_funcion' => 'historial_envios',     'submenu_mostrar' => 1, 'submenu_orden' => 2, 'submenu_estado' => 1],
            ['id_submenu' => 60, 'id_menu' => 16, 'submenu_nombre' => 'Detalle de Resumen',        'submenu_funcion' => 'detalle_resumen',      'submenu_mostrar' => 0, 'submenu_orden' => 3, 'submenu_estado' => 1],
            ['id_submenu' => 61, 'id_menu' => 16, 'submenu_nombre' => 'Generar Nota',              'submenu_funcion' => 'generar_nota',         'submenu_mostrar' => 0, 'submenu_orden' => 4, 'submenu_estado' => 1],
            ['id_submenu' => 64, 'id_menu' => 17, 'submenu_nombre' => 'Reporte de ventas',         'submenu_funcion' => 'reporte_de_ventas',    'submenu_mostrar' => 1, 'submenu_orden' => 0, 'submenu_estado' => 1],
            ['id_submenu' => 66, 'id_menu' => 15, 'submenu_nombre' => 'Proformas',                 'submenu_funcion' => 'proformas',            'submenu_mostrar' => 1, 'submenu_orden' => 2,  'submenu_estado' => 1],
            ['id_submenu' => 67, 'id_menu' => 15, 'submenu_nombre' => 'Detalle Proforma',         'submenu_funcion' => 'gestion_proforma',     'submenu_mostrar' => 0, 'submenu_orden' => 3,  'submenu_estado' => 1],
            ['id_submenu' => 68, 'id_menu' => 15, 'submenu_nombre' => 'Registro de Pagos',        'submenu_funcion' => 'registro_pagos',       'submenu_mostrar' => 1, 'submenu_orden' => 4,  'submenu_estado' => 1],
            ['id_submenu' => 69, 'id_menu' => 15, 'submenu_nombre' => 'Notas de venta',            'submenu_funcion' => 'notas_de_venta',       'submenu_mostrar' => 1, 'submenu_orden' => 5, 'submenu_estado' => 1],
            ['id_submenu' => 70, 'id_menu' => 15, 'submenu_nombre' => 'Clientes',                  'submenu_funcion' => 'clientes',             'submenu_mostrar' => 1, 'submenu_orden' => 1, 'submenu_estado' => 1],
            ['id_submenu' => 71, 'id_menu' =>  1, 'submenu_nombre' => 'Cajas',                     'submenu_funcion' => 'cajas',                'submenu_mostrar' => 0, 'submenu_orden' => 7, 'submenu_estado' => 1],
            ['id_submenu' => 72, 'id_menu' => 17, 'submenu_nombre' => 'Control de Pagos de Cuotas','submenu_funcion' => 'control_pagos_de_cuotas','submenu_mostrar'=> 1, 'submenu_orden' => 2, 'submenu_estado' => 1],
            ['id_submenu' => 73, 'id_menu' => 17, 'submenu_nombre' => 'Ventas por Vendedor',       'submenu_funcion' => 'ventas_por_vendedor',  'submenu_mostrar' => 1, 'submenu_orden' => 3, 'submenu_estado' => 1],
            ['id_submenu' => 74, 'id_menu' => 17, 'submenu_nombre' => 'Ventas por Cliente',        'submenu_funcion' => 'ventas_por_cliente',   'submenu_mostrar' => 1, 'submenu_orden' => 4, 'submenu_estado' => 1],
            ['id_submenu' => 75, 'id_menu' => 17, 'submenu_nombre' => 'Productos más vendidos',    'submenu_funcion' => 'productos_mas_vendidos','submenu_mostrar'=> 1, 'submenu_orden' => 5, 'submenu_estado' => 1],
            ['id_submenu' => 76, 'id_menu' =>  1, 'submenu_nombre' => 'Empresas',                  'submenu_funcion' => 'empresas',             'submenu_mostrar' => 1, 'submenu_orden' => 4, 'submenu_estado' => 1],
            ['id_submenu' => 77, 'id_menu' =>  1, 'submenu_nombre' => 'Planes',                    'submenu_funcion' => 'plan',                 'submenu_mostrar' => 1, 'submenu_orden' => 3, 'submenu_estado' => 1],
            ['id_submenu' => 78, 'id_menu' =>  1, 'submenu_nombre' => 'Sucursal',                  'submenu_funcion' => 'sucursal',             'submenu_mostrar' => 0, 'submenu_orden' => 8,  'submenu_estado' => 1],
            ['id_submenu' => 79, 'id_menu' => 14, 'submenu_nombre' => 'Kardex Valorizado',         'submenu_funcion' => 'kardex_valorizado',     'submenu_mostrar' => 1, 'submenu_orden' => 10, 'submenu_estado' => 1],
            ['id_submenu' => 80, 'id_menu' => 14, 'submenu_nombre' => 'Stock por Establecimiento', 'submenu_funcion' => 'stock_establecimiento', 'submenu_mostrar' => 1, 'submenu_orden' => 4,  'submenu_estado' => 1],
            ['id_submenu' => 81, 'id_menu' => 14, 'submenu_nombre' => 'Transferencias de Stock',   'submenu_funcion' => 'transferencias_stock',  'submenu_mostrar' => 1, 'submenu_orden' => 5,  'submenu_estado' => 1],
            ['id_submenu' => 82, 'id_menu' => 14, 'submenu_nombre' => 'Mercadería en Tránsito',    'submenu_funcion' => 'mercaderia_transito',   'submenu_mostrar' => 1, 'submenu_orden' => 6,  'submenu_estado' => 1],
            ['id_submenu' => 83, 'id_menu' => 18, 'submenu_nombre' => 'Movimientos de Caja',       'submenu_funcion' => 'movimientos_caja',      'submenu_mostrar' => 1, 'submenu_orden' => 1,  'submenu_estado' => 1],
            ['id_submenu' => 84, 'id_menu' => 18, 'submenu_nombre' => 'Arqueo de Caja',            'submenu_funcion' => 'arqueo_caja',           'submenu_mostrar' => 1, 'submenu_orden' => 2,  'submenu_estado' => 1],
            ['id_submenu' => 85, 'id_menu' => 19, 'submenu_nombre' => 'Cuentas por Cobrar',        'submenu_funcion' => 'cuentas_cobrar',        'submenu_mostrar' => 1, 'submenu_orden' => 1,  'submenu_estado' => 1],
            ['id_submenu' => 86, 'id_menu' => 19, 'submenu_nombre' => 'Cuentas por Pagar',         'submenu_funcion' => 'cuentas_pagar',         'submenu_mostrar' => 1, 'submenu_orden' => 2,  'submenu_estado' => 1],
            ['id_submenu' => 87, 'id_menu' => 17, 'submenu_nombre' => 'Reporte de Caja',           'submenu_funcion' => 'reporte_caja',          'submenu_mostrar' => 1, 'submenu_orden' => 6,  'submenu_estado' => 1],
            ['id_submenu' => 88, 'id_menu' => 17, 'submenu_nombre' => 'Reporte CxC',               'submenu_funcion' => 'reporte_cxc',           'submenu_mostrar' => 1, 'submenu_orden' => 7,  'submenu_estado' => 1],
            ['id_submenu' => 89, 'id_menu' => 17, 'submenu_nombre' => 'Reporte CxP',               'submenu_funcion' => 'reporte_cxp',           'submenu_mostrar' => 1, 'submenu_orden' => 8,  'submenu_estado' => 1],
            ['id_submenu' => 90, 'id_menu' => 17, 'submenu_nombre' => 'Reporte de Stock',          'submenu_funcion' => 'reporte_stock',         'submenu_mostrar' => 1, 'submenu_orden' => 9,  'submenu_estado' => 1],
            ['id_submenu' => 91, 'id_menu' => 17, 'submenu_nombre' => 'Reporte de Compras',        'submenu_funcion' => 'reporte_compras',       'submenu_mostrar' => 1, 'submenu_orden' => 10, 'submenu_estado' => 1],
            ['id_submenu' => 92, 'id_menu' => 17, 'submenu_nombre' => 'Reporte de Transferencias', 'submenu_funcion' => 'reporte_transferencias','submenu_mostrar' => 1, 'submenu_orden' => 11, 'submenu_estado' => 1],
            ['id_submenu' => 93, 'id_menu' => 17, 'submenu_nombre' => 'Corte Mensual',             'submenu_funcion' => 'reporte_corte_mensual', 'submenu_mostrar' => 1, 'submenu_orden' => 12, 'submenu_estado' => 1],
        ]);

        DB::table('opciones')->truncate();
        DB::table('opciones')->insert([
            ['id_opciones' =>  2, 'id_submenu' =>  1, 'opciones_funcion' => 'gestion_menus',                   'opciones_nombre' => 'Gestión De Menús',                   'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' =>  3, 'id_submenu' =>  7, 'opciones_funcion' => 'gestion_submenus',                'opciones_nombre' => 'Gestión De Submenús',                 'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' =>  4, 'id_submenu' =>  8, 'opciones_funcion' => 'gestion_opciones',                'opciones_nombre' => 'Gestión De Opciones',                 'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' =>  5, 'id_submenu' =>  4, 'opciones_funcion' => 'gestion_usuarios',                'opciones_nombre' => 'Gestión De Usuarios',                 'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' =>  6, 'id_submenu' =>  5, 'opciones_funcion' => 'gestion_roles',                   'opciones_nombre' => 'Gestión De Roles',                    'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' =>  7, 'id_submenu' =>  6, 'opciones_funcion' => 'gestion_permisos',                'opciones_nombre' => 'Gestión De Permisos',                 'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 65, 'id_submenu' => 48, 'opciones_funcion' => 'gestion_iconos',                  'opciones_nombre' => 'Gestión De Iconos',                   'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 66, 'id_submenu' => 49, 'opciones_funcion' => 'gestion_proveedores',             'opciones_nombre' => 'Gestión De Proveedores',              'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 68, 'id_submenu' => 50, 'opciones_funcion' => 'gestion_familias',                'opciones_nombre' => 'Gestión De Familias',                 'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 69, 'id_submenu' => 51, 'opciones_funcion' => 'gestion_categorias',              'opciones_nombre' => 'Gestión De Categorías',               'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 71, 'id_submenu' => 52, 'opciones_funcion' => 'gestion_productos',               'opciones_nombre' => 'Gestión De Productos',                'opciones_orden' => 2, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 72, 'id_submenu' => 53, 'opciones_funcion' => 'registro_compras',                'opciones_nombre' => 'Registro De Compras',                 'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 73, 'id_submenu' => 53, 'opciones_funcion' => 'historial_compras',               'opciones_nombre' => 'Historial De Compras',                'opciones_orden' => 2, 'opciones_mostrar' => 0, 'opciones_estado' => 1],
            ['id_opciones' => 74, 'id_submenu' => 54, 'opciones_funcion' => 'gestion_ordenes_compra',          'opciones_nombre' => 'Gestión De Órdenes De Compra',        'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 75, 'id_submenu' => 55, 'opciones_funcion' => 'movimientos_productos',           'opciones_nombre' => 'Movimientos De Productos',            'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 76, 'id_submenu' => 56, 'opciones_funcion' => 'gestion_ventas',                  'opciones_nombre' => 'Gestión De Ventas',                   'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 77, 'id_submenu' => 57, 'opciones_funcion' => 'detalle_venta',                   'opciones_nombre' => 'Detalle De Venta',                    'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 78, 'id_submenu' => 58, 'opciones_funcion' => 'pendientes_declarar',             'opciones_nombre' => 'Pendientes De Declarar',              'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 79, 'id_submenu' => 58, 'opciones_funcion' => 'resumen_diario',                  'opciones_nombre' => 'Resumen Diario',                      'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 80, 'id_submenu' => 59, 'opciones_funcion' => 'historial_ventas_sunat',          'opciones_nombre' => 'Historial De Ventas SUNAT',           'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 81, 'id_submenu' => 59, 'opciones_funcion' => 'historial_resumenes_diarios',     'opciones_nombre' => 'Historial De Resúmenes Diarios',      'opciones_orden' => 2, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 82, 'id_submenu' => 59, 'opciones_funcion' => 'historial_bajas_facturas',        'opciones_nombre' => 'Historial De Bajas De Facturas',      'opciones_orden' => 3, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 83, 'id_submenu' => 60, 'opciones_funcion' => 'detalle_resumen',                 'opciones_nombre' => 'Detalle De Resumen',                  'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 86, 'id_submenu' => 64, 'opciones_funcion' => 'reporte_ventas',                  'opciones_nombre' => 'Reporte De Ventas',                   'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 87, 'id_submenu' => 61, 'opciones_funcion' => 'generar_nota',                    'opciones_nombre' => 'Generar Nota',                        'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 89, 'id_submenu' => 66, 'opciones_funcion' => 'gestion_proformas',               'opciones_nombre' => 'Gestión De Proformas',                'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 90, 'id_submenu' => 68, 'opciones_funcion' => 'registro_pagos',                  'opciones_nombre' => 'Registro De Pagos',                   'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 91, 'id_submenu' => 69, 'opciones_funcion' => 'historial_notas_venta',           'opciones_nombre' => 'Historial De Notas De Venta',         'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 92, 'id_submenu' => 70, 'opciones_funcion' => 'gestion_de_clientes',             'opciones_nombre' => 'Gestión De Clientes',                 'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 93, 'id_submenu' => 71, 'opciones_funcion' => 'gestion_de_cajas',                'opciones_nombre' => 'Gestión De Cajas',                    'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 94, 'id_submenu' => 72, 'opciones_funcion' => 'opcion_control_pagos_de_cuotas',  'opciones_nombre' => 'Pagos De Cuotas',                     'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 95, 'id_submenu' => 73, 'opciones_funcion' => 'opciones_ventas_por_vendedor',    'opciones_nombre' => 'Ventas Por Vendedor',                 'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 96, 'id_submenu' => 74, 'opciones_funcion' => 'opciones_ventas_por_cliente',     'opciones_nombre' => 'Ventas Por Cliente',                  'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 97, 'id_submenu' => 75, 'opciones_funcion' => 'opciones_productos_mas_vendidos', 'opciones_nombre' => 'Productos Más Vendidos',              'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 98, 'id_submenu' => 76, 'opciones_funcion' => 'opcion_gestion_empresas',         'opciones_nombre' => 'Gestión De Empresas',                 'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 99, 'id_submenu' => 77, 'opciones_funcion' => 'gestion_planes',                  'opciones_nombre' => 'Gestión De Planes',                   'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 100,'id_submenu' => 78, 'opciones_funcion' => 'sucursal_opcion',                 'opciones_nombre' => 'Gestión De Sucursales',               'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 101,'id_submenu' => 79, 'opciones_funcion' => 'kardex_valorizado',               'opciones_nombre' => 'Kardex Valorizado',                    'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 102,'id_submenu' => 80, 'opciones_funcion' => 'stock_establecimiento',           'opciones_nombre' => 'Stock Por Establecimiento',            'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 103,'id_submenu' => 81, 'opciones_funcion' => 'transferencias_stock',            'opciones_nombre' => 'Transferencias De Stock',              'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 104,'id_submenu' => 82, 'opciones_funcion' => 'mercaderia_transito',             'opciones_nombre' => 'Mercadería En Tránsito',               'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 105,'id_submenu' => 83, 'opciones_funcion' => 'movimientos_caja',                'opciones_nombre' => 'Movimientos De Caja',                  'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 106,'id_submenu' => 84, 'opciones_funcion' => 'arqueo_caja',                     'opciones_nombre' => 'Arqueo De Caja',                       'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 107,'id_submenu' => 85, 'opciones_funcion' => 'cuentas_cobrar',                  'opciones_nombre' => 'Cuentas Por Cobrar',                   'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 108,'id_submenu' => 86, 'opciones_funcion' => 'cuentas_pagar',                   'opciones_nombre' => 'Cuentas Por Pagar',                    'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 109,'id_submenu' => 87, 'opciones_funcion' => 'reporte_caja',                    'opciones_nombre' => 'Reporte de Caja',                      'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 110,'id_submenu' => 88, 'opciones_funcion' => 'reporte_cxc',                     'opciones_nombre' => 'Reporte CxC',                          'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 111,'id_submenu' => 89, 'opciones_funcion' => 'reporte_cxp',                     'opciones_nombre' => 'Reporte CxP',                          'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 112,'id_submenu' => 90, 'opciones_funcion' => 'opciones_reporte_stock',          'opciones_nombre' => 'Reporte de Stock',                     'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 113,'id_submenu' => 91, 'opciones_funcion' => 'opciones_reporte_compras',        'opciones_nombre' => 'Reporte de Compras',                   'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 114,'id_submenu' => 92, 'opciones_funcion' => 'opciones_reporte_transferencias', 'opciones_nombre' => 'Reporte de Transferencias',            'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
            ['id_opciones' => 115,'id_submenu' => 93, 'opciones_funcion' => 'opciones_reporte_corte_mensual',  'opciones_nombre' => 'Corte Mensual',                        'opciones_orden' => 1, 'opciones_mostrar' => 1, 'opciones_estado' => 1],
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
