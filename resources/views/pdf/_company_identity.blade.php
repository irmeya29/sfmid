@if(! empty($company['company.ifu']) || ! empty($company['company.rccm']))
    <div class="erp-company-strip">
        @if(! empty($company['company.ifu']))
            <span><strong>IFU :</strong> {{ $company['company.ifu'] }}</span>
        @endif
        @if(! empty($company['company.rccm']))
            <span><strong>RCCM :</strong> {{ $company['company.rccm'] }}</span>
        @endif
    </div>
@endif
