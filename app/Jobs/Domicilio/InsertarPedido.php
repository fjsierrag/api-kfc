<?php

namespace App\Jobs\Domicilio;

use App\Services\PedidoServices;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;

class InsertarPedido implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $uidPedido;
    private $objSolicitudPedido;
    private $sqlHelpers;
    private $mensajeError = "Error no determinado";
    private $conexionBDD = null;

    public $retryAfter = 15;
    public $tries = 1;

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
        $resultadoInsercion = $pedidoServices->guardarPedido();

        if(!$resultadoInsercion) throw new \Exception($pedidoServices->mensajeError);
        //Eliminar registro procesado
        Redis::del($this->uidPedido);
    }

    public function failed(\Exception $exception)
    {
        $jsonPedido = Redis::get($this->uidPedido);
        $objPedido = json_decode($jsonPedido);
        $pedidoServices = new PedidoServices($objPedido);
        $pedidoServices->limpiarPedidoFallido();
        // Send user notification of failure, etc...
    }
}