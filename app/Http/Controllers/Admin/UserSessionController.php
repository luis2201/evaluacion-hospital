<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class UserSessionController extends Controller
{
    public function destroy(User $user, AuditService $audit): RedirectResponse
    {
        $this->authorize('update', $user);
        DB::table('sessions')->where('user_id', $user->id)->delete();
        $audit->record('SESIONES_REVOCADAS', 'users', $user->id);

        return back()->with('status', 'Las sesiones del usuario fueron revocadas.');
    }
}
