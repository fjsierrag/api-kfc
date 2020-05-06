<?php

namespace App\Jobs\Domicilio;

use App\Exceptions\PedidoNoInsertado;
use App\Services\PedidoServices;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class InsertarPedido implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $uidPedido;
    private $objSolicitudPedido;
    private $sqlHelpers;
    private $mensajeError = "Error no determinado";
    private $conexionBDD = null;

    public $retryAfter = 30;
    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @param $uidPedido string
     */
    public function __construct($uidPedido)
    {
        $this->uidPedido = $uidPedido;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {

        $jsonPedido = Redis::get($this->uidPedido);
        $objPedido = json_decode($jsonPedido);

        $pedidoServices = new PedidoServices($objPedido);

        if($this->attempts()>1){
            $pedidoServices->limpiarPedidoFallido();
        }

        //$resultadoInsercion = $pedidoServices->guardarPedido($this->attempts());
        $resultadoInsercion = $pedidoServices->guardarPedido();

        if(!$resultadoInsercion) {
            throw new PedidoNoInsertado($pedidoServices->mensajeError);
        }
        //Eliminar registro procesado
        Redis::del($this->uidPedido);
    }

    public function failed(\Exception $exception)
    {
        //INSERTE AQUI CODIGO DE NOTIFICACION DE ERRORES HEAVY

        // Send user notification of failure, etc...
    }
}