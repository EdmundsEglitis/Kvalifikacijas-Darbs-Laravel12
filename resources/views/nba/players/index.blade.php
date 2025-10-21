@extends('layouts.nba')
@section('title','All players')

@section('content')
    <style>
        .scroll-stable { scrollbar-gutter: stable both-edges; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>

    {{-- Align to navbar width on desktop, full width on mobile --}}
    <main class="pt-20 md:max-w-7xl md:mx-auto md:px-4 min-h-screen">
        <div class="px-4">
            <h1 class="text-2xl sm:text-3xl font-bold text-white mb-4 sm:mb-6">NBA Spēlētāji</h1>
        </div>

        @php
            $sort = request('sort','name');               // name | team | height | weight
            $dir  = request('dir','asc') === 'desc' ? 'desc' : 'asc';
            $per  = (int) request('perPage', 50);
            $q    = (string) request('q', '');

            $is = fn($c) => $sort === $c;
            $nextDir = fn($c) => ($sort === $c && $dir === 'asc') ? 'desc' : 'asc';
            $arrow = fn($c) => $sort === $c ? ($dir === 'asc' ? '▲' : '▼') : '';
            $sortUrl = function (string $col, ?string $forceDir = null) use ($nextDir) {
                return request()->fullUrlWithQuery([
                    'sort' => $col,
                    'dir'  => $forceDir ?: $nextDir($col),
                    'page' => 1,
                ]);
            };
        @endphp

        {{-- Search + basic controls (sticky) --}}
        <div class="px-4">
            <form method="GET"
                  class="sticky top-16 z-20 mb-3 sm:mb-4 bg-[#1f2937]/95 backdrop-blur supports-[backdrop-filter]:bg-[#1f2937]/80
                         p-3 sm:p-4 rounded-lg shadow w-full">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
                    <div class="w-full sm:flex-1">
                        <label for="q" class="sr-only">Meklēt</label>
                        <div class="relative">
                            <input id="q" type="search" name="q" value="{{ $q }}"
                                   placeholder="Meklēt pēc vārda vai komandas…"
                                   inputmode="search" autocomplete="off"
                                   class="w-full rounded-lg pl-10 pr-3 py-3 bg-[#111827] text-white placeholder-gray-400
                                          border border-[#374151] focus:outline-none focus:ring-2 focus:ring-[#84CC16]" />
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">🔎</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <label for="perPage" class="text-sm text-gray-300 whitespace-nowrap">Rindu skaits:</label>
                        <select id="perPage" name="perPage" onchange="this.form.submit()"
                                class="rounded-lg px-3 py-3 bg-[#111827] text-white border border-[#374151] focus:ring-2 focus:ring-[#84CC16]">
                            @foreach([12,24,48,96,192] as $pp)
                                <option value="{{ $pp }}" @selected($per === $pp)>{{ $pp }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Keep current sort while searching --}}
                    <input type="hidden" name="sort" value="{{ $sort }}">
                    <input type="hidden" name="dir"  value="{{ $dir }}">

                    <button class="w-full sm:w-auto bg-[#84CC16] text-[#111827] px-4 py-3 rounded-lg font-semibold hover:bg-[#a3e635] transition"
                            type="submit">Meklēt</button>
                </div>

                {{-- SORT BAR (cards-friendly) --}}
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    {{-- Sort “chips” --}}
                    <a href="{{ $sortUrl('name') }}"
                       class="inline-flex items-center gap-1 rounded-full px-3 py-2 text-sm
                              {{ $is('name') ? 'bg-[#84CC16] text-[#111827] font-semibold' : 'bg-[#111827] text-gray-200 hover:bg-[#0f172a]' }}">
                        Vārds {!! $is('name') ? '<span class="text-[11px]">'.$arrow('name').'</span>' : '' !!}
                    </a>
                    <a href="{{ $sortUrl('team') }}"
                       class="inline-flex items-center gap-1 rounded-full px-3 py-2 text-sm
                              {{ $is('team') ? 'bg-[#84CC16] text-[#111827] font-semibold' : 'bg-[#111827] text-gray-200 hover:bg-[#0f172a]' }}">
                        Komanda {!! $is('team') ? '<span class="text-[11px]">'.$arrow('team').'</span>' : '' !!}
                    </a>
                    <a href="{{ $sortUrl('height') }}"
                       class="inline-flex items-center gap-1 rounded-full px-3 py-2 text-sm
                              {{ $is('height') ? 'bg-[#84CC16] text-[#111827] font-semibold' : 'bg-[#111827] text-gray-200 hover:bg-[#0f172a]' }}">
                        Augums {!! $is('height') ? '<span class="text-[11px]">'.$arrow('height').'</span>' : '' !!}
                    </a>
                    <a href="{{ $sortUrl('weight') }}"
                       class="inline-flex items-center gap-1 rounded-full px-3 py-2 text-sm
                              {{ $is('weight') ? 'bg-[#84CC16] text-[#111827] font-semibold' : 'bg-[#111827] text-gray-200 hover:bg-[#0f172a]' }}">
                        Svars {!! $is('weight') ? '<span class="text-[11px]">'.$arrow('weight').'</span>' : '' !!}
                    </a>

                    {{-- Direction toggle --}}
                    <a href="{{ request()->fullUrlWithQuery(['dir' => $dir === 'asc' ? 'desc' : 'asc', 'page' => 1]) }}"
                       class="ml-auto inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm bg-[#0f172a] text-gray-200 hover:bg-[#111827]"
                       aria-label="Mainīt kārtošanas virzienu">
                        {{ $dir === 'asc' ? '↑ Augoši' : '↓ Dilstoši' }}
                    </a>
                </div>
            </form>
        </div>

        @if($players->count())
            {{-- CARD GRID (replaces table on all viewports) --}}
            <section class="px-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                    @foreach($players as $player)
                        <a href="{{ route('nba.player.show', $player->external_id) }}"
                           class="group rounded-xl bg-[#1f2937] ring-1 ring-[#374151] p-3 sm:p-4 shadow
                                  hover:ring-[#84CC16] transition focus:outline-none focus:ring-2 focus:ring-[#84CC16]"
                           aria-label="Skatīt {{ $player->full_name }} profilu">
                            <div class="flex items-start gap-3">
                                {{-- avatar --}}
                                @if($player->image)
                                    <img src="{{ $player->image }}" alt="Foto: {{ $player->full_name }}"
                                         class="h-14 w-14 rounded-full object-cover ring-2 ring-[#84CC16] shrink-0"
                                         width="56" height="56" loading="lazy" decoding="async">
                                @else
                                    <span class="h-14 w-14 rounded-full bg-[#0f172a] text-gray-400 ring-1 ring-[#374151] grid place-items-center shrink-0">—</span>
                                @endif

                                {{-- main info --}}
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <h2 class="text-white font-semibold truncate group-hover:text-[#84CC16]">
                                            {{ $player->full_name }}
                                        </h2>
                                        @if($player->team_id)
                                            <span class="ml-auto hidden sm:inline-flex text-xs px-2 py-1 rounded-full bg-[#0f172a] text-gray-200">
                                                {{ $player->team_name ?? 'N/A' }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="mt-1 text-sm">
                                        {{-- team (mobile visible) --}}
                                        <div class="sm:hidden text-[#84CC16] truncate">
                                            @if($player->team_id)
                                                {{ $player->team_name ?? 'N/A' }}
                                            @else
                                                <span class="text-gray-400">Brīvais aģents</span>
                                            @endif
                                        </div>

                                        {{-- meta row --}}
                                        <div class="mt-2 flex items-center gap-2 text-gray-300">
                                            <div class="flex items-center gap-1 rounded-lg bg-[#111827] px-2 py-1">
                                                <span class="text-xs text-gray-400">Augums</span>
                                                <span class="font-medium text-[#F3F4F6]">{{ $player->display_height ?? '-' }}</span>
                                            </div>
                                            <div class="flex items-center gap-1 rounded-lg bg-[#111827] px-2 py-1">
                                                <span class="text-xs text-gray-400">Svars</span>
                                                <span class="font-medium text-[#F3F4F6]">{{ $player->display_weight ?? '-' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- foot actions --}}
                            <div class="mt-3 flex items-center justify-between text-xs text-gray-400">
                                <span>Klikšķini, lai skatītu profilu</span>
                                <span class="inline-flex items-center gap-1 text-[#84CC16]">Skatīt →</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>

            {{-- Pagination --}}
            <div class="mt-4 sm:mt-6 px-4">
                <div class="flex justify-center">
                    {{ $players->appends(request()->query())->links('vendor.pagination.custom-dark') }}
                </div>
            </div>
        @else
            <div class="mt-6 px-4">
                <div class="rounded-xl bg-[#111827] ring-1 ring-[#374151] p-6 text-gray-300">
                    <p class="text-lg font-semibold">Nav atrasti spēlētāji.</p>
                    <p class="text-sm mt-1 text-gray-400">Pamēģini citu meklēšanas frāzi vai atiestati filtrus.</p>
                    <div class="mt-3">
                        <a href="{{ route(\Illuminate\Support\Facades\Route::currentRouteName()) }}"
                           class="inline-flex items-center gap-2 bg-[#84CC16] text-[#111827] px-4 py-2 rounded-lg font-semibold hover:bg-[#a3e635] transition">
                            Reset
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </main>
    <br><br>
@endsection
