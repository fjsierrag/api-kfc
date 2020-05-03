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
            "cabecera" => "bail|required",
            "detalle" => "bail|required|array|min:1",
            "modificadores" => "bail|nullable|array",
            "formasPago" => "bail|required",

            //Detalle
            "detalle.*.codigoApp" => "required|string",
            "detalle.*.detalleApp" => "required",
            "detalle.*.codPlu" => "required|integer",
            "detalle.*.cantidad" => "required|numeric",
            "detalle.*.precioBruto" => "required|numeric",

            //Modificadores
            "modificadores.*.detalleApp" => "required",
            "modificadores.*.codModificador" => "required|integer",

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
