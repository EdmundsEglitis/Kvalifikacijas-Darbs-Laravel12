@extends('layouts.nba')
@section('title','Spēlētāja profils')

@push('head')
<style>
  th.sortable { cursor: pointer; user-select: none; }
  th.sortable .arrow { display:inline-block; width:1ch; margin-left:.35rem; opacity:.65; }
</style>
@endpush

@section('content')
<main class="pt-24 max-w-7xl mx-auto px-4 space-y-10">
  {{-- Header --}}
  <section class="bg-[#1f2937] rounded-xl p-6 flex items-center gap-6">
    <img
      src="{{ $player->image ?? $details->headshot_href ?? 'https://via.placeholder.com/120' }}"
      class="h-28 w-28 rounded-full ring-4 ring-[#84CC16] object-cover"
      alt="Spēlētāja foto">
    <div>
      <h1 class="text-3xl font-bold">{{ $player->full_name }}</h1>
      <p class="text-gray-300">
        {{ $details->position['displayName'] ?? '' }}
        @if($teamHeader)
          <a href="{{ route('nba.team.show', $teamHeader->external_id) }}"
             class="text-[#84CC16] hover:underline inline-flex items-center gap-2">
            <img src="{{ $teamHeader->logo }}" class="h-5 w-5" alt="Komandas logo">
            <span>{{ $teamHeader->name }}</span>
          </a>
        @endif
      </p>
      <p class="text-gray-400">Krekla nr.: {{ $details->display_jersey ?? '-' }}</p>
    </div>
  </section>

  {{-- Bio --}}
  <section class="bg-[#1f2937] rounded-xl p-6">
    <h2 class="text-2xl font-semibold mb-4">Profils</h2>
    <ul class="grid grid-cols-2 gap-4 text-sm">
      <li>
        <strong>Augums:</strong>
        {{ $details->display_height ?? '-' }}
        @if(!empty($metrics['height_cm']))
          <span class="text-gray-400">({{ $metrics['height_cm'] }} cm)</span>
        @endif
      </li>
      <li>
        <strong>Svars:</strong>
        {{ $details->display_weight ?? '-' }}
        @if(!empty($metrics['weight_kg']))
          <span class="text-gray-400">({{ $metrics['weight_kg'] }} kg)</span>
        @endif
      </li>
      <li><strong>Vecums:</strong> {{ $details->age ?? '-' }}</li>
      <li><strong>Dz. datums:</strong> {{ $details->display_dob ?? '-' }}</li>
      <li><strong>Dzimtā vieta:</strong> {{ $details->birth_place ?? '-' }}</li>
      <li><strong>Pieredze:</strong> {{ $details->display_experience ?? '-' }}</li>
      <li><strong>Drafts:</strong> {{ $details->display_draft ?? '-' }}</li>
      <li><strong>Statuss:</strong> {{ $cleanStatus ?? '-' }}</li>
    </ul>
  </section>

  {{-- Career averages --}}
  <section class="bg-[#1f2937] rounded-xl p-6">
    <h2 class="text-2xl font-semibold mb-4">Karjeras vidējie</h2>
    @if($career && $career->games > 0)
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 text-center">
        <div><p class="font-bold text-[#84CC16]">{{ number_format($career->pts,1) }}</p><p>PPG</p></div>
        <div><p class="font-bold text-[#84CC16]">{{ number_format($career->reb,1) }}</p><p>RPG</p></div>
        <div><p class="font-bold text-[#84CC16]">{{ number_format($career->ast,1) }}</p><p>APG</p></div>
        <div><p class="font-bold text-[#84CC16]">{{ number_format($career->stl,1) }}</p><p>SPG</p></div>
        <div><p class="font-bold text-[#84CC16]">{{ number_format($career->blk,1) }}</p><p>BPG</p></div>
        <div><p class="font-bold text-[#84CC16]">{{ number_format($career->min,1) }}</p><p>MIN</p></div>
      </div>
    @else
      <p class="text-gray-400">Nav pieejama statistika.</p>
    @endif
  </section>

  {{-- Current season averages --}}
  <section class="bg-[#1f2937] rounded-xl p-6">
    <h2 class="text-2xl font-semibold mb-4">{{ now()->year }}. gada sezonas vidējie</h2>
    @if($season && $season->games > 0)
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 text-center">
        <div><p class="font-bold text-[#84CC16]">{{ number_format($season->pts,1) }}</p><p>PPG</p></div>
        <div><p class="font-bold text-[#84CC16]">{{ number_format($season->reb,1) }}</p><p>RPG</p></div>
        <div><p class="font-bold text-[#84CC16]">{{ number_format($season->ast,1) }}</p><p>APG</p></div>
        <div><p class="font-bold text-[#84CC16]">{{ number_format($season->stl,1) }}</p><p>SPG</p></div>
        <div><p class="font-bold text-[#84CC16]">{{ number_format($season->blk,1) }}</p><p>BPG</p></div>
        <div><p class="font-bold text-[#84CC16]">{{ number_format($season->min,1) }}</p><p>MIN</p></div>
      </div>
    @else
      <p class="text-gray-400">Nav pieejama statistika {{ now()->year }}. gadam.</p>
    @endif
  </section>

  {{-- Game logs --}}
  <section class="bg-[#1f2937] rounded-xl p-6">
    <h2 class="text-2xl font-semibold mb-4">Spēļu protokoli</h2>
    <div class="overflow-x-auto">
      <table id="logsTable" class="min-w-full text-left text-sm">
        <thead class="bg-[#111827] border-b border-[#374151] text-gray-400">
          <tr>
            <th class="px-4 py-2">Datums</th>
            <th class="px-4 py-2">Pretinieks</th>
            <th class="px-4 py-2">Rezultāts</th>
            <th class="px-4 py-2">Rezultāts (punkti)</th>
            <th class="px-4 py-2">MIN</th>
            <th class="px-4 py-2">FG</th>
            <th class="px-4 py-2">FG%</th>
            <th class="px-4 py-2">3PT</th>
            <th class="px-4 py-2">3PT%</th>
            <th class="px-4 py-2">FT</th>
            <th class="px-4 py-2">FT%</th>
            <th class="px-4 py-2">REB</th>
            <th class="px-4 py-2">AST</th>
            <th class="px-4 py-2">STL</th>
            <th class="px-4 py-2">BLK</th>
            <th class="px-4 py-2">TO</th>
            <th class="px-4 py-2">PF</th>
            <th class="px-4 py-2">PTS</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#374151]">
          @forelse($gamelogs as $log)
            <tr class="odd:bg-[#1f2937] even:bg-[#111827] hover:bg-[#374151]">
              {{-- Datums (saite uz spēli) --}}
              <td class="px-4 py-2">
                @if(!empty($log->event_id))
                  <a href="{{ route('nba.games.show', $log->event_id) }}"
                     class="hover:text-[#84CC16] underline underline-offset-2">
                    {{ $log->game_date ? \Carbon\Carbon::parse($log->game_date)->format('M d, Y') : '-' }}
                  </a>
                @else
                  {{ $log->game_date ? \Carbon\Carbon::parse($log->game_date)->format('M d, Y') : '-' }}
                @endif
              </td>

              {{-- Pretinieks --}}
              <td class="px-4 py-2 flex items-center gap-2">
                @if($log->opponent_team_id ?? false)
                  <a href="{{ route('nba.team.show', $log->opponent_team_id) }}"
                     class="flex items-center gap-2 hover:text-[#84CC16]">
                    <img src="{{ $log->opponent_team_logo }}" class="h-6 w-6 rounded-full" alt="Pretinieka logo">
                    <span>{{ $log->opponent_team_name }}</span>
                  </a>
                @else
                  {{ $log->opponent_name ?? '-' }}
                @endif
              </td>

              <td class="px-4 py-2">{{ $log->result ?? '-' }}</td>

              {{-- Rezultāts (arī saite uz spēli) --}}
              <td class="px-4 py-2">
                @if(!empty($log->event_id) && !empty($log->score))
                  <a href="{{ route('nba.games.show', $log->event_id) }}"
                     class="hover:text-[#84CC16] underline underline-offset-2">
                    {{ $log->score }}
                  </a>
                @else
                  {{ $log->score ?? '-' }}
                @endif
              </td>

              <td class="px-4 py-2">{{ $log->minutes ?? '-' }}</td>
              <td class="px-4 py-2">{{ $log->fg ?? '-' }}</td>
              <td class="px-4 py-2">{{ $log->fg_pct ?? '-' }}</td>
              <td class="px-4 py-2">{{ $log->three_pt ?? '-' }}</td>
              <td class="px-4 py-2">{{ $log->three_pt_pct ?? '-' }}</td>
              <td class="px-4 py-2">{{ $log->ft ?? '-' }}</td>
              <td class="px-4 py-2">{{ $log->ft_pct ?? '-' }}</td>
              <td class="px-4 py-2">{{ $log->rebounds ?? '-' }}</td>
              <td class="px-4 py-2">{{ $log->assists ?? '-' }}</td>
              <td class="px-4 py-2">{{ $log->steals ?? '-' }}</td>
              <td class="px-4 py-2">{{ $log->blocks ?? '-' }}</td>
              <td class="px-4 py-2">{{ $log->turnovers ?? '-' }}</td>
              <td class="px-4 py-2">{{ $log->fouls ?? '-' }}</td>
              <td class="px-4 py-2 font-bold text-[#84CC16]">{{ $log->points ?? '-' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="18" class="text-center py-4 text-gray-400">Nav pieejamu spēļu ierakstu.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
</main>
@endsection

@push('scripts')
<script>
(function(){
  const table = document.getElementById('logsTable');
  if (!table) return;
  const tbody = table.tBodies[0];

  // 0 Datums | 1 Pretinieks | 2 Rezultāts | 3 Rezultāts (punkti) | 4 MIN | 5 FG | 6 FG% | 7 3PT | 8 3PT% | 9 FT | 10 FT% | 11 REB | 12 AST | 13 STL | 14 BLK | 15 TO | 16 PF | 17 PTS

  function rows(){ return Array.from(tbody.querySelectorAll('tr')); }

  const NULL = Symbol('null');
  const nullLast = (v) => (v === NULL ? {k: null, n:true} : {k: v, n:false});

  function parseDate(text){
    const ts = Date.parse((text||'').trim());
    return isNaN(ts) ? NULL : ts;
  }
  function parseMinutes(text){
    const t = (text || '').trim();
    if (!t || t === '-' || t === '—') return NULL;
    if (/^(DNP|DNPCD|DND|NWT|NA|Inactive)$/i.test(t)) return NULL;

    let m = t.match(/^(\d+):(\d{1,2})(?::(\d{1,2}))?$/);
    if (m) {
      const hasHours = m[3] !== undefined;
      const h  = hasHours ? +m[1] : 0;
      const mm = hasHours ? +m[2] : +m[1];
      const ss = hasHours ? +m[3] : +m[2];
      return h*3600 + mm*60 + ss;
    }
    m = t.match(/^(\d+(?:\.\d+)?)$/); // minutes only
    if (m) return Math.round(parseFloat(m[1]) * 60);
    m = t.match(/^(\d+)m\s*(\d{1,2})s$/i);
    if (m) return (+m[1])*60 + (+m[2]);

    return NULL;
  }
  function parsePct(text){
    const t = (text||'').replace('%','').trim();
    if (!t || t === '-') return NULL;
    const n = Number(t);
    return Number.isFinite(n) ? n : NULL;
  }
  function parseNum(text){
    const t = (text||'').trim();
    if (!t || t === '-') return NULL;
    const n = Number(t);
    return Number.isFinite(n) ? n : NULL;
  }
  function parseMakesAttempts(text){
    const m = (text||'').replace(/\s/g,'').match(/^(\d+)[-–](\d+)$/);
    if (!m) return NULL;
    const makes = +m[1], att = +m[2];
    if (att === 0) return [0,0];
    return [(makes/att)*100, att];
  }
  function parseScore(text){
    const m = (text||'').replace(/\s/g,'').match(/(\d+)[^\d]+(\d+)/);
    if (!m) return NULL;
    const a = +m[1], b = +m[2];
    return [a+b, a-b];
  }

  function sortKey(td, idx){
    const text = td?.innerText?.trim() || '';
    switch(idx){
      case 0:  return nullLast(parseDate(text));
      case 1:  return nullLast(text.toLowerCase());
      case 2:  return nullLast(text.toLowerCase());
      case 3:  return nullLast(parseScore(text));
      case 4:  return nullLast(parseMinutes(text));
      case 5:  { const r = parseMakesAttempts(text); return r===NULL?nullLast(NULL):nullLast(r); }
      case 6:  return nullLast(parsePct(text));
      case 7:  { const r = parseMakesAttempts(text); return r===NULL?nullLast(NULL):nullLast(r); }
      case 8:  return nullLast(parsePct(text));
      case 9:  { const r = parseMakesAttempts(text); return r===NULL?nullLast(NULL):nullLast(r); }
      case 10: return nullLast(parsePct(text));
      case 11: return nullLast(parseNum(text));
      case 12: return nullLast(parseNum(text));
      case 13: return nullLast(parseNum(text));
      case 14: return nullLast(parseNum(text));
      case 15: return nullLast(parseNum(text));
      case 16: return nullLast(parseNum(text));
      case 17: return nullLast(parseNum(text));
      default: return nullLast(text.toLowerCase());
    }
  }

  function cmp(a, b){
    if (Array.isArray(a.k) || Array.isArray(b.k)){
      const A = Array.isArray(a.k)?a.k:[a.k];
      const B = Array.isArray(b.k)?b.k:[b.k];
      const len = Math.max(A.length, B.length);
      for (let i=0;i<len;i++){
        const x = A[i], y = B[i];
        if (x === y) continue;
        if (typeof x === 'number' && typeof y === 'number') return x - y;
        return String(x).localeCompare(String(y), undefined, {numeric:true, sensitivity:'base'});
      }
      return 0;
    }
    if (a.n && !b.n) return 1;
    if (!a.n && b.n) return -1;
    if (typeof a.k === 'number' && typeof b.k === 'number') return a.k - b.k;
    return String(a.k).localeCompare(String(b.k), undefined, {numeric:true, sensitivity:'base'});
  }

  function buildArrows(){
    const ths = Array.from(table.tHead.rows[0].cells);
    ths.forEach((th, i) => {
      th.classList.add('sortable');
      th.setAttribute('tabindex','0');
      th.setAttribute('aria-sort','none');
      const span = document.createElement('span');
      span.className = 'arrow';
      span.textContent = '↕';
      th.appendChild(span);

      const toggle = (() => {
        let dir = 'desc'; // statistikā pirmajā klikā parasti ērtāk dilstoši
        return () => {
          dir = (dir === 'asc' ? 'desc' : 'asc');
          ths.forEach(h=>{
            if (h!==th){
              h.setAttribute('aria-sort','none');
              const a=h.querySelector('.arrow'); if(a) a.textContent='↕';
            }
          });
          th.setAttribute('aria-sort', dir);
          th.querySelector('.arrow').textContent = dir === 'asc' ? '▲' : '▼';
          applySort(i, dir);
        };
      })();

      th.addEventListener('click', toggle);
      th.addEventListener('keydown', (e)=>{
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
        if (e.key === 'ArrowUp') { e.preventDefault(); applySort(i, 'asc'); th.setAttribute('aria-sort','asc'); th.querySelector('.arrow').textContent='▲'; }
        if (e.key === 'ArrowDown') { e.preventDefault(); applySort(i, 'desc'); th.setAttribute('aria-sort','desc'); th.querySelector('.arrow').textContent='▼'; }
      });
    });
  }

  function applySort(colIndex, dir){
    const data = rows().map((tr, idx) => {
      const td = tr.children[colIndex];
      return { tr, idx, key: sortKey(td, colIndex) };
    });

    data.sort((A, B) => {
      const c = cmp(A.key, B.key);
      return (dir === 'asc' ? c : -c) || (A.idx - B.idx); // stabila kārtošana
    });

    const frag = document.createDocumentFragment();
    data.forEach(x => frag.appendChild(x.tr));
    tbody.appendChild(frag);
  }

  buildArrows();
})();
</script>
@endpush
