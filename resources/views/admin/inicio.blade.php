@extends("layouts.admin")
@section("main")
    <div class="container">
        <div class="card" style="width: 18rem;">
            <div class="card-body">
                <h5 class="card-title">Conexiones</h5>
                <p class="card-text">Administración de datos de conexión a las bases de datos de los locales que tienen activo el servicio de domicilio.</p>
                <a href="{{ route("admin.locales")}}" class="btn btn-outline-primary btn-block">Ir</a>
            </div>
        </div>
        <div class="card" style="width: 18rem;">
            <div class="card-body">
                <h5 class="card-title">Jobs Fallidos</h5>
                <p class="card-text">Listado de Jobs que no pudieron ser correctamente ejecutados.</p>
                <a href="{{ route("admin.jobs-fallidos")}}" class="btn btn-outline-primary btn-block">Ir</a>
            </div>
        </div>
    </div>
@endsection
