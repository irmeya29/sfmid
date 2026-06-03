<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #0f172a; }
        h1 { font-size: 18px; }
        h2 { font-size: 13px; margin-top: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #cbd5e1; padding: 5px; text-align: left; }
        th { background: #f1f5f9; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>Rapports et statistiques</h1>

    <h2>Ventes par periode</h2>
    <table><tbody>@foreach($report['sales'] as $row)<tr><td>{{ $row->period }}</td><td>{{ $row->count }}</td><td class="right">{{ number_format((float) $row->total, 0, ',', ' ') }}</td></tr>@endforeach</tbody></table>

    <h2>Factures impayees</h2>
    <table><tbody>@foreach($report['unpaidInvoices'] as $invoice)<tr><td>{{ $invoice->number }}</td><td>{{ $invoice->client?->name }}</td><td class="right">{{ number_format((float) $invoice->balance_due, 0, ',', ' ') }}</td></tr>@endforeach</tbody></table>

    <h2>Paiements encaisses</h2>
    <table><tbody>@foreach($report['payments'] as $row)<tr><td>{{ $row->period }}</td><td>{{ $row->count }}</td><td class="right">{{ number_format((float) $row->total, 0, ',', ' ') }}</td></tr>@endforeach</tbody></table>

    <h2>Depenses par categorie</h2>
    <table><tbody>@foreach($report['expensesByCategory'] as $row)<tr><td>{{ $row->category }}</td><td>{{ $row->count }}</td><td class="right">{{ number_format((float) $row->total, 0, ',', ' ') }}</td></tr>@endforeach</tbody></table>

    @if($canViewMargin)
        <h2>Marge par produit</h2>
        <table><tbody>@foreach($report['margin'] as $row)<tr><td>{{ $row->product_name }}</td><td class="right">{{ number_format((float) $row->sales_total, 0, ',', ' ') }}</td><td class="right">{{ number_format((float) $row->margin, 0, ',', ' ') }}</td></tr>@endforeach</tbody></table>
    @endif
</body>
</html>
