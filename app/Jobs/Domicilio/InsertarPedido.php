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

        if(!$resultadoInsercion) throw new \Exception($this->mensajeError);
        //Eliminar registro procesado
        Redis::del($this->uidPedido);
    }
}