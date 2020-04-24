<nav class="col-md-2 d-none d-md-block bg-light sidebar">
    <div class="sidebar-sticky">
        <ul class="nav flex-column">
            <li class="nav-item">

                <a class="nav-link {{ request()->routeIs('admin.inicio') ? 'active' : '' }}"
                   href="{{ route("admin.inicio")}}">
                    <i data-feather="home"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.locales') ? 'active' : '' }}"
                   href="{{ route("admin.locales")}}">
                    <i data-feather="edit"></i> Locales
                </a>
            </li>
            <li class="nav-item">
                <a class="btn btn-block btn-outline-danger" href="{{ route("logout-basic")}}" role="button">
                    Salir <i data-feather="log-out"></i>
                </a>
            </li>
        </ul>
    </div>
</nav>