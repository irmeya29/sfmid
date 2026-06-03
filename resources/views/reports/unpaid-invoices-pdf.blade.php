<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Factures impayees</title>
    @php($currency = $company['sales.currency'] ?? 'FCFA')
    @include('pdf._document_styles')
</head>
<body>
    @include('pdf._header', [
        'documentTitle' => 'Factures impayées',
        'documentNumber' => 'IMP-'.now()->format('Ymd'),
        'documentDate' => now()->format('d/m/Y H:i'),
        'documentStatus' => 'Rapport',
    ])
    @include('pdf._footer')

    <main>
        <section class="section">
            <table>
                <thead><tr><th>Facture</th><th>Client</th><th>Date</th><th>Echeance</th><th>Statut</th><th class="right">Total</th><th class="right">Solde</th></tr></thead>
                <tbody>
                    @foreach($invoices as $invoice)
                        <tr>
                            <td class="strong">{{ $invoice->number }}</td>
                            <td>{{ $invoice->client?->name }}</td>
                            <td>{{ $invoice->issue_date?->format('d/m/Y') }}</td>
                            <td>{{ $invoice->due_date?->format('d/m/Y') ?: '-' }}</td>
                            <td>{{ $invoice->status->label() }}</td>
                            <td class="right">{{ number_format((float) $invoice->total, 0, ',', ' ') }} {{ $currency }}</td>
                            <td class="right strong">{{ number_format((float) $invoice->balance_due, 0, ',', ' ') }} {{ $currency }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
        <table class="totals">
            <tr class="grand"><td>Total impayé</td><td class="right">{{ number_format($invoices->sum(fn($invoice) => (float) $invoice->balance_due), 0, ',', ' ') }} {{ $currency }}</td></tr>
        </table>
    </main>
</body>
</html>
