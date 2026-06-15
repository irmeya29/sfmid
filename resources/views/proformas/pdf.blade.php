<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $proforma->number }}</title>
    @php
        $currency = $company['sales.currency'] ?? 'FCFA';
        $money = fn ($value) => number_format((float) $value, 0, ',', ' ').' '.$currency;
        $qty = fn ($value) => number_format((float) $value, (float) $value == floor((float) $value) ? 0 : 3, ',', ' ');
    @endphp
    @include('pdf._document_styles')
</head>
<body>
    @include('pdf._header', [
        'documentTitle' => 'Proforma',
        'documentNumber' => $proforma->number,
        'documentDate' => $proforma->issue_date?->format('d/m/Y'),
        'hideDocumentHeading' => true,
    ])
    @include('pdf._footer')

    <main>
        <table class="erp-title">
            <tr>
                <td class="erp-title-main">
                    <div class="type">Facture proforma</div>
                    <div class="number">Reference document : {{ $proforma->number }}</div>
                </td>
                <td class="erp-title-meta">
                    <div class="meta-line">Date : <strong>{{ $proforma->issue_date?->format('d/m/Y') ?: '-' }}</strong></div>
                </td>
            </tr>
        </table>

        <table class="erp-info">
            <tr>
                <td style="width: 58%;">
                    <div class="erp-card">
                        <div class="erp-card-title">Client</div>
                        <div class="erp-card-name">{{ $proforma->client?->name ?: '-' }}</div>
                        <div class="erp-row"><span class="erp-label">Code client</span> {{ $proforma->client?->code ?: '-' }}</div>
                        @if($proforma->client?->phone)<div class="erp-row"><span class="erp-label">Telephone</span> {{ $proforma->client->phone }}</div>@endif
                        @if($proforma->client?->email)<div class="erp-row"><span class="erp-label">Email</span> {{ $proforma->client->email }}</div>@endif
                        @if($proforma->client?->ifu)<div class="erp-row"><span class="erp-label">IFU</span> {{ $proforma->client->ifu }}</div>@endif
                        @if($proforma->client?->rccm)<div class="erp-row"><span class="erp-label">RCCM</span> {{ $proforma->client->rccm }}</div>@endif
                    </div>
                </td>
                <td class="erp-info-gap"></td>
                <td style="width: 42%;">
                    <div class="erp-card">
                        <div class="erp-card-title">Conditions commerciales</div>
                        @if($proforma->deliverySite)
                            <div class="erp-row"><span class="erp-label">Site</span> {{ $proforma->deliverySite->name }}</div>
                            @if($proforma->deliverySite->address)<div class="erp-row"><span class="erp-label">Adresse</span> {{ $proforma->deliverySite->address }}</div>@endif
                        @else
                            <div class="erp-row"><span class="erp-label">Site</span> Non renseigne</div>
                        @endif
                        <div class="erp-row"><span class="erp-label">Incoterm</span> {{ $proforma->incoterm ?: '-' }}</div>
                        <div class="erp-row"><span class="erp-label">Reglement</span> {{ $proforma->payment_terms ?: $proforma->terms ?: '-' }}</div>
                        <div class="erp-row"><span class="erp-label">Delai livraison</span> {{ $proforma->delivery_delay ?: '-' }}</div>
                        <div class="erp-row"><span class="erp-label">Devise</span> {{ $currency }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="erp-subject"><strong>Objet :</strong><span class="erp-subject-text">{{ $proforma->subject ?: '-' }}</span></div>

        <table class="erp-lines">
            <thead>
                <tr>
                    <th style="width: 16%;">Reference</th>
                    <th>Designation</th>
                    <th style="width: 8%;" class="right">Qte</th>
                    <th style="width: 9%;">Unite</th>
                    <th style="width: 13%;" class="right">Prix U</th>
                    <th style="width: 15%;" class="right">Montant</th>
                </tr>
            </thead>
            <tbody>
                @foreach($proforma->items as $item)
                    <tr>
                        <td class="ref">{{ $item->client_product_reference ?: $item->product_code }}</td>
                        <td class="name">{{ $item->product_name }}</td>
                        <td class="right">{{ $qty($item->quantity) }}</td>
                        <td>{{ $item->unit }}</td>
                        <td class="right">{{ number_format((float) $item->unit_price, 0, ',', ' ') }}</td>
                        <td class="right strong">{{ number_format((float) ($item->line_total_ttc ?? $item->line_total), 0, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="erp-after-lines">
            <div class="erp-notes">
                @if($proforma->notes)
                    <strong>Notes</strong><br>{!! nl2br(e($proforma->notes)) !!}
                @endif
            </div>
            <table class="erp-totals">
                <tr><td>Total brut</td><td class="right">{{ $money($proforma->subtotal) }}</td></tr>
                <tr><td>Remise</td><td class="right">{{ $money($proforma->discount_total) }}</td></tr>
                <tr><td>Total HT</td><td class="right">{{ $money($proforma->subtotal - $proforma->discount_total) }}</td></tr>
                <tr><td>TVA</td><td class="right">{{ $money($proforma->tax_total) }}</td></tr>
                <tr class="grand"><td>Total TTC</td><td class="right">{{ $money($proforma->total) }}</td></tr>
            </table>
            <div class="erp-clear"></div>
        </div>

        <div class="amount-in-words">
            Arr&ecirc;t&eacute;e la pr&eacute;sente proforma &agrave; la somme de : <strong>{{ \App\Support\NumberFormatter::moneyToWords($proforma->total, $currency) }}</strong>.
        </div>

        <div class="stamp-area">
            <div class="responsible-title">LE RESPONSABLE</div>
        </div>
    </main>
</body>
</html>
