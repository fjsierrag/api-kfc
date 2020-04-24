<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">

        <link rel="stylesheet" href="{{asset("css/app.css")}}">
        <link rel="stylesheet" href="{{asset("js/plugins/bootstrap/css/bootstrap.css")}}">
        <link rel="stylesheet" href="{{asset("js/plugins/alertifyjs/css/alertify.min.css")}}">
        <link rel="stylesheet" href="{{asset("js/plugins/alertifyjs/css/themes/default.min.css")}}">
        <link rel="stylesheet" href="{{asset("js/plugins/alertifyjs/css/themes/bootstrap.min.css")}}">

        <script src="{{asset("js/jquery-3.5.0.js")}}"></script>
    </head>
    <body>
    <nav class="navbar navbar-dark fixed-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-sm-3 col-md-2 mr-0" href="{{ route("home")}}" >KFC - Domicilio</a>
        <ul class="navbar-nav px-3">
            <li class="nav-item text-nowrap">
                <a class="nav-link" href="{{ route("logout-basic")}}">Salir <i data-feather="log-out"></i></a>
            </li>
        </ul>
    </nav>
    <div class="container-fluid">
        <div class="row">
            @include('partials.navegacion-izquierda')
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4">
               @yield("main")
            </main>
        </div>
    </div>
    <script src="{{asset("js/plugins/bootstrap/js/bootstrap.min.js")}}"></script>
    <script src="{{asset("js/plugins/feather-icons/feather-icons.min.js")}}"></script>
    <script src="{{asset("js/plugins/alertifyjs/alertify.js")}}"></script>
    @yield("js")
    <script>
        feather.replace();
    </script>
    </body>
</html>
