<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
class Logs extends Model
{
    use HasFactory;
    public  function insertarLog(Exception $e) {
        // Obtener información sobre el lugar donde ocurrió la excepción
        $trace = debug_backtrace();
        $caller = $trace[1] ?? [];

        $controller = class_basename($caller['class'] ?? 'Desconocido');
        $function = $caller['function'] ?? 'Desconocida';

        // Ruta base para los logs
        $rutaBase = storage_path('logs');

        // Fecha actual
        $fecha = now();

        // Crear la ruta completa del directorio (año/mes)
        $rutaCompleta = $rutaBase . '/' . $fecha->format('Y/m');

        // Crear la ruta completa del archivo (año/mes/día)
        $rutaArchivo = $rutaCompleta . '/' . $fecha->format('d') . '.txt';

        // IP donde se registra el error
        $ip = request()->ip();

        // Crear la línea de texto que se va a insertar en el archivo
        $log = $fecha->format('Y-m-d H:i:s') . ' [UTC -5] [ip] ' . $ip . ' [lugar] ' . $controller . ' - ' . $function . ' [texto] ' . $e->getMessage() . PHP_EOL;

        // Verificar si el directorio año/mes existe, si no, crearlo
        if (!file_exists($rutaCompleta)) {
            mkdir($rutaCompleta, 0755, true);
        }

        // Escribir el log en el archivo
        file_put_contents($rutaArchivo, $log, FILE_APPEND);
    }
}
