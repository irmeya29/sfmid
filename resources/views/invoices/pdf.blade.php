<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->number }}</title>
    @php
        $currency = $company['sales.currency'] ?? 'FCFA';
        $money = fn ($value) => number_format((float) $value, 0, ',', ' ').' '.$currency;
        $qty = fn ($value) => number_format((float) $value, (float) $value == floor((float) $value) ? 0 : 3, ',', ' ');
    @endphp
    @include('pdf._document_styles')
</head>
<body>
    @include('pdf._header', [
        'documentTitle' => 'Facture',
        'documentNumber' => $invoice->number,
        'documentDate' => $invoice->issue_date?->format('d/m/Y'),
        'documentStatus' => $invoice->status->label(),
        'hideDocumentHeading' => true,
    ])
    @include('pdf._footer')

    <main>
        <table class="erp-title">
            <tr>
                <td class="erp-title-main">
                    <div class="type">Facture</div>
                    <div class="number">Reference document : {{ $invoice->number }}</div>
                </td>
                <td class="erp-title-meta">
                    <div class="meta-line">Date : <strong>{{ $invoice->issue_date?->format('d/m/Y') ?: '-' }}</strong></div>
                    <div class="meta-line">Echeance : <strong>{{ $invoice->due_date?->format('d/m/Y') ?: '-' }}</strong></div>
                </td>
            </tr>
        </table>

        <table class="erp-info">
            <tr>
                <td style="width: 58%;">
                    <div class="erp-card">
                        <div class="erp-card-title">Client facture</div>
                        <div class="erp-card-name">{{ $invoice->client?->name ?: '-' }}</div>
                        <div class="erp-row"><span class="erp-label">Code client</span> {{ $invoice->client?->code ?: '-' }}</div>
                        @if($invoice->client?->phone)<div class="erp-row"><span class="erp-label">Telephone</span> {{ $invoice->client->phone }}</div>@endif
                        @if($invoice->client?->email)<div class="erp-row"><span class="erp-label">Email</span> {{ $invoice->client->email }}</div>@endif
                        @if($invoice->client?->ifu)<div class="erp-row"><span class="erp-label">IFU</span> {{ $invoice->client->ifu }}</div>@endif
                    </div>
                </td>
                <td class="erp-info-gap"></td>
                <td style="width: 42%;">
                    <div class="erp-card">
                        <div class="erp-card-title">References</div>
                        <div class="erp-row"><span class="erp-label">BL</span> {{ $invoice->deliveryNote?->number ?: '-' }}</div>
                        <div class="erp-row"><span class="erp-label">BC client</span> {{ $invoice->customerOrder?->customer_reference ?: $invoice->customerOrder?->number ?: '-' }}</div>
                        <div class="erp-row"><span class="erp-label">Proforma</span> {{ $invoice->proforma?->number ?: '-' }}</div>
                        <div class="erp-row"><span class="erp-label">Devise</span> {{ $currency }}</div>
                        @if($invoice->payment_terms)<div class="erp-row" style="margin-top: 6px;">{!! nl2br(e($invoice->payment_terms)) !!}</div>@endif
                    </div>
                </td>
            </tr>
        </table>

        <table class="erp-lines">
            <thead>
                <tr>
                    <th style="width: 14%;">Reference</th>
                    <th>Designation</th>
                    <th style="width: 8%;" class="right">Qte</th>
                    <th style="width: 9%;">Unite</th>
                    <th style="width: 13%;" class="right">Prix U</th>
                    <th style="width: 10%;" class="right">Remise</th>
                    <th style="width: 14%;" class="right">Montant</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                    <tr>
                        <td class="ref">{{ $item->client_product_reference ?: ($item->product_internal_reference ?: $item->product_code) }}</td>
                        <td class="name">{{ $item->product_name }}<br><span class="muted-cell">Ref SFMID : {{ $item->product_internal_reference ?: $item->product_code }}</span></td>
                        <td class="right">{{ $qty($item->quantity) }}</td>
                        <td>{{ $item->unit }}</td>
                        <td class="right">{{ number_format((float) $item->unit_price, 0, ',', ' ') }}</td>
                        <td class="right">{{ number_format((float) $item->discount_amount, 0, ',', ' ') }}</td>
                        <td class="right strong">{{ number_format((float) $item->line_total, 0, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="erp-after-lines">
            <div class="erp-notes">
                @if($invoice->notes)
                    <strong>Notes</strong><br>{!! nl2br(e($invoice->notes)) !!}
                @endif
            </div>
            <table class="erp-totals">
                <tr><td>Total HT</td><td class="right">{{ $money($invoice->subtotal) }}</td></tr>
                <tr><td>Remise</td><td class="right">{{ $money($invoice->discount_total) }}</td></tr>
                <tr><td>Total net</td><td class="right">{{ $money($invoice->subtotal - $invoice->discount_total) }}</td></tr>
                <tr><td>TVA</td><td class="right">{{ $money($invoice->tax_total) }}</td></tr>
                <tr class="grand"><td>Total TTC</td><td class="right">{{ $money($invoice->total) }}</td></tr>
            </table>
            <div class="erp-clear"></div>
        </div>

        <div class="amount-in-words">
            Arr&ecirc;t&eacute;e la pr&eacute;sente facture &agrave; la somme de :
            <br>
            <strong>{{ \App\Support\NumberFormatter::moneyToWords($invoice->total, $currency) }}</strong>.
        </div>

        <div class="stamp-area">
            <div class="stamp-box">
                <div class="stamp-box-title">Signature et cachet</div>
            </div>
        </div>
    </main>
</body>
</html>
