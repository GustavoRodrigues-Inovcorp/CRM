<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class ProfileController extends Controller
{
    /* Página de perfil */
    public function edit(Request $request)
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail,
            'status'          => session('status'),
            'twoFactorEnabled' => $request->user()->two_factor_enabled,
        ]);
    }

    /* Atualizar informações básicas */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $request->user()->id,
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_photo' => 'nullable|boolean',
        ]);

        $user = $request->user();
        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        // If a new photo was uploaded, store it (takes precedence).
        if ($request->hasFile('photo')) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $data['profile_photo_path'] = $request->file('photo')->store('profile-photos', 'public');
        } elseif (!empty($validated['remove_photo']) && $request->boolean('remove_photo')) {
            // If client requested removal (and didn't upload a new one), delete existing.
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $data['profile_photo_path'] = null;
        }

        $user->update($data);

        return back()->with('status', 'profile-updated');
    }

    /* Atualizar password */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'password'         => 'required|min:8|confirmed',
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'password-updated');
    }

    /* Gerar QR Code para ativar 2FA */
    public function setupTwoFactor(Request $request)
    {
        $google2fa = new Google2FA();
        $google2fa->setWindow((int) config('google2fa.window', 1));

        // Start from a clean setup state each time a new QR is requested.
        session()->forget('2fa_secret');

        /* Gera chave secreta */
        $secret = $google2fa->generateSecretKey();

        /* Guarda temporariamente na sessão */
        session(['2fa_secret' => $secret]);

        /* Gera o QR Code em SVG */
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $request->user()->email,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        $writer   = new Writer($renderer);
        $qrCode   = base64_encode($writer->writeString($qrCodeUrl));

        return response()->json([
            'secret' => $secret,
            'qrCode' => 'data:image/svg+xml;base64,' . $qrCode,
        ]);
    }

    /* Confirmar e ativar 2FA */
    public function enableTwoFactor(Request $request)
    {
        $request->validate([
            'code'   => 'required|string|size:6',
        ]);

        $google2fa = new Google2FA();
        $google2fa->setWindow((int) config('google2fa.window', 1));

        /* Prefer server-side secret stored in session to avoid tampering or desync */
        $secret = session('2fa_secret') ?? $request->input('secret');

        if (!$secret) {
            return back()->withErrors(['code' => 'Segredo 2FA em falta. Tenta novamente.']);
        }

        /* Verifica o código introduzido */
        $valid = $google2fa->verifyKey($secret, $request->code);

        if (!$valid) {
            return back()->withErrors(['code' => 'Código inválido. Tenta novamente.']);
        }

        /* Ativa o 2FA */
        $request->user()->update([
            'google2fa_secret'   => $secret,
            'two_factor_enabled' => true,
        ]);

        session()->forget('2fa_secret');

        return back()->with('status', '2fa-enabled');
    }

    /* Desativar 2FA */
    public function disableTwoFactor(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password',
        ]);

        $request->user()->update([
            'google2fa_secret'   => null,
            'two_factor_enabled' => false,
        ]);

        session()->forget('2fa_secret');

        return back()->with('status', '2fa-disabled');
    }

    /* Eliminar conta */
    public function destroy(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password',
        ]);

        $user = $request->user();
        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /* Remover foto de perfil */
    public function destroyPhoto(Request $request)
    {
        $user = $request->user();

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->profile_photo_path = null;
            $user->save();
        }

        return back()->with('status', 'photo-removed');
    }
}