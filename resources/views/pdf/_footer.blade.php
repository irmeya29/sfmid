@php
    $footerImagePath = $company['pdf.footer_image_path'] ?? 'branding/pdf-footer-print.jpg';
    $footerImageAbsolutePath = public_path($footerImagePath);
@endphp

<footer>
    @if(file_exists($footerImageAbsolutePath))
        <img src="{{ $footerImageAbsolutePath }}" alt="Pied de page SFMID" class="footer-image">
    @else
        <div class="footer-accent"></div>
        <div class="footer-content">
            {{ $company['pdf.footer_note'] ?? 'Merci pour votre confiance.' }}
            <br>
            {{ $company['company.name'] ?? 'SFMID' }}
            @if(! empty($company['company.phone'])) - Tel : {{ $company['company.phone'] }} @endif
            @if(! empty($company['company.email'])) - {{ $company['company.email'] }} @endif
            <br>
            Document genere par SFMID Gestion - {{ now()->format('d/m/Y H:i') }}
        </div>
    @endif
</footer>
