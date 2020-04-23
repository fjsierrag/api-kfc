<?php


namespace App\Util;


use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\DriverManager;
use Illuminate\Support\Facades\Log;

class Helpers
{
    static function leerParametrosEnv($nombresConfiguraciones)
    {
        $parametros = [];
        foreach ($nombresConfiguraciones as $nombre) {
            $valor = env($nombre);
            if (!$valor) {
                $mensajeError = "No se pudo leer el parámetro de configuración '$nombre' en el archivo .env";
                Log::error($mensajeError);
                return false;
            }
            $parametros[$nombre] = $valor;
        }
        return $parametros;
    }

    static function isJSON($string)
    {
        return is_string($string) && is_array(json_decode($string, true)) && (json_last_error(
            ) == JSON_ERROR_NONE) ? true : false;
    }

    public static function decodeJWT($jwt = null)
    {
        if ($jwt) {
            list($header, $claims, $signature) = explode('.', $jwt);

            $header = self::decodeFragment($header);
            $claims = self::decodeFragment($claims);
            $signature = (string)base64_decode($signature);

            return [
                'header' => $header,
                'claims' => $claims,
                'signature' => $signature
            ];
        }

        return false;
    }

    protected static function decodeFragment($value)
    {
        return (array)json_decode(base64_decode($value));
    }
}