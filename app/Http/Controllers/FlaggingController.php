<?php

namespace App\Http\Controllers;

use App\Services\MenuVisibilityService;
use Illuminate\Http\Request;

class FlaggingController extends Controller
{
    public function index(Request $request, MenuVisibilityService $menuVisibilityService)
    {
        $this->authorizeFlagging($request);

        return view('flagging.index', [
            'flaggableMenus' => $menuVisibilityService->flaggableMenus(),
            'hiddenMenus' => $menuVisibilityService->hiddenMenus(),
            'token' => $request->query('token'),
            'secretConfigured' => (bool) config('hris.flagging_secret'),
        ]);
    }

    public function update(Request $request, MenuVisibilityService $menuVisibilityService)
    {
        $this->authorizeFlagging($request);

        if ($request->input('action') === 'reset') {
            $menuVisibilityService->resetToConfig();

            return redirect()->route('flagging.index', ['token' => $request->query('token')])
                ->with('success', 'Status menu dikembalikan ke config default.');
        }

        $menuVisibilityService->replaceHiddenMenus($request->input('hidden_menus', []));

        return redirect()->route('flagging.index', ['token' => $request->query('token')])
            ->with('success', 'Status menu berhasil diperbarui.');
    }

    private function authorizeFlagging(Request $request): void
    {
        abort_unless($request->user()?->hasRole('admin'), 403, 'Hanya admin yang dapat membuka flagging.');

        $secret = (string) config('hris.flagging_secret');
        if ($secret !== '') {
            abort_unless(hash_equals($secret, (string) $request->query('token')), 403, 'Token flagging tidak valid.');
        }
    }
}
