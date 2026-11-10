<a class="nav-link {{ request()->routeIs('handyman.dashboard') ? 'active' : '' }}" href="{{ route('handyman.dashboard') }}"><i class="bi bi-speedometer2"></i>Overview</a>
<a class="nav-link {{ request()->routeIs('handyman.requests.*', 'handyman.offers.*') ? 'active' : '' }}" href="{{ route('handyman.requests.index') }}"><i class="bi bi-bullseye"></i>Matching requests</a>
<a class="nav-link" href="{{ route('handymen.show', $user->id) }}"><i class="bi bi-person-badge"></i>My public profile</a>
<a class="nav-link {{ request()->routeIs('account.*') ? 'active' : '' }}" href="{{ route('account.edit') }}"><i class="bi bi-person-gear"></i>Account &amp; skills</a>
