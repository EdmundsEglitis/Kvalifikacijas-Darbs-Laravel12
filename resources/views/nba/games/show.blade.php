@extends('layouts.nba')
@section('title', 'Spēles protokols')

@push('head')
<style>
  th.sortable { cursor: pointer; user-select: none; }
  th.sortable .arrow { display:inline-block; width:1ch; margin-left:.35rem; opacity:.65; }
  /* Ensure sorting UI never blocks clicks inside the body */
  table.js-sortable-table thead { position: relative; z-index: 1; }
  table.js-sortable-table tbody { position: relative; z-index: 0; }
</style>
@endpush

@section('content')
<main class="max-w-7xl mx-auto px-4 py-6 space-y-6">

  {{-- Galvene / tablo --}}
  <section class="bg-[#1f2937] border border-[#374151] rounded-xl p-4 sm:p-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div class="flex items-center gap-4">

        {{-- Komanda A --}}
        @php
          $teamAId   = $A['team_id'] ?? $A['team_external_id'] ?? null;
          $teamAHref = $A['team_href'] ?? ($teamAId ? route('nba.team.show', $teamAId) : null);
        @endphp
        <div class="flex items-center gap-2">
          @if(!empty($A['logo']))
            <img src="{{ $A['logo'] }}" alt="{{ $A['team'] }} logo" class="h-8 w-8 object-contain rounded bg-white p-[2px]" />
          @else
            <div class="h-8 w-8 rounded bg-white/10"></div>
          @endif

          @if($teamAHref)
            <a href="{{ $teamAHref }}" class="text-white font-semibold hover:text-[#84CC16]">
              {{ $A['team'] ?? '—' }}
            </a>
          @else
            <div class="text-white font-semibold">{{ $A['team'] ?? '—' }}</div>
          @endif
        </div>

        {{-- Rezultāts --}}
        <div class="text-2xl sm:text-3xl font-bold tabular-nums {{ $game['winner'] === 0 ? 'text-[#84CC16]' : ($game['winner'] === 1 ? 'text-[#F97316]' : 'text-white') }}">
          {{ $game['score'] }}
        </div>

        {{-- Komanda B --}}
        @php
          $teamBId   = $B['team_id'] ?? $B['team_external_id'] ?? null;
          $teamBHref = $B['team_href'] ?? ($teamBId ? route('nba.team.show', $teamBId) : null);
        @endphp
        <div class="flex items-center gap-2">
          @if($teamBHref)
            <a href="{{ $teamBHref }}" class="text-white font-semibold text-right hover:text-[#84CC16]">
              {{ $B['team'] ?? '—' }}
            </a>
          @else
            <div class="text-white font-semibold text-right">{{ $B['team'] ?? '—' }}</div>
          @endif

          @if(!empty($B['logo']))
            <img src="{{ $B['logo'] }}" alt="{{ $B['team'] }} logo" class="h-8 w-8 object-contain rounded bg-white p-[2px]" />
          @else
            <div class="h-8 w-8 rounded bg-white/10"></div>
          @endif
        </div>
      </div>

      <div class="text-sm text-gray-300">
        <div>Notikuma ID: <span class="text-white">{{ $game['event_id'] }}</span></div>
        <div>Datums: <span class="text-white">{{ \Illuminate\Support\Carbon::parse($game['date'])->toFormattedDateString() }}</span></div>
      </div>
    </div>
  </section>

  {{-- Divu komandu tabulas --}}
  <div class="grid gap-6 lg:grid-cols-2">

    {{-- Komanda A tabula --}}
    <section class="bg-[#1f2937] border border-[#374151] rounded-xl overflow-hidden">
      <div class="px-4 py-3 bg-[#0f172a] border-b border-[#374151] flex items-center justify-between">
        <h2 class="font-semibold">
          @if($teamAHref)
            <a href="{{ $teamAHref }}" class="hover:text-[#84CC16]">{{ $A['team'] ?? 'Komanda A' }}</a>
          @else
            {{ $A['team'] ?? 'Komanda A' }}
          @endif
        </h2>
        <div class="text-sm text-gray-300">Punkti: <span class="font-semibold text-white">{{ $A['totals']['pts'] ?? '—' }}</span></div>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-[900px] w-full text-sm js-sortable-table">
          <thead class="bg-[#0f172a] text-gray-300">
            <tr>
              <th class="px-3 py-2 text-left  sortable" data-col="player" data-type="text">Spēlētājs <span class="arrow">↕</span></th>
              <th class="px-3 py-2 text-center sortable" data-col="min"    data-type="time">MIN <span class="arrow">↕</span></th>
              <th class="px-3 py-2 text-center sortable" data-col="fg"     data-type="ma">Metieni <span class="arrow">↕</span></th>
              <th class="px-3 py-2 text-center sortable" data-col="tp"     data-type="ma">3P <span class="arrow">↕</span></th>
              <th class="px-3 py-2 text-center sortable" data-col="ft"     data-type="ma">Sodi <span class="arrow">↕</span></th>
              <th class="px-3 py-2 text-center sortable" data-col="reb"    data-type="num">Atl.b. <span class="arrow">↕</span></th>
              <th class="px-3 py-2 text-center sortable" data-col="ast"    data-type="num">Rez.p. <span class="arrow">↕</span></th>
              <th class="px-3 py-2 text-center sortable" data-col="stl"    data-type="num">Pārtv. <span class="arrow">↕</span></th>
              <th class="px-3 py-2 text-center sortable" data-col="blk"    data-type="num">Bloki <span class="arrow">↕</span></th>
              <th class="px-3 py-2 text-center sortable" data-col="tov"    data-type="num">Kļ. <span class="arrow">↕</span></th>
              <th class="px-3 py-2 text-center sortable" data-col="pf"     data-type="num">Piez. <span class="arrow">↕</span></th>
              <th class="px-3 py-2 text-right  sortable" data-col="pts"    data-type="num">Punkti <span class="arrow">↕</span></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#374151] text-[#F3F4F6]">
            @foreach($A['players'] as $p)
              @php
                $pid     = $p['player_id'] ?? $p['player_external_id'] ?? null;
                $pHref   = $p['player_href'] ?? ($pid ? route('nba.player.show', $pid) : null);
                $pImg    = $p['img'] ?? null;
                $pName   = $p['name'] ?? '—';
              @endphp
              <tr class="odd:bg-[#1f2937] even:bg-[#111827]">
                <td class="px-3 py-2" data-player="{{ \Illuminate\Support\Str::lower(($p['name'] ?? '').' '.($p['team'] ?? '')) }}">
                  @if($pHref)
                    <a class="flex items-center gap-2 hover:text-[#84CC16]" href="{{ $pHref }}">
                      @if($pImg)
                        <img src="{{ $pImg }}" class="h-6 w-6 rounded-full object-cover ring-1 ring-white/10" alt="">
                      @else
                        <div class="h-6 w-6 rounded-full bg-white/10"></div>
                      @endif
                      <span class="whitespace-nowrap">{{ $pName }}</span>
                    </a>
                  @else
                    <div class="flex items-center gap-2">
                      @if($pImg)
                        <img src="{{ $pImg }}" class="h-6 w-6 rounded-full object-cover ring-1 ring-white/10" alt="">
                      @else
                        <div class="h-6 w-6 rounded-full bg-white/10"></div>
                      @endif
                      <span class="whitespace-nowrap">{{ $pName }}</span>
                    </div>
                  @endif
                </td>
                <td class="px-3 py-2 text-center" data-min="{{ $p['min'] ?? '' }}">{{ $p['min'] ?? '—' }}</td>
                <td class="px-3 py-2 text-center" data-ma="{{ $p['fg'] ?? '' }}">{{ $p['fg'] ?? '—' }}</td>
                <td class="px-3 py-2 text-center" data-ma="{{ $p['tp'] ?? '' }}">{{ $p['tp'] ?? '—' }}</td>
                <td class="px-3 py-2 text-center" data-ma="{{ $p['ft'] ?? '' }}">{{ $p['ft'] ?? '—' }}</td>
                <td class="px-3 py-2 text-center" data-num="{{ $p['reb'] ?? '' }}">{{ $p['reb'] ?? '—' }}</td>
                <td class="px-3 py-2 text-center" data-num="{{ $p['ast'] ?? '' }}">{{ $p['ast'] ?? '—' }}</td>
                <td class="px-3 py-2 text-center" data-num="{{ $p['stl'] ?? '' }}">{{ $p['stl'] ?? '—' }}</td>
                <td class="px-3 py-2 text-center" data-num="{{ $p['blk'] ?? '' }}">{{ $p['blk'] ?? '—' }}</td>
                <td class="px-3 py-2 text-center" data-num="{{ $p['tov'] ?? '' }}">{{ $p['tov'] ?? '—' }}</td>
                <td class="px-3 py-2 text-center" data-num="{{ $p['pf']  ?? '' }}">{{ $p['pf']  ?? '—' }}</td>
                <td class="px-3 py-2 text-right  font-semibold" data-num="{{ $p['pts'] ?? '' }}">{{ $p['pts'] ?? '—' }}</td>
              </tr>
            @endforeach

            {{-- Kopsavilkums --}}
            <tr class="bg-[#0b1220] text-white font-medium">
              <td class="px-3 py-2">Kopā</td>
              <td class="px-3 py-2 text-center">—</td>
              <td class="px-3 py-2 text-center">
                {{ $A['totals']['fg']['m'] ?? 0 }}-{{ $A['totals']['fg']['a'] ?? 0 }}
                @if(!is_null($A['totals']['fg']['pct'])) ({{ $A['totals']['fg']['pct'] }}%) @endif
              </td>
              <td class="px-3 py-2 text-center">
                {{ $A['totals']['tp']['m'] ?? 0 }}-{{ $A['totals']['tp']['a'] ?? 0 }}
                @if(!is_null($A['totals']['tp']['pct'])) ({{ $A['totals']['tp']['pct'] }}%) @endif
              </td>
              <td class="px-3 py-2 text-center">
                {{ $A['totals']['ft']['m'] ?? 0 }}-{{ $A['totals']['ft']['a'] ?? 0 }}
                @if(!is_null($A['totals']['ft']['pct'])) ({{ $A['totals']['ft']['pct'] }}%) @endif
              </td>
              <td class="px-3 py-2 text-center">{{ $A['totals']['reb'] ?? 0 }}</td>
              <td class="px-3 py-2 text-center">{{ $A['totals']['ast'] ?? 0 }}</td>
              <td class="px-3 py-2 text-center">{{ $A['totals']['stl'] ?? 0 }}</td>
              <td class="px-3 py-2 text-center">{{ $A['totals']['blk'] ?? 0 }}</td>
              <td class="px-3 py-2 text-center">{{ $A['totals']['tov'] ?? 0 }}</td>
              <td class="px-3 py-2 text-center">{{ $A['totals']['pf']  ?? 0 }}</td>
              <td class="px-3 py-2 text-right font-semibold">{{ $A['totals']['pts'] ?? 0 }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    {{-- Komanda B tabula --}}
    <section class="bg-[#1f2937] border border-[#374151] rounded-xl overflow-hidden">
      <div class="px-4 py-3 bg-[#0f172a] border-b border-[#374151] flex items-center justify-between">
        <h2 class="font-semibold">
          @if($teamBHref)
            <a href="{{ $teamBHref }}" class="hover:text-[#84CC16]">{{ $B['team'] ?? 'Komanda B' }}</a>
          @else
            {{ $B['team'] ?? 'Komanda B' }}
          @endif
        </h2>
        <div class="text-sm text-gray-300">Punkti: <span class="font-semibold text-white">{{ $B['totals']['pts'] ?? '—' }}</span></div>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-[900px] w-full text-sm js-sortable-table">
          <thead class="bg-[#0f172a] text-gray-300">
            <tr>
              <th class="px-3 py-2 text-left  sortable" data-col="player" data-type="text">Spēlētājs <span class="arrow">↕</span></th>
              <th class="px-3 py-2 text-center sortable" data-col="min"    data-type="time">MIN <span class="arrow">↕</span></th>
              <th class="px-3 py-2 text-center sortable" data-col="fg"     data-type="ma">Metieni <span class="arrow">↕</span></th>
              <th class="px-3 py-2 text-center sortable" data-col="tp"     data-type="ma">3P <span class="arrow">↕</span></th>
              <th class="px-3 py-2 text-center sortable" data-col="ft"     data-type="ma">Sodi <span class="arrow">↕</span></th>
              <th class="px-3 py-2 text-center sortable" data-col="reb"    data-type="num">Atl.b. <span class="arrow">↕</span></th>
              <th class="px-3 py-2 text-center sortable" data-col="ast"    data-type="num">Rez.p. <span class="arrow">↕</span></th>
              <th class="px-3 py-2 text-center sortable" data-col="stl"    data-type="num">Pārtv. <span class="arrow">↕</span></th>
              <th class="px-3 py-2 text-center sortable" data-col="blk"    data-type="num">Bloki <span class="arrow">↕</span></th>
              <th class="px-3 py-2 text-center sortable" data-col="tov"    data-type="num">Kļ. <span class="arrow">↕</span></th>
              <th class="px-3 py-2 text-center sortable" data-col="pf"     data-type="num">Piez. <span class="arrow">↕</span></th>
              <th class="px-3 py-2 text-right  sortable" data-col="pts"    data-type="num">Punkti <span class="arrow">↕</span></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#374151] text-[#F3F4F6]">
            @foreach($B['players'] as $p)
              @php
                $pid     = $p['player_id'] ?? $p['player_external_id'] ?? null;
                $pHref   = $p['player_href'] ?? ($pid ? route('nba.player.show', $pid) : null);
                $pImg    = $p['img'] ?? null;
                $pName   = $p['name'] ?? '—';
              @endphp
              <tr class="odd:bg-[#1f2937] even:bg-[#111827]">
                <td class="px-3 py-2" data-player="{{ \Illuminate\Support\Str::lower(($p['name'] ?? '').' '.($p['team'] ?? '')) }}">
                  @if($pHref)
                    <a class="flex items-center gap-2 hover:text-[#84CC16]" href="{{ $pHref }}">
                      @if($pImg)
                        <img src="{{ $pImg }}" class="h-6 w-6 rounded-full object-cover ring-1 ring-white/10" alt="">
                      @else
                        <div class="h-6 w-6 rounded-full bg-white/10"></div>
                      @endif
                      <span class="whitespace-nowrap">{{ $pName }}</span>
                    </a>
                  @else
                    <div class="flex items-center gap-2">
                      @if($pImg)
                        <img src="{{ $pImg }}" class="h-6 w-6 rounded-full object-cover ring-1 ring-white/10" alt="">
                      @else
                        <div class="h-6 w-6 rounded-full bg-white/10"></div>
                      @endif
                      <span class="whitespace-nowrap">{{ $pName }}</span>
                    </div>
                  @endif
                </td>
                <td class="px-3 py-2 text-center" data-min="{{ $p['min'] ?? '' }}">{{ $p['min'] ?? '—' }}</td>
                <td class="px-3 py-2 text-center" data-ma="{{ $p['fg'] ?? '' }}">{{ $p['fg'] ?? '—' }}</td>
                <td class="px-3 py-2 text-center" data-ma="{{ $p['tp'] ?? '' }}">{{ $p['tp'] ?? '—' }}</td>
                <td class="px-3 py-2 text-center" data-ma="{{ $p['ft'] ?? '' }}">{{ $p['ft'] ?? '—' }}</td>
                <td class="px-3 py-2 text-center" data-num="{{ $p['reb'] ?? '' }}">{{ $p['reb'] ?? '—' }}</td>
                <td class="px-3 py-2 text-center" data-num="{{ $p['ast'] ?? '' }}">{{ $p['ast'] ?? '—' }}</td>
                <td class="px-3 py-2 text-center" data-num="{{ $p['stl'] ?? '' }}">{{ $p['stl'] ?? '—' }}</td>
                <td class="px-3 py-2 text-center" data-num="{{ $p['blk'] ?? '' }}">{{ $p['blk'] ?? '—' }}</td>
                <td class="px-3 py-2 text-center" data-num="{{ $p['tov'] ?? '' }}">{{ $p['tov'] ?? '—' }}</td>
                <td class="px-3 py-2 text-center" data-num="{{ $p['pf']  ?? '' }}">{{ $p['pf']  ?? '—' }}</td>
                <td class="px-3 py-2 text-right  font-semibold" data-num="{{ $p['pts'] ?? '' }}">{{ $p['pts'] ?? '—' }}</td>
              </tr>
            @endforeach

            {{-- Kopsavilkums --}}
            <tr class="bg-[#0b1220] text-white font-medium">
              <td class="px-3 py-2">Kopā</td>
              <td class="px-3 py-2 text-center">—</td>
              <td class="px-3 py-2 text-center">
                {{ $B['totals']['fg']['m'] ?? 0 }}-{{ $B['totals']['fg']['a'] ?? 0 }}
                @if(!is_null($B['totals']['fg']['pct'])) ({{ $B['totals']['fg']['pct'] }}%) @endif
              </td>
              <td class="px-3 py-2 text-center">
                {{ $B['totals']['tp']['m'] ?? 0 }}-{{ $B['totals']['tp']['a'] ?? 0 }}
                @if(!is_null($B['totals']['tp']['pct'])) ({{ $B['totals']['tp']['pct'] }}%) @endif
              </td>
              <td class="px-3 py-2 text-center">
                {{ $B['totals']['ft']['m'] ?? 0 }}-{{ $B['totals']['ft']['a'] ?? 0 }}
                @if(!is_null($B['totals']['ft']['pct'])) ({{ $B['totals']['ft']['pct'] }}%) @endif
              </td>
              <td class="px-3 py-2 text-center">{{ $B['totals']['reb'] ?? 0 }}</td>
              <td class="px-3 py-2 text-center">{{ $B['totals']['ast'] ?? 0 }}</td>
              <td class="px-3 py-2 text-center">{{ $B['totals']['stl'] ?? 0 }}</td>
              <td class="px-3 py-2 text-center">{{ $B['totals']['blk'] ?? 0 }}</td>
              <td class="px-3 py-2 text-center">{{ $B['totals']['tov'] ?? 0 }}</td>
              <td class="px-3 py-2 text-center">{{ $B['totals']['pf']  ?? 0 }}</td>
              <td class="px-3 py-2 text-right font-semibold">{{ $B['totals']['pts'] ?? 0 }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

  </div>

</main>
@endsection

@push('scripts')
<script>
(function(){
  // Palīgfunkcijas tabulu šķirošanai
  const NULL = Symbol('null');

  function parseTime(t){
    t = (t||'').trim();
    if (!t || t==='—' || t==='-') return NULL;
    let m = t.match(/^(\d+):(\d{1,2})(?::(\d{1,2}))?$/);
    if (m){
      const hasH = m[3] !== undefined;
      const h  = hasH ? +m[1] : 0;
      const mm = hasH ? +m[2] : +m[1];
      const ss = hasH ? +m[3] : +m[2];
      return h*3600 + mm*60 + ss;
    }
    m = t.match(/^(\d+(?:\.\d+)?)$/);
    if (m) return Math.round(parseFloat(m[1]) * 60);
    m = t.match(/^(\d+)m\s*(\d{1,2})s$/i);
    if (m) return (+m[1])*60 + (+m[2]);
    return NULL;
  }

  // "m-a" -> precizitāte, tad mēģinājumi
  function parseMakesAttempts(s){
    s = (s||'').replace(/\s/g,'');
    const m = s.match(/^(\d+)[-–](\d+)$/);
    if (!m) return NULL;
    const makes = +m[1], att = +m[2];
    const pct   = att === 0 ? 0 : (makes/att);
    return [pct, att];
  }

  function parseNum(s){
    s = (s||'').trim();
    if (!s || s==='—' || s==='-') return NULL;
    const n = Number(s);
    return Number.isFinite(n) ? n : NULL;
  }

  function cmpVal(a, b){
    const A = Array.isArray(a) ? a : [a];
    const B = Array.isArray(b) ? b : [b];
    for (let i=0; i<Math.max(A.length,B.length); i++){
      const x = A[i], y = B[i];
      if (x === y) continue;
      if (x === NULL) return 1;
      if (y === NULL) return -1;
      if (typeof x === 'number' && typeof y === 'number') return x - y;
      return String(x).localeCompare(String(y), undefined, {numeric:true, sensitivity:'base'});
    }
    return 0;
  }

  function valueFromCell(td, type){
    if (type === 'time') return parseTime(td.getAttribute('data-min') || td.textContent);
    if (type === 'ma')   return parseMakesAttempts(td.getAttribute('data-ma') || td.textContent);
    if (type === 'num')  return parseNum(td.getAttribute('data-num') || td.textContent);
    return (td.getAttribute('data-player') || td.textContent || '').toLowerCase().trim();
  }

  function makeSortable(table){
    const thead = table.tHead;
    if (!thead) return;
    const tbody = table.tBodies[0];
    const headers = Array.from(thead.querySelectorAll('th.sortable'));

    function clearArrows(except){
      headers.forEach(h => {
        if (h !== except){
          h.setAttribute('aria-sort','none');
          const a = h.querySelector('.arrow'); if (a) a.textContent = '↕';
        }
      });
    }

    headers.forEach((th) => {
      const type = th.getAttribute('data-type') || 'text';
      let asc = false;
      th.setAttribute('tabindex','0');
      th.setAttribute('aria-sort','none');

      const toggle = () => {
        asc = !asc;
        clearArrows(th);
        th.setAttribute('aria-sort', asc ? 'asc' : 'desc');
        const a = th.querySelector('.arrow');
        if (a) a.textContent = asc ? '▲' : '▼';

        const colIndex = Array.from(th.parentElement.children).indexOf(th);
        const rows = Array.from(tbody.querySelectorAll('tr'))
          .filter(tr => !tr.classList.contains('bg-[#0b1220]')); // kopsavilkumu nelokam

        const data = rows.map((tr, i) => {
          const td  = tr.children[colIndex];
          return { tr, i, key: valueFromCell(td, type) };
        });

        data.sort((A,B) => {
          const c = cmpVal(A.key, B.key);
          return (asc ? c : -c) || (A.i - B.i);
        });

        tbody.append(...data.map(x => x.tr));
      };

      th.addEventListener('click', toggle);
      th.addEventListener('keydown', (e)=>{
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
        if (e.key === 'ArrowUp')   { e.preventDefault(); if (!asc) toggle(); }
        if (e.key === 'ArrowDown') { e.preventDefault(); if (asc)  toggle(); }
      });
    });
  }

  document.querySelectorAll('table.js-sortable-table').forEach(makeSortable);
})();
</script>
@endpush
