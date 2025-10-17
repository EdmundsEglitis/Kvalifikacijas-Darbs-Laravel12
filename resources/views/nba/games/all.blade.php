@extends('layouts.nba')
@section('title','Visas spēles')

@push('head')
<style>
  @keyframes fadeUp { from { opacity:.0; transform: translateY(6px) } to { opacity:1; transform:none } }
  .fade-up { animation: fadeUp .35s ease-out both; }
  .img-fade { opacity:.0; transition: opacity .3s ease; }
  .img-fade.loaded { opacity:1; }
  @keyframes shimmer { 0%{background-position:-200% 0} 100%{background-position:200% 0} }
  .shimmer { background: linear-gradient(90deg, rgba(255,255,255,0.06) 25%, rgba(255,255,255,0.12) 37%, rgba(255,255,255,0.06) 63%);
             background-size: 400% 100%; animation: shimmer 1.6s infinite; }

  /* galvenes padarām acīmredzami klikšķināmas */
  th.sortable { cursor: pointer; user-select: none; }
  th.sortable .arrow { display:inline-block; width:1ch; margin-left:.35rem; opacity:.65; }
</style>
@endpush

@section('content')
<main class="max-w-7xl mx-auto px-4 pb-16 pt-10 space-y-8">

  {{-- Filtri --}}
  <section class="bg-[#1f2937] border border-[#374151] rounded-2xl p-4 sm:p-5 fade-up">
    <form method="GET" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-7 items-end">
      <div>
        <label class="block text-xs text-gray-400 mb-1">No sezonas</label>
        <select name="from" class="w-full bg-[#0f172a] border border-[#374151] rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#84CC16]/40">
          @foreach($seasons as $s)
            <option value="{{ $s }}" @selected((int)$from === (int)$s)>{{ $s }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-xs text-gray-400 mb-1">Līdz sezonai</label>
        <select name="to" class="w-full bg-[#0f172a] border border-[#374151] rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#84CC16]/40">
          @foreach($seasons as $s)
            <option value="{{ $s }}" @selected((int)$to === (int)$s)>{{ $s }}</option>
          @endforeach
        </select>
      </div>

      <div class="lg:col-span-2">
        <label class="block text-xs text-gray-400 mb-1">Komanda / Pretinieks / Spēlētājs</label>
        <input name="team" value="{{ $teamQuery }}" placeholder="piem., Celtics vai Tatum"
               class="w-full bg-[#0f172a] border border-[#374151] rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#84CC16]/40" />
      </div>

      <div class="lg:col-span-2">
        <label class="block text-xs text-gray-400 mb-1">Uzvarētājs satur</label>
        <input name="winner" value="{{ $winnerQ ?? '' }}" placeholder="piem., Celtics"
               class="w-full bg-[#0f172a] border border-[#374151] rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#84CC16]/40" />
      </div>

      <div>
        <label class="block text-xs text-gray-400 mb-1">Rindu skaits lapā</label>
        <select name="per_page" class="w-full bg-[#0f172a] border border-[#374151] rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#84CC16]/40">
          @foreach([10,25,50,100] as $pp)
            <option value="{{ $pp }}" @selected((int)$per === $pp)>{{ $pp }}</option>
          @endforeach
        </select>
      </div>

      <div class="flex gap-3 lg:col-span-7">
        <button class="px-4 py-2 rounded-lg bg-[#84CC16] text-[#111827] font-semibold hover:bg-[#a3e635] transition">Piemērot</button>
        <a href="{{ route('nba.games.all') }}"
           class="px-4 py-2 rounded-lg bg-white/10 text-white hover:bg-white/20 transition">Atiestatīt</a>
        <input id="q" type="text" placeholder="Ātrā meklēšana šajā lapā…"
               class="flex-1 min-w-[200px] bg-[#0f172a] border border-[#374151] rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#84CC16]/40" />
      </div>
    </form>
  </section>

  {{-- Tabula --}}
  <section class="bg-[#1f2937] border border-[#374151] rounded-2xl overflow-hidden fade-up">
    <div class="overflow-x-auto">
      <table id="gamesTable" class="min-w-[1000px] w-full text-sm">
        <thead class="bg-[#0f172a] text-gray-300 sticky top-0 z-10">
          <tr>
            <th class="px-4 py-2 text-left">Datums / Laiks</th>
            <th class="px-4 py-2 text-left">Mājinieki</th>
            <th class="px-4 py-2 text-left">Viesi</th>
            <th class="px-4 py-2 text-left">Rezultāts</th>
            <th class="px-4 py-2 text-left">Uzvarētājs</th>
            <th class="px-4 py-2 text-right">Statistika</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#374151] text-[#F3F4F6]">
          @forelse($rows as $r)
            <tr class="odd:bg-[#1f2937] even:bg-[#111827] hover:bg-[#374151]/60 transition">
              <td class="px-4 py-2 whitespace-nowrap">{{ $r['date_disp'] }}</td>

              <td class="px-4 py-2">
                <div class="flex items-center gap-2">
                  @if(!empty($r['home_logo']))
                    <img src="{{ $r['home_logo'] }}" class="h-6 w-6 object-contain rounded bg-white p-[2px]" alt="">
                  @endif
                  @if(!empty($r['home_id']))
                    <a class="hover:text-[#84CC16]" href="{{ route('nba.team.show', $r['home_id']) }}">{{ $r['home_name'] }}</a>
                  @else
                    <span>{{ $r['home_name'] }}</span>
                  @endif
                </div>
              </td>

              <td class="px-4 py-2">
                <div class="flex items-center gap-2">
                  @if(!empty($r['away_logo']))
                    <img src="{{ $r['away_logo'] }}" class="h-6 w-6 object-contain rounded bg-white p-[2px]" alt="">
                  @endif
                  @if(!empty($r['away_id']))
                    <a class="hover:text-[#84CC16]" href="{{ route('nba.team.show', $r['away_id']) }}">{{ $r['away_name'] }}</a>
                  @else
                    <span>{{ $r['away_name'] }}</span>
                  @endif
                </div>
              </td>

              <td class="px-4 py-2">{{ $r['score'] }}</td>

              <td class="px-4 py-2">
                @php $w = $r['winner']; @endphp
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs
                             {{ $w==='—' ? 'bg-white/10 text-gray-300' : 'bg-[#84CC16]/20 text-[#84CC16]' }}">
                  {{ $w }}
                </span>
              </td>

              <td class="px-4 py-2 text-right">
                <a href="{{ route('nba.games.show', $r['event_id']) }}"
                   class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20">
                  Atvērt →
                </a>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="px-4 py-4 text-center text-gray-400">Spēles nav atrastas.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  @if($rows instanceof \Illuminate\Contracts\Pagination\Paginator)
    <div class="flex justify-end">
      {{ $rows->appends(request()->query())->onEachSide(1)->links('vendor.pagination.custom-dark') }}
    </div>
  @endif

  <section class="pb-8 fade-up">
    <h2 class="text-xl sm:text-2xl font-semibold mb-3">Statistikas skaidrojumi</h2>
    <div class="grid gap-3 sm:gap-4 [grid-template-columns:repeat(auto-fit,minmax(180px,1fr))]">
      @foreach($legend as $item)
        <div class="bg-[#1f2937] border border-[#374151] rounded-xl p-3 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-black/20 transition">
          <div class="text-sm font-semibold text-white mb-1">{{ $item[0] }}</div>
          <p class="text-xs text-gray-300">{{ $item[1] }}</p>
        </div>
      @endforeach
    </div>
  </section>
</main>
@endsection

@push('scripts')
<script>
  const q = document.getElementById('q');
  const table = document.getElementById('gamesTable');
  const tbody = table?.querySelector('tbody');

  const rows = () => Array.from(tbody.querySelectorAll('tr'));

  // -------- ātrā meklēšana --------
  const clearMarks = (el) => {
    el.querySelectorAll('mark[data-hi]').forEach(m => {
      const t = document.createTextNode(m.textContent);
      m.replaceWith(t);
    });
  };
  const hi = (el, terms) => {
    if (!terms.length) return;
    const walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT, {
      acceptNode: n => n.nodeValue.trim() ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT
    });
    const rgx = new RegExp('(' + terms.map(t => t.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|') + ')', 'gi');
    const nodes = [];
    while (walker.nextNode()) nodes.push(walker.currentNode);
    nodes.forEach(n => {
      const frag = document.createDocumentFragment();
      let last = 0, m;
      const s = n.nodeValue;
      while ((m = rgx.exec(s)) !== null) {
        if (m.index > last) frag.appendChild(document.createTextNode(s.slice(last, m.index)));
        const mark = document.createElement('mark');
        mark.setAttribute('data-hi', '1');
        mark.className = 'bg-[#84CC16]/30 text-white rounded px-0.5';
        mark.textContent = m[0];
        frag.appendChild(mark);
        last = m.index + m[0].length;
      }
      if (last < s.length) frag.appendChild(document.createTextNode(s.slice(last)));
      if (frag.childNodes.length) n.parentNode.replaceChild(frag, n);
    });
  };

  let t;
  q?.addEventListener('input', () => {
    clearTimeout(t);
    t = setTimeout(() => {
      const raw = (q.value || '').trim().toLowerCase();
      const terms = raw.split(/\s+/).filter(Boolean);
      rows().forEach(r => {
        clearMarks(r);
        if (!terms.length) { r.style.display = ''; return; }
        const hay = r.innerText.toLowerCase();
        const match = terms.every(term => hay.includes(term));
        r.style.display = match ? '' : 'none';
        if (match) hi(r, terms);
      });
    }, 160);
  });
  // -------- beigas --------

  // -------- kārtošanas palīgfunkcijas --------
  function parseDateLike(text) {
    const s = text.trim()
      .replace(/\u2013|\u2014/g, '-')   // normalizē domuzīmes
      .replace(/(\d{1,2})\.(\d{1,2})\.(\d{4})(.*)?/, '$3-$2-$1$4'); // dd.mm.gggg -> gggg-mm-dd
    const ts = Date.parse(s);
    return isNaN(ts) ? null : ts;
  }

  function sortKey(td, colIndex) {
    const text = td?.innerText?.trim() || '';

    if (colIndex === 0) { // Datums/Laiks
      const ts = parseDateLike(text);
      return ts !== null ? ts : text.toLowerCase();
    }
    if (colIndex === 1 || colIndex === 2) { // komandas
      return text.toLowerCase();
    }
    if (colIndex === 3) { // Rezultāts "110–100"
      const m = text.replace(/\s/g,'').match(/(\d+)[^\d]+(\d+)/);
      if (m) {
        const a = parseInt(m[1], 10), b = parseInt(m[2], 10);
        return [a + b, a - b];
      }
      return [0, 0];
    }
    if (colIndex === 4) return text.toLowerCase(); // Uzvarētājs
    if (colIndex === 5) { // Statistika saite
      const a = td.querySelector('a');
      return a ? (a.getAttribute('href') || '').toLowerCase() : text.toLowerCase();
    }
    const num = Number(text.replace(/[^0-9.+-]/g, ''));
    return Number.isFinite(num) ? num : text.toLowerCase();
  }

  function compareKeys(a, b) {
    const arrA = Array.isArray(a) ? a : [a];
    const arrB = Array.isArray(b) ? b : [b];
    for (let i = 0; i < Math.max(arrA.length, arrB.length); i++) {
      const x = arrA[i], y = arrB[i];
      if (x === y) continue;
      if (typeof x === 'number' && typeof y === 'number') return x - y;
      return ('' + x).localeCompare('' + y, undefined, { numeric: true, sensitivity: 'base' });
    }
    return 0;
  }

  function clearOtherArrows(activeTh) {
    Array.from(table.tHead.rows[0].cells).forEach(th => {
      if (th !== activeTh) {
        th.setAttribute('aria-sort', 'none');
        const span = th.querySelector('.arrow');
        if (span) span.textContent = '↕';
      }
    });
  }

  function bindSorting() {
    if (!table) return;
    const headCells = Array.from(table.tHead.rows[0].cells);

    headCells.forEach((th, i) => {
      // izlaižam pēdējo kolonnu "Statistika"
      if (i === headCells.length - 1) return;

      th.classList.add('sortable');
      th.setAttribute('tabindex', '0');
      th.setAttribute('aria-sort', 'none');

      const arrow = document.createElement('span');
      arrow.className = 'arrow';
      arrow.textContent = '↕';
      th.appendChild(arrow);

      const applySort = (dir) => {
        const data = rows().map((tr, idx) => {
          const td = tr.children[i];
          return { tr, idx, key: sortKey(td, i) };
        });

        data.sort((A, B) => {
          const cmp = compareKeys(A.key, B.key);
          return dir === 'asc' ? cmp || (A.idx - B.idx) : -cmp || (A.idx - B.idx);
        });

        const frag = document.createDocumentFragment();
        data.forEach(x => frag.appendChild(x.tr));
        tbody.appendChild(frag);
      };

      let current = 'none';

      const toggle = () => {
        const next = current === 'asc' ? 'desc' : 'asc';
        clearOtherArrows(th);
        current = next;
        th.setAttribute('aria-sort', next);
        th.querySelector('.arrow').textContent = next === 'asc' ? '▲' : '▼';
        applySort(next);
      };

      th.addEventListener('click', toggle);
      th.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
        if (e.key === 'ArrowUp') { e.preventDefault(); current = 'desc'; toggle(); }
        if (e.key === 'ArrowDown') { e.preventDefault(); current = 'asc'; toggle(); }
      });
    });
  }

  bindSorting();
</script>
@endpush
