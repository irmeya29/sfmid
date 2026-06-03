<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $deliveryNote->number }}</title>
    @php
        $qty = fn ($value) => number_format((float) $value, (float) $value == floor((float) $value) ? 0 : 3, ',', ' ');
    @endphp
    @include('pdf._document_styles')
</head>
<body>
    @include('pdf._header', [
        'documentTitle' => 'Bordereau de livraison',
        'documentNumber' => $deliveryNote->number,
        'documentDate' => $deliveryNote->planned_delivery_date?->format('d/m/Y'),
        'documentStatus' => $deliveryNote->status->label(),
        'hideDocumentHeading' => true,
    ])
    @include('pdf._footer')

    <main>
        <table class="erp-title">
            <tr>
                <td class="erp-title-main">
                    <div class="type">Bordereau de livraison</div>
                    <div class="number">Reference document : {{ $deliveryNote->number }}</div>
                </td>
                <td class="erp-title-meta">
                    <div class="meta-line">Date prevue : <strong>{{ $deliveryNote->planned_delivery_date?->format('d/m/Y') ?: '-' }}</strong></div>
                    @if($deliveryNote->delivered_at)<div class="meta-line">Livre le : <strong>{{ $deliveryNote->delivered_at->format('d/m/Y H:i') }}</strong></div>@endif
                    <span class="erp-status">{{ $deliveryNote->status->label() }}</span>
                </td>
            </tr>
        </table>

        <table class="erp-info">
            <tr>
                <td style="width: 58%;">
                    <div class="erp-card">
                        <div class="erp-card-title">Client livre</div>
                        <div class="erp-card-name">{{ $deliveryNote->client?->name ?: '-' }}</div>
                        <div class="erp-row"><span class="erp-label">Code client</span> {{ $deliveryNote->client?->code ?: '-' }}</div>
                        @if($deliveryNote->client?->phone)<div class="erp-row"><span class="erp-label">Telephone</span> {{ $deliveryNote->client->phone }}</div>@endif
                        @if($deliveryNote->client?->email)<div class="erp-row"><span class="erp-label">Email</span> {{ $deliveryNote->client->email }}</div>@endif
                        @if($deliveryNote->client?->ifu)<div class="erp-row"><span class="erp-label">IFU</span> {{ $deliveryNote->client->ifu }}</div>@endif
                    </div>
                </td>
                <td class="erp-info-gap"></td>
                <td style="width: 42%;">
                    <div class="erp-card">
                        <div class="erp-card-title">Livraison / reception</div>
                        @if($deliveryNote->customerOrder)<div class="erp-row"><span class="erp-label">BC client</span> {{ $deliveryNote->customerOrder->customer_reference ?: $deliveryNote->customerOrder->number }}</div>@endif
                        @if($deliveryNote->proforma)<div class="erp-row"><span class="erp-label">Proforma</span> {{ $deliveryNote->proforma->number }}</div>@endif
                        @if($deliveryNote->deliverySite)<div class="erp-row"><span class="erp-label">Site</span> {{ $deliveryNote->deliverySite->name }}</div>@endif
                        @if($deliveryNote->delivery_address)<div class="erp-row"><span class="erp-label">Adresse</span> {{ $deliveryNote->delivery_address }}</div>@endif
                        @if($deliveryNote->receiver_name)<div class="erp-row"><span class="erp-label">Recu par</span> {{ $deliveryNote->receiver_name }}</div>@endif
                        @if($deliveryNote->receiver_phone)<div class="erp-row"><span class="erp-label">Telephone</span> {{ $deliveryNote->receiver_phone }}</div>@endif
                    </div>
                </td>
            </tr>
        </table>

        <table class="erp-lines">
            <thead>
                <tr>
                    <th style="width: 16%;">Reference</th>
                    <th>Designation</th>
                    <th style="width: 13%;" class="right">Qte demandee</th>
                    <th style="width: 13%;" class="right">Qte livree</th>
                    <th style="width: 10%;">Unite</th>
                    <th style="width: 18%;">Observation</th>
                </tr>
            </thead>
            <tbody>
                @foreach($deliveryNote->items as $item)
                    <tr>
                        <td class="ref">{{ $item->client_product_reference ?: ($item->product_internal_reference ?: $item->product_code) }}</td>
                        <td class="name">{{ $item->product_name }}<br><span class="muted-cell">Ref SFMID : {{ $item->product_internal_reference ?: $item->product_code }}</span></td>
                        <td class="right">{{ $qty($item->quantity) }}</td>
                        <td class="right">{{ $qty($item->delivered_quantity) }}</td>
                        <td>{{ $item->unit }}</td>
                        <td></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if($deliveryNote->notes)
            <section class="section note-box">
                <div class="box-title">Notes</div>
                <div>{!! nl2br(e($deliveryNote->notes)) !!}</div>
            </section>
        @endif

        <section class="signatures">
            <div class="signature"><div class="signature-line">Magasinier</div></div>
            <div class="signature"><div class="signature-line">Transporteur</div></div>
            <div class="signature"><div class="signature-line">Receptionnaire</div></div>
        </section>
    </main>
</body>
</html>
