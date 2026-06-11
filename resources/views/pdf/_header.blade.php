@php
    $companyName = $company['company.name'] ?? 'SFMID';
    $companyFullName = $company['company.full_name'] ?? null;
    $logoPath = $company['company.logo_path'] ?? null;
    $logoAbsolutePath = $logoPath ? public_path($logoPath) : public_path('logo.png');
    $headerImagePath = $company['pdf.header_image_path'] ?? 'branding/pdf-header-print.jpg';
    $headerImageAbsolutePath = public_path($headerImagePath);
@endphp

<header class="pdf-letterhead">
    @if(file_exists($headerImageAbsolutePath))
        <img src="{{ $headerImageAbsolutePath }}" alt="{{ $companyFullName ?: $companyName }}" class="letterhead-image">
    @else
        <div class="letterhead-accent"></div>
        <table class="header-table">
            <tr>
                <td>
                    <div class="logo-box">
                        @if($logoAbsolutePath && file_exists($logoAbsolutePath))
                            <img src="{{ $logoAbsolutePath }}" alt="{{ $companyName }}">
                        @else
                            {{ $companyName }}
                        @endif
                    </div>
                    <div class="company">{{ $companyFullName ?: $companyName }}</div>
                    <div class="muted">
                        {{ $company['company.address'] ?? 'Burkina Faso' }}<br>
                        @if(! empty($company['company.phone']))Tel : {{ $company['company.phone'] }} @endif
                        @if(! empty($company['company.email'])){{ ! empty($company['company.phone']) ? ' - ' : '' }}{{ $company['company.email'] }}@endif
                        <br>
                        @if(! empty($company['company.ifu']))IFU : {{ $company['company.ifu'] }} @endif
                        @if(! empty($company['company.rccm']))RCCM : {{ $company['company.rccm'] }} @endif
                    </div>
                </td>
            </tr>
        </table>
    @endif
</header>

@unless($hideDocumentHeading ?? false)
    <div class="document-heading">
        <h1>{{ $documentTitle }}</h1>
        <div class="document-meta-strip">
            <span><strong>N°</strong> {{ $documentNumber ?? now()->format('YmdHis') }}</span>
            @isset($documentDate)<span><strong>Date</strong> {{ $documentDate }}</span>@endisset
        </div>
    </div>
@endunless
