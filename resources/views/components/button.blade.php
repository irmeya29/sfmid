@props([
    'href' => null,
    'type' => 'button',
    'tone' => 'primary',
    'icon' => null,
    'tooltip' => null,
    'iconOnly' => false,
])

@php
    $label = trim(strip_tags((string) $slot));
    $iconMap = [
        'Ajouter' => 'plus',
        'Appliquer' => 'sliders-horizontal',
        'Annuler' => 'x',
        'Bon de commande' => 'clipboard-check',
        'Créer' => 'plus',
        'Créer un BC fournisseur' => 'clipboard-check',
        'Créer une catégorie' => 'plus',
        'Créer un fournisseur' => 'plus',
        'Changer le mot de passe' => 'lock-keyhole',
        "Demande d'achat" => 'file-plus-2',
        'Enregistrer' => 'save',
        'Enregistrer le profil' => 'save',
        'Exporter' => 'download',
        'Facture fournisseur' => 'file-text',
        'Filtrer' => 'filter',
        'Modifier' => 'pencil',
        'Nouveau fournisseur' => 'plus',
        'Nouvelle catégorie' => 'plus',
        'Rapports' => 'bar-chart-3',
        'Réinitialiser' => 'rotate-ccw',
        'Règlement' => 'credit-card',
        'Valider' => 'check',
        'Validations' => 'badge-check',
        'Voir' => 'eye',
    ];
    $resolvedIcon = $icon ?: ($iconMap[$label] ?? null);
    $resolvedTooltip = $tooltip ?: ($iconOnly ? $label : null);
    $classes = [
        'primary' => 'bg-[#2676B3] text-white hover:bg-[#1E5F91]',
        'secondary' => 'border border-[#2676B3]/30 bg-white text-[#2676B3] hover:bg-[#EAF4FB]',
        'success' => 'bg-[#2676B3] text-white hover:bg-[#1E5F91]',
        'danger' => 'bg-red-600 text-white hover:bg-red-700',
        'ghost' => 'text-[#2676B3] hover:bg-[#EAF4FB]',
        'orange' => 'bg-[#FA820A] text-white hover:bg-[#D96B00]',
    ][$tone] ?? 'bg-[#2676B3] text-white hover:bg-[#1E5F91]';
    $sizeClasses = $iconOnly
        ? 'h-9 w-9 px-0 py-0'
        : 'gap-2 px-4 py-2.5';
@endphp

@if($href)
    <a href="{{ $href }}" @if($resolvedTooltip) data-tooltip="{{ $resolvedTooltip }}" aria-label="{{ $resolvedTooltip }}" @endif {{ $attributes->merge(['class' => "inline-flex items-center justify-center rounded-xl text-sm font-semibold transition {$sizeClasses} {$classes}"]) }}>
        @if($resolvedIcon)<i data-lucide="{{ $resolvedIcon }}" class="h-4 w-4 shrink-0"></i>@endif
        @unless($iconOnly)<span>{{ $slot }}</span>@endunless
    </a>
@else
    <button type="{{ $type }}" @if($resolvedTooltip) data-tooltip="{{ $resolvedTooltip }}" aria-label="{{ $resolvedTooltip }}" @endif {{ $attributes->merge(['class' => "inline-flex items-center justify-center rounded-xl text-sm font-semibold transition {$sizeClasses} {$classes}"]) }}>
        @if($resolvedIcon)<i data-lucide="{{ $resolvedIcon }}" class="h-4 w-4 shrink-0"></i>@endif
        @unless($iconOnly)<span>{{ $slot }}</span>@endunless
    </button>
@endif
