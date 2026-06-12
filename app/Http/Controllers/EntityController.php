<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use App\Models\Person;
use App\Models\Deal;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EntityController extends Controller
{
    public function index(Request $request)
    {
        /* ─── Filtros de pesquisa ─── */
        $query = Entity::where('user_id', auth()->id())
            ->withCount(['people', 'deals']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('vat', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $entities = $query->latest()->get();

        return Inertia::render('Entities/Index', [
            'entities' => $entities,
            'filters'  => [
                'search' => $request->search ?? '',
                'status' => $request->status ?? '',
            ],
        ]);
    }

    public function show(Entity $entity)
    {
        abort_if($entity->user_id !== auth()->id(), 403);

        $entity->load([
            'people',
            'deals' => fn($q) => $q->with('user:id,name')->latest(),
        ]);

        return Inertia::render('Entities/Show', [
            'entity' => $entity,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'vat'     => 'nullable|string|max:50',
            'email'   => 'nullable|email|max:255',
            'phone'   => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'status'  => 'required|in:active,inactive,prospect',
        ]);

        Entity::create([...$validated, 'user_id' => auth()->id()]);

        return back()->with('success', 'Entidade criada com sucesso.');
    }

    public function update(Request $request, Entity $entity)
    {
        abort_if($entity->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'vat'     => 'nullable|string|max:50',
            'email'   => 'nullable|email|max:255',
            'phone'   => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'status'  => 'required|in:active,inactive,prospect',
        ]);

        $entity->update($validated);

        return back()->with('success', 'Entidade atualizada.');
    }

    public function destroy(Entity $entity)
    {
        abort_if($entity->user_id !== auth()->id(), 403);
        $entity->delete();
        return back()->with('success', 'Entidade eliminada.');
    }
}