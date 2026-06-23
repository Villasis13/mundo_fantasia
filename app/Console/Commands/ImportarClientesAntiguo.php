<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class ImportarClientesAntiguo extends Command
{
    protected $signature   = 'importar:clientes-antiguo';
    protected $description = 'Importa clientes desde el dump SQL antiguo a la tabla clientes';

    private const TMP_DB    = 'mundo_import_tmp';
    private const SQL_FILE  = 'req/mundo_20260611_192544.sql';
    private const ID_EMPRESA = 1;

    // id_tipo_documento según la tabla tipo_documento del sistema actual
    private const ID_TIPO_DNI = 2;
    private const ID_TIPO_RUC = 4;

    public function handle(): int
    {
        // ── 1. Crear BD temporal e importar dump ──────────────────────
        $this->info('Creando base de datos temporal...');
        DB::statement('DROP DATABASE IF EXISTS `' . self::TMP_DB . '`');
        DB::statement('CREATE DATABASE `' . self::TMP_DB . '` CHARACTER SET latin1 COLLATE latin1_swedish_ci');

        $sqlPath = str_replace('/', DIRECTORY_SEPARATOR, base_path(self::SQL_FILE));
        $host    = config('database.connections.mysql.host', '127.0.0.1');
        $port    = config('database.connections.mysql.port', 3306);
        $user    = config('database.connections.mysql.username', 'root');
        $pass    = config('database.connections.mysql.password', '');

        $this->info('Importando SQL dump...');
        $passFlag = $pass ? " -p\"{$pass}\"" : '';
        $cmd      = "mysql -h\"{$host}\" -P{$port} -u\"{$user}\"{$passFlag} \"" . self::TMP_DB . "\"";

        $descriptors = [
            0 => ['file', $sqlPath, 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $proc = proc_open($cmd, $descriptors, $pipes);

        if (!is_resource($proc)) {
            $this->error('No se pudo ejecutar mysql. Verifica que mysql esté en el PATH.');
            DB::statement('DROP DATABASE IF EXISTS `' . self::TMP_DB . '`');
            return self::FAILURE;
        }

        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $ret = proc_close($proc);

        if ($ret !== 0) {
            $this->error("Error importando SQL (código {$ret}): {$stderr}");
            DB::statement('DROP DATABASE IF EXISTS `' . self::TMP_DB . '`');
            return self::FAILURE;
        }
        $this->info('SQL importado correctamente.');

        // ── 2. Conexión a BD temporal ─────────────────────────────────
        Config::set('database.connections.tmp_import', [
            'driver'    => 'mysql',
            'host'      => $host,
            'port'      => $port,
            'database'  => self::TMP_DB,
            'username'  => $user,
            'password'  => $pass,
            'charset'   => 'latin1',
            'collation' => 'latin1_swedish_ci',
            'prefix'    => '',
            'strict'    => false,
        ]);
        $tmp = DB::connection('tmp_import');

        // ── 3. Leer clientes del sistema antiguo ──────────────────────
        $this->info('Leyendo clientes de tclientes...');
        $clientes = $tmp->table('tclientes')
            ->select(['CODIGO', 'NRODOC', 'TIPDOC', 'CLIENTE', 'APEPAT', 'APEMAT', 'NOMBRES',
                      'DIRECCION', 'TELEFONOS', 'CELULAR', 'EMAIL', 'ACTIVIX'])
            ->get();

        $this->info("  → {$clientes->count()} clientes encontrados.");

        // ── 4. Mapear e insertar ──────────────────────────────────────
        $insertados = 0;
        $omitidos   = 0;
        $now        = now();

        foreach ($clientes as $c) {
            $nrodoc = $this->utf8(trim($c->NRODOC ?? ''));

            // Determinar tipo de documento por longitud del número
            $lenDoc = strlen($nrodoc);
            if ($lenDoc === 11) {
                $idTipoDoc = self::ID_TIPO_RUC;
            } elseif ($lenDoc === 8) {
                $idTipoDoc = self::ID_TIPO_DNI;
            } else {
                // Fallback: si empieza con 10 o 20 y tiene 11 dígitos → RUC
                $idTipoDoc = self::ID_TIPO_DNI;
            }

            // Construir nombre
            $nombres  = $this->utf8(trim($c->NOMBRES ?? ''));
            $apePat   = $this->utf8(trim($c->APEPAT ?? ''));
            $apeMat   = $this->utf8(trim($c->APEMAT ?? ''));
            $cliente  = $this->utf8(trim($c->CLIENTE ?? ''));

            if ($apePat || $nombres) {
                $partes = array_filter([$apePat, $apeMat, $nombres]);
                $nombre = implode(' ', $partes);
            } else {
                $nombre = $cliente;
            }

            // Para RUC: razón social es el campo CLIENTE
            $razonSocial = $idTipoDoc === self::ID_TIPO_RUC ? $cliente : null;

            // Teléfono: preferir celular si telefonos está vacío
            $telefono = $this->utf8(trim($c->TELEFONOS ?? ''))
                     ?: $this->utf8(trim($c->CELULAR ?? ''));

            // Omitir si ya existe ese número de documento
            if ($nrodoc && DB::table('clientes')->where('cliente_numero', $nrodoc)->exists()) {
                $omitidos++;
                continue;
            }

            DB::table('clientes')->insert([
                'id_empresa'         => self::ID_EMPRESA,
                'id_tipo_documento'  => $idTipoDoc,
                'cliente_codigo'     => $this->utf8(trim($c->CODIGO ?? '')),
                'cliente_numero'     => $nrodoc,
                'cliente_nombre'     => $nombre ?: $cliente,
                'cliente_razonsocial'=> $razonSocial,
                'cliente_direccion'  => $this->utf8(trim($c->DIRECCION ?? '')) ?: null,
                'cliente_telefono'   => $telefono ?: null,
                'cliente_correo'     => $this->utf8(trim($c->EMAIL ?? '')) ?: null,
                'cliente_fecha'      => $now,
                'cliente_estado'     => (int) ($c->ACTIVIX ?? 1) === 1 ? 1 : 0,
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);
            $insertados++;
        }

        // ── 5. Limpiar BD temporal ───────────────────────────────────
        $this->info('Eliminando base de datos temporal...');
        DB::statement('DROP DATABASE IF EXISTS `' . self::TMP_DB . '`');

        // ── 6. Resumen ───────────────────────────────────────────────
        $this->newLine();
        $this->table(
            ['Dato', 'Valor'],
            [
                ['Clientes en dump',          $clientes->count()],
                ['Insertados',                $insertados],
                ['Omitidos (ya existían)',     $omitidos],
                ['Total en tabla clientes',   DB::table('clientes')->count()],
            ]
        );

        return self::SUCCESS;
    }

    private function utf8(string $s): string
    {
        if (mb_detect_encoding($s, 'UTF-8', true)) {
            return $s;
        }
        return mb_convert_encoding($s, 'UTF-8', 'ISO-8859-1');
    }
}
