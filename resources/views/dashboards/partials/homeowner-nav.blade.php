<a class="nav-link {{ request()->routeIs('homeowner.dashboard') ? 'active' : '' }}" href="{{ route('homeowner.dashboard') }}"><i class="bi bi-speedometer2"></i>Overview</a>
<a class="nav-link {{ request()->routeIs('homeowner.requests.index', 'homeowner.requests.show', 'homeowner.requests.edit') ? 'active' : '' }}" href="{{ route('homeowner.requests.index') }}"><i class="bi bi-clipboard-check"></i>My requests</a>
<a class="nav-link {{ request()->routeIs('homeowner.requests.create') ? 'active' : '' }}" href="{{ route('homeowner.requests.create') }}"><i class="bi bi-plus-circle"></i>New request</a>
<a class="nav-link" href="{{ route('handymen.index') }}"><i class="bi bi-search"></i>Find a handyman</a>
<a class="nav-link {{ request()->routeIs('account.*') ? 'active' : '' }}" href="{{ route('account.edit') }}"><i class="bi bi-person-gear"></i>Account settings</a>
