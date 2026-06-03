<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $order->number }}</title>
    @php($currency = $company['sales.currency'] ?? 'FCFA')
    @include('pdf._document_styles')
</head>
<body>
    @include('pdf._header', ['documentTitle' => 'Bon de commande fournisseur', 'documentNumber' => $order->number, 'documentDate' => $order->order_date?->format('d/m/Y'), 'documentStatus' => $order->status])
    @include('pdf._footer')
    <main>
        <section class="section">
            <div class="half box"><div class="box-title">Fournisseur</div><div class="strong">{{ $order->supplier?->name }}</div><div>{{ $order->supplier?->address }}</div><div>Tel. {{ $order->supplier?->phone ?: '-' }}</div><div>IFU {{ $order->supplier?->ifu ?: '-' }}</div></div>
            <div class="half box"><div class="box-title">Commande</div><div>Date commande : {{ $order->order_date?->format('d/m/Y') }}</div><div>Livraison prévue : {{ $order->expected_delivery_date?->format('d/m/Y') ?: '-' }}</div><div>Conditions : {{ $order->terms ?: '-' }}</div></div>
        </section>
        <section class="section"><table><thead><tr><th>Code</th><th>Désignation</th><th class="right">Qté</th><th>Unité</th><th class="right">PU</th><th class="right">Total</th></tr></thead><tbody>@foreach($order->items as $item)<tr><td>{{ $item->product_code }}</td><td>{{ $item->product_name }}</td><td class="right">{{ \App\Support\NumberFormatter::quantity($item->quantity) }}</td><td>{{ $item->unit }}</td><td class="right">{{ number_format((float)$item->unit_price,0,',',' ') }}</td><td class="right strong">{{ number_format((float)$item->line_total,0,',',' ') }}</td></tr>@endforeach</tbody></table></section>
        <table class="totals"><tr><td>Sous-total</td><td class="right">{{ number_format((float)$order->subtotal,0,',',' ') }} {{ $currency }}</td></tr><tr><td>Taxes</td><td class="right">{{ number_format((float)$order->tax_total,0,',',' ') }} {{ $currency }}</td></tr><tr class="grand"><td>Total</td><td class="right">{{ number_format((float)$order->total,0,',',' ') }} {{ $currency }}</td></tr></table>
        @if($order->notes)<section class="section note-box"><div class="box-title">Notes</div>{!! nl2br(e($order->notes)) !!}</section>@endif
        <section class="signatures"><div class="signature"><div class="signature-line">Achats</div></div><div class="signature"><div class="signature-line">Direction</div></div><div class="signature"><div class="signature-line">Fournisseur</div></div></section>
    </main>
</body>
</html>
