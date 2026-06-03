<?php

namespace App\Http\Controllers;

use App\Enums\ClientStatus;
use App\Enums\ClientType;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Services\Audit\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Client::class);

        $clients = Client::query()
            ->with('creator')
            ->search($request->string('search')->toString())
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->toString()))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('clients.index', [
            'clients' => $clients,
            'types' => ClientType::options(),
            'statuses' => ClientStatus::options(),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'type' => $request->string('type')->toString(),
                'status' => $request->string('status')->toString(),
            ],
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Client::class);

        return view('clients.create', [
            'client' => new Client([
                'type' => ClientType::Other,
                'status' => ClientStatus::Active,
                'payment_delay_days' => 0,
            ]),
            'types' => ClientType::options(),
            'statuses' => ClientStatus::options(),
        ]);
    }

    public function store(StoreClientRequest $request, ActivityLogger $activityLogger): RedirectResponse
    {
        Gate::authorize('create', Client::class);

        $client = DB::transaction(function () use ($request, $activityLogger): Client {
            $data = $request->validated();

            $client = Client::query()->create([
                ...$data,
                'code' => $data['code'] ?: $this->generateClientCode(),
                'created_by' => $request->user()->id,
            ]);

            $activityLogger->log(
                action: 'created',
                module: 'clients',
                description: "Client {$client->code} créé.",
                subject: $client,
                newValues: $client->only([
                    'code',
                    'name',
                    'type',
                    'phone',
                    'email',
                    'ifu',
                    'rccm',
                    'status',
                ]),
            );

            return $client;
        });

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Client créé avec succès.');
    }

    public function show(Client $client): View
    {
        Gate::authorize('view', $client);

        $client->load([
            'contacts',
            'deliverySites',
            'proformas',
            'deliveryNotes',
            'invoices',
        ]);

        return view('clients.show', compact('client'));
    }

    public function edit(Client $client): View
    {
        Gate::authorize('update', $client);

        return view('clients.edit', [
            'client' => $client,
            'types' => ClientType::options(),
            'statuses' => ClientStatus::options(),
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client, ActivityLogger $activityLogger): RedirectResponse
    {
        Gate::authorize('update', $client);

        DB::transaction(function () use ($request, $client, $activityLogger): void {
            $oldValues = $client->only([
                'code',
                'name',
                'type',
                'phone',
                'email',
                'address',
                'ifu',
                'rccm',
                'payment_delay_days',
                'commercial_terms',
                'status',
            ]);

            $client->update($request->validated());

            $activityLogger->log(
                action: 'updated',
                module: 'clients',
                description: "Client {$client->code} modifié.",
                subject: $client,
                oldValues: $oldValues,
                newValues: $client->fresh()->only(array_keys($oldValues)),
            );
        });

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Client modifié avec succès.');
    }

    public function destroy(Client $client, ActivityLogger $activityLogger): RedirectResponse
    {
        Gate::authorize('delete', $client);

        DB::transaction(function () use ($client, $activityLogger): void {
            $activityLogger->log(
                action: 'deleted',
                module: 'clients',
                description: "Client {$client->code} supprimé.",
                subject: $client,
                oldValues: $client->only(['code', 'name', 'status']),
            );

            $client->delete();
        });

        return redirect()
            ->route('clients.index')
            ->with('success', 'Client supprimé avec succès.');
    }

    private function generateClientCode(): string
    {
        do {
            $code = 'CLI-'.now()->format('ym').'-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (Client::withTrashed()->where('code', $code)->exists());

        return $code;
    }
}
