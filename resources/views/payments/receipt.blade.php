<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $payment->number }}</title>
    @php($currency = $company['sales.currency'] ?? 'FCFA')
    @include('pdf._document_styles')
    <style>.receipt-amount{background:#e8f3f6;border:1px solid #b8d9e1;border-radius:8px;color:#102a43;font-size:24px;font-weight:800;margin-top:18px;padding:16px;text-align:center}</style>
</head>
<body>
    @include('pdf._header', [
        'documentTitle' => 'Reçu de paiement',
        'documentNumber' => $payment->number,
        'documentDate' => $payment->payment_date?->format('d/m/Y'),
        'documentStatus' => $payment->status->label(),
    ])
    @include('pdf._footer')

    <main>
        <section class="section">
            <div class="half box">
                <div class="box-title">Client</div>
                <div class="strong">{{ $payment->invoice?->client?->name }}</div>
                <div>Facture : {{ $payment->invoice?->number }}</div>
                <div>Total facture : {{ number_format((float) $payment->invoice?->total, 0, ',', ' ') }} {{ $currency }}</div>
                <div>Solde actuel : {{ number_format((float) $payment->invoice?->balance_due, 0, ',', ' ') }} {{ $currency }}</div>
            </div>
            <div class="half box">
                <div class="box-title">Paiement</div>
                <div>Mode : <span class="strong">{{ $payment->method }}</span></div>
                <div>Reference : {{ $payment->reference ?: '-' }}</div>
                <div>Date : {{ $payment->payment_date?->format('d/m/Y') }}</div>
                <div>Statut : {{ $payment->status->label() }}</div>
            </div>
        </section>

        <div class="receipt-amount">{{ number_format((float) $payment->amount, 0, ',', ' ') }} {{ $currency }}</div>

        @if($payment->notes)
            <section class="section note-box">
                <div class="box-title">Notes</div>
                <div>{!! nl2br(e($payment->notes)) !!}</div>
            </section>
        @endif

        <section class="signatures">
            <div class="signature"><div class="signature-line">Caissier</div></div>
            <div class="signature"><div class="signature-line">{{ $company['pdf.signature_left'] ?? 'SFMID' }}</div></div>
            <div class="signature"><div class="signature-line">{{ $company['pdf.signature_right'] ?? 'Client' }}</div></div>
        </section>
    </main>
</body>
</html>
