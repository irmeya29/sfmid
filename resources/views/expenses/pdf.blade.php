<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport depenses</title>
    @php($currency = $company['sales.currency'] ?? 'FCFA')
    @include('pdf._document_styles')
</head>
<body>
    @include('pdf._header', [
        'documentTitle' => 'Rapport dépenses',
        'documentNumber' => 'DEP-'.now()->format('Ymd'),
        'documentDate' => now()->format('d/m/Y H:i'),
        'documentStatus' => 'Export',
    ])
    @include('pdf._footer')

    <main>
        <section class="section">
            <table>
                <thead><tr><th>Numero</th><th>Date</th><th>Categorie</th><th>Beneficiaire</th><th>Statut</th><th class="right">Montant</th></tr></thead>
                <tbody>
                    @foreach($expenses as $expense)
                        <tr><td>{{ $expense->number }}</td><td>{{ $expense->expense_date?->format('d/m/Y') }}</td><td>{{ $expense->category?->name }}</td><td>{{ $expense->beneficiary ?: '-' }}</td><td>{{ $expense->status->label() }}</td><td class="right strong">{{ number_format((float) $expense->amount, 0, ',', ' ') }} {{ $currency }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </section>
        <table class="totals">
            <tr class="grand"><td>Total dépenses</td><td class="right">{{ number_format($expenses->sum(fn($expense) => (float) $expense->amount), 0, ',', ' ') }} {{ $currency }}</td></tr>
        </table>
    </main>
</body>
</html>
