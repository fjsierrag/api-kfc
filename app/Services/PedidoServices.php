<?php


namespace App\Services;


use App\Util\DBHelpers;
use App\Util\SqlHelpers;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\ParameterType;
use Illuminate\Support\Facades\Log;

class PedidoServices
{
    public $objSolicitudPedido;
    private $sqlHelpers;
    public $mensajeError = "Error no determinado";
    private $conexionBDD=null;

    public function __construct($data)
    {
        $this->objSolicitudPedido = $data;
    }

    public function validar(){
        if (!$this->inicializar()) {
            Log::error($this->mensajeError);
            return false;
        }
        if (!$this->validarDatos()) {
            Log::error($this->mensajeError);
            return false;
        }

        //Cierro la conexion a la BDD
        $this->conexionBDD->close();
        return true;
    }

    //public function guardarPedido($numero)
    public function guardarPedido()
    {
        if (!$this->inicializar()) return false;
        if (!$this->ingresarCabecera()) return false;
        if (!$this->ingresaDetalle()) return false;
        if (!$this->ingresarModificadores()) return false;
        if (!$this->ingresarFormasPago()) return false;

        if (!$this->ingresarPedidoSistema()) return false;

        //Cierro la conexion a la BDD
        $this->conexionBDD->close();
        return true;
    }

    public function limpiarPedidoFallido(){
        $this->inicializar();
        $cabecera = $this->cabecera();
        $modificadores = collect($this->objSolicitudPedido->modificadores);
        $idsModificadores=$modificadores->pluck("detalleApp")->all();

        $this->conexionBDD->delete("FormasPago_App",['codigo_app'=>$cabecera->codigoApp]);
        $this->conexionBDD->executeQuery('DELETE FROM Modificadores_App WHERE detalle_app IN (?)',
                                         array($idsModificadores),
                                         array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY)
        );
        $this->conexionBDD->delete("Detalle_App",['codigo_app'=>$cabecera->codigoApp]);
        $this->conexionBDD->delete("Cabecera_App",['codigo_app'=>$cabecera->codigoApp]);
        return true;
    }

    private function inicializar()
    {
        $cabecera = $this->cabecera();

        $idRestauranteDomicilioENV=\Config::get('app.idbasedomicilio');

        if($idRestauranteDomicilioENV>0) $idRestaurante=$idRestauranteDomicilioENV;
        else $idRestaurante = $cabecera->codRestaurante;

        if (!$this->inicializarSQL($idRestaurante)) {
            return false;
        }
        return true;
    }

    private function inicializarSQL($idRestaurante)
    {
        $conexionBDD = DBHelpers::crearConexionBDDRestaurante($idRestaurante);
        if (!$conexionBDD) {
            $this->mensajeError = "Error al conectar a la base del Restaurante ID $idRestaurante";
            return false;
        }
        $this->conexionBDD = $conexionBDD;
        $this->sqlHelpers = new SqlHelpers($this->conexionBDD);
        return true;
    }

    private function validarDatos()
    {
        if (!$this->comprobarDetalle()) {
            return false;
        }
        Log::info("Validacion exitosa, detalles correctos");

        if (!$this->comprobarModificadores()) {
            return false;
        }
        Log::info("Validacion exitosa, modificadores correctos");

        if (!$this->comprobarFormasPago()) {
            return false;
        }
        Log::info("Validacion exitosa, formas de pago correctos");

        return true;
    }

    private function comprobarDetalle()
    {
        // La comprobación de campos obligatorios se ejecuto al recibir la petición,
        // ya no es necesario realizarla aqui

        if (!$this->detallesPertenecenCabecera()) {
            return false;
        }

        if (!$this->revisarMontosPedido()) {
            return false;
        }

        if ($this->existenDetallesAppBDD()) {
            return false;
        }

        $datosRecargoDomicilio = $this->buscarRecargoDomicilio();
        if (false === $datosRecargoDomicilio) {
            return false;
        }

        $respuesta = $datosRecargoDomicilio["respuesta"];
        $recargo = $datosRecargoDomicilio["recargo"];
        if(!is_numeric($recargo)){
            Log::error("No existe el plu de recargo");
            return false;
        }
        if($recargo==0){
            Log::error("No existe el plu de recargo");
            return false;
        }
        return true;
    }

    private function detallesPertenecenCabecera()
    {
        $cabecera = $this->cabecera();

        $codCabecera = trim($cabecera->codigoApp);
        $detalle = $this->objSolicitudPedido->detalle;
        foreach ($detalle as $det) {
            $codDetalle = trim($det->codigoApp);
            if (!($codCabecera === $codDetalle)) {
                $this->mensajeError = "No todos los productos corresponden a la cabecera del pedido";
                return false;
            }
        }
        return true;
    }

    private function revisarMontosPedido()
    {
        $cabecera = $this->cabecera();
        $totalCabecera = $cabecera->totalFactura;
        $detalles = $this->objSolicitudPedido->detalle;

        $totalDetalles = array_reduce(
            $detalles,
            function ($total, $detalle) {
                return $total += $detalle->precioBruto;
            }
        );

        if (!($totalDetalles == $totalCabecera)) {
            $this->mensajeError = "La suma de los productos no corresponde al total de la cabecera";
            return false;
        }

        return true;
    }

    private function existenDetallesAppBDD()
    {
        $detalles = $this->objSolicitudPedido->detalle;
        $IDsDetalles = array_map(
            function ($detalle) {
                return $detalle->detalleApp;
            },
            $detalles
        );

        $sqlQuery = "SELECT detalle_app FROM Detalle_App WHERE detalle_App in (?)";

        $res = $this->conexionBDD->executeQuery(
            $sqlQuery,
            array($IDsDetalles),
            array(Connection::PARAM_STR_ARRAY)
        )
            ->fetchAll();

        //Los detalles no existen en la BDD, continuar
        if (empty($res)) {
            return false;
        }

        //Los detalles ya existen en la BDD, detener
        $this->mensajeError = "Codigo(s) del detalle ya existen en la base de datos";
        return true;
    }

    private function buscarRecargoDomicilio()
    {
        $cabecera = $this->cabecera();
        $codCabecera = trim($cabecera->codigoApp);
        $codRestaurante = $cabecera->codRestaurante;
        $query = "EXEC App_DetalleApp_ValidaDatos :codCabecera , :codRestaurante , :recargo , :respuesta";

        $stmt = $this->conexionBDD->prepare($query);
        $stmt->bindParam("codCabecera", $codCabecera);
        $stmt->bindParam("codRestaurante", $codRestaurante);

        //Parametros de output, al ejecutar la consulta las variables se llenaran con los valores que retorna el SP
        $recargo = $respuesta = null;
        $stmt->bindParam("recargo", $recargo, ParameterType::INTEGER, 100);
        $stmt->bindParam("respuesta", $respuesta, ParameterType::STRING, 250);

        $stmt->execute();

        if ("200" !== $respuesta || $recargo == 0) {
            $this->mensajeError = $respuesta;
            return false;
        }

        return ["recargo" => $recargo, "respuesta" => $respuesta];
    }

    private function comprobarModificadores()
    {
        // La comprobación de campos obligatorios se ejecuto al recibir la petición,
        // ya no es necesario realizarla aqui

        //Separar los IDs de cada detalle
        $detalles = collect($this->objSolicitudPedido->detalle);
        $idsDetalles = $detalles->pluck('detalleApp');

        //Separar los detalles de los modificadores
        $modificadores = collect($this->objSolicitudPedido->modificadores);

        /*
         * Si el campo detalleApp de los modificadores no coincide con ningun
         * id de los detalles, detener.
         */
        foreach ($modificadores as $mod) {
            $existe = $idsDetalles->search($mod->detalleApp);

            if (false === $existe) {
                $this->mensajeError = "Los modificadores no pertenecen al mismo pedido";
                return false;
            }
        }
        return true;
    }

    private function comprobarFormasPago()
    {
        if (!$this->formasPagoPertenecenCabecera()) return false;

        if (!$this->totalFormasPagoCoincideCabecera()) {
            return false;
        }

        return true;
    }

    private function formasPagoPertenecenCabecera()
    {
        $cabecera = $this->cabecera();
        $codCabecera = trim($cabecera->codigoApp);
        $formasPago = $this->objSolicitudPedido->formasPago;
        if(is_array($formasPago)){
            foreach ($formasPago as $fp) {
                $codigoAppFormaPago = trim($fp->codigoApp);
                if (!($codCabecera === $codigoAppFormaPago)) {
                    $this->mensajeError = "No todas las formas de pago corresponden a la cabecera del pedido";
                    return false;
                }
            }
        }else{
            $codigoAppFormaPago = trim($formasPago->codigoApp);
            if (!($codCabecera === $codigoAppFormaPago)) {
                $this->mensajeError = "No todas las formas de pago corresponden a la cabecera del pedido";
                return false;
            }
        }
        return true;
    }

    private function totalFormasPagoCoincideCabecera()
    {
        $cabecera = $this->cabecera();
        $totalCabecera = $cabecera->totalFactura;
        $formasPago = $this->objSolicitudPedido->formasPago;

        if(is_array($formasPago)){
            $totalFormasPago = array_reduce(
                $formasPago,
                function ($total, $formaPago) {
                    return $total += $formaPago->totalPagar;
                }
            );
        }else{
            $totalFormasPago = $formasPago->totalPagar;
        }


        if (!($totalFormasPago == $totalCabecera)) {
            $this->mensajeError = "La suma de las formas de pago es distinta al total de la cabecera";
            return false;
        }

        return true;
    }


    private function ingresarCabecera()
    {
        $cabecera = $this->cabecera();

        // TODO: Mejorar la logica con la que se setea esta variable,
        // esta variable define si es necesario agregar o no el parametro
        // "codigo_pickup" antes de ejecutar el SP de insercion de cabecera
        $esBaseEcuador = \Config::get('app.idbasedomicilio');;
        $parametroCodigoPickup = (0==$esBaseEcuador)?":codigo_pickup,":"";

        $query = "EXEC App_CabeceraApp_IngresarDatos 
                :codigo_app, :cod_Restaurante,
                :fecha_Pedido, :telefono_cliente,
                :consumidor_final, :identificacion_cliente,
                :nombres_cliente, :direccion_cliente,
                :email_cliente, :calle1_domicilio,
                :calle2_domicilio, :observaciones_domicilio,
                :numDireccion_domicilio, :cod_ZipCode,
                :tipo_Inmueble, :total_Factura,
                :observacion_pedido, :transaccion,
                :medio, :dispositivo,
                $parametroCodigoPickup :respuesta ";

        $stmt = $this->conexionBDD->prepare($query);
        $codigoApp=isset($cabecera->codigoApp) ? $cabecera->codigoApp : null;
        $codRestaurante=isset($cabecera->codRestaurante) ? $cabecera->codRestaurante : null;
        $fechaPedido = isset($cabecera->fechaPedido) ? $cabecera->fechaPedido : null;
        $telefonoCliente=isset($cabecera->telefonoCliente) ? $cabecera->telefonoCliente : null;
        $consumidorFinal=isset($cabecera->consumidorFinal) ? $cabecera->consumidorFinal : null;
        $identificacionCliente=isset($cabecera->identificacionCliente) ? $cabecera->identificacionCliente : null;
        $nombresCliente=isset($cabecera->nombresCliente) ? $cabecera->nombresCliente : null;
        $direccionCliente=isset($cabecera->direccionCliente) ? $cabecera->direccionCliente : null;
        $emailCliente=isset($cabecera->emailCliente) ? $cabecera->emailCliente : null;
        $calle1Domicilio=isset($cabecera->calle1Domicilio) ? $cabecera->calle1Domicilio : null;
        $calle2Domicilio=isset($cabecera->calle2Domicilio) ? $cabecera->calle2Domicilio : null;
        $observacionesDomicilio=isset($cabecera->observacionesDomicilio) ? $cabecera->observacionesDomicilio : null;
        $numDirecciondomicilio=isset($cabecera->numDirecciondomicilio) ? $cabecera->numDirecciondomicilio : null;
        $codZipCode=isset($cabecera->codZipCode) ? $cabecera->codZipCode : null;
        $tipoInmueble = isset($cabecera->tipoInmueble) ? $cabecera->tipoInmueble : null;
        $totalFactura = isset($cabecera->totalFactura) ? $cabecera->totalFactura : null;
        $observacionesPedido = isset($cabecera->observacionesPedido) ? $cabecera->observacionesPedido : null;
        $transaccion = isset($cabecera->transaccion) ? $cabecera->transaccion : null;
        $medio = isset($cabecera->medio) ? $cabecera->medio : null;
        $dispositivo = isset($cabecera->dispositivo) ? $cabecera->dispositivo : null;
        $codigoPickup = isset($cabecera->codigoPickup) ? $cabecera->codigoPickup : "CODIGOPICKUPFS";
        $respuesta = null;

        $stmt->bindParam("codigo_app", $codigoApp);
        $stmt->bindParam("cod_Restaurante", $codRestaurante);
        $stmt->bindParam("fecha_Pedido", $fechaPedido);
        $stmt->bindParam("telefono_cliente", $telefonoCliente);
        $stmt->bindParam("consumidor_final", $consumidorFinal);
        $stmt->bindParam("identificacion_cliente", $identificacionCliente);
        $stmt->bindParam("nombres_cliente", $nombresCliente);
        $stmt->bindParam("direccion_cliente",$direccionCliente);
        $stmt->bindParam("email_cliente", $emailCliente);
        $stmt->bindParam("calle1_domicilio", $calle1Domicilio);
        $stmt->bindParam("calle2_domicilio", $calle2Domicilio);
        $stmt->bindParam("observaciones_domicilio",$observacionesDomicilio);
        $stmt->bindParam("numDireccion_domicilio",$numDirecciondomicilio);
        $stmt->bindParam("cod_ZipCode", $codZipCode);
        $stmt->bindParam("tipo_Inmueble", $tipoInmueble);
        $stmt->bindParam("total_Factura",$totalFactura);
        $stmt->bindParam("observacion_pedido",$observacionesPedido);
        $stmt->bindParam("transaccion", $transaccion);
        $stmt->bindParam("medio", $medio);
        $stmt->bindParam("dispositivo",$dispositivo);

        $stmt->bindParam("respuesta", $respuesta, ParameterType::STRING, 250);
        if((0==$esBaseEcuador)) $stmt->bindParam("codigo_pickup", $codigoPickup);

        $stmt->execute();

        // El SP retorna una cadena de caracteres NULL cuando se guarda el pedido correctamente
        // aqui "corrijo" ese comportamiento
        $result = strtolower(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $respuesta));

        if( empty($result) || "ok"== $result ) {
            return true;
        }

        $this->mensajeError = $result;
        return false;
    }

    private function ingresaDetalle()
    {
        $detalles=collect($this->objSolicitudPedido->detalle);

        $res = $detalles->map(function($det){
            try{
                $this->conexionBDD->insert(
                    "dbo.Detalle_App",
                    [
                        'codigo_app' => $det->codigoApp,
                        'detalle_app' => $det->detalleApp,
                        'cod_plu' => $det->codPlu,
                        'cantidad' => $det->cantidad,
                        'precio_Bruto' => $det->precioBruto,
                    ]
                );
            }catch(\Exception $ex){
                $this->mensajeError=$ex->getMessage();
                return false;
            }

            return true;
        });

        $hayFalsos=$res->contains(false);
        // Si en la coleccion $res hay algun falso, retornar false
        return !$hayFalsos;
    }

    private function ingresarModificadores()
    {
        $modificadores=collect($this->objSolicitudPedido->modificadores);

        $res = $modificadores->map(function($mod){
            try{
                $this->conexionBDD->insert(
                    "dbo.Modificadores_App",
                    [
                        'detalle_app' => $mod->detalleApp,
                        'cod_Modificador' => $mod->codModificador
                    ]
                );
            }catch(\Exception $ex){
                $this->mensajeError=$ex->getMessage();
                return false;
            }

            return true;
        });
        $hayFalsos=$res->contains(false);

        // Si en la coleccion $res hay algun falso, retornar false
        return !$hayFalsos;
    }

    private function ingresarFormasPago()
    {
        $fp = $this->objSolicitudPedido->formasPago;

        if(is_array($fp)){
            $formasPago = collect($fp);
        }else{
            $formasPago = collect()->add($fp);
        }

        $res = $formasPago->map(function($fp){
            try{
                $this->conexionBDD->insert(
                    "dbo.FormasPago_App",
                    [
                        'codigo_app' => $fp->codigoApp,
                        'cod_formaPago' => $fp->codformaPago,
                        'total_Pagar' => $fp->totalPagar,
                        'billete' => $fp->billete
                    ]
                );
            }catch(\Exception $ex){
                $this->mensajeError=$ex->getMessage();
                return false;
            }
            return true;
        });
        $hayFalsos=$res->contains(false);

        // Si en la coleccion $res hay algun falso, retornar false
        return !$hayFalsos;
    }

    private function ingresarPedidoSistema(){
        $cabecera = $this->cabecera();
        $codigoApp=isset($cabecera->codigoApp) ? $cabecera->codigoApp : null;

        $query = "EXEC App_IngresaPedidoSistema :codigoApp";
        $stmt = $this->conexionBDD->prepare($query);
        $stmt->bindParam("codigoApp", $codigoApp);
        try{
            $res = $stmt->execute();
        }catch(PDOException $ex){
            $this->mensajeError = $ex->getMessage();
            return false;
        }
        return $res;
    }

    private function cabecera(){
        $objPedido = $this->objSolicitudPedido;
        if(is_array($objPedido->cabecera)) return $objPedido->cabecera[0];
        return $objPedido->cabecera;
    }
}