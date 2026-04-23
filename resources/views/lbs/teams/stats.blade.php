@extends('layouts.app')
@section('title', $team->name . ' – Komandas statistika')

@section('subnav')
  <x-teamnav :team="$team" />
@endsection

@section('content')
  <div class="max-w-7xl mx-auto px-4 space-y-16">

    {{-- Header --}}
    <section class="flex flex-col items-center space-y-4 text-center">
      @php
        $hasLogo = $team->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($team->logo);
      @endphp

      @if($hasLogo)
        <img src="{{ asset('storage/' . $team->logo) }}" alt="{{ $team->name }}"
             class="h-28 w-28 object-contain shadow-lg bg-[#111827] rounded-xl ring-2 ring-[#84CC16]/40">
      @endif

      <h1 class="text-4xl font-extrabold text-white drop-shadow">{{ $team->name }}</h1>
      <p class="text-[#F3F4F6]/70 text-sm">Komandas pārskats & statistika</p>

      {{-- Season filter --}}
      <form method="GET" class="mt-4">
        <label for="season_id" class="sr-only">Sezona</label>
        <select
          id="season_id"
          name="season_id"
          onchange="this.form.submit()"
          class="px-4 py-2 rounded-lg bg-[#1f2937] border border-[#374151] text-white focus:outline-none focus:ring-2 focus:ring-[#84CC16]"
        >
          <option value="">Visas sezonas</option>
          @foreach($parentLeagues as $season)
            <option value="{{ $season->id }}" {{ (string)request('season_id') === (string)$season->id ? 'selected' : '' }}>
              {{ $season->name }}
            </option>
          @endforeach
        </select>
      </form>
    </section>

    {{-- W/L --}}
    <section>
      <h2 class="text-2xl font-bold text-white mb-6">Komandas rezultāti</h2>
      <div class="flex justify-center gap-6">
        <div class="p-6 bg-[#1f2937] rounded-xl shadow-lg text-center w-40 border border-[#374151] hover:border-[#84CC16] transition">
          <p class="text-3xl font-extrabold text-[#84CC16]">{{ (int) $wins }}</p>
          <p class="mt-1 text-sm text-[#F3F4F6]/80">Uzvaras</p>
        </div>
        <div class="p-6 bg-[#1f2937] rounded-xl shadow-lg text-center w-40 border border-[#374151] hover:border-[#F97316] transition">
          <p class="text-3xl font-extrabold text-[#F97316]">{{ (int) $losses }}</p>
          <p class="mt-1 text-sm text-[#F3F4F6]/80">Zaudējumi</p>
        </div>
      </div>
    </section>

    {{-- Team averages --}}
    <section>
      <h2 class="text-2xl font-bold text-white mb-6">Vidējie rādītāji vidējā spēlē</h2>
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-6">
        @foreach($averageStats as $stat)
          <div class="p-5 bg-[#1f2937] rounded-xl shadow border border-[#374151] hover:border-[#84CC16] transition flex flex-col items-center">
            <p class="text-2xl font-bold text-[#84CC16]">{{ number_format((float)($stat['avg'] ?? 0), 1) }}</p>
            <p class="text-sm text-[#F3F4F6]/70 mt-1">{{ $stat['label'] ?? '' }}</p>
          </div>
        @endforeach
      </div>
    </section>

    {{-- Players table --}}
    <section>
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold text-white">Spēlētāju statistika</h2>
        <span class="px-3 py-1 text-xs rounded-full bg-[#84CC16]/20 text-[#84CC16] font-semibold">
          {{ $playersStats->count() }} spēlētāji
        </span>
      </div>

      <div class="overflow-x-auto rounded-2xl shadow-lg border border-[#374151] bg-[#1f2937]">
        <table id="playersTable" class="min-w-full">
          <thead class="bg-[#0f172a] sticky top-0 z-10 select-none">
            <tr class="text-[#F3F4F6]/70 text-xs">
              <th class="px-4 py-3 text-left uppercase tracking-wide">Spēlētājs</th>
              <th class="px-4 py-3 text-right uppercase tracking-wide cursor-pointer" data-sort="number">PPG</th>
              <th class="px-4 py-3 text-right uppercase tracking-wide cursor-pointer" data-sort="number">G</th>
              <th class="px-4 py-3 text-right uppercase tracking-wide cursor-pointer" data-sort="time">MIN</th>
              <th class="px-4 py-3 text-right uppercase tracking-wide cursor-pointer" data-sort="number">RPG</th>
              <th class="px-4 py-3 text-right uppercase tracking-wide cursor-pointer" data-sort="number">APG</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#374151]">
            @foreach($playersStats as $p)
              @php
                $hasPhoto = !empty($p['photo']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($p['photo']);
                $jersey   = $p['jersey_number'] ?? null;
                $gp       = (int)($p['gamesPlayed'] ?? 0);
                $sec      = (int)($p['minutes'] ?? 0);
                $minsText = $gp === 0 ? '—' : sprintf('%d:%02d', intdiv($sec,60), $sec%60);
              @endphp

              <tr class="odd:bg-[#1f2937] even:bg-[#16202d] hover:bg-[#2a3647] transition">
                <td class="px-4 py-3">
                  <a href="{{ route('lbs.player.show', $p['id']) }}" class="flex items-center gap-3 group">
                    @if($hasPhoto)
                      <img src="{{ asset('storage/' . $p['photo']) }}" alt="{{ $p['name'] }}"
                           class="h-9 w-9 object-cover rounded-full border border-[#84CC16]/40">
                    @else
                      <div class="h-9 w-9 rounded-full bg-[#0b1220] border border-white/10 grid place-items-center text-[11px] text-white/90">
                        {{ $jersey ?: '—' }}
                      </div>
                    @endif

                    <span class="font-medium text-white group-hover:text-[#84CC16] transition-colors">
                      {{ $p['name'] }}
                    </span>
                  </a>
                </td>

                <td class="px-4 py-3 text-right">
                  <span class="font-semibold text-[#84CC16] tabular-nums">{{ number_format((float)$p['ppg'], 1) }}</span>
                </td>
                <td class="px-4 py-3 text-right tabular-nums">{{ (int)$p['gamesPlayed'] }}</td>
                <td class="px-4 py-3 text-right tabular-nums" data-time="{{ $gp === 0 ? 0 : $sec }}">{{ $minsText }}</td>
                <td class="px-4 py-3 text-right tabular-nums">{{ number_format((float)$p['rpg'], 1) }}</td>
                <td class="px-4 py-3 text-right tabular-nums">{{ number_format((float)$p['apg'], 1) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </section>

  </div>
@endsection

@push('scripts')
<script>
(function(){
  const table = document.getElementById('playersTable');
  if (!table) return;

  function val(td, type){
    if (type === 'time') {
      const secs = td.getAttribute('data-time');
      if (secs !== null) return parseInt(secs, 10) || 0;
      const txt = (td.textContent || '').trim();
      const m = txt.match(/^(\d+):(\d{2})$/);
      return m ? (parseInt(m[1],10)*60 + parseInt(m[2],10)) : 0;
    }
    const raw = (td.textContent || '').replace(/[^\d.\-]/g,'').trim();
    const n = parseFloat(raw);
    return isNaN(n) ? -Infinity : n;
  }

  function setIndicator(th, dir){
    table.querySelectorAll('thead th').forEach(h => h.removeAttribute('aria-sort'));
    th.setAttribute('aria-sort', dir === 1 ? 'ascending' : 'descending');
  }

  table.querySelectorAll('thead th[data-sort]').forEach(th => {
    let dir = 1;
    th.addEventListener('click', () => {
      const headers = Array.from(th.parentNode.children);
      const col = headers.indexOf(th);
      const type = th.getAttribute('data-sort');

      const rows = Array.from(table.tBodies[0].rows);
      rows.sort((a,b) => {
        const va = val(a.cells[col], type);
        const vb = val(b.cells[col], type);
        if (va < vb) return -1 * dir;
        if (va > vb) return  1 * dir;
        return 0;
      });

      dir *= -1;
      setIndicator(th, -dir);

      const frag = document.createDocumentFragment();
      rows.forEach(r => frag.appendChild(r));
      table.tBodies[0].appendChild(frag);
    });
  });
})();
</script>
@endpush