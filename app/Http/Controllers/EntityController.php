<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EntityController extends Controller
{
    public function index()
    {
        $entities = Entity::where('user_id', auth()->id())
            ->latest()
            ->get();

        return Inertia::render('Entities/Index', [
            'entities' => $entities,
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

        Entity::create([
            ...$validated,
            'user_id' => auth()->id(),
        ]);

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