<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            /* ─── Dados globais para pesquisa ⌘K ─── */
            'entities' => $request->user()
                ? \App\Models\Entity::where('user_id', $request->user()->id)
                    ->get(['id', 'name', 'email', 'phone'])
                : [],
            'people' => $request->user()
                ? \App\Models\Person::where('user_id', $request->user()->id)
                    ->with('entity:id,name')
                    ->get(['id', 'name', 'email', 'entity_id'])
                : [],
            'deals' => $request->user()
                ? \App\Models\Deal::where('user_id', $request->user()->id)
                    ->get(['id', 'title', 'value', 'stage'])
                : [],
        ];
    }
}
