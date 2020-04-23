<?php

namespace App\Http\Requests\Domicilio;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;

class InsertarPedidoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            // Estructura
            "cabecera" => "bail|required|array|max:1",
            "detalle" => "bail|required|array|min:1",
            "modificadores" => "bail|nullable|array",
            "formasPago" => "bail|required|array|min:1",

            // Cabecera
            "cabecera.0.codigoApp" => "required|string",
            "cabecera.0.codRestaurante" => "required|integer",
            "cabecera.0.fechaPedido" => "required|string",
            "cabecera.0.telefonoCliente" => "required|string",
            "cabecera.0.nombresCliente" => "required|string",
            "cabecera.0.calle1Domicilio" => "required|string",
            "cabecera.0.calle2Domicilio" => "required|string",
            "cabecera.0.observacionesDomicilio" => "required|string",
            "cabecera.0.numDirecciondomicilio" => "required|string",
            "cabecera.0.codZipCode" => "required|string",
            "cabecera.0.tipoInmueble" => "required|integer",
            "cabecera.0.totalFactura" => "required|numeric",
            "cabecera.0.observacionesPedido" => "nullable|string",
            "cabecera.0.transaccion" => "nullable|string",
            "cabecera.0.medio" => "nullable|string",
            "cabecera.0.dispositivo" => "nullable|string",

            "cabecera.0.consumidorFinal" => "required|boolean",
            "cabecera.0.identificacionCliente" => "required_if:cabecera.0.consumidorFinal,false|string|nullable",
            "cabecera.0.direccionCliente" => "required_if:cabecera.0.consumidorFinal,false|string|nullable",
            "cabecera.0.emailCliente" => "required_if:cabecera.0.consumidorFinal,false|email|nullable",
            //Detalle
            "detalle.*.codigoApp" => "required|string",
            "detalle.*.detalleApp" => "required",
            "detalle.*.codPlu" => "required|integer",
            "detalle.*.cantidad" => "required|numeric",
            "detalle.*.precioBruto" => "required|numeric",

            //Modificadores
            "modificadores.*.detalleApp" => "required",
            "modificadores.*.codModificador" => "required|integer",

            //Formas de pago
            "formasPago.*.codigoApp" => "required|string",
            "formasPago.*.codformaPago" => "required|integer",
            "formasPago.*.totalPagar" => "required|numeric",
            "formasPago.*.billete" => "nullable|numeric",
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw (new ValidationException($validator, $this->buildResponse($validator)));
    }

    protected function buildResponse(Validator $validator)
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
}
