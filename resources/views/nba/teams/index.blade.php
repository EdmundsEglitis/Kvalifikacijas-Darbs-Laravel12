@extends('layouts.nba')
@section('title','All teams')

@section('content')
    <main class="pt-20 max-w-7xl mx-auto px-4">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
            <h1 class="text-3xl font-bold text-white">Visas NBA Komandas</h1>

            {{-- slim search --}}
            <form method="GET" class="w-full sm:w-80">
                <input
                    type="text"
                    name="q"
                    value="{{ $q ?? '' }}"
                    placeholder="🔍 Meklē koamndas…"
                    class="w-full rounded-lg px-4 py-2 bg-[#1f2937] text-white placeholder-gray-400 border border-[#374151] focus:outline-none focus:ring-2 focus:ring-[#84CC16]"
                />
            </form>
        </div>

        @if($teams->count())
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-5">
                @foreach($teams as $team)
                    <a href="{{ route('nba.team.show', $team->external_id) }}"
                       class="group bg-[#1f2937] rounded-xl shadow hover:bg-[#374151] transition p-4 flex flex-col items-center text-center">
                        @if($team->logo)
                            <img src="{{ $team->logo }}"
                                 alt="{{ $team->name }}"
                                 class="h-16 w-16 mb-3 object-contain"
                                 loading="lazy">
                        @else
                            <div class="h-16 w-16 mb-3 rounded-full bg-[#111827] flex items-center justify-center text-xs text-gray-400">
                                Nav Logo
                            </div>
                        @endif

                        <div class="space-y-1">
                            <h2 class="text-sm font-semibold text-white group-hover:text-[#84CC16]">
                                {{ $team->name }}
                            </h2>
                            <p class="text-xs text-gray-400">
                                {{ $team->short_name ?? $team->abbreviation ?? '—' }}
                            </p>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-6 flex justify-between items-center text-sm text-gray-300">
                <div>
                    @php
                        $from = ($teams->currentPage() - 1) * $teams->perPage() + 1;
                        $to   = min($teams->total(), $teams->currentPage() * $teams->perPage());
                    @endphp
                    Rāda {{ $from }}–{{ $to }} no {{ $teams->total() }} komandām
                </div>
                <div>
                    {{ $teams->links(view()->exists('vendor.pagination.custom-dark') ? 'vendor.pagination.custom-dark' : 'pagination::tailwind') }}
                </div>
            </div>
        @else
            <p class="text-gray-400">Nav atrastas komandas.</p>
        @endif
    </main>
</body>
</html>
@endsection