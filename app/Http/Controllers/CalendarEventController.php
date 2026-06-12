<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\Entity;
use App\Models\Person;
use App\Models\Deal;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CalendarEventController extends Controller
{
    public function index()
    {
        $events = CalendarEvent::where('user_id', auth()->id())
            ->with('eventable')
            ->get()
            ->map(fn($e) => [
                'id'    => $e->id,
                'title' => $e->title,
                'start' => $e->start_at->format('Y-m-d\TH:i:s'),
                'end'   => $e->end_at?->format('Y-m-d\TH:i:s'),
                'color' => $e->color ?? $this->typeColor($e->type),
                'extendedProps' => [
                    'type'           => $e->type,
                    'description'    => $e->description,
                    'location'       => $e->location,
                    'completed'      => $e->completed,
                    'eventable_type' => match($e->eventable_type) {
                        Entity::class => 'entity',
                        Person::class => 'person',
                        Deal::class   => 'deal',
                        default       => null,
                    },
                    'eventable_id'   => $e->eventable_id,
                    'eventable_name' => $e->eventable?->name ?? $e->eventable?->title ?? null,
                ],
            ]);

        $entities = Entity::where('user_id', auth()->id())->orderBy('name')->get(['id', 'name']);
        $people   = Person::where('user_id', auth()->id())->orderBy('name')->get(['id', 'name']);
        $deals    = Deal::where('user_id', auth()->id())->orderBy('title')->get(['id', 'title']);

        return Inertia::render('Calendar/Index', [
            'events'   => $events,
            'entities' => $entities,
            'people'   => $people,
            'deals'    => $deals,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'type'            => 'required|in:meeting,call,task,email',
            'start_at'        => 'required|date',
            'end_at'          => 'nullable|date',
            'location'        => 'nullable|string|max:255',
            'eventable_type'  => 'nullable|in:entity,person,deal',
            'eventable_id'    => 'nullable|integer',
        ]);

        if (!empty($validated['eventable_type'])) {
            $validated['eventable_type'] = match($validated['eventable_type']) {
                'entity' => Entity::class,
                'person' => Person::class,
                'deal'   => Deal::class,
            };
        }

        CalendarEvent::create([
            ...$validated,
            'user_id' => auth()->id(),
            'color'   => $this->typeColor($validated['type']),
        ]);

        return back()->with('success', 'Evento criado.');
    }

    public function update(Request $request, CalendarEvent $calendarEvent)
    {
        abort_if($calendarEvent->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'type'        => 'required|in:meeting,call,task,email',
            'start_at'    => 'required|date',
            'end_at'      => 'nullable|date',
            'location'    => 'nullable|string|max:255',
            'completed'   => 'boolean',
        ]);

        $calendarEvent->update([
            ...$validated,
            'color' => $this->typeColor($validated['type']),
        ]);

        return back()->with('success', 'Evento atualizado.');
    }

    public function destroy(CalendarEvent $calendarEvent)
    {
        abort_if($calendarEvent->user_id !== auth()->id(), 403);
        $calendarEvent->delete();
        return back()->with('success', 'Evento eliminado.');
    }

    /* Cor por tipo de evento */
    private function typeColor(string $type): string
    {
        return match($type) {
            'meeting' => '#3b82f6',
            'call'    => '#10b981',
            'task'    => '#8b5cf6',
            'email'   => '#f59e0b',
            default   => '#6b7280',
        };
    }
}