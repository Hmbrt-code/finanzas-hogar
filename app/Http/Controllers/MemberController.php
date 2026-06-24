<?php

namespace App\Http\Controllers;

use App\Models\Household;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    // ── GET /members ──────────────────────────────────────────────
    public function index(): Response
    {
        $member    = auth()->user()->member;
        $household = $member->household;

        $members = $household->members()
            ->with('user:id,email')
            ->orderByRaw("CASE role WHEN 'admin' THEN 1 WHEN 'member' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->get()
            ->map(fn ($m) => [
                'id'         => $m->id,
                'name'       => $m->name,
                'role'       => $m->role,
                'avatar'     => $m->avatar,
                'user_email' => $m->user?->email,
                'is_self'    => $m->user_id === auth()->id(),
            ]);

        return Inertia::render('Members/Index', [
            'members'     => $members,
            'inviteCode'  => $household->invite_code,
            'inviteUrl'   => url('/join/' . $household->invite_code),
            'currentRole' => $member->role,
            'household'   => ['id' => $household->id, 'name' => $household->name],
        ]);
    }

    // ── PATCH /members/{member}/role ──────────────────────────────
    public function updateRole(Request $request, Member $member): RedirectResponse
    {
        $currentMember = auth()->user()->member;
        $household     = $currentMember->household;

        abort_if($currentMember->role !== 'admin', 403);
        abort_if($member->household_id !== $household->id, 403);

        $validated = $request->validate([
            'role' => 'required|in:admin,member,readonly',
        ]);

        // No puede auto-degradarse si es el único admin
        if ($member->id === $currentMember->id && $validated['role'] !== 'admin') {
            $adminCount = $household->members()->where('role', 'admin')->count();
            abort_if($adminCount <= 1, 422, 'No puedes degradarte si eres el único administrador.');
        }

        $member->update(['role' => $validated['role']]);

        return back();
    }

    // ── DELETE /members/{member} ──────────────────────────────────
    public function destroy(Member $member): RedirectResponse
    {
        $currentMember = auth()->user()->member;
        $household     = $currentMember->household;

        $isSelf  = $member->user_id === auth()->id();
        $isAdmin = $currentMember->role === 'admin';

        abort_if(! $isSelf && ! $isAdmin, 403);
        abort_if($member->household_id !== $household->id, 403);

        // No puede irse si es el único admin
        if ($isSelf && $member->role === 'admin') {
            $adminCount = $household->members()->where('role', 'admin')->count();
            abort_if($adminCount <= 1, 422, 'No puedes salir si eres el único administrador.');
        }

        $member->delete();

        if ($isSelf) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
            return redirect()->route('login');
        }

        return back();
    }

    // ── POST /members/regenerate-code ─────────────────────────────
    public function regenerateCode(): RedirectResponse
    {
        $currentMember = auth()->user()->member;
        abort_if($currentMember->role !== 'admin', 403);

        $currentMember->household->update([
            'invite_code' => Household::generateUniqueCode(),
        ]);

        return back();
    }

    // ── GET /join/{code} ──────────────────────────────────────────
    public function showJoin(string $code): Response|RedirectResponse
    {
        $household = Household::where('invite_code', $code)->firstOrFail();
        $user      = auth()->user();

        // Ya pertenece a este hogar
        if ($user->member?->household_id === $household->id) {
            return redirect()->route('members.index');
        }

        return Inertia::render('Members/Join', [
            'household'   => ['id' => $household->id, 'name' => $household->name],
            'inviteCode'  => $code,
            'currentUser' => ['name' => $user->name, 'email' => $user->email],
        ]);
    }

    // ── POST /join/{code} ─────────────────────────────────────────
    public function acceptJoin(string $code): RedirectResponse
    {
        $household = Household::where('invite_code', $code)->firstOrFail();
        $user      = auth()->user();

        // Ya pertenece a este hogar
        if ($user->member?->household_id === $household->id) {
            return redirect()->route('members.index');
        }

        $oldMember    = $user->member;
        $oldHousehold = $oldMember?->household;

        // Eliminar member record del hogar anterior
        $oldMember?->delete();

        // Crear nuevo member en el hogar destino
        Member::create([
            'household_id' => $household->id,
            'user_id'      => $user->id,
            'name'         => $user->name,
            'role'         => 'member',
        ]);

        // Si el hogar anterior quedó vacío, eliminarlo (categorías se cascade-delete)
        if ($oldHousehold && $oldHousehold->members()->count() === 0) {
            $oldHousehold->delete();
        }

        return redirect()->route('members.index');
    }
}
