@extends("layouts.admin")
@section("main")
    <div class="container">
    <h2>Jobs Fallidos</h2>
    <div class="table-responsive">
        <table class="table table-striped table-sm" id="tb-conexiones">
            <thead>
            <tr>
                <th>CONEXION</th>
                <th>COLA</th>
                <th>PAYLOAD</th>
                <th>EXCEPCIÓN</th>
                <th>FECHA ERROR</th>
                <th>ACCIONES</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($jobs as $registro)
                <tr class="fila" data-id-job="{{$registro->id}}" >
                    <td>@if(isset($registro["IDTienda"])){{$registro["IDTienda"]}}@endif</td>
                    <td>@if(isset($registro["Nombre"])){{$registro["Nombre"]}}@endif</td>
                    <td>@if(isset($registro["Nombre_Servidor"])){{$registro["Nombre_Servidor"]}}@endif</td>
                    <td>@if(isset($registro["Instancia"])){{$registro["Instancia"]}}@endif</td>
                    <td>@if(isset($registro["Puerto"])){{$registro["Puerto"]}}@endif</td>
                    <td>
                        <button class="btn btn-sm btn-dark bt-editar" data-toggle="modal" data-target="#modal-editar">
                            Editar
                        </button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="modal fade" id="modal-editar" data-backdrop="static" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar datos conexión</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="container">
                        <div class="inputs-formulario">
                            <div class="form-group row">
                                <label for="conexion-servidor" class="col-4 col-form-label">Servidor</label>
                                <div class="col-8">
                                    <div class="input-group">
                                        <input id="conexion-servidor" name="conexion-servidor" type="text"
                                               class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="conexion-instancia" class="col-4 col-form-label">Instancia</label>
                                <div class="col-8">
                                    <input id="conexion-instancia" name="conexion-instancia" type="text"
                                           class="form-control">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="conexion-puerto" class="col-4 col-form-label">Puerto</label>
                                <div class="col-8">
                                    <input id="conexion-puerto" name="conexion-puerto" type="number" step="1"
                                           class="form-control">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="conexion-bdd" class="col-4 col-form-label">Nombre BDD</label>
                                <div class="col-8">
                                    <input id="conexion-bdd" name="conexion-bdd" type="text" class="form-control">
                                </div>
                            </div>
                            <div class="form-group row">
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="editar" id="checkEditarUsuarioClave">
                                        <label class="form-check-label" for="checkEditarUsuarioClave">
                                            Editar usuario y clave de conexión
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div id="camposUsuarioClave" style="display:none">
                            <div class="form-group row">
                                <label for="conexion-usuario" class="col-4 col-form-label">Usuario</label>
                                <div class="col-8">
                                    <input id="conexion-usuario" name="conexion-usuario" type="text"
                                           class="form-control">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="conexion-clave" class="col-4 col-form-label">Clave</label>
                                <div class="col-8">
                                    <input id="conexion-clave" name="conexion-clave" type="password"
                                           class="form-control">
                                </div>
                            </div>
                            </div>
                        </div>
                        <div class="mensaje-guardando" style="display:none">
                            <p>
                                Probando datos de conexion y guardando... <br>
                                Este proceso puede tardar hasta 1 minuto.
                            </p>
                        </div>
                    </div>
                </div><!-- /.container -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btn-guardar-conexion">Guardar</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
    </div>
@endsection
@section("js")
    <script>
        var restauranteActivo = 0;
        $modalEditar = $('#modal-editar');
        $btnGuardarConexion = $("#btn-guardar-conexion");

        $modalEditar.on("show.bs.modal", function (event) {
            var button = $(event.relatedTarget);
            var dataTienda = buscarDataRestaurante(button);
            console.log(dataTienda);
            restauranteActivo = dataTienda.idRestaurante;
            $("#conexion-servidor").val(dataTienda.servidor.trim());
            $("#conexion-instancia").val(dataTienda.instancia.trim());
            $("#conexion-puerto").val(parseInt(dataTienda.puerto.trim()));
            $("#conexion-bdd").val(dataTienda.bdd.trim());
        });

        $modalEditar.on("hide.bs.modal", limpiarModalConexion);

        $btnGuardarConexion.on("click", guardarConexion);

        $("#checkEditarUsuarioClave").on("click", mostrarOcultarCamposUsuariosClave);
        function buscarDataRestaurante($target) {
            let parentData = $target.closest("tr.fila").data();
            return parentData || null;
        }

        function limpiarModalConexion() {
            $("#conexion-servidor").val("");
            $("#conexion-instancia").val("");
            $("#conexion-puerto").val("");
            $("#conexion-bdd").val("");
            $("#conexion-usuario").val("");
            $("#conexion-clave").val("");
            $("#checkEditarUsuarioClave").attr("checked",false);
        }

        function guardarConexion() {
            var data = {
                codTienda: restauranteActivo,
                servidor: $("#conexion-servidor").val(),
                instancia: $("#conexion-instancia").val(),
                puerto: $("#conexion-puerto").val(),
                nombreBDD: $("#conexion-bdd").val(),
                editarUsuarioClave: $("#checkEditarUsuarioClave").is(":checked"),
                usuario: $("#conexion-usuario").val(),
                clave: $("#conexion-clave").val(),
            };

            var peticionAjax = $.ajax({
                url: "{{ route("admin.guardar-conexion") }}",
                type: "GET",
                data: data,
                beforeSend: function () {
                    deshabilitarModalEditar();
                },
                success: function (response) {
                    var conexionBDD = response.conexionbdd;
                    if (!conexionBDD.conecta) {
                        alertify.alert(
                            "Datos inválidos",
                            "Los datos de conexión no son válidos: <br/>" + conexionBDD.error
                        );
                        return false;
                    }
                    $modalEditar.modal("hide");
                    return true;
                }
            });
            peticionAjax.always(function () {
                habilitarModalEditar();
            });
        }

        function deshabilitarModalEditar() {
            //Deshabilitar botones de cierre
            var $btnCerrarModal = $("#modal-editar").find("button.close");
            var $btnsFooter = $("#modal-editar .modal-footer").find("button");

            $btnCerrarModal.addClass("disabled");
            $btnCerrarModal.attr("disabled", true);
            $btnsFooter.addClass("disabled");
            $btnsFooter.attr("disabled", true);

            //Ocultar inputs y mostrar mensaje
            $("#modal-editar").find(".inputs-formulario").hide();
            $("#modal-editar").find(".mensaje-guardando").show();
        }

        function habilitarModalEditar() {
            var $btnCerrarModal = $("#modal-editar").find("button.close");
            var $btnsFooter = $("#modal-editar .modal-footer").find("button");

            $btnCerrarModal.removeClass("disabled");
            $btnCerrarModal.attr("disabled", false);

            $btnsFooter.removeClass("disabled");
            $btnsFooter.attr("disabled", false);

            // Ocultar inputs y mostrar mensaje
            $("#modal-editar").find(".inputs-formulario").show();
            $("#modal-editar").find(".mensaje-guardando").hide();
        }

        function mostrarOcultarCamposUsuariosClave(evt){
            var $target=$(evt.target);
            var activo=$target.is(":checked");

            if(true===activo){
                $("#camposUsuarioClave").fadeIn(300);
            }else{
                $("#camposUsuarioClave").fadeOut(300);
            }
        }
    </script>
@endsection