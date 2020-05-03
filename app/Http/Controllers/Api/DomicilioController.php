<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\Domicilio\InsertarPedido;
use App\Models\Azure\RestauranteDomicilio;
use App\Services\PedidoServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;

class DomicilioController extends Controller
{
    
    public function pedidoApp(Request $request){

        $data = $request->getContent();
        $objPeticion = json_decode($data);

        $cabecera = $request->get("cabecera");
        $detalle = $request->get("detalle");
        $modificadores = $request->get("modificadores");
        $formasPago = $request->get("formasPago");

        //Validacion de cabecera
        $validacion=$this->validarCampo($objPeticion->cabecera,$cabecera,$this->reglasValidacionCabecera(),true);

        if(true !== $validacion) return $validacion;

        //Validacion de formas de pago
        $validacion=$this->validarCampo($objPeticion->formasPago,$formasPago,$this->reglasValidacionFormasPago(),true);
        if(true !== $validacion) return $validacion;

        $uid = Uuid::uuid4()->toString();

        $pedidoServices = new PedidoServices(json_decode($data));
        $resultadoValidacionBDD = $pedidoServices->validar();

        if( !$resultadoValidacionBDD ) return ["codigo"=>400,"mensaje"=>"error","causa"=>$pedidoServices->mensajeError];

        Redis::set($uid, $data);
        InsertarPedido::dispatch($uid);
        return ["codigo"=>200,"mensaje"=>"Pedido ingresado exitosamente","causa"=>"Pedido ingresado exitosamente"];
    }

    public function estadoPedidoApp(Request $request){
        $pub = Redis::publish('test-channel', json_encode(['foo' => 'bar']));
    }

    public function pingGeneral(Request $request){
        DB::connection("")->enableQueryLog();
        RestauranteDomicilio::domicilioActivo()->get(["IDRestaurante","IDTienda"]);
        $log = DB::getQueryLog();
    }
    public function pingPorTienda(Request $request){
        $pub = Redis::publish('test-channel', json_encode(['foo' => 'bar']));
    }

    protected function buildResponse($validator)
    {
        return Response::json(
            [
                'codigo' => 400,
                'mensaje' => "Error en validacion de datos recibidos",
                'causa' => $validator->errors()->all(),
                'consulta' => "",
            ]
        );
    }

    private function reglasValidacionCabecera(){
        return [
            "codigoApp" => "required|string",
            "codRestaurante" => "required|integer",
            "fechaPedido" => "required|string",
            "telefonoCliente" => "required|string",
            "nombresCliente" => "required|string",
            "calle1Domicilio" => "required|string",
            "calle2Domicilio" => "required|string",
            "observacionesDomicilio" => "required|string",
            "numDirecciondomicilio" => "required|string",
            "codZipCode" => "required|string",
            "tipoInmueble" => "required|integer",
            "totalFactura" => "required|numeric",
            "observacionesPedido" => "nullable|string",
            "transaccion" => "nullable|string",
            "medio" => "nullable|string",
            "dispositivo" => "nullable|string",
            "consumidorFinal" => "required|boolean",
            "identificacionCliente" => "required_if:consumidorFinal,false|string|nullable",
            "direccionCliente" => "required_if:consumidorFinal,false|string|nullable",
            "emailCliente" => "required_if:consumidorFinal,false|email|nullable"
        ];
    }

    private function reglasValidacionFormasPago(){
        return [
            //Formas de pago
            "codigoApp" => "required|string",
            "codformaPago" => "required|integer",
            "totalPagar" => "required|numeric",
            "billete" => "nullable|numeric",
        ];
    }

    private function validarCampo($campo,$valores,$reglas,$revisarArray=false){
        if($revisarArray && is_array($campo)){
            foreach($valores as $item){
                $validador = Validator::make($item,$reglas);
                if(true==$validador->fails()) return $this->buildResponse($validador);
            }
        }else{

            $validador = Validator::make($valores,$reglas);
            if(true==$validador->fails()) return $this->buildResponse($validador);
        }
        return true;
    }

}


