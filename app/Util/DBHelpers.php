<?php


namespace App\Util;


use App\Models\Admin\ConexionDomicilio;
use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\DriverManager;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class DBHelpers
{
    static function crearConexionBDDRestaurante($idRestaurante){
        $parametrosConexion = self::datosConexionRestaurante($idRestaurante);
        if(!$parametrosConexion) return false;
        return self::crearConexionBDD($parametrosConexion);
    }

    private static function datosConexionRestaurante($idRestaurante)
    {
        $conexionDomicilio = ConexionDomicilio::where("IDRestaurante",$idRestaurante)->first();
        if(!$conexionDomicilio) {
            Log::error("No existen datos de conexi�n para el restaurante ID $idRestaurante");
            return false;
        }

        $datosLog=[
            "servidor" => $conexionDomicilio->Nombre_Servidor,
            "instancia" => $conexionDomicilio->Instancia,
            "puerto" => $conexionDomicilio->Puerto,
            "base" => $conexionDomicilio->BDD,
        ];
        Log::info(json_encode($datosLog));

        return [
            "servidor" => $conexionDomicilio->Nombre_Servidor,
            "instancia" => $conexionDomicilio->Instancia,
            "puerto" => $conexionDomicilio->Puerto,
            "base" => $conexionDomicilio->BDD,
            "usuario" => Crypt::decryptString($conexionDomicilio->Usuario),
            "clave" => Crypt::decryptString($conexionDomicilio->Clave)
        ];
    }

    static function crearConexionBDD($parametrosConexion){
        $servidor=empty($parametrosConexion["instancia"])?$parametrosConexion["servidor"]:$parametrosConexion["servidor"] . "\\" . $parametrosConexion["instancia"];
        $parametrosConexion = array(
            'dbname' => $parametrosConexion["base"],
            'host' => $servidor,
            'user' => $parametrosConexion["usuario"],
            'port' => $parametrosConexion["puerto"],
            'password' => $parametrosConexion["clave"],
            'driver' => 'pdo_sqlsrv',
        );

        $conexion = DriverManager::getConnection($parametrosConexion);
        try {
            $conexion->connect();
        } catch (PDOException $ex) {
            Log::error($ex->getMessage());
            return false;
        }
        return $conexion;
    }

    static function comprobarConexion($parametrosConexion){
        $conecta = false;
        $error=null;
        $servidor=empty($parametrosConexion["instancia"])?$parametrosConexion["servidor"]:$parametrosConexion["servidor"] . "\\" . $parametrosConexion["instancia"];

        $parametrosConexion = array(
            'dbname' => $parametrosConexion["base"],
            'host' => $servidor,
            'user' => $parametrosConexion["usuario"],
            'port' => null,
            'password' => $parametrosConexion["clave"],
            'driver' => 'pdo_sqlsrv'
        );

        $conexion = DriverManager::getConnection($parametrosConexion);

        try {
            $conexion->ping();
            $conecta=true;
        } catch (\PDOException $ex) {
            $error=$ex->getMessage();
        }

        return ["conecta"=>$conecta,"error"=>$error];
    }
}