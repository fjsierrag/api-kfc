@extends("layouts.admin")
@section("main")
    <div class="container">
    <h2>Jobs Fallidos</h2>
    <div class="table-responsive">
        <table class="table table-striped table-sm" id="tb-conexiones">
            <thead>
            <tr>
                <th>JOB</th>
                <th>INTENTOS</th>
                <th>FECHA ERROR</th>
                <th>ACCIONES</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($jobs as $registro)
                <tr class="fila"
                    data-id-job="{{$registro["failed_job_id"]}}"
                    data-tipo="{{$registro["tipo_job"]}}"
                    data-excepcion="{{$registro["excepcion"]}}"
                >
                    <td>{{$registro["tipo_job"]}}</td>
                    <td>{{$registro["intentos"]}}</td>

                    <td>{{$registro["fecha_fallo"]}}</td>
                    <td>
                        <button class="btn btn-sm btn-info bt-modificar">
                            Modificar
                        </button>
                        <button class="btn btn-sm btn-dark bt-excepcion">
                            Ver error
                        </button>
                        <button class="btn btn-sm btn-dark bt-reintentar">
                            Reintentar
                        </button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="modal fade" id="modal-editar" data-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modificar petición de pedido</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="container">
                           <div id="contenedor-ace" style="height:350px"></div>
                    </div>
                </div><!-- /.container -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btn-ok-modificar">OK</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
    </div>
@endsection
@section("js")
    <script src="{{asset("js/plugins/ace-editor/ace.js")}}"></script>
    <script>
        var aceEditor = ace.edit("contenedor-ace", {
            mode: "ace/mode/json"
        });
        var $tablaConexiones=$("#tb-conexiones");
        $modalEditar = $('#modal-editar');
        $btnGuardarConexion = $("#btn-guardar-conexion");
        $tablaConexiones.on("click", "button.bt-modificar", function () {
            var $this = $(this);
            var dataJob = buscarDataJob($this);
            var idJobFallido=dataJob.idJob;
            if("App\\Jobs\\Domicilio\\InsertarPedido"==dataJob.tipo){
                var peticionBuscarJSON = $.ajax({
                    url: "{{ route("admin.json-job") }}",
                    type: "GET",
                    data: {idJobFallido:idJobFallido},
                    success: function (response) {
                        var estado=response.estado;
                        if("error"===estado) {
                            alertify.alert(response.mensaje);
                            return false;
                        }
                        aceEditor.setValue(response.contenido);
                        $("#modal-editar").modal("show");
                    },
                    error: function(error){}
                });
            }
            else{
                alertify.alert("No editable");
            }
        });

        $modalEditar.on("hide.bs.modal", limpiarModalConexion);

        $("#tb-conexiones").on("click", "button.bt-reintentar", function () {
            var $this = $(this);
            var dataJob = buscarDataJob($this);
            var idJobFallido=dataJob.idJob;

            var alerta=alertify.alert("Información","Enviando...")
                .set({
                    closable: false,
                    onshow:null,
                    onclose:function(){
                        this.setContent("Enviando...");
                    }
                });

            var peticionAjax = $.ajax({
                url: "{{ route("admin.reintentar-job") }}",
                type: "GET",
                data: {idJobFallido:idJobFallido},
                beforeSend: function () {},
                success: function (response) {
                    var estado=response.estado
                    var tituloModal = ("ok" === estado)?"Correcto":"Error";
                    alerta.set({
                        title:tituloModal
                    }).setContent(response.mensaje);
                    if("ok" === estado) location.reload();
                },
                error: function(error){}
            });
            peticionAjax.always(function () {});

        });

        $("#tb-conexiones").on("click", "button.bt-excepcion", function () {
            var $this = $(this);
            var dataJob = buscarDataJob($this);

            alertify.alert().setting({
                'title':"Información",
                'message': "<p style='font-family: sans-serif;font-size: 14px'>"+dataJob.excepcion ,
                'transition':'fade'
            }).show();
        });

        function limpiarModalConexion() {
            aceEditor.session.setValue("");
        }

        function buscarDataJob($target) {
            let parentData = $target.closest("tr.fila").data();
            return parentData || null;
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