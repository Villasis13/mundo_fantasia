<?php

namespace App\Console\Commands;

use App\Models\Logs;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature   = 'backup:database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera un backup de la base de datos';

    /**
     * Execute the console command.
     *
     * @return int
     */
    private $logs;

    public function __construct()
    {
        parent::__construct();
        $this->logs = new Logs();
    }
    public function handle()
    {
        try {
            $db       = config('database.connections.mysql.database');
            $user     = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $host     = config('database.connections.mysql.host');

            $fecha   = date('Y-m-d_H-i-s');
            $carpeta = storage_path('app/backups');
            $archivo = "{$carpeta}/backup_{$db}_{$fecha}.sql";

            if (!file_exists($carpeta)) {
                mkdir($carpeta, 0755, true);
            }

            // Conexión directa para generar el backup
            $pdo = new \PDO("mysql:host={$host};dbname={$db}", $user, $password);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $sql = "-- Backup de {$db} generado el " . date('Y-m-d H:i:s') . "\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            // Obtener todas las tablas
            $tablas = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($tablas as $tabla) {

                // Estructura de la tabla
                $sql .= "DROP TABLE IF EXISTS `{$tabla}`;\n";
                $createTable = $pdo->query("SHOW CREATE TABLE `{$tabla}`")->fetch(\PDO::FETCH_ASSOC);
                $sql .= $createTable['Create Table'] . ";\n\n";

                // Datos de la tabla
                $filas = $pdo->query("SELECT * FROM `{$tabla}`")->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($filas as $fila) {
                    $valores = array_map(function ($valor) use ($pdo) {
                        return $valor === null ? 'NULL' : $pdo->quote($valor);
                    }, array_values($fila));

                    $sql .= "INSERT INTO `{$tabla}` VALUES (" . implode(', ', $valores) . ");\n";
                }
                $sql .= "\n";
            }

            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

            // Guardar el archivo
            file_put_contents($archivo, $sql);

            $this->info("Backup generado: {$archivo}");

            // Enviar por correo
            Mail::raw('Adjunto el respaldo de la base de datos.', function ($message) use ($archivo) {
                $message->to(config('services.email.backup'))
                    ->subject('Respaldo de BD ' . date('d/m/Y'))
                    ->from(config('services.email.username'), 'Soporte')
                    ->attach($archivo);
            });

            $this->info('Correo enviado correctamente.');

            // Eliminar backups de más de 7 días
            $archivosViejos = glob("{$carpeta}/backup_*.sql");
            foreach ($archivosViejos as $viejo) {
                if (filemtime($viejo) < strtotime('-7 days')) {
                    unlink($viejo);
                    $this->info("Backup antiguo eliminado: {$viejo}");
                }
            }

        } catch (\Exception $e) {
            $this->error('Excepción: ' . $e->getMessage());
            $this->logs->insertarLog($e);
        }
    }
}
