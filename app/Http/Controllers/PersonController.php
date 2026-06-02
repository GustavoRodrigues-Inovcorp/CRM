<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\Entity;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PersonController extends Controller
{
    public function index()
    {
        /* Carrega pessoas do utilizador autenticado com a entidade associada */
        $people = Person::where('user_id', auth()->id())
            ->with('entity')
            ->latest()
            ->get();

        /* Passa também a lista de entidades para o dropdown do modal */
        $entities = Entity::where('user_id', auth()->id())
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('People/Index', [
            'people'   => $people,
            'entities' => $entities,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'nullable|email|max:255',
            'phone'     => 'nullable|string|max:50',
            'mobile'    => 'nullable|string|max:50',
            'position'  => 'nullable|string|max:255',
            'entity_id' => 'nullable|exists:entities,id',
            'status'    => 'required|in:active,inactive',
            'notes'     => 'nullable|string',
        ]);

        Person::create([
            ...$validated,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Pessoa criada com sucesso.');
    }

    public function update(Request $request, Person $person)
    {
        /* Garante que só o dono pode editar */
        abort_if($person->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'nullable|email|max:255',
            'phone'     => 'nullable|string|max:50',
            'mobile'    => 'nullable|string|max:50',
            'position'  => 'nullable|string|max:255',
            'entity_id' => 'nullable|exists:entities,id',
            'status'    => 'required|in:active,inactive',
            'notes'     => 'nullable|string',
        ]);

        $person->update($validated);

        return back()->with('success', 'Pessoa atualizada.');
    }

    public function destroy(Person $person)
    {
        abort_if($person->user_id !== auth()->id(), 403);
        $person->delete();
        return back()->with('success', 'Pessoa eliminada.');
    }
}