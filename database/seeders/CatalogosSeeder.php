<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogosSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        DB::table('medida')->truncate();
        DB::table('medida')->insert([
            ['id_medida' => 1,  'medida_codigo_unidad' => '4A',      'medida_nombre' => 'BOBINAS',                  'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 2,  'medida_codigo_unidad' => 'BJ',      'medida_nombre' => 'BALDE',                    'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 3,  'medida_codigo_unidad' => 'BLL',     'medida_nombre' => 'BARRILES',                 'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 4,  'medida_codigo_unidad' => 'BG',      'medida_nombre' => 'BOLSA',                    'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 5,  'medida_codigo_unidad' => 'BO',      'medida_nombre' => 'BOTELLAS',                 'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 6,  'medida_codigo_unidad' => 'BX',      'medida_nombre' => 'CAJA',                     'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 7,  'medida_codigo_unidad' => 'CT',      'medida_nombre' => 'CARTONES',                 'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 8,  'medida_codigo_unidad' => 'CMK',     'medida_nombre' => 'CENTIMETRO CUADRADO',      'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 9,  'medida_codigo_unidad' => 'CMQ',     'medida_nombre' => 'CENTIMETRO CUBICO',        'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 10, 'medida_codigo_unidad' => 'CMT',     'medida_nombre' => 'CENTIMETRO LINEAL',        'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 11, 'medida_codigo_unidad' => 'CEN',     'medida_nombre' => 'CIENTO DE UNIDADES',       'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 12, 'medida_codigo_unidad' => 'CY',      'medida_nombre' => 'CILINDRO',                 'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 13, 'medida_codigo_unidad' => 'CJ',      'medida_nombre' => 'CONOS',                    'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 14, 'medida_codigo_unidad' => 'DZN',     'medida_nombre' => 'DOCENA',                   'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 15, 'medida_codigo_unidad' => 'DZP',     'medida_nombre' => 'DOCENA POR 10**6',         'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 16, 'medida_codigo_unidad' => 'BE',      'medida_nombre' => 'FARDO',                    'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 17, 'medida_codigo_unidad' => 'GLI',     'medida_nombre' => 'GALON INGLES (4,545956L)', 'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 18, 'medida_codigo_unidad' => 'GRM',     'medida_nombre' => 'GRAMO',                    'medida_activo' => 0, 'medida_grupo' => 1],
            ['id_medida' => 19, 'medida_codigo_unidad' => 'GRO',     'medida_nombre' => 'GRUESA',                   'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 20, 'medida_codigo_unidad' => 'HLT',     'medida_nombre' => 'HECTOLITRO',               'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 21, 'medida_codigo_unidad' => 'LEF',     'medida_nombre' => 'HOJA',                     'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 22, 'medida_codigo_unidad' => 'SET',     'medida_nombre' => 'JUEGO',                    'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 23, 'medida_codigo_unidad' => 'KGM',     'medida_nombre' => 'KILOGRAMO',                'medida_activo' => 0, 'medida_grupo' => 1],
            ['id_medida' => 24, 'medida_codigo_unidad' => 'KTM',     'medida_nombre' => 'KILOMETRO',                'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 25, 'medida_codigo_unidad' => 'KWH',     'medida_nombre' => 'KILOVATIO HORA',           'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 26, 'medida_codigo_unidad' => 'KT',      'medida_nombre' => 'KIT',                      'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 27, 'medida_codigo_unidad' => 'CA',      'medida_nombre' => 'LATAS',                    'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 28, 'medida_codigo_unidad' => 'LBR',     'medida_nombre' => 'LIBRAS',                   'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 29, 'medida_codigo_unidad' => 'LTR',     'medida_nombre' => 'LITRO',                    'medida_activo' => 0, 'medida_grupo' => 2],
            ['id_medida' => 30, 'medida_codigo_unidad' => 'MWH',     'medida_nombre' => 'MEGAWATT HORA',            'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 31, 'medida_codigo_unidad' => 'MTR',     'medida_nombre' => 'METRO',                    'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 32, 'medida_codigo_unidad' => 'MTK',     'medida_nombre' => 'METRO CUADRADO',           'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 33, 'medida_codigo_unidad' => 'MTQ',     'medida_nombre' => 'METRO CUBICO',             'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 34, 'medida_codigo_unidad' => 'MGM',     'medida_nombre' => 'MILIGRAMOS',               'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 35, 'medida_codigo_unidad' => 'MLT',     'medida_nombre' => 'MILILITRO',                'medida_activo' => 0, 'medida_grupo' => 2],
            ['id_medida' => 36, 'medida_codigo_unidad' => 'MMT',     'medida_nombre' => 'MILIMETRO',                'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 37, 'medida_codigo_unidad' => 'MMK',     'medida_nombre' => 'MILIMETRO CUADRADO',       'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 38, 'medida_codigo_unidad' => 'MMQ',     'medida_nombre' => 'MILIMETRO CUBICO',         'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 39, 'medida_codigo_unidad' => 'MLL',     'medida_nombre' => 'MILLARES',                 'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 40, 'medida_codigo_unidad' => 'UM',      'medida_nombre' => 'MILLON DE UNIDADES',       'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 41, 'medida_codigo_unidad' => 'ONZ',     'medida_nombre' => 'ONZAS',                    'medida_activo' => 0, 'medida_grupo' => 2],
            ['id_medida' => 42, 'medida_codigo_unidad' => 'PF',      'medida_nombre' => 'PALETAS',                  'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 43, 'medida_codigo_unidad' => 'PK',      'medida_nombre' => 'PAQUETE',                  'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 44, 'medida_codigo_unidad' => 'PR',      'medida_nombre' => 'PAR',                      'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 45, 'medida_codigo_unidad' => 'FOT',     'medida_nombre' => 'PIES',                     'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 46, 'medida_codigo_unidad' => 'FTK',     'medida_nombre' => 'PIES CUADRADOS',           'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 47, 'medida_codigo_unidad' => 'FTQ',     'medida_nombre' => 'PIES CUBICOS',             'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 48, 'medida_codigo_unidad' => 'C62',     'medida_nombre' => 'PIEZAS',                   'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 49, 'medida_codigo_unidad' => 'PG',      'medida_nombre' => 'PLACAS',                   'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 50, 'medida_codigo_unidad' => 'ST',      'medida_nombre' => 'PLIEGO',                   'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 51, 'medida_codigo_unidad' => 'INH',     'medida_nombre' => 'PULGADAS',                 'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 52, 'medida_codigo_unidad' => 'RM',      'medida_nombre' => 'RESMA',                    'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 53, 'medida_codigo_unidad' => 'DR',      'medida_nombre' => 'TAMBOR',                   'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 54, 'medida_codigo_unidad' => 'STN',     'medida_nombre' => 'TONELADA CORTA',           'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 55, 'medida_codigo_unidad' => 'LTN',     'medida_nombre' => 'TONELADA LARGA',           'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 56, 'medida_codigo_unidad' => 'TNE',     'medida_nombre' => 'TONELADAS',                'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 57, 'medida_codigo_unidad' => 'TU',      'medida_nombre' => 'TUBOS',                    'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 58, 'medida_codigo_unidad' => 'NIU',     'medida_nombre' => 'UNIDAD (BIENES)',          'medida_activo' => 1, 'medida_grupo' => null],
            ['id_medida' => 59, 'medida_codigo_unidad' => 'ZZ',      'medida_nombre' => 'UNIDAD (SERVICIOS)',       'medida_activo' => 1, 'medida_grupo' => null],
            ['id_medida' => 60, 'medida_codigo_unidad' => 'GLL',     'medida_nombre' => 'US GALON (3,7843 L)',      'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 61, 'medida_codigo_unidad' => 'YRD',     'medida_nombre' => 'YARDA',                    'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 62, 'medida_codigo_unidad' => 'YDK',     'medida_nombre' => 'YARDA CUADRADA',           'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 63, 'medida_codigo_unidad' => 'SACOS',   'medida_nombre' => 'SACOS',                    'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 64, 'medida_codigo_unidad' => 'ROLLOS',  'medida_nombre' => 'ROLLOS',                   'medida_activo' => 0, 'medida_grupo' => null],
            ['id_medida' => 65, 'medida_codigo_unidad' => 'BOTELLON','medida_nombre' => 'BOTELLON',                 'medida_activo' => 0, 'medida_grupo' => null],
        ]);

        DB::table('tipo_documento')->truncate();
        DB::table('tipo_documento')->insert([
            ['id_tipo_documento' => 1, 'tipodocumento_codigo' => '0', 'tipo_documento_identidad' => 'DOC.TRIB.NO.DOM.SIN.RUC',         'tipo_documento_identidad_abr' => '-',    'tipo_documento_estado' => 1],
            ['id_tipo_documento' => 2, 'tipodocumento_codigo' => '1', 'tipo_documento_identidad' => 'Documento Nacional de Identidad',  'tipo_documento_identidad_abr' => 'DNI',  'tipo_documento_estado' => 1],
            ['id_tipo_documento' => 3, 'tipodocumento_codigo' => '4', 'tipo_documento_identidad' => 'Carnet de extranjería',            'tipo_documento_identidad_abr' => 'EXTR', 'tipo_documento_estado' => 1],
            ['id_tipo_documento' => 4, 'tipodocumento_codigo' => '6', 'tipo_documento_identidad' => 'Registro Unico de Contributentes', 'tipo_documento_identidad_abr' => 'RUC',  'tipo_documento_estado' => 1],
            ['id_tipo_documento' => 5, 'tipodocumento_codigo' => '7', 'tipo_documento_identidad' => 'Pasaporte',                        'tipo_documento_identidad_abr' => 'PAS',  'tipo_documento_estado' => 1],
            ['id_tipo_documento' => 6, 'tipodocumento_codigo' => 'A', 'tipo_documento_identidad' => 'Cédula Diplomática de identidad',  'tipo_documento_identidad_abr' => 'CDI',  'tipo_documento_estado' => 1],
            ['id_tipo_documento' => 7, 'tipodocumento_codigo' => 'B', 'tipo_documento_identidad' => 'DOC.IDENT.PAIS.RESIDENCIA-NO.D',   'tipo_documento_identidad_abr' => 'NO',   'tipo_documento_estado' => 1],
        ]);

        DB::table('tipo_pago')->truncate();
        DB::table('tipo_pago')->insert([
            ['id_tipo_pago' => 1, 'tipo_pago_nombre' => 'EFECTIVO',              'tipo_pago_estado' => 1],
            ['id_tipo_pago' => 2, 'tipo_pago_nombre' => 'TARJETA DÉBITO',        'tipo_pago_estado' => 1],
            ['id_tipo_pago' => 3, 'tipo_pago_nombre' => 'TARJETA CRÉDITO',       'tipo_pago_estado' => 1],
            ['id_tipo_pago' => 4, 'tipo_pago_nombre' => 'POS',                   'tipo_pago_estado' => 1],
            ['id_tipo_pago' => 5, 'tipo_pago_nombre' => 'YAPE',                  'tipo_pago_estado' => 1],
            ['id_tipo_pago' => 6, 'tipo_pago_nombre' => 'PLIN',                  'tipo_pago_estado' => 1],
            ['id_tipo_pago' => 7, 'tipo_pago_nombre' => 'QR',                    'tipo_pago_estado' => 1],
            ['id_tipo_pago' => 8, 'tipo_pago_nombre' => 'TRANSFERENCIA BANCARIA','tipo_pago_estado' => 1],
            ['id_tipo_pago' => 9, 'tipo_pago_nombre' => 'DEPÓSITO',              'tipo_pago_estado' => 1],
        ]);

        DB::table('tipo_afectacion')->truncate();
        DB::table('tipo_afectacion')->insert([
            ['id_tipo_afectacion' => 1, 'codigo' => '10', 'descripcion' => 'OP. GRAVADAS',   'codigo_afectacion' => '1000', 'nombre_afectacion' => 'IGV', 'tipo_afectacion' => 'VAT'],
            ['id_tipo_afectacion' => 2, 'codigo' => '20', 'descripcion' => 'OP. EXONERADAS', 'codigo_afectacion' => '9997', 'nombre_afectacion' => 'EXO', 'tipo_afectacion' => 'VAT'],
            ['id_tipo_afectacion' => 3, 'codigo' => '30', 'descripcion' => 'OP. INAFECTAS',  'codigo_afectacion' => '9998', 'nombre_afectacion' => 'INA', 'tipo_afectacion' => 'FRE'],
            ['id_tipo_afectacion' => 4, 'codigo' => '21', 'descripcion' => 'OP. GRATUITAS',  'codigo_afectacion' => '9996', 'nombre_afectacion' => 'GRA', 'tipo_afectacion' => 'FRE'],
        ]);

        DB::table('tipo_ncreditos')->truncate();
        DB::table('tipo_ncreditos')->insert([
            ['id_tipo_ncreditos' => 1,  'codigo' => '01', 'tipo_nota_descripcion' => 'Anulación de la operacion',                   'estado' => 0],
            ['id_tipo_ncreditos' => 2,  'codigo' => '02', 'tipo_nota_descripcion' => 'Anulación por error en el RUC',               'estado' => 0],
            ['id_tipo_ncreditos' => 3,  'codigo' => '03', 'tipo_nota_descripcion' => 'Corrección por error en la descripcion',      'estado' => 0],
            ['id_tipo_ncreditos' => 4,  'codigo' => '04', 'tipo_nota_descripcion' => 'Descuento Global',                            'estado' => 0],
            ['id_tipo_ncreditos' => 5,  'codigo' => '05', 'tipo_nota_descripcion' => 'Descuento por ítem',                          'estado' => 0],
            ['id_tipo_ncreditos' => 6,  'codigo' => '06', 'tipo_nota_descripcion' => 'Devolución total',                            'estado' => 0],
            ['id_tipo_ncreditos' => 7,  'codigo' => '07', 'tipo_nota_descripcion' => 'Devolución por ítem',                         'estado' => 0],
            ['id_tipo_ncreditos' => 8,  'codigo' => '08', 'tipo_nota_descripcion' => 'Bonificación',                                'estado' => 0],
            ['id_tipo_ncreditos' => 9,  'codigo' => '09', 'tipo_nota_descripcion' => 'Disminición en el valor',                     'estado' => 0],
            ['id_tipo_ncreditos' => 10, 'codigo' => '10', 'tipo_nota_descripcion' => 'Otros conceptos',                             'estado' => 0],
            ['id_tipo_ncreditos' => 11, 'codigo' => '11', 'tipo_nota_descripcion' => 'Ajustes de operaciones de exportacion',       'estado' => 0],
            ['id_tipo_ncreditos' => 12, 'codigo' => '12', 'tipo_nota_descripcion' => 'Ajustes afectos al IVAP',                     'estado' => 0],
            ['id_tipo_ncreditos' => 13, 'codigo' => '13', 'tipo_nota_descripcion' => 'Corrección del monto neto pendiente de pago', 'estado' => 1],
        ]);

        DB::table('tipo_ndebitos')->truncate();
        DB::table('tipo_ndebitos')->insert([
            ['id_tipo_ndebitos' => 1, 'codigo' => '01', 'tipo_nota_descripcion' => 'Intereses por mora',                 'estado' => 0],
            ['id_tipo_ndebitos' => 2, 'codigo' => '02', 'tipo_nota_descripcion' => 'Aumento en el valor',                'estado' => 0],
            ['id_tipo_ndebitos' => 3, 'codigo' => '03', 'tipo_nota_descripcion' => 'Penalidades / Otros conceptos',      'estado' => 0],
            ['id_tipo_ndebitos' => 4, 'codigo' => '10', 'tipo_nota_descripcion' => 'Ajustes de operaciones de exportación','estado' => 0],
            ['id_tipo_ndebitos' => 5, 'codigo' => '11', 'tipo_nota_descripcion' => 'Ajustes afectos al IVAP',            'estado' => 0],
        ]);

        DB::table('monedas')->truncate();
        DB::table('monedas')->insert([
            ['id_moneda' => 1, 'moneda' => 'SOLES',   'abreviado' => 'sol', 'abrstandar' => 'PEN', 'simbolo' => 'S/', 'activo' => 1],
            ['id_moneda' => 2, 'moneda' => 'DÓLARES', 'abreviado' => 'dol', 'abrstandar' => 'USD', 'simbolo' => '$',  'activo' => 1],
            ['id_moneda' => 3, 'moneda' => 'EUROS',   'abreviado' => 'eur', 'abrstandar' => 'EUR', 'simbolo' => 'E',  'activo' => 0],
        ]);

        DB::table('tipo_venta')->truncate();
        DB::table('tipo_venta')->insert([
            ['id_tipo_venta' => 1, 'tipo_venta_nombre' => 'Boleta',  'tipo_venta_estado' => 1],
            ['id_tipo_venta' => 2, 'tipo_venta_nombre' => 'Factura', 'tipo_venta_estado' => 1],
        ]);

        DB::table('planes')->truncate();
        DB::table('planes')->insert([
            ['id_plan' => 1, 'plan_nombre' => 'Plan Mensual', 'plan_descripcion' => null, 'plan_precio' => 100.00, 'plan_duracion_dias' => 30, 'plan_estado' => 1, 'created_at' => '2026-03-19 08:54:39', 'updated_at' => '2026-03-19 08:54:39'],
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
