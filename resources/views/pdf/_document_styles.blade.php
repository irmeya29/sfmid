@php
    $currency = $company['sales.currency'] ?? 'FCFA';
    $companyName = $company['company.name'] ?? 'SFMID';
    $companyFullName = $company['company.full_name'] ?? null;
    $logoPath = $company['company.logo_path'] ?? null;
    $logoAbsolutePath = $logoPath ? public_path($logoPath) : null;
@endphp
<style>
    @page { margin: 3.9cm 1.35cm 3.9cm; }
    * { box-sizing: border-box; }
    body { color: #27364a; font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; font-weight: 400; line-height: 1.52; margin: 0; }
    .pdf-letterhead { left: -1.35cm; position: fixed; right: -1.35cm; top: -3.9cm; }
    .letterhead-image { display: block; width: 100%; }
    .letterhead-accent { background: #2676B3; border-bottom: 3px solid #FA820A; height: 8px; margin: 0; }
    .document-heading { border-bottom: 1px solid #dbe3ea; margin: 0 0 16px; padding-bottom: 10px; }
    .document-heading h1 { border: 0; margin: 0; padding: 0; }
    .document-meta-strip { background: #f8fbfd; border: 1px solid #dbe3ea; margin-top: 10px; padding: 8px 10px; text-align: center; }
    .document-meta-strip span { display: inline-block; margin: 0 10px; }
    .document-top { border-collapse: separate; border-spacing: 7px 0; margin: 0 0 18px; table-layout: fixed; width: 100%; }
    .document-top td { border: 0; padding: 0; vertical-align: top; }
    .doc-panel { background: #fff; border: 1px solid #cfd9e4; min-height: 96px; padding: 10px 11px; }
    .doc-panel-title { border: 2px solid #2676B3; color: #2676B3; display: inline-block; font-size: 16px; font-weight: 700; line-height: 1; margin-bottom: 8px; padding: 8px 13px; text-transform: uppercase; }
    .doc-panel-heading { color: #2676B3; font-size: 10px; font-weight: 700; letter-spacing: .3px; margin-bottom: 7px; text-transform: uppercase; }
    .doc-panel-line { line-height: 1.45; margin-top: 2px; }
    .doc-panel-label { color: #667085; display: inline-block; line-height: 1.45; min-width: 58px; vertical-align: baseline; }
    .doc-reference { font-size: 10px; margin-top: 6px; }
    .doc-main-title { color: #17324D; font-size: 11px; font-weight: 600; margin-bottom: 2px; text-transform: uppercase; }
    .document-lines { border: 1px solid #cfd9e4; margin-top: 6px; table-layout: fixed; }
    .document-lines th { background: #fff; border-bottom: 2px solid #2676B3; border-right: 1px solid #dbe3ea; color: #17324D; font-size: 10px; padding: 7px 8px; text-transform: none; }
    .document-lines th:last-child, .document-lines td:last-child { border-right: 0; }
    .document-lines td { border-bottom: 1px solid #e6edf3; border-right: 1px solid #edf2f7; min-height: 26px; padding: 7px 8px; }
    .document-lines tbody tr:nth-child(even) td { background: #f5f8fb; }
    .document-lines tbody tr:nth-child(odd) td { background: #fff; }
    .document-lines .designation { color: #10203f; font-weight: 500; }
    .document-lines .muted-cell { color: #667085; font-size: 10px; }
    .document-bottom { clear: both; margin-top: 14px; width: 100%; }
    .total-box { border: 1px solid #cfd9e4; float: right; page-break-inside: avoid; width: 39%; }
    .total-box td { background: #fff !important; border-bottom: 1px solid #e6edf3; padding: 7px 9px; }
    .total-box tr:last-child td { border-bottom: 0; }
    .total-box .grand td { background: #FFF3E7 !important; border-top: 2px solid #FA820A; color: #17324D; font-size: 14px; font-weight: 700; }
    .commercial-note { clear: both; color: #667085; font-size: 10px; padding-top: 12px; width: 56%; }
    .erp-title { border-collapse: collapse; margin: 0 0 14px; width: 100%; }
    .erp-title td { border-bottom: 0; padding: 0; vertical-align: middle; }
    .erp-title-main { border-left: 5px solid #FA820A; padding-left: 12px !important; }
    .erp-title-main .type { color: #2676B3; font-size: 25px; font-weight: 700; line-height: 1.05; text-transform: uppercase; }
    .erp-title-main .number { color: #667085; font-size: 11px; margin-top: 4px; }
    .erp-title-meta { text-align: right; }
    .erp-title-meta .meta-line { color: #334155; font-size: 11px; margin-bottom: 3px; }
    .erp-status { background: #FFF3E7; border: 1px solid #FA820A; color: #A94D00; display: inline-block; font-size: 9.5px; font-weight: 700; padding: 4px 9px; text-transform: uppercase; }
    .erp-info { border-collapse: separate; border-spacing: 0 0; margin: 0 0 22px; table-layout: fixed; width: 100%; }
    .erp-info td { border: 0; padding: 0; vertical-align: top; }
    .erp-info-gap { width: 20px; }
    .erp-card { background: #fbfdff; border-left: 3px solid #2676B3; min-height: 112px; padding: 12px 13px; }
    .erp-card-title { color: #2676B3; font-size: 10.5px; font-weight: 700; letter-spacing: .35px; margin-bottom: 8px; padding-bottom: 5px; text-transform: uppercase; }
    .erp-card-name { color: #10203f; font-size: 13.5px; font-weight: 700; margin-bottom: 5px; text-transform: uppercase; }
    .erp-row { line-height: 1.45; margin-top: 2px; }
    .erp-label { color: #667085; display: inline-block; line-height: 1.45; min-width: 72px; vertical-align: baseline; }
    .erp-subject { background: #f8fbfd; border-left: 3px solid #FA820A; color: #10203f; font-size: 11.5px; line-height: 1.45; margin: -8px 0 12px; padding: 9px 11px; page-break-inside: avoid; }
    .erp-subject strong { color: #2676B3; display: inline; font-size: 11.5px; line-height: 1.45; margin-right: 4px; text-transform: uppercase; vertical-align: baseline; }
    .erp-subject .erp-subject-text { display: inline; font-size: 11.5px; line-height: 1.45; vertical-align: baseline; }
    .erp-lines { border: 1px solid #cfd9e4; table-layout: fixed; }
    .erp-lines th { background: #2676B3; border-right: 1px solid rgba(255,255,255,.28); color: #fff; font-size: 9.8px; font-weight: 700; padding: 8px 7px; text-transform: uppercase; }
    .erp-lines th:last-child { border-right: 0; }
    .erp-lines td { border-bottom: 1px solid #e6edf3; padding: 7px; }
    .erp-lines tbody tr:nth-child(even) td { background: #f7fafc; }
    .erp-lines tbody tr:nth-child(odd) td { background: #fff; }
    .erp-lines .ref { color: #64748b; font-size: 10.5px; }
    .erp-lines .name { color: #10203f; font-weight: 600; }
    .erp-after-lines { clear: both; margin-top: 14px; width: 100%; }
    .erp-notes { color: #475569; float: left; font-size: 10.5px; line-height: 1.45; width: 56%; }
    .erp-totals { border: 1px solid #cfd9e4; float: right; page-break-inside: avoid; width: 38%; }
    .erp-totals td { background: #fff !important; border-bottom: 1px solid #e6edf3; padding: 7px 9px; }
    .erp-totals tr:last-child td { border-bottom: 0; }
    .erp-totals .grand td { background: #2676B3 !important; border-top: 2px solid #FA820A; color: #fff; font-size: 13px; font-weight: 700; }
    .erp-clear { clear: both; }
    .amount-in-words { clear: both; color: #10203f; font-size: 10.5px; line-height: 1.45; margin-top: 16px; padding: 0; page-break-inside: avoid; white-space: nowrap; }
    .amount-in-words strong { color: #17324D; text-transform: uppercase; }
    .stamp-area { clear: both; margin-top: 42px; page-break-inside: avoid; text-align: right; }
    .stamp-box { border: 1px dashed #94a3b8; display: inline-block; height: 118px; padding: 10px; text-align: center; vertical-align: top; width: 260px; }
    .stamp-box-title { color: #64748b; font-size: 10px; font-weight: 700; text-transform: uppercase; }
    .responsible-title { color: #10203f; display: inline-block; font-size: 11px; font-weight: 700; padding-right: 34px; text-transform: uppercase; }
    footer { bottom: -3.9cm; color: #667085; font-size: 8.5px; left: -1.35cm; position: fixed; right: -1.35cm; text-align: center; }
    .footer-image { display: block; width: 100%; }
    .footer-accent { background: #2676B3; border-top: 2px solid #FA820A; height: 5px; margin: 0 0 8px; }
    .footer-content { border-top: 1px solid #dbe3ea; padding-top: 7px; }
    .brand, .doc-meta { border-bottom: none; display: table-cell; vertical-align: top; width: 55%; }
    .doc-meta { width: 45%; }
    .header-table { margin-bottom: 0; width: 100%; }
    .header-table td { border-bottom: 0; padding: 0; }
    .company { color: #2676B3; font-size: 18px; font-weight: 700; text-transform: uppercase; }
    .logo-box { border: 1px solid #dbe3ea; color: #2676B3; display: inline-block; font-size: 15px; font-weight: 700; min-height: 44px; line-height: 42px; margin-bottom: 6px; padding: 0 14px; text-align: center; }
    .logo-box img { max-height: 48px; max-width: 142px; }
    h1 { color: #2676B3; font-size: 24px; font-weight: 700; letter-spacing: .5px; text-align: center; text-transform: uppercase; }
    h2 { color: #2676B3; font-size: 13px; font-weight: 600; margin: 0 0 8px; }
    .muted { color: #667085; }
    .strong { color: #17324D; font-weight: 600; }
    .pill { background: #FFF3E7; border: 1px solid #FFD8AD; color: #D96B00; display: inline-block; font-size: 9.5px; font-weight: 600; padding: 4px 8px; text-transform: uppercase; }
    main { position: relative; }
    .section { margin-top: 16px; page-break-inside: avoid; }
    .box { background: #f8fbfd; border: 1px solid #dbe3ea; border-radius: 6px; min-height: 104px; padding: 12px; }
    .half { display: inline-block; vertical-align: top; width: 49%; }
    .half + .half { margin-left: 1.4%; }
    .box-title { color: #2676B3; font-size: 9.5px; font-weight: 700; letter-spacing: .45px; margin-bottom: 7px; text-transform: uppercase; }
    table { border-collapse: collapse; width: 100%; }
    th { background: #2676B3; color: #fff; font-size: 9.5px; font-weight: 600; padding: 8px 7px; text-align: left; text-transform: uppercase; }
    td { border-bottom: 1px solid #dbe3ea; padding: 7px; vertical-align: top; }
    tbody tr:nth-child(even) td { background: #f8fbfd; }
    .right { text-align: right; }
    .center { text-align: center; }
    thead { display: table-header-group; }
    tfoot { display: table-row-group; }
    tr { page-break-inside: avoid; }
    .totals { border: 1px solid #dbe3ea; float: right; margin-top: 14px; page-break-inside: avoid; width: 42%; }
    .totals td { background: #fff !important; border-bottom: 1px solid #dbe3ea; padding: 7px 9px; }
    .totals .grand td { background: #FFF3E7 !important; border-top: 2px solid #FA820A; color: #17324D; font-size: 12.5px; font-weight: 700; padding-top: 8px; }
    .note-box { background: #f8fbfd; border: 1px solid #dbe3ea; border-radius: 6px; clear: both; padding: 10px; page-break-inside: avoid; }
    .arrete { background: #f8fbfd; border: 1px solid #dbe3ea; clear: both; margin-top: 28px; padding: 12px; text-align: center; }
    .signatures { margin-top: 34px; page-break-inside: avoid; width: 100%; }
    .signature { display: inline-block; text-align: center; vertical-align: top; width: 31%; }
    .signature + .signature { margin-left: 2.6%; }
    .signature-line { border-top: 1px solid #475569; color: #10203f; margin-top: 46px; padding-top: 7px; }
    .invoice-summary { background: #f8fbfd; border: 1px solid #dbe3ea; margin-top: 12px; width: 100%; }
    .invoice-summary td { border-bottom: 0; padding: 10px 12px; }
    .invoice-summary .label { color: #667085; font-size: 9.5px; text-transform: uppercase; }
    .invoice-summary .value { color: #10203f; font-size: 13px; font-weight: 600; margin-top: 2px; }
    .items-table td.description { color: #10203f; }
    .amount-words { clear: both; color: #10203f; font-style: italic; margin-top: 18px; }
</style>
