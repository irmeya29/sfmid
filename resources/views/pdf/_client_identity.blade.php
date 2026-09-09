<div class="erp-card erp-client-card">
    <div class="erp-card-title">{{ $clientTitle ?? 'Client' }}</div>
    @if(filled($client?->name))
        <div class="erp-card-name">{{ $client->name }}</div>
    @endif
    @foreach([
        'code' => 'Code client',
        'address' => 'Adresse',
        'postal_address' => 'BP',
        'tax_regime' => 'Régime fiscal',
        'legal_form' => 'Forme juridique',
        'rccm' => 'RCCM',
        'ifu' => 'IFU',
        'cnss' => 'CNSS',
        'share_capital' => 'Capital social',
        'phone' => 'Tél.',
        'email' => 'Email',
    ] as $field => $label)
        @if(filled($client?->{$field}))
            <span class="erp-client-detail"><span class="erp-label">{{ $label }}</span><span class="erp-value">{{ $client->{$field} }}</span></span>
        @endif
    @endforeach
</div>
