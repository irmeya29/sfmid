@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm font-semibold text-slate-500">
            Affichage de {{ $paginator->firstItem() }} à {{ $paginator->lastItem() }} sur {{ $paginator->total() }} résultats
        </p>

        <div class="flex flex-wrap items-center gap-2">
            @if ($paginator->onFirstPage())
                <span class="rounded-xl border border-slate-200 bg-slate-100 px-3 py-2 text-sm font-bold text-slate-400">Précédent</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100">Précédent</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 py-2 text-sm font-bold text-slate-400">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="rounded-xl bg-slate-950 px-3 py-2 text-sm font-black text-white">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100">Suivant</a>
            @else
                <span class="rounded-xl border border-slate-200 bg-slate-100 px-3 py-2 text-sm font-bold text-slate-400">Suivant</span>
            @endif
        </div>
    </nav>
@endif
