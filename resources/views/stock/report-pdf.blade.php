<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    @include('pdf._document_styles')
</head>
<body>
    @include('pdf._header', [
        'documentTitle' => $title,
        'documentNumber' => 'STOCK-'.now()->format('Ymd'),
        'documentDate' => now()->format('d/m/Y H:i'),
        'documentStatus' => 'Rapport',
    ])
    @include('pdf._footer')

    <main>
        <section class="section">
            @if($report === 'suspense')
                <table>
                    <thead><tr><th>Client</th><th>Produit</th><th>Facture</th><th class="right">Quantite</th><th class="right">Restant</th></tr></thead>
                    <tbody>@foreach($rows as $row)<tr><td>{{ $row->client?->name }}</td><td>{{ $row->product?->code }} - {{ $row->product?->name }}</td><td>{{ $row->invoice?->number ?: '-' }}</td><td class="right">{{ \App\Support\NumberFormatter::quantity($row->quantity) }}</td><td class="right strong">{{ \App\Support\NumberFormatter::quantity($row->remainingQuantity()) }}</td></tr>@endforeach</tbody>
                </table>
            @else
                <table>
                    <thead><tr><th>Code</th><th>Produit</th><th>Categorie</th><th class="right">Physique</th><th class="right">Reserve</th><th class="right">Suspens</th><th class="right">Outil</th><th class="right">Seuil</th></tr></thead>
                    <tbody>@foreach($rows as $product)<tr><td>{{ $product->code }}</td><td>{{ $product->name }}</td><td>{{ $product->category?->name }}</td><td class="right">{{ \App\Support\NumberFormatter::quantity($product->physical_stock) }}</td><td class="right">{{ \App\Support\NumberFormatter::quantity($product->reserved_stock) }}</td><td class="right">{{ \App\Support\NumberFormatter::quantity($product->suspense_stock) }}</td><td class="right">{{ \App\Support\NumberFormatter::quantity($product->tool_stock) }}</td><td class="right">{{ \App\Support\NumberFormatter::quantity($product->alert_threshold) }}</td></tr>@endforeach</tbody>
                </table>
            @endif
        </section>
    </main>
</body>
</html>
