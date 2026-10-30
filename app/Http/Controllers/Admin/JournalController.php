<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JournalActivite;
use Illuminate\Http\Request;

class JournalController extends Controller
{
    public function index(Request $request)
    {
        $entrees = JournalActivite::with('user')
            ->when($request->filled('action'), fn ($q) => $q->where('action', 'like', $request->string('action').'%'))
            ->when($request->filled('q'), fn ($q) => $q->where('description', 'like', '%'.$request->string('q').'%'))
            ->latest('created_at')->paginate(40)->withQueryString();

        $actions = JournalActivite::query()->selectRaw('DISTINCT action')->orderBy('action')->pluck('action')
            ->map(fn ($a) => explode('.', $a)[0])->unique()->values();

        return view('admin.journal', compact('entrees', 'actions'));
    }
}
