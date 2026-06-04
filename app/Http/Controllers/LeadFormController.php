<?php

namespace App\Http\Controllers;

use App\Models\LeadForm;
use App\Models\LeadFormSubmission;
use App\Models\Entity;
use App\Models\Deal;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeadFormController extends Controller
{
    public function index()
    {
        $forms = LeadForm::where('user_id', auth()->id())
            ->withCount('submissions')
            ->latest()
            ->get();

        return Inertia::render('LeadForms/Index', [
            'forms' => $forms,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                 => 'required|string|max:255',
            'description'          => 'nullable|string',
            'fields'               => 'required|array|min:1',
            'fields.*.label'       => 'required|string',
            'fields.*.type'        => 'required|in:text,email,phone,textarea,select',
            'fields.*.required'    => 'boolean',
            'confirmation_message' => 'nullable|string',
        ]);

        LeadForm::create([
            ...$validated,
            'user_id' => auth()->id(),
            'slug'    => LeadForm::generateSlug($validated['name']),
        ]);

        return back()->with('success', 'Formulário criado.');
    }

    public function update(Request $request, LeadForm $leadForm)
    {
        abort_if($leadForm->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'name'                 => 'required|string|max:255',
            'description'          => 'nullable|string',
            'fields'               => 'required|array|min:1',
            'fields.*.label'       => 'required|string',
            'fields.*.type'        => 'required|in:text,email,phone,textarea,select',
            'fields.*.required'    => 'boolean',
            'confirmation_message' => 'nullable|string',
            'active'               => 'boolean',
        ]);

        $leadForm->update($validated);

        return back()->with('success', 'Formulário atualizado.');
    }

    public function destroy(LeadForm $leadForm)
    {
        abort_if($leadForm->user_id !== auth()->id(), 403);
        $leadForm->delete();
        return back()->with('success', 'Formulário eliminado.');
    }

    /* ─── Página pública do formulário ─── */
    public function show(string $slug)
    {
        $form = LeadForm::where('slug', $slug)
            ->where('active', true)
            ->firstOrFail();

        return Inertia::render('LeadForms/Public', [
            'form' => $form,
        ]);
    }

    /* ─── Submissão pública ─── */
    public function submit(Request $request, string $slug)
    {
        $form = LeadForm::where('slug', $slug)
            ->where('active', true)
            ->firstOrFail();

        /* Valida campos obrigatórios dinamicamente */
        $rules = [];
        foreach ($form->fields as $field) {
            if ($field['required'] ?? false) {
                $rules['field_' . $field['label']] = 'required';
            }
        }
        $request->validate($rules);

        /* Cria entidade automaticamente se tiver nome/email */
        $entity = null;
        $data   = $request->except(['_token']);

        /* Procura campo de nome da empresa */
        $companyName = null;
        $email       = null;
        $phone       = null;

        foreach ($form->fields as $field) {
            $key = 'field_' . $field['label'];
            if (in_array(strtolower($field['label']), ['empresa', 'company', 'nome da empresa'])) {
                $companyName = $request->get($key);
            }
            if ($field['type'] === 'email') {
                $email = $request->get($key);
            }
            if ($field['type'] === 'phone') {
                $phone = $request->get($key);
            }
        }

        /* Cria entidade/lead automaticamente */
        if ($companyName || $email) {
            $entity = Entity::create([
                'user_id' => $form->user_id,
                'name'    => $companyName ?? ($email ? explode('@', $email)[1] : 'Lead ' . now()->format('d/m/Y')),
                'email'   => $email,
                'phone'   => $phone,
                'status'  => 'prospect',
            ]);

            /* Cria negócio Lead automaticamente */
            Deal::create([
                'user_id'   => $form->user_id,
                'entity_id' => $entity->id,
                'title'     => 'Lead — ' . ($companyName ?? $email ?? 'Formulário Web'),
                'stage'     => 'lead',
                'value'     => 0,
            ]);
        }

        /* Guarda a submissão */
        LeadFormSubmission::create([
            'lead_form_id' => $form->id,
            'entity_id'    => $entity?->id,
            'data'         => $data,
            'ip'           => $request->ip(),
            'origin'       => $request->header('referer'),
        ]);

        return back()->with('submitted', true);
    }

    /* ─── Ver submissões de um formulário ─── */
    public function submissions(LeadForm $leadForm)
    {
        abort_if($leadForm->user_id !== auth()->id(), 403);

        $submissions = $leadForm->submissions()
            ->with('entity')
            ->latest()
            ->get();

        return Inertia::render('LeadForms/Submissions', [
            'form'        => $leadForm,
            'submissions' => $submissions,
        ]);
    }
}