<?php

namespace App\Http\Controllers;

use App\Enums\CodigoRol;
use App\Models\Auditoria;
use App\Models\ReporteDescarga;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isAdministrator() || $request->user()->hasRole(CodigoRol::AuditorLectura), 403);
        $audits = Auditoria::query()->with('usuario')
            ->when($request->filled('accion'), fn ($query) => $query->where('accion', $request->string('accion')))
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('desde'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('desde')))
            ->when($request->filled('hasta'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('hasta')))
            ->latest('created_at')->paginate(30)->withQueryString();
        $downloads = ReporteDescarga::query()->with(['usuario', 'evaluacion'])->latest('descargado_at')->limit(15)->get();
        $users = User::query()->orderBy('name')->get(['id', 'name']);
        $actions = Auditoria::query()->distinct()->orderBy('accion')->pluck('accion');

        return view('audit.index', compact('audits', 'downloads', 'users', 'actions'));
    }
}
