<?php

namespace App\Http\Controllers;

use App\Models\Admin\ConexionDomicilio;
use App\Models\Admin\FailedJob;
use App\Models\Azure\RestauranteDomicilio;
use App\Util\DBHelpers;
use App\Util\Ping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;

class AdminController extends Controller
{
    public function inicio()
    {
        return view("admin.inicio");
    }

    public function localesDomicilio()
    {
        // DB::connection("sqlsrv_az_ec")->enableQueryLog();
        // DB::connection("sqlite")->enableQueryLog();

        $idBaseDomicilioConfig = Config::get("app.idbasedomicilio");

        if(0==$idBaseDomicilioConfig){
            $restaurantesDomicilio = RestauranteDomicilio::domicilioActivo()->orderBy("IDTienda")->get(["IDRestaurante", "IDTienda", "Nombre"]);
            $idsRestaurantesDomicilio = $restaurantesDomicilio->modelKeys();

        }else{
            $objConexion=new RestauranteDomicilio();
            $objConexion->IDRestaurante=$idBaseDomicilioConfig;
            $objConexion->IDTienda="DOMICILIO";
            $objConexion->Nombre="DOMICILIO";
            $restaurantesDomicilio=collect()
                ->push($objConexion);
            $idsRestaurantesDomicilio = [$idBaseDomicilioConfig];
        }

        $conexionesDomicilio = ConexionDomicilio::whereIn("IDRestaurante", $idsRestaurantesDomicilio)->get()->keyBy(
            "IDRestaurante"
        );
        // dd($conexionesDomicilio,$restaurantesDomicilio);
        $reg = $restaurantesDomicilio->map(
            function ($res) use ($conexionesDomicilio) {
                $conexion = $conexionesDomicilio->get($res->IDRestaurante);

                if ($conexion) {
                    return $res->toArray() + $conexion->toArray();
                }
                return $res->toArray();
            }
        );
        //$registrosTabla=array_combine($restaurantesDomicilioTabla,$conexionesDomicilio);
        //$registrosTabla=$restaurantesDomicilioTabla+$conexionesDomicilio;
        // $log = DB::connection("sqlsrv_az_ec")->getQueryLog();
        // $log = DB::connection("sqlite")->getQueryLog();
        // dump($log);
        return view("admin.locales-domicilio", ["registrosTabla" => $reg]);
    }

    public function guardarConexion(Request $request)
    {
        $codTienda = $request->get("codTienda");
        if(empty($codTienda)) abort(404);
        $conexionDomicilio = ConexionDomicilio::firstWhere('IDRestaurante', $codTienda);

        $existePing = $this->ping($request->get("servidor"));
        if(empty($conexionDomicilio)){
            $parametrosConexion = $request->only(["nombreBDD","servidor","instancia","usuario","puerto","clave"]);
            $estadoConexion = $this->pingBDD($parametrosConexion);
            if( true !== $estadoConexion["conecta"] ){
                return ["ping" => $existePing, "conexionbdd"=>$estadoConexion];
            }

            $valoresConexion = [
                'IDRestaurante' => $request->get("codTienda"),
                'Instancia' => $request->get("instancia"),
                'BDD' => $request->get("nombreBDD"),
                'Usuario' => Crypt::encryptString($request->get("usuario")),
                'Clave' => Crypt::encryptString($request->get("clave")),
                'Puerto' => $request->get("puerto"),
            ];

            if(!empty($request->get("servidor"))) $valoresConexion["Nombre_Servidor"] = $request->get("servidor");
            ConexionDomicilio::create($valoresConexion);

            return ["ping" => $existePing, "conexionbdd"=>$estadoConexion];
        }

        $guardarUsuarioClave = $request->get("editarUsuarioClave");
        if("true" == $guardarUsuarioClave){
            $parametrosConexion = $request->only(["nombreBDD","servidor","instancia","usuario","puerto","clave"]);
            $estadoConexion = $this->pingBDD($parametrosConexion);
            if( true !== $estadoConexion["conecta"] ){
                return ["ping" => $existePing, "conexionbdd"=>$estadoConexion];
            }

            $valoresConexion = [
                'Instancia' => $request->get("instancia"),
                'BDD' => $request->get("nombreBDD"),
                'Usuario' => Crypt::encryptString($request->get("usuario")),
                'Clave' => Crypt::encryptString($request->get("clave")),
                'Puerto' => $request->get("puerto"),
            ];
        }else{
            $parametrosConexion = $request->only(["nombreBDD","servidor","instancia","puerto"]);
            $parametrosConexion["usuario"] = Crypt::decryptString($conexionDomicilio->Usuario);
            $parametrosConexion["clave"] = Crypt::decryptString($conexionDomicilio->Clave);
            $estadoConexion = $this->pingBDD($parametrosConexion);
            if( true !== $estadoConexion["conecta"] ){
                return ["ping" => $existePing, "conexionbdd"=>$estadoConexion];
            }
            $valoresConexion = [
                'Instancia' => $request->get("instancia"),
                'BDD' => $request->get("nombreBDD"),
                'Puerto' => $request->get("puerto"),
            ];
        }

        if(!empty($request->get("servidor"))) $valoresConexion["Nombre_Servidor"]=$request->get("servidor");

        $conexionDomicilio->update($valoresConexion);

        return ["ping" => $existePing, "conexionbdd"=>$estadoConexion];
    }

    public function probarConexionBDD(Request $request) {

        $idRestauranteRequest = $request->get("idRestaurante");
        $conexionDomicilio = ConexionDomicilio::where("IDRestaurante", $idRestauranteRequest)->firstOrFail();

        $parametrosConexion = array(
            'nombreBDD' => $conexionDomicilio->BDD,
            'servidor' => $conexionDomicilio->Nombre_Servidor,
            'instancia'=> $conexionDomicilio->Instancia,
            'puerto' =>  $conexionDomicilio->Puerto,
            'usuario' => Crypt::decryptString($conexionDomicilio->Usuario),
            'clave' => Crypt::decryptString($conexionDomicilio->Clave)
        );

        $resultadoPingBDD = $this->pingBDD($parametrosConexion);
        return $resultadoPingBDD;
    }

    public function probarPing(Request $request) {
        $idRestauranteRequest = $request->get("idRestaurante");
        $conexionDomicilio = ConexionDomicilio::where("IDRestaurante", $idRestauranteRequest)->firstOrFail();
        $existePing=$this->ping($conexionDomicilio->Nombre_Servidor);
        return ["ping"=>$existePing];
    }

    public function jobsFallidos(){
        $jobs = FailedJob::all();
        return view("admin.jobs-fallidos",["jobs"=>$jobs]);
    }
    private function pingBDD($parametros){

        $parametrosConexion = array(
            'base' => $parametros["nombreBDD"],
            'servidor' => $parametros["servidor"],
            'instancia'=> $parametros["instancia"],
            'usuario' => $parametros["usuario"],
            'puerto' =>  $parametros["puerto"],
            'clave' => $parametros["clave"]
        );

        $estadoConexion = DBHelpers::comprobarConexion($parametrosConexion);

        return $estadoConexion;

    }

    private function ping($host)
    {
        try{
            $ping = new Ping($host);
            $ping->setTimeout(3);
            $latency = $ping->ping();
        }catch(\Exception $ex){
            $latency = false;
        }

        return ($latency == false) ? false : true;
    }

}


