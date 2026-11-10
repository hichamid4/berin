<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\UserRole;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $role = UserRole::tryFrom($request->string('role')->toString());
        $status = $request->string('status')->toString();
        $search = trim($request->string('q')->toString());

        $users = User::query()
            ->withTrashed()
            ->with(['homeownerProfile', 'handymanProfile'])
            ->when($role, fn (Builder $query) => $query->where('role', $role))
            ->when(in_array($status, ['active', 'blocked', 'anonymized'], true), fn (Builder $query) => $query->where('status', $status))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'role', 'status', 'search'));
    }
}
