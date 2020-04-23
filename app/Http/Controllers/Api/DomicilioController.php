<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Domicilio\InsertarPedidoRequest;
use App\Jobs\Domicilio\InsertarPedido;
use App\Models\Azure\RestauranteDomicilio;
use App\Services\PedidoServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Ramsey\Uuid\Uuid;

class DomicilioController extends Controller
{

    public function pedidoApp(InsertarPedidoRequest $request){

        $uid = Uuid::uuid4()->toString();
        $data = json_encode($request->validated());

        $pedidoServices=new PedidoServices(json_decode($data));
        $resultadoValidacion = $pedidoServices->validar();

        if( !$resultadoValidacion ) return ["codigo"=>400,"mensaje"=>"error","causa"=>$pedidoServices->mensajeError];

        Redis::set($uid, $data);
        InsertarPedido::dispatchNow($uid);
        return ["codigo"=>200,"mensaje"=>"Pedido ingresado exitosamente","causa"=>"Pedido ingresado exitosamente"];
    }

    public function estadoPedidoApp(Request $request){
        $pub = Redis::publish('test-channel', json_encode(['foo' => 'bar']));
    }

    public function pingGeneral(Request $request){
        DB::connection("")->enableQueryLog();
        RestauranteDomicilio::domicilioActivo()->get(["IDRestaurante","IDTienda"]);
        $log = DB::getQueryLog();
        dd($log);
    }
    public function pingPorTienda(Request $request){
        $pub = Redis::publish('test-channel', json_encode(['foo' => 'bar']));
    }
}


