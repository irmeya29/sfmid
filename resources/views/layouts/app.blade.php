<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SFMID Gestion')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Montserrat', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --sfmid-blue: #2676B3;
            --sfmid-blue-dark: #1E5F91;
            --sfmid-blue-soft: #EAF4FB;
            --sfmid-orange: #FA820A;
            --sfmid-orange-dark: #D96B00;
            --sfmid-orange-soft: #FFF3E7;
            --sfmid-white: #FFFFFF;
            --sfmid-ink: #17324D;
        }
        body { font-family: Montserrat, ui-sans-serif, system-ui, sans-serif; font-weight: 400; letter-spacing: 0; }
        body.bg-slate-100 { background: linear-gradient(180deg, #f7fbfe 0%, #eef6fb 100%) !important; }
        .font-black { font-weight: 700 !important; }
        .font-bold { font-weight: 600 !important; }
        .font-semibold { font-weight: 500 !important; }
        main table { width: 100%; border-collapse: separate; border-spacing: 0; }
        main thead { background: var(--sfmid-blue-soft); }
        main th { color: var(--sfmid-blue-dark); font-size: .72rem; font-weight: 600; letter-spacing: 0; text-transform: uppercase; white-space: nowrap; }
        main th, main td { border-bottom: 1px solid #dbe8f2; padding: .9rem 1rem; vertical-align: middle; }
        main tbody tr:hover { background: #f8fbfe; }
        main input, main select, main textarea { border-radius: .75rem; background: #fff; }
        main input:focus, main select:focus, main textarea:focus { border-color: var(--sfmid-blue) !important; box-shadow: 0 0 0 3px rgba(38, 118, 179, .12) !important; outline: none !important; }
        main nav[role="navigation"] span, main nav[role="navigation"] a { border-radius: .75rem !important; }
        [data-tooltip] { position: relative; }
        [data-tooltip]::before,
        [data-tooltip]::after { opacity: 0; pointer-events: none; position: absolute; transition: opacity .14s ease, transform .14s ease; z-index: 60; }
        [data-tooltip]::before {
            background: var(--sfmid-ink);
            border-radius: .55rem;
            bottom: calc(100% + .55rem);
            color: #fff;
            content: attr(data-tooltip);
            font-size: .68rem;
            font-weight: 500;
            left: 50%;
            max-width: 12rem;
            padding: .38rem .55rem;
            text-align: center;
            transform: translateX(-50%) translateY(4px);
            white-space: nowrap;
        }
        [data-tooltip]::after {
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-top: 5px solid var(--sfmid-ink);
            bottom: calc(100% + .25rem);
            content: "";
            left: 50%;
            transform: translateX(-50%) translateY(4px);
        }
        [data-tooltip]:hover::before,
        [data-tooltip]:hover::after,
        [data-tooltip]:focus-visible::before,
        [data-tooltip]:focus-visible::after { opacity: 1; transform: translateX(-50%) translateY(0); }
        details > summary::-webkit-details-marker { display: none; }
        details[open] .submenu { animation: menuSlide .16s ease-out; }
        @keyframes menuSlide { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }

        .bg-slate-950 { background-color: var(--sfmid-blue) !important; }
        .hover\:bg-slate-800:hover, .hover\:bg-cyan-800:hover, .hover\:bg-blue-700:hover { background-color: var(--sfmid-blue-dark) !important; }
        .text-slate-950, .text-cyan-900, .text-cyan-800, .text-cyan-700, .text-blue-700, .text-indigo-700 { color: var(--sfmid-blue) !important; }
        .border-slate-300:focus, .focus\:border-slate-900:focus, .focus\:border-cyan-700:focus, .focus\:border-cyan-600:focus { border-color: var(--sfmid-blue) !important; }
        .focus\:ring-slate-900\/10:focus, .focus\:ring-cyan-700\/10:focus, .focus\:ring-cyan-100:focus { --tw-ring-color: rgba(38, 118, 179, .14) !important; }
        .bg-cyan-50, .bg-blue-50, .bg-indigo-50 { background-color: var(--sfmid-blue-soft) !important; }
        .bg-cyan-600, .bg-blue-600, .bg-indigo-600, .bg-purple-600, .bg-emerald-600, .bg-green-600 { background-color: var(--sfmid-blue) !important; }
        .hover\:bg-cyan-700:hover, .hover\:bg-indigo-700:hover, .hover\:bg-purple-700:hover, .hover\:bg-emerald-700:hover, .hover\:bg-green-700:hover { background-color: var(--sfmid-blue-dark) !important; }
        .border-cyan-200, .border-blue-200, .border-indigo-200 { border-color: rgba(38, 118, 179, .28) !important; }
        .bg-amber-50, .bg-orange-50, .bg-yellow-50 { background-color: var(--sfmid-orange-soft) !important; }
        .bg-amber-100, .bg-orange-100, .bg-yellow-100 { background-color: #FFE3C2 !important; }
        .text-amber-700, .text-amber-800, .text-amber-900, .text-orange-700, .text-orange-800, .text-yellow-800 { color: var(--sfmid-orange-dark) !important; }
        .bg-amber-600, .bg-orange-600 { background-color: var(--sfmid-orange) !important; }
        .hover\:bg-amber-700:hover, .hover\:bg-orange-700:hover { background-color: var(--sfmid-orange-dark) !important; }
        .border-amber-200, .border-orange-200, .border-yellow-200 { border-color: rgba(250, 130, 10, .32) !important; }
        .ring-slate-200 { --tw-ring-color: rgba(38, 118, 179, .22) !important; }
        .shadow-sm { --tw-shadow-color: rgba(38, 118, 179, .10); }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
@php
    $user = auth()->user();
    $can = fn (string $permission): bool => (bool) $user?->hasPermission($permission);
    $canAny = fn (array $permissions): bool => (bool) $user?->hasAnyPermission($permissions);

    $pendingValidationCount =
        \App\Models\Proforma::query()->where('status', \App\Enums\DocumentStatus::PendingValidation->value)->count()
        + \App\Models\DeliveryNote::query()->where('status', \App\Enums\DocumentStatus::PendingValidation->value)->count()
        + \App\Models\Invoice::query()->where('status', \App\Enums\InvoiceStatus::PendingValidation->value)->count()
        + \App\Models\Payment::query()->where('status', \App\Enums\PaymentStatus::PendingValidation->value)->count()
        + \App\Models\Expense::query()->where('status', \App\Enums\ExpenseStatus::PendingValidation->value)->count()
        + \App\Models\StockMovement::query()->where('status', \App\Enums\StockMovementStatus::PendingValidation->value)->count();

    $lowStockCount = \App\Models\Product::query()->whereColumn('physical_stock', '<=', 'alert_threshold')->count();
    $overdueInvoiceCount = \App\Models\Invoice::query()->unpaid()->whereDate('due_date', '<', today())->count();
    $notificationCount = $pendingValidationCount + $lowStockCount + $overdueInvoiceCount;

    $quickLinks = [
        ['label' => 'Tableau de bord', 'route' => 'dashboard', 'icon' => 'layout-dashboard', 'show' => $can('dashboard.view'), 'active' => ['dashboard'], 'count' => null],
        ['label' => 'Validations', 'route' => 'validations.index', 'icon' => 'badge-check', 'show' => $can('validations.view'), 'active' => ['validations.*'], 'count' => $pendingValidationCount],
        ['label' => 'Rapports', 'route' => 'reports.index', 'icon' => 'bar-chart-3', 'show' => $canAny(['reports.view_sales', 'reports.view_finance', 'reports.view_stock', 'reports.view_expenses']), 'active' => ['reports.*'], 'count' => null],
    ];

    $navGroups = [
        [
            'label' => 'Facturation',
            'caption' => 'Clients, ventes et encaissements',
            'icon' => 'receipt-text',
            'routes' => ['clients.*', 'proformas.*', 'delivery-notes.*', 'invoices.*', 'payments.*'],
            'items' => [
                ['label' => 'Clients', 'route' => 'clients.index', 'icon' => 'users', 'show' => $can('clients.view'), 'active' => ['clients.*']],
                ['label' => 'Proformas', 'route' => 'proformas.index', 'icon' => 'file-text', 'show' => $can('proformas.view'), 'active' => ['proformas.*']],
                ['label' => 'BC clients', 'route' => 'customer-orders.index', 'icon' => 'clipboard-check', 'show' => $can('proformas.view'), 'active' => ['customer-orders.*']],
                ['label' => 'Livraisons', 'route' => 'delivery-notes.index', 'icon' => 'truck', 'show' => $can('delivery_notes.view'), 'active' => ['delivery-notes.*']],
                ['label' => 'Factures', 'route' => 'invoices.index', 'icon' => 'file-badge', 'show' => $can('invoices.view'), 'active' => ['invoices.*']],
                ['label' => 'Paiements', 'route' => 'payments.index', 'icon' => 'credit-card', 'show' => $can('payments.view'), 'active' => ['payments.*']],
            ],
        ],
        [
            'label' => 'Stock & inventaire',
            'caption' => 'Produits, mouvements, alertes',
            'icon' => 'boxes',
            'routes' => ['stock.*', 'products.*', 'product-categories.*'],
            'items' => [
                ['label' => 'Stock physique', 'route' => 'stock.physical', 'icon' => 'warehouse', 'show' => $can('stock.view'), 'active' => ['stock.physical', 'stock.reserved', 'stock.suspense', 'stock.tool']],
                ['label' => 'Mouvements', 'route' => 'stock.movements', 'icon' => 'arrow-left-right', 'show' => $can('stock.view'), 'active' => ['stock.movements', 'stock.entries.*', 'stock.exits.*', 'stock.adjustments.*']],
                ['label' => 'Rapports stock', 'route' => 'stock.reports.low-stock', 'icon' => 'clipboard-list', 'show' => $can('stock.view'), 'active' => ['stock.reports.*']],
                ['label' => 'Produits', 'route' => 'products.index', 'icon' => 'package', 'show' => $can('products.view'), 'active' => ['products.*']],
                ['label' => 'Catégories produits', 'route' => 'product-categories.index', 'icon' => 'tags', 'show' => $can('products.view'), 'active' => ['product-categories.*']],
            ],
        ],
        [
            'label' => 'Trésorerie',
            'caption' => 'Recettes automatiques et charges',
            'icon' => 'wallet-cards',
            'routes' => ['treasury.*', 'expense-categories.*'],
            'items' => [
                ['label' => 'Journal de trésorerie', 'route' => 'treasury.index', 'icon' => 'landmark', 'show' => $can('treasury.view'), 'active' => ['treasury.*']],
                ['label' => 'Catégories charges', 'route' => 'expense-categories.index', 'icon' => 'list-tree', 'show' => $can('expense_categories.view'), 'active' => ['expense-categories.*']],
            ],
        ],
        [
            'label' => 'Achats',
            'caption' => 'Fournisseurs et commandes',
            'icon' => 'shopping-cart',
            'routes' => ['suppliers.*', 'purchases.*'],
            'items' => [
                ['label' => 'Fournisseurs', 'route' => 'suppliers.index', 'icon' => 'factory', 'show' => $can('suppliers.view'), 'active' => ['suppliers.*']],
                ['label' => 'Achats fournisseurs', 'route' => 'purchases.index', 'icon' => 'clipboard-check', 'show' => $can('purchases.view'), 'active' => ['purchases.*']],
            ],
        ],
        [
            'label' => 'Administration',
            'caption' => 'Accès, rôles et audit',
            'icon' => 'shield-check',
            'routes' => ['users.*', 'roles.*', 'activity-logs.*'],
            'items' => [
                ['label' => 'Utilisateurs', 'route' => 'users.index', 'icon' => 'user-cog', 'show' => $can('users.view'), 'active' => ['users.*']],
                ['label' => 'Rôles et permissions', 'route' => 'roles.index', 'icon' => 'key-round', 'show' => $can('roles.view'), 'active' => ['roles.*']],
                ['label' => 'Journal d’activité', 'route' => 'activity-logs.index', 'icon' => 'history', 'show' => $can('activity_logs.view'), 'active' => ['activity-logs.*']],
            ],
        ],
    ];

    $bottomLinks = [
        ['label' => 'Paramètres', 'route' => 'settings.index', 'icon' => 'settings', 'show' => $can('settings.view'), 'active' => ['settings.*']],
    ];
@endphp

<div class="flex min-h-screen">
    <div id="mobile-overlay" class="fixed inset-0 z-30 hidden bg-slate-950/50 backdrop-blur-sm lg:hidden"></div>

    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-80 -translate-x-full border-r border-slate-200 bg-white shadow-2xl transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 lg:shadow-none">
        <div class="flex h-full flex-col">
            <div class="border-b border-slate-200 px-5 py-5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                            <img src="{{ route('brand.logo') }}" alt="Logo SFMID" class="h-full w-full object-contain p-1.5">
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase text-cyan-700">SFMID</p>
                            <h1 class="text-base font-black text-slate-950">Gestion commerciale</h1>
                        </div>
                    </div>
                    <button type="button" id="close-sidebar" class="rounded-xl border border-slate-200 p-2 text-slate-600 hover:bg-slate-100 lg:hidden" title="Fermer le menu">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto px-4 py-4 text-sm">
                <div class="space-y-1">
                    @foreach($quickLinks as $item)
                        @if($item['show'])
                            @php
                                $isActive = request()->routeIs(...$item['active']);
                            @endphp
                            <a href="{{ route($item['route']) }}" class="group flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-sm font-bold transition {{ $isActive ? 'bg-slate-950 text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-950' }}">
                                <span class="flex h-6 w-6 items-center justify-center rounded-md {{ $isActive ? 'bg-white/12 text-white' : 'bg-slate-100 text-slate-500 group-hover:bg-white group-hover:text-slate-900' }}">
                                    <i data-lucide="{{ $item['icon'] }}" class="h-3.5 w-3.5"></i>
                                </span>
                                <span class="flex-1">{{ $item['label'] }}</span>
                                @if($item['count'])
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-black text-amber-800">{{ $item['count'] }}</span>
                                @endif
                            </a>
                        @endif
                    @endforeach
                </div>

                <div class="my-5 border-t border-slate-200"></div>

                <div class="space-y-3">
                    @foreach($navGroups as $group)
                        @php
                            $visibleItems = collect($group['items'])->filter(fn ($item) => $item['show']);
                            $isGroupActive = request()->routeIs(...$group['routes']);
                        @endphp

                        @if($visibleItems->isNotEmpty())
                            <section class="rounded-xl border {{ $isGroupActive ? 'border-cyan-200 bg-cyan-50/50' : 'border-slate-200 bg-white' }} px-2 py-2">
                                <div class="mb-1 flex items-center gap-2 px-1.5 py-1">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ $isGroupActive ? 'bg-cyan-100 text-cyan-800' : 'bg-slate-100 text-slate-500' }}">
                                        <i data-lucide="{{ $group['icon'] }}" class="h-4 w-4"></i>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-xs font-black uppercase text-slate-950">{{ $group['label'] }}</span>
                                    </span>
                                </div>

                                <div class="space-y-0.5">
                                    @foreach($visibleItems as $item)
                                        @php
                                            $isActive = request()->routeIs(...$item['active']);
                                        @endphp
                                        <a href="{{ route($item['route']) }}" class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-sm font-bold transition {{ $isActive ? 'bg-cyan-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}">
                                            <i data-lucide="{{ $item['icon'] }}" class="h-4 w-4 shrink-0"></i>
                                            <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    @endforeach
                </div>
            </nav>

            <div class="border-t border-slate-200 p-4">
                <div class="grid gap-2">
                    @foreach($bottomLinks as $item)
                        @if($item['show'])
                            <a href="{{ route($item['route']) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 px-3 py-2.5 text-xs font-black transition {{ request()->routeIs(...$item['active']) ? 'bg-slate-950 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                                <i data-lucide="{{ $item['icon'] }}" class="h-4 w-4"></i>
                                {{ $item['label'] }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </aside>

    <div class="flex min-h-screen min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur">
            <div class="flex items-center justify-between gap-4 px-4 py-4 sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" id="open-sidebar" class="rounded-xl border border-slate-300 p-2 text-slate-700 lg:hidden" title="Ouvrir le menu">
                        <i data-lucide="menu" class="h-5 w-5"></i>
                    </button>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <img src="{{ route('brand.logo') }}" alt="Logo SFMID" class="h-7 w-7 rounded-lg border border-slate-200 bg-white object-contain p-1 lg:hidden">
                            <p class="text-xs font-bold uppercase text-slate-500">@yield('subtitle', 'Application SFMID')</p>
                        </div>
                        <h2 class="mt-1 truncate text-xl font-black text-slate-950">@yield('page-title', 'Tableau de bord')</h2>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ $can('validations.view') ? route('validations.index') : '#' }}" class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-100" title="Notifications">
                        <i data-lucide="bell" class="h-5 w-5"></i>
                        @if($notificationCount > 0)
                            <span class="absolute -right-1 -top-1 min-w-5 rounded-full bg-red-600 px-1.5 py-0.5 text-center text-[10px] font-black text-white">{{ $notificationCount }}</span>
                        @endif
                    </a>
                    <details class="group relative">
                        <summary class="flex cursor-pointer list-none items-center gap-2 rounded-xl border border-slate-200 bg-white px-2 py-2 text-sm font-black text-slate-700 transition hover:bg-slate-100 sm:px-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-950 text-xs font-black text-white">
                                {{ str($user?->name ?? 'U')->substr(0, 1)->upper() }}
                            </span>
                            <span class="hidden max-w-36 truncate sm:block">{{ $user?->name }}</span>
                            <i data-lucide="chevron-down" class="h-4 w-4 text-slate-400 transition group-open:rotate-180"></i>
                        </summary>

                        <div class="absolute right-0 z-30 mt-2 w-64 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl">
                            <div class="border-b border-slate-100 px-4 py-3">
                                <p class="truncate text-sm font-black text-slate-950">{{ $user?->name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $user?->email }}</p>
                            </div>
                            <div class="p-2">
                                <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-100">
                                    <i data-lucide="circle-user-round" class="h-4 w-4 text-slate-500"></i>
                                    Mon profil
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-bold text-red-700 hover:bg-red-50">
                                        <i data-lucide="log-out" class="h-4 w-4"></i>
                                        Déconnexion
                                    </button>
                                </form>
                            </div>
                        </div>
                    </details>
                </div>
            </div>
        </header>

        <main class="min-w-0 flex-1 px-4 py-6 sm:px-6 xl:px-8">
            <x-alert type="success" :message="session('success')" />
            <x-alert type="error" :message="session('error')" />

            @if($errors->any())
                <x-alert type="error" message="Veuillez corriger les champs signalés avant de continuer." />
            @endif

            @yield('content')
        </main>
    </div>
</div>

<x-confirm-modal />

<script>
    lucide.createIcons();

    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobile-overlay');
    const openButton = document.getElementById('open-sidebar');
    const closeButton = document.getElementById('close-sidebar');

    function openSidebar(){ sidebar.classList.remove('-translate-x-full'); overlay.classList.remove('hidden'); }
    function closeSidebar(){ sidebar.classList.add('-translate-x-full'); overlay.classList.add('hidden'); }

    openButton?.addEventListener('click', openSidebar);
    closeButton?.addEventListener('click', closeSidebar);
    overlay?.addEventListener('click', closeSidebar);

    document.querySelectorAll('[data-password-toggle]').forEach(button => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordToggle);
            if (!input) return;

            const visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            button.setAttribute('aria-label', visible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
            button.innerHTML = `<i data-lucide="${visible ? 'eye' : 'eye-off'}" class="h-4 w-4"></i>`;
            lucide.createIcons();
        });
    });
</script>
</body>
</html>
