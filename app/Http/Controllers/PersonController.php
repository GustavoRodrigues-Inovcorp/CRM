<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\Entity;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PersonController extends Controller
{
    public function index(Request $request)
    {
        $query = Person::where('user_id', auth()->id())
            ->with('entity:id,name')
            ->withCount('deals');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('position', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $people = $query->latest()->get();

        $entities = Entity::where('user_id', auth()->id())
            ->orderBy('name')->get(['id', 'name']);

        return Inertia::render('People/Index', [
            'people'   => $people,
            'entities' => $entities,
            'filters'  => [
                'search' => $request->search ?? '',
                'status' => $request->status ?? '',
            ],
        ]);
    }

    public function show(Person $person)
    {
        abort_if($person->user_id !== auth()->id(), 403);

        $person->load([
            'entity',
            'deals' => fn($q) => $q->with('user:id,name')->latest(),
        ]);

        /* ─── Eventos do calendário associados a esta pessoa ─── */
        $events = \App\Models\CalendarEvent::where('user_id', auth()->id())
            ->where('eventable_type', \App\Models\Person::class)
            ->where('eventable_id', $person->id)
            ->latest('start_at')
            ->get(['id', 'title', 'type', 'start_at', 'completed']);

        return Inertia::render('People/Show', [
            'person' => $person,
            'events' => $events,
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

        Person::create([...$validated, 'user_id' => auth()->id()]);

        return back()->with('success', 'Pessoa criada com sucesso.');
    }

    public function update(Request $request, Person $person)
    {
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