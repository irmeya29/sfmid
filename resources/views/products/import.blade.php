@extends('layouts.app')

@section('title', 'Import produits | SFMID Gestion')
@section('subtitle', 'Catalogue et stock')
@section('page-title', 'Import produits')

@section('content')
    <div class="max-w-5xl">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('products.create') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-950">
                &larr; Retour au nouveau produit
            </a>

            <x-button :href="route('products.import.template')" tone="secondary" icon="file-down">Telecharger le modele</x-button>
        </div>

        <form id="product-import-form" method="POST" action="{{ route('products.import') }}" enctype="multipart/form-data" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf

            <div class="grid gap-5 lg:grid-cols-[1fr_auto] lg:items-end">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Fichier CSV produits</label>
                    <input id="csv-file" type="file" name="csv_file" accept=".csv,text/csv,text/plain" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-[#2676B3] focus:ring-2 focus:ring-[#2676B3]/10">
                    <p class="mt-2 text-xs text-slate-500">Le code produit est obligatoire. Les codes deja existants ou repetes dans le fichier sont ignores.</p>
                    @error('csv_file')
                        <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <x-button id="import-submit" type="submit" icon="upload">Importer</x-button>
            </div>

            <div class="mt-6">
                <div class="mb-2 flex items-center justify-between text-sm font-semibold text-slate-700">
                    <span>Progression</span>
                    <span id="import-progress-label">{{ session('import_progress', 0) }}%</span>
                </div>
                <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                    <div id="import-progress-bar" class="h-full rounded-full bg-[#2676B3] transition-all duration-300" style="width: {{ session('import_progress', 0) }}%"></div>
                </div>
            </div>
        </form>

        @if(session('import_errors'))
            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <p class="font-semibold">Certaines lignes ont ete ignorees :</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach(session('import_errors') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <script>
        const importForm = document.getElementById('product-import-form');
        const progressBar = document.getElementById('import-progress-bar');
        const progressLabel = document.getElementById('import-progress-label');
        const submitButton = document.getElementById('import-submit');

        function setImportProgress(value) {
            progressBar.style.width = `${value}%`;
            progressLabel.textContent = `${value}%`;
        }

        importForm?.addEventListener('submit', () => {
            let progress = 0;

            submitButton?.setAttribute('disabled', 'disabled');
            setImportProgress(progress);

            const interval = window.setInterval(() => {
                progress = Math.min(progress + 5, 95);
                setImportProgress(progress);

                if (progress >= 95) {
                    window.clearInterval(interval);
                }
            }, 180);
        });
    </script>
@endsection
