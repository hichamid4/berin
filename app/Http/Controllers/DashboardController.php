<?php

namespace App\Http\Controllers;

use App\UserRole;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function show(string $role): View
    {
        $role = UserRole::tryFrom($role);
        abort_unless($role && request()->user()->role === $role, 403);

        return view('dashboards.'.$role->value, ['user' => request()->user()]);
    }
}
