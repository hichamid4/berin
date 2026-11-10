<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use Illuminate\Contracts\View\View;

class SkillController extends Controller
{
    public function index(): View
    {
        $skills = Skill::query()
            ->withCount(['services', 'handymen'])
            ->orderBy('name')
            ->get();

        return view('admin.skills.index', compact('skills'));
    }
}
