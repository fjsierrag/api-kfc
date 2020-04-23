<nav class="col-md-2 d-none d-md-block bg-light sidebar">
    <div class="sidebar-sticky">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.inicio') ? 'active' : '' }}" href="{{ route("admin.inicio")}}" >
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.locales') ? 'active' : '' }}" href="{{ route("admin.locales")}}" >
                    Locales
                </a>
            </li>
            <li class="nav-item">
                <a class="btn btn-block btn-outline-danger" href="{{ route("logout-basic")}}" role="button">
                    Salir
                </a>
            </li>
        </ul>
    </div>
</nav>