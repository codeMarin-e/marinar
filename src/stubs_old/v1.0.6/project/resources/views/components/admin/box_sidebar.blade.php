<ul class="sidebar navbar-nav">
    {{--   DASHBOARD --}}
    <li class="nav-item @if(request()->route()->named("{$whereIam}.home")) active @endif">
        <a class="nav-link " href="{{route("{$whereIam}.home")}}">
            <i class="fa fa-fw fa-tachometer-alt mr-1"></i>
            <span>@lang("admin/box_sidebar.dashboard")</span>
        </a>
    </li>

</ul>
