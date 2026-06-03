<html>
<head><meta charset="UTF-8"></head>
<body>
    <h1>Rapports et statistiques</h1>
    <h2>Ventes par periode</h2>
    <table border="1"><tr><th>Periode</th><th>Nombre</th><th>Total</th></tr>@foreach($report['sales'] as $row)<tr><td>{{ $row->period }}</td><td>{{ $row->count }}</td><td>{{ $row->total }}</td></tr>@endforeach</table>
    <h2>Factures impayees</h2>
    <table border="1"><tr><th>Numero</th><th>Client</th><th>Solde</th></tr>@foreach($report['unpaidInvoices'] as $invoice)<tr><td>{{ $invoice->number }}</td><td>{{ $invoice->client?->name }}</td><td>{{ $invoice->balance_due }}</td></tr>@endforeach</table>
    <h2>Paiements encaisses</h2>
    <table border="1"><tr><th>Periode</th><th>Nombre</th><th>Total</th></tr>@foreach($report['payments'] as $row)<tr><td>{{ $row->period }}</td><td>{{ $row->count }}</td><td>{{ $row->total }}</td></tr>@endforeach</table>
    <h2>Stock bas</h2>
    <table border="1"><tr><th>Code</th><th>Produit</th><th>Stock</th><th>Seuil</th></tr>@foreach($report['lowStock'] as $product)<tr><td>{{ $product->code }}</td><td>{{ $product->name }}</td><td>{{ $product->physical_stock }}</td><td>{{ $product->alert_threshold }}</td></tr>@endforeach</table>
    <h2>Stock en suspens</h2>
    <table border="1"><tr><th>Client</th><th>Produit</th><th>Quantite restante</th></tr>@foreach($report['suspense'] as $row)<tr><td>{{ $row->client?->name }}</td><td>{{ $row->product?->name }}</td><td>{{ $row->remainingQuantity() }}</td></tr>@endforeach</table>
    <h2>Depenses par categorie</h2>
    <table border="1"><tr><th>Categorie</th><th>Nombre</th><th>Total</th></tr>@foreach($report['expensesByCategory'] as $row)<tr><td>{{ $row->category }}</td><td>{{ $row->count }}</td><td>{{ $row->total }}</td></tr>@endforeach</table>
    @if($canViewMargin)
        <h2>Marge par produit</h2>
        <table border="1"><tr><th>Produit</th><th>Ventes</th><th>Marge</th></tr>@foreach($report['margin'] as $row)<tr><td>{{ $row->product_name }}</td><td>{{ $row->sales_total }}</td><td>{{ $row->margin }}</td></tr>@endforeach</table>
    @endif
</body>
</html>
