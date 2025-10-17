@extends('layouts.nba')
@section('title','All players')

@section('content')
    {{-- Utilities for nicer scroll behavior --}}
    <style>
        .scroll-stable { scrollbar-gutter: stable both-edges; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>

    <main class="pt-20 max-w-7xl mx-auto px-3 sm:px-4 overflow-x-hidden max-w-[100vw]">
        <h1 class="text-2xl sm:text-3xl font-bold text-white mb-4 sm:mb-6">NBA Spēlētāji</h1>

        {{-- Sticky search / controls; capped to viewport width so it never forces page to overflow --}}
        <form method="GET"
              class="sticky top-16 z-20 mb-4 sm:mb-6 bg-[#1f2937]/95 backdrop-blur supports-[backdrop-filter]:bg-[#1f2937]/80
                     p-3 sm:p-4 rounded-lg shadow flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4
                     w-full max-w-[100vw]">
            <div class="w-full sm:flex-1">
                <label for="q" class="sr-only">Meklēt</label>
                <input
                    id="q"
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Meklēt pēc vārda vai komandas..."
                    inputmode="search"
                    autocomplete="off"
                    class="w-full rounded-lg px-3 py-3 bg-[#111827] text-white placeholder-gray-400
                           border border-[#374151] focus:outline-none focus:ring-2 focus:ring-[#84CC16]"
                />
            </div>

            {{-- Per page --}}
            <div class="flex items-center gap-2">
                <label for="perPage" class="text-sm text-gray-300 whitespace-nowrap">Rindu skaits:</label>
                <select id="perPage" name="perPage" onchange="this.form.submit()"
                        class="rounded-lg px-3 py-3 bg-[#111827] text-white border border-[#374151]
                               focus:ring-2 focus:ring-[#84CC16]">
                    @foreach([10,25,50,100,200] as $pp)
                        <option value="{{ $pp }}" @selected((int)request('perPage', 50) === $pp)>{{ $pp }}</option>
                    @endforeach
                </select>
            </div>

            @php
                $sort = request('sort','name');
                $dir  = request('dir','asc') === 'desc' ? 'desc' : 'asc';
            @endphp

            {{-- Mobile sort controls (selects); hidden on md+ where header links handle sorting --}}
            <div class="grid grid-cols-2 gap-2 sm:hidden">
                <select name="sort"
                        class="col-span-1 rounded-lg px-3 py-3 bg-[#111827] text-white border border-[#374151] focus:ring-2 focus:ring-[#84CC16]">
                    @foreach(['name'=>'Vārds','team'=>'Komanda','height'=>'Augums','weight'=>'Svars'] as $key => $label)
                        <option value="{{ $key }}" @selected($sort===$key)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="dir"
                        class="col-span-1 rounded-lg px-3 py-3 bg-[#111827] text-white border border-[#374151] focus:ring-2 focus:ring-[#84CC16]">
                    <option value="asc" @selected($dir==='asc')>Augoši</option>
                    <option value="desc" @selected($dir==='desc')>Dilstoši</option>
                </select>
            </div>

            {{-- Hidden inputs preserved only on md+ so table headers can toggle sort --}}
            <input type="hidden" name="sort" value="{{ $sort }}" class="hidden sm:block">
            <input type="hidden" name="dir" value="{{ $dir }}" class="hidden sm:block">

            <div class="sm:ml-auto">
                <button
                    class="w-full sm:w-auto bg-[#84CC16] text-[#111827] px-4 py-3 rounded-lg font-semibold
                           hover:bg-[#a3e635] transition"
                    type="submit"
                >Meklēt</button>
            </div>
        </form>

        @php
            $nextDir = fn($col) => ($sort === $col && $dir === 'asc') ? 'desc' : 'asc';
            $arrow = fn($col) => $sort === $col ? ($dir === 'asc' ? '▲' : '▼') : '';
            $sortUrl = function($col) use ($nextDir) {
                return request()->fullUrlWithQuery([
                    'sort' => $col,
                    'dir'  => $nextDir($col),
                    'page' => 1,
                ]);
            };
        @endphp

        @if($players->count())

            {{-- MOBILE: card list (no horizontal overflow; content shrinks/truncates) --}}
            <div class="space-y-3 md:hidden">
                @foreach($players as $player)
                    <a href="{{ route('nba.player.show', $player->external_id) }}"
                       class="block rounded-xl bg-[#1f2937] ring-1 ring-[#374151] p-3 shadow hover:ring-[#84CC16] transition">
                        <div class="flex items-center gap-3">
                            <img
                                src="{{ $player->image ?: 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==' }}"
                                alt="Foto"
                                class="h-14 w-14 rounded-full object-cover ring-2 ring-[#84CC16] shrink-0"
                                loading="lazy">
                            <div class="min-w-0">
                                <div class="text-base font-semibold text-white truncate">
                                    {{ $player->full_name }}
                                </div>
                                <div class="text-sm text-gray-300">
                                    @if($player->team_id)
                                        <span class="text-[#84CC16]">{{ $player->team_name ?? 'N/A' }}</span>
                                    @else
                                        <span class="text-gray-400">Brīvais aģents</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-2 text-sm text-gray-300">
                            <div class="rounded-lg bg-[#111827] px-3 py-2">
                                <div class="text-gray-400 text-xs">Augums</div>
                                <div class="text-[#F3F4F6]">{{ $player->display_height ?? '-' }}</div>
                            </div>
                            <div class="rounded-lg bg-[#111827] px-3 py-2">
                                <div class="text-gray-400 text-xs">Svars</div>
                                <div class="text-[#F3F4F6]">{{ $player->display_weight ?? '-' }}</div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            {{-- DESKTOP/TABLET: table; ONLY this wrapper scrolls horizontally --}}
            <div class="overflow-x-auto scroll-stable bg-[#1f2937] shadow rounded-lg hidden md:block [-webkit-overflow-scrolling:touch]">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-[#111827] border-b border-[#374151] text-gray-400">
                        <tr>
                            <th class="px-4 py-3">
                                <a href="{{ $sortUrl('name') }}" class="flex items-center gap-2 hover:text-[#84CC16]">
                                    <span>Vārds</span>
                                    <span class="text-xs">{{ $arrow('name') }}</span>
                                </a>
                            </th>
                            <th class="px-4 py-3">
                                <a href="{{ $sortUrl('team') }}" class="flex items-center gap-2 hover:text-[#84CC16]">
                                    <span>Komanda</span>
                                    <span class="text-xs">{{ $arrow('team') }}</span>
                                </a>
                            </th>
                            <th class="px-4 py-3">
                                <a href="{{ $sortUrl('height') }}" class="flex items-center gap-2 hover:text-[#84CC16]">
                                    <span>Augums</span>
                                    <span class="text-xs">{{ $arrow('height') }}</span>
                                </a>
                            </th>
                            <th class="px-4 py-3">
                                <a href="{{ $sortUrl('weight') }}" class="flex items-center gap-2 hover:text-[#84CC16]">
                                    <span>Svars</span>
                                    <span class="text-xs">{{ $arrow('weight') }}</span>
                                </a>
                            </th>
                            <th class="px-4 py-3">Foto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#374151] text-[#F3F4F6]">
                        @foreach($players as $player)
                            <tr class="odd:bg-[#1f2937] even:bg-[#111827] hover:bg-[#374151]/70 transition">
                                <td class="px-4 py-3">
                                    <a href="{{ route('nba.player.show', $player->external_id) }}"
                                       class="text-[#84CC16] hover:underline font-medium">
                                        {{ $player->full_name }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    @if($player->team_id)
                                        <a href="{{ route('nba.team.show', $player->team_id) }}"
                                           class="text-[#84CC16] hover:underline">
                                            {{ $player->team_name ?? 'N/A' }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">Brīvais aģents</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $player->display_height ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $player->display_weight ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    @if($player->image)
                                        <img src="{{ $player->image }}" alt="Photo"
                                             class="h-10 w-10 rounded-full object-cover ring-2 ring-[#84CC16]" loading="lazy">
                                    @else
                                        <span class="text-gray-500">No photo</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="mt-4 sm:mt-6">
                {{-- MOBILE: large prev/next + short scrollable number row --}}
                <nav class="md:hidden sticky bottom-2 z-30 px-2">
                    <div class="bg-[#1f2937]/95 backdrop-blur supports-[backdrop-filter]:bg-[#1f2937]/80
                                rounded-2xl ring-1 ring-[#374151] p-3 shadow-lg">

                        {{-- Prev / Next --}}
                        <div class="flex items-center justify-between gap-2">
                            @php
                                $currentPage = $players->currentPage();
                                $prevUrl = $players->onFirstPage() ? null : request()->fullUrlWithQuery(['page' => $currentPage - 1]);
                                $nextUrl = $players->hasMorePages() ? request()->fullUrlWithQuery(['page' => $currentPage + 1]) : null;
                            @endphp

                            <a
                                href="{{ $prevUrl ?: '#' }}"
                                class="flex-1 inline-flex items-center justify-center h-11 rounded-xl
                                       font-semibold px-3
                                       {{ $players->onFirstPage()
                                            ? 'bg-[#0b1220] text-gray-400 pointer-events-none'
                                            : 'bg-[#111827] text-white hover:bg-[#0f172a]' }}"
                                aria-label="Iepriekšējā lapa"
                            >← Iepriekš</a>

                            <a
                                href="{{ $nextUrl ?: '#' }}"
                                class="flex-1 inline-flex items-center justify-center h-11 rounded-xl
                                       font-semibold px-3
                                       {{ $players->hasMorePages()
                                            ? 'bg-[#111827] text-white hover:bg-[#0f172a]'
                                            : 'bg-[#0b1220] text-gray-400 pointer-events-none' }}"
                                aria-label="Nākamā lapa"
                            >Nākamā →</a>
                        </div>

                        {{-- Compact page numbers (scrollable, snap to center) --}}
                        @php
                            $current = $players->currentPage();
                            $last    = $players->lastPage();
                            $start   = max(1, $current - 3);
                            $end     = min($last, $current + 3);
                        @endphp
                        <div class="mt-3 overflow-x-auto no-scrollbar">
                            <ol class="min-w-max flex items-center gap-2 px-1 snap-x snap-mandatory">
                                @if($start > 1)
                                    <li class="snap-center">
                                        <a href="{{ request()->fullUrlWithQuery(['page' => 1]) }}"
                                           class="inline-flex items-center justify-center w-11 h-11 rounded-xl
                                                  bg-[#111827] text-white hover:bg-[#0f172a]"
                                           aria-label="Lapa 1">1</a>
                                    </li>
                                    @if($start > 2)
                                        <li class="text-gray-400 px-1">…</li>
                                    @endif
                                @endif

                                @for($p = $start; $p <= $end; $p++)
                                    <li class="snap-center">
                                        <a href="{{ request()->fullUrlWithQuery(['page' => $p]) }}"
                                           class="inline-flex items-center justify-center w-11 h-11 rounded-xl
                                                  {{ $p === $current
                                                      ? 'bg-[#84CC16] text-[#111827] font-bold'
                                                      : 'bg-[#0f172a] text-gray-200 hover:bg-[#111827]' }}"
                                           aria-current="{{ $p === $current ? 'page' : 'false' }}"
                                           aria-label="Lapa {{ $p }}">{{ $p }}</a>
                                    </li>
                                @endfor

                                @if($end < $last)
                                    @if($end < $last - 1)
                                        <li class="text-gray-400 px-1">…</li>
                                    @endif
                                    <li class="snap-center">
                                        <a href="{{ request()->fullUrlWithQuery(['page' => $last]) }}"
                                           class="inline-flex items-center justify-center w-11 h-11 rounded-xl
                                                  bg-[#111827] text-white hover:bg-[#0f172a]"
                                           aria-label="Pēdējā lapa ({{ $last }})">{{ $last }}</a>
                                    </li>
                                @endif
                            </ol>
                        </div>

                        <div class="mt-2 text-center text-xs text-gray-400">
                            {{ $current }} / {{ $last }}
                        </div>
                    </div>
                </nav>

                {{-- DESKTOP/TABLET: keep your existing paginator --}}
                <div class="hidden md:flex justify-center">
                    {{ $players->appends(request()->query())->links('vendor.pagination.custom-dark') }}
                </div>
            </div>

        @else
            <p class="text-gray-400 mt-4">Nav atrasti spēlētāji.</p>
        @endif

    </main>
    <br>
    <br>
@endsection
