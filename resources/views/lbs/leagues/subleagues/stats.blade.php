@extends('layouts.app')
@section('title', $subLeague->name . ' – Statistika')

@section('subnav')
  <x-lbs-subnav :subLeague="$subLeague" />
@endsection

@section('content')
  {{-- In-page stats navbar (sticky below subnav) --}}
  <nav class="sticky top-28 z-30 bg-transparent backdrop-blur-md border-t border-[#374151]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex space-x-6 py-3 text-sm sm:text-base font-medium">
        <a href="#teams" class="text-[#84CC16]">Komandu statistika</a>
        <a href="#top-players" class="hover:text-[#84CC16] text-[#F3F4F6]/80">Top spēlētāji</a>
        <a href="#all-players" class="hover:text-[#84CC16] text-[#F3F4F6]/80">Spēlētāji</a>
      </div>
    </div>
  </nav>

  <div class="max-w-7xl mx-auto px-4 space-y-20 pt-6">
  <section id="teams">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-3xl font-extrabold text-white">
      {{ $subLeague->name }} — Komandu statistika
    </h1>
    <span class="px-3 py-1 rounded-full bg-[#84CC16]/20 text-[#84CC16] text-xs">
      {{ $teamsStats->count() }} komandas
    </span>
  </div>

  @php $sortedTeams = $teamsStats->sortByDesc('wins'); @endphp

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach($sortedTeams as $teamStat)
      @php $team = $teamStat['team'] ?? null; @endphp

      @if($team && $team->id)
        <a
          href="{{ route('lbs.team.show', $team->id) }}"
          class="block bg-[#1f2937] border border-[#374151] hover:border-[#84CC16]
                 rounded-xl p-6 shadow transition hover:-translate-y-0.5 hover:shadow-xl
                 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#84CC16]/50"
          aria-label="Atvērt komandas {{ $team->name }} lapu"
        >
          <h2 class="text-lg font-semibold text-white">{{ $team->name }}</h2>
          <p class="mt-2 text-[#84CC16] font-bold">Uzvaras: {{ $teamStat['wins'] }}</p>
          <p class="text-[#F97316] font-bold">Zaudējumi: {{ $teamStat['losses'] }}</p>
        </a>
      @else
        <div class="bg-[#1f2937] border border-[#374151] rounded-xl p-6 shadow">
          <h2 class="text-lg font-semibold text-white">{{ $team?->name ?? 'Komanda' }}</h2>
          <p class="mt-2 text-[#84CC16] font-bold">Uzvaras: {{ $teamStat['wins'] }}</p>
          <p class="text-[#F97316] font-bold">Zaudējumi: {{ $teamStat['losses'] }}</p>
        </div>
      @endif
    @endforeach
  </div>
</section>


    <section id="top-players">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-white">Top spēlētāji</h2>
        <span class="px-3 py-1 rounded-full bg-white/10 text-white text-xs">
          {{ is_countable($topPlayers) ? count($topPlayers) : $topPlayers->count() }}
        </span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($topPlayers as $stat => $player)
          @if($player)
            <div class="bg-[#1f2937] border border-[#374151] hover:border-[#84CC16] rounded-xl p-6 text-center shadow transition">
              <h3 class="text-sm uppercase font-semibold text-[#F3F4F6]/80">{{ $stat }}</h3>
              <a href="{{ route('lbs.player.show', $player->id) }}"
                 class="mt-3 block text-lg font-bold text-white hover:text-[#84CC16] transition">
                {{ $player->name }}
              </a>
              <p class="text-[#84CC16] font-semibold">Vidēji: {{ $player->avg_value }}</p>
            </div>
          @endif
        @endforeach
      </div>
    </section>

    <section id="all-players">
  <h2 class="text-2xl font-bold text-white mb-6">Visi spēlētāji</h2>

  <input
    id="player-search"
    type="text"
    placeholder="🔍 Meklēt spēlētāju..."
    class="mb-6 w-full rounded-lg px-4 py-2 bg-[#1f2937] text-white placeholder-gray-400 border border-[#374151] focus:outline-none focus:ring-2 focus:ring-[#84CC16] focus:border-[#84CC16]"
  />

  <div class="overflow-x-auto rounded-lg border border-[#374151] shadow">
  <table id="players-table" class="min-w-[720px] sm:min-w-full">
  <thead class="bg-[#1f2937] text-[#F3F4F6]/80 text-xs uppercase sticky top-0 z-10">
    <tr>
      <th data-sort class="px-4 py-3 text-left font-semibold cursor-pointer">
        Spēlētājs <span class="ml-1 text-[#9CA3AF] sort-arrow">↕</span>
      </th>
      <th data-sort class="px-4 py-3 text-left font-semibold cursor-pointer">
        Komanda <span class="ml-1 text-[#9CA3AF] sort-arrow">↕</span>
      </th>
      <th data-sort data-type="number" class="px-4 py-3 text-right font-semibold cursor-pointer">
        Punkti AVG <span class="ml-1 text-[#9CA3AF] sort-arrow">↕</span>
      </th>
      <th data-sort data-type="number" class="px-4 py-3 text-right font-semibold cursor-pointer">
        Atlēkušās AVG <span class="ml-1 text-[#9CA3AF] sort-arrow">↕</span>
      </th>
      <th data-sort data-type="number" class="px-4 py-3 text-right font-semibold cursor-pointer">
        Piespēles AVG <span class="ml-1 text-[#9CA3AF] sort-arrow">↕</span>
      </th>
    </tr>
  </thead>
      <tbody class="divide-y divide-[#374151] bg-[#111827]">
        @foreach($playersStats as $player)
          <tr class="hover:bg-[#1f2937] transition">
            <td class="px-4 py-3">
              <a href="{{ route('lbs.player.show', $player->id) }}" class="hover:text-[#84CC16]">
                {{ $player->name }}
              </a>
            </td>
            <td class="px-4 py-3">
              @if(optional($player->team)->id)
                <a href="{{ route('lbs.team.show', $player->team->id) }}" class="hover:text-[#84CC16]">
                  {{ $player->team->name }}
                </a>
              @else
                <span class="text-[#F3F4F6]/60">—</span>
              @endif
            </td>
            <td class="px-4 py-3 text-right font-medium">{{ $player->avg_points }}</td>
            <td class="px-4 py-3 text-right font-medium">{{ $player->avg_rebounds }}</td>
            <td class="px-4 py-3 text-right font-medium">{{ $player->avg_assists }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <p class="mt-3 text-xs text-gray-400 sm:hidden">👉 Velc tabulu horizontāli, lai redzētu visas kolonnas.</p>
</section>

  </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const table = document.getElementById('players-table');
  if (!table) return;

  const thead = table.tHead;
  const tbody = table.tBodies[0];
  if (!thead || !tbody) return;

  // Global sort state
  let sortState = { idx: -1, dir: 'none' }; // dir: 'none' | 'asc' | 'desc'

  // Helpers
  const cellText = (td) => {
    const a = td.querySelector('a');
    return (a ? a.textContent : td.textContent).trim();
  };

  const parseNum = (s) => {
    const n = parseFloat(String(s).replace(/\s/g,'').replace(',', '.'));
    return Number.isFinite(n) ? n : null;
  };

  const compare = (a, b, numeric, dir) => {
    if (numeric) {
      const n1 = parseNum(a), n2 = parseNum(b);
      const v1 = n1 === null ? -Infinity : n1;
      const v2 = n2 === null ? -Infinity : n2;
      return dir === 'asc' ? v1 - v2 : v2 - v1;
    }
    const v = String(a).localeCompare(String(b), 'lv', { numeric: true, sensitivity: 'base' });
    return dir === 'asc' ? v : -v;
  };

  const clearArrowsExcept = (keepTh) => {
    thead.querySelectorAll('th[data-sort]').forEach(th => {
      if (th !== keepTh) {
        th.setAttribute('aria-sort', 'none');
        th.dataset.dir = 'none';
        const arrow = th.querySelector('.sort-arrow') || null;
        if (arrow) arrow.textContent = '↕';
      }
    });
  };

  const setArrow = (th, dir) => {
    let arrow = th.querySelector('.sort-arrow');
    if (!arrow) {
      arrow = document.createElement('span');
      arrow.className = 'ml-1 text-[#9CA3AF] sort-arrow';
      th.appendChild(arrow);
    }
    arrow.textContent = dir === 'asc' ? '▲' : dir === 'desc' ? '▼' : '↕';
  };

  const doSort = (colIdx, dir, numeric) => {
    // Build a stable sortable list
    const items = Array.from(tbody.rows).map((tr, i) => ({
      tr,
      i,
      key: cellText(tr.cells[colIdx])
    }));

    items.sort((A, B) => {
      const d = compare(A.key, B.key, numeric, dir);
      return d !== 0 ? d : A.i - B.i; // stability
    });

    const frag = document.createDocumentFragment();
    items.forEach(x => frag.appendChild(x.tr));
    tbody.appendChild(frag);
  };

  // Click handler (event delegation)
  thead.addEventListener('click', (e) => {
    const th = e.target.closest('th[data-sort]');
    if (!th || !thead.contains(th)) return;

    const colIdx = th.cellIndex;
    const numeric = th.getAttribute('data-type') === 'number';

    // Compute next direction
    let nextDir = 'asc';
    if (sortState.idx === colIdx) {
      nextDir = sortState.dir === 'asc' ? 'desc' : 'asc';
    }

    // Perform sort
    doSort(colIdx, nextDir, numeric);

    // Update UI + state
    clearArrowsExcept(th);
    th.dataset.dir = nextDir;
    th.setAttribute('aria-sort', nextDir);
    setArrow(th, nextDir);
    sortState = { idx: colIdx, dir: nextDir };
  });

  // Optional: keyboard support
  thead.querySelectorAll('th[data-sort]').forEach(th => {
    th.setAttribute('tabindex', '0');
    th.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        th.click();
      }
    });
  });

  // Keep your search working
  const searchInput = document.getElementById('player-search');
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      const term = e.target.value.toLowerCase();
      Array.from(tbody.rows).forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(term) ? '' : 'none';
      });
    });
  }
});
</script>
@endpush

