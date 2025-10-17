@extends('layouts.nba')
@section('title','Spēlētāju pārlūks un salīdzināšana')

@push('head')
<style>
  @keyframes fadeUp { from { opacity:.0; transform: translateY(6px) } to { opacity:1; transform:none } }
  .fade-up { animation: fadeUp .35s ease-out both; }
  .fade-up-delayed { animation: fadeUp .45s .08s ease-out both; }
  .img-fade { opacity:.0; transition: opacity .3s ease; }
  .img-fade.loaded { opacity:1; }
  @keyframes shimmer { 0%{background-position:-200% 0} 100%{background-position:200% 0} }
  .shimmer { background: linear-gradient(90deg, rgba(255,255,255,0.06) 25%, rgba(255,255,255,0.12) 37%, rgba(255,255,255,0.06) 63%);
             background-size: 400% 100%; animation: shimmer 1.6s infinite; }
</style>
@endpush

@section('content')
<main class="max-w-7xl mx-auto px-4 pb-16 pt-10 space-y-8">

  <section class="bg-[#1f2937] border border-[#374151] rounded-2xl p-4 sm:p-5 fade-up">
    <form method="GET" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6 items-end">
      <div>
        <label class="block text-xs text-gray-400 mb-1">No sezonas</label>
        <select name="from" class="w-full bg-[#0f172a] border border-[#374151] rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#84CC16]/40">
          @foreach($seasons as $s)
            <option value="{{ $s }}" {{ (int)$from === (int)$s ? 'selected' : '' }}>{{ $s }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-xs text-gray-400 mb-1">Līdz sezonai</label>
        <select name="to" class="w-full bg-[#0f172a] border border-[#374151] rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#84CC16]/40">
          @foreach($seasons as $s)
            <option value="{{ $s }}" {{ (int)$to === (int)$s ? 'selected' : '' }}>{{ $s }}</option>
          @endforeach
        </select>
      </div>
      <div class="lg:col-span-2">
        <label class="block text-xs text-gray-400 mb-1">Komanda (nosaukums vai saīsinājums)</label>
        <input name="team" value="{{ $teamQuery }}" placeholder="piem., BOS vai Celtics"
               class="w-full bg-[#0f172a] border border-[#374151] rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#84CC16]/40" />
      </div>
      <div class="lg:col-span-2">
        <label class="block text-xs text-gray-400 mb-1">Spēlētājs</label>
        <input name="player" value="{{ $playerQuery }}" placeholder="Spēlētāja vārds"
               class="w-full bg-[#0f172a] border border-[#374151] rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#84CC16]/40" />
      </div>

      <div class="flex flex-col gap-3 lg:col-span-6 sm:flex-row">
        <button class="px-4 py-2 rounded-lg bg-[#84CC16] text-[#111827] font-semibold hover:bg-[#a3e635] transition focus:outline-none focus:ring-2 focus:ring-[#84CC16]/40">
          Piemērot
        </button>
        <a href="{{ route('nba.compare') }}"
           class="px-4 py-2 rounded-lg bg-white/10 text-white hover:bg-white/20 transition focus:outline-none focus:ring-2 focus:ring-white/30">
          Atiestatīt
        </a>
        <input id="q" type="text" placeholder="Ātrā meklēšana šīs lapas spēlētājos"
               class="flex-1 min-w-[200px] bg-[#0f172a] border border-[#374151] rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#84CC16]/40" />

      </div>
    </form>
  </section>

  <section class="bg-[#1f2937] border border-[#374151] rounded-2xl p-4 sm:p-5 fade-up-delayed">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="text-sm text-gray-300">Atlasiet rindas (atzīme) salīdzināšanai (līdz 5).</div>
      <div class="flex gap-2">
        <button id="compareBtn"
                class="px-3 py-2 rounded-lg bg-white/10 text-white hover:bg-white/20 transition disabled:opacity-40 focus:outline-none focus:ring-2 focus:ring-white/30"
                disabled>
          Salīdzināt atlasītās
        </button>
        <button id="clearSelBtn"
                class="px-3 py-2 rounded-lg bg-white/10 text-white hover:bg-white/20 transition focus:outline-none focus:ring-2 focus:ring-white/30">
          Notīrīt atlasi
        </button>
      </div>
    </div>

    <div id="compareLoading" class="hidden mt-4">
      <div class="rounded-xl border border-[#374151] bg-[#0f172a]/50 p-4 flex items-center gap-3">
        <div class="h-6 w-6 rounded-full border-2 border-white/20 border-t-white animate-spin"></div>
        <div class="h-4 w-40 rounded shimmer"></div>
      </div>
    </div>

    <div id="compareArea" class="mt-4 hidden">
      <h3 class="text-white font-semibold mb-3">Salīdzinājums</h3>
      <div id="compareGrid" class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(240px,1fr))]"></div>
    </div>
  </section>

  @if($rows instanceof \Illuminate\Contracts\Pagination\Paginator)
    <div class="flex justify-end mt-4">
      {{ $rows->appends(request()->query())->onEachSide(1)->links('vendor.pagination.custom-dark') }}
    </div>
  @endif

  {{-- Karte mobilajām ierīcēm --}}
  <section class="sm:hidden space-y-3">
    @forelse($rows as $r)
      <article class="bg-[#1f2937] border border-[#374151] rounded-2xl p-4 fade-up hover:-translate-y-0.5 hover:shadow-xl hover:shadow-black/20 transition">
        <div class="flex items-center justify-between gap-3">
          <div class="flex items-center gap-3 min-w-0">
            @if(!empty($r['headshot']))
              <img loading="lazy" src="{{ $r['headshot'] }}" class="h-10 w-10 rounded-full object-cover ring-1 ring-white/10 img-fade" onload="this.classList.add('loaded')" alt="">
            @else
              <div class="h-10 w-10 rounded-full bg-white/10"></div>
            @endif
            <div class="min-w-0">
              <div class="text-white font-semibold truncate">{{ $r['player'] }}</div>
              <div class="text-xs text-gray-400 truncate">{{ $r['team'] }} ({{ $r['abbr'] }}) • {{ $r['season'] }}</div>
            </div>
          </div>
          <label class="shrink-0 inline-flex items-center gap-2 text-xs">
            <input type="checkbox" class="rowSel accent-[#84CC16]" data-payload='{{ $r['payload'] }}'>
            <span class="text-gray-300">Salīdzināt</span>
          </label>
        </div>

        <div class="mt-3 grid grid-cols-3 gap-3 text-sm">
          <div><div class="text-[#F3F4F6]/60 text-xs">PPG</div><div class="font-semibold">{{ $r['ppg'] }}</div></div>
          <div><div class="text-[#F3F4F6]/60 text-xs">RPG</div><div class="font-semibold">{{ $r['rpg'] }}</div></div>
          <div><div class="text-[#F3F4F6]/60 text-xs">APG</div><div class="font-semibold">{{ $r['apg'] }}</div></div>
          <div><div class="text-[#F3F4F6]/60 text-xs">SPG</div><div class="font-semibold">{{ $r['spg'] }}</div></div>
          <div><div class="text-[#F3F4F6]/60 text-xs">BPG</div><div class="font-semibold">{{ $r['bpg'] }}</div></div>
          <div><div class="text-[#F3F4F6]/60 text-xs">MPG</div><div class="font-semibold">{{ $r['mpg'] }}</div></div>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-300">
          <span>G: {{ $r['games'] }}</span>
          <span>•</span>
          <span>U/Z: {{ $r['wl_text'] }}</span>
          <span>•</span>
          <span>FG: {{ $r['fg_pct'] }}</span>
          <span>3P: {{ $r['tp_pct'] }}</span>
          <span>FT: {{ $r['ft_pct'] }}</span>
        </div>
      </article>
    @empty
      <div class="text-gray-400">Nav rezultātu.</div>
    @endforelse
  </section>

  {{-- Tabula (≥ sm) --}}
  <section class="hidden sm:block bg-[#1f2937] border border-[#374151] rounded-2xl overflow-hidden fade-up">
    <div class="overflow-x-auto will-change-transform">
      <table id="playersTable" class="min-w-[1100px] w-full text-sm">
        <thead class="bg-[#0f172a] text-gray-300 sticky top-0 z-10">
          <tr>
            <th class="px-3 py-2 w-10"></th>
            <th data-sort="season" class="px-3 py-2 cursor-pointer select-none hover:text-white">Sezona</th>
            <th class="px-3 py-2 text-left">Spēlētājs</th>
            <th class="px-3 py-2 text-left">Komanda</th>
            <th data-sort="games" class="px-3 py-2 text-right cursor-pointer select-none hover:text-white">G</th>
            <th class="px-3 py-2 text-right">U/Z</th>
            <th data-sort="ppg" class="px-3 py-2 text-right cursor-pointer select-none hover:text-white">PPG</th>
            <th data-sort="rpg" class="px-3 py-2 text-right cursor-pointer select-none hover:text-white">RPG</th>
            <th data-sort="apg" class="px-3 py-2 text-right cursor-pointer select-none hover:text-white">APG</th>
            <th data-sort="spg" class="px-3 py-2 text-right cursor-pointer select-none hover:text-white">SPG</th>
            <th data-sort="bpg" class="px-3 py-2 text-right cursor-pointer select-none hover:text-white">BPG</th>
            <th data-sort="tpg" class="px-3 py-2 text-right cursor-pointer select-none hover:text-white">TOV</th>
            <th data-sort="mpg" class="px-3 py-2 text-right cursor-pointer select-none hover:text-white">MPG</th>
            <th data-sort="fg_pct" class="px-3 py-2 text-right cursor-pointer select-none hover:text-white">FG%</th>
            <th data-sort="tp_pct" class="px-3 py-2 text-right cursor-pointer select-none hover:text-white">3P%</th>
            <th data-sort="ft_pct" class="px-3 py-2 text-right cursor-pointer select-none hover:text-white">FT%</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#374151] text-[#F3F4F6]">
          @foreach($rows as $i => $r)
            <tr class="odd:bg-[#1f2937] even:bg-[#111827] hover:bg-[#374151]/60 transition will-change-transform"
                style="animation-delay: {{ ($i%15)*20 }}ms; animation-duration:.35s" >
              <td class="px-3 py-2">
                <input type="checkbox" class="rowSel accent-[#84CC16]" data-payload='{{ $r['payload'] }}'>
              </td>
              <td class="px-3 py-2">{{ $r['season'] }}</td>
              <td class="px-3 py-2">
                <div class="flex items-center gap-2">
                  @if(!empty($r['headshot']))
                    <img loading="lazy" src="{{ $r['headshot'] }}" class="h-6 w-6 rounded-full object-cover ring-1 ring-white/10 img-fade" onload="this.classList.add('loaded')" alt="">
                  @else
                    <div class="h-6 w-6 rounded-full bg-white/10"></div>
                  @endif
                  <a class="hover:text-[#84CC16]" href="{{ route('nba.player.show', $r['player_id']) }}">{{ $r['player'] }}</a>
                </div>
              </td>
              <td class="px-3 py-2">
                <div class="flex items-center gap-2">
                  @if(!empty($r['logo']))
                    <img loading="lazy" src="{{ $r['logo'] }}" class="h-5 w-5 object-contain rounded bg-white p-[2px] img-fade" onload="this.classList.add('loaded')" alt="">
                  @endif
                  <a class="hover:text-[#84CC16]" href="{{ route('nba.team.show', $r['team_id']) }}">{{ $r['team'] }}</a>
                  <span class="text-xs text-gray-400">({{ $r['abbr'] }})</span>
                </div>
              </td>
              <td class="px-3 py-2 text-right">{{ $r['games'] }}</td>
              <td class="px-3 py-2 text-right">{{ $r['wl_text'] }}</td>
              <td class="px-3 py-2 text-right">{{ $r['ppg'] }}</td>
              <td class="px-3 py-2 text-right">{{ $r['rpg'] }}</td>
              <td class="px-3 py-2 text-right">{{ $r['apg'] }}</td>
              <td class="px-3 py-2 text-right">{{ $r['spg'] }}</td>
              <td class="px-3 py-2 text-right">{{ $r['bpg'] }}</td>
              <td class="px-3 py-2 text-right">{{ $r['tpg'] }}</td>
              <td class="px-3 py-2 text-right">{{ $r['mpg'] }}</td>
              <td class="px-3 py-2 text-right">{{ $r['fg_pct'] }}</td>
              <td class="px-3 py-2 text-right">{{ $r['tp_pct'] }}</td>
              <td class="px-3 py-2 text-right">{{ $r['ft_pct'] }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </section>

  @if($rows instanceof \Illuminate\Contracts\Pagination\Paginator)
    <div class="flex justify-end mt-4">
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
  const tableRows = Array.from(document.querySelectorAll('#playersTable tbody tr'));
  q?.addEventListener('input', (e) => {
    const term = e.target.value.trim().toLowerCase();
    tableRows.forEach(r => {
      const hay = r.innerText.toLowerCase();
      r.style.display = hay.includes(term) ? '' : 'none';
    });
  });

  const headers = document.querySelectorAll('#playersTable thead th[data-sort]');
  headers.forEach(h => {
    h.addEventListener('click', () => {
      const tbody = document.querySelector('#playersTable tbody');
      const idx   = Array.from(h.parentElement.children).indexOf(h);
      const asc   = !(h.dataset.asc === 'true');
      headers.forEach(x => x.removeAttribute('data-asc'));
      h.dataset.asc = asc;

      const visible = Array.from(tbody.querySelectorAll('tr'))
        .filter(tr => tr.style.display !== 'none');

      const parseNum = (text) => {
        if (!text) return NaN;
        const t = text.replaceAll(',', '').replace('%','').replace('—','').trim();
        const n = parseFloat(t);
        return isNaN(n) ? NaN : n;
      };

      visible.sort((a,b) => {
        const A = a.children[idx].innerText.trim();
        const B = b.children[idx].innerText.trim();
        const An = parseNum(A), Bn = parseNum(B);
        const both = isFinite(An) && isFinite(Bn);
        if (both) return asc ? (An - Bn) : (Bn - An);
        return asc ? A.localeCompare(B) : B.localeCompare(A);
      });

      tbody.append(...visible);
    });
  });

  const selBoxes    = document.querySelectorAll('.rowSel');
  const compareBtn  = document.getElementById('compareBtn');
  const clearSelBtn = document.getElementById('clearSelBtn');
  const compareArea = document.getElementById('compareArea');
  const compareGrid = document.getElementById('compareGrid');
  const compareLoading = document.getElementById('compareLoading');

  function selectedPayloads() {
    return Array.from(selBoxes)
      .filter(x => x.checked)
      .slice(0,5)
      .map(x => JSON.parse(x.dataset.payload));
  }

  selBoxes.forEach(cb => {
    cb.addEventListener('change', () => {
      const sel = selectedPayloads();
      if (sel.length > 5) { cb.checked = false; return; }
      compareBtn.disabled = sel.length === 0;
    });
  });

  clearSelBtn.addEventListener('click', () => {
    selBoxes.forEach(x => x.checked = false);
    compareBtn.disabled = true;
    compareGrid.innerHTML = '';
    compareArea.classList.add('hidden');
  });

  const num = (v) => (v===null||v===undefined||v==='—') ? NaN : Number(v);
  function vsLeader(sel, field, higherIsBetter=true) {
    const values = sel.map(p => num(p[field]));
    const valid  = values.filter(v => isFinite(v));
    if (!valid.length) return sel.map(_ => ({label:'—', cls:'text-gray-300'}));
    const leader = higherIsBetter ? Math.max(...valid) : Math.min(...valid);
    return values.map(v => {
      if (!isFinite(v)) return {label:'—', cls:'text-gray-300'};
      let behindPct;
      if (leader === 0) behindPct = 0;
      else if (higherIsBetter) behindPct = ((leader - v) / Math.abs(leader)) * 100;
      else behindPct = ((v - leader) / Math.abs(leader)) * 100;
      if (Math.abs(behindPct) < 0.5) return {label:'Līderis', cls:'text-[#84CC16]'};
      return {label:`-${Math.round(behindPct)}% pret līderi`, cls:'text-[#F97316]'};
    });
  }
  const line = (c) => `<div class="text-xs mt-0.5 ${c.cls}">${c.label}</div>`;

  compareBtn.addEventListener('click', () => {
    const sel = selectedPayloads();
    if (!sel.length) { compareArea.classList.add('hidden'); return; }
    compareLoading.classList.remove('hidden'); compareArea.classList.add('hidden');

    setTimeout(() => {
      const cmpPPG = vsLeader(sel, 'ppg', true);
      const cmpRPG = vsLeader(sel, 'rpg', true);
      const cmpAPG = vsLeader(sel, 'apg', true);
      const cmpSPG = vsLeader(sel, 'spg', true);
      const cmpBPG = vsLeader(sel, 'bpg', true);
      const cmpTOV = vsLeader(sel, 'tpg', false);
      const cmpMPG = vsLeader(sel, 'mpg', true);
      const cmpFG  = vsLeader(sel, 'fg_pct', true);
      const cmpTP  = vsLeader(sel, 'tp_pct', true);
      const cmpFT  = vsLeader(sel, 'ft_pct', true);

      compareGrid.innerHTML = sel.map((p,i) => {
        const head = p.headshot
          ? `<img src="${p.headshot}" loading="lazy" class="h-7 w-7 rounded-full object-cover ring-1 ring-white/10 img-fade loaded" />`
          : `<div class="h-7 w-7 rounded-full bg-white/10"></div>`;
        const logo = p.logo
          ? `<img src="${p.logo}" loading="lazy" class="h-6 w-6 object-contain rounded bg-white p-[2px] img-fade loaded" />`
          : '';

        const pct = (v) => (v==null ? '—' : (Number(v) <= 1 ? `${(Number(v)*100).toFixed(1)}%` : `${Number(v).toFixed(1)}%`));
        const one = (v) => (v==null ? '—' : Number(v).toFixed(1));

        return `
          <article class="bg-[#0f172a]/60 border border-[#374151] rounded-xl p-4 transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-black/20 fade-up">
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center gap-2">
                ${head}
                <div class="text-white font-semibold">${p.player}</div>
                <span class="text-xs text-gray-300">(${p.season})</span>
              </div>
              <div class="flex items-center gap-2">${logo}<span class="text-xs text-gray-300">${p.team} (${p.abbr ?? '—'})</span></div>
            </div>

            <div class="grid grid-cols-3 gap-3 text-sm">
              <div><div class="text-[#F3F4F6]/60 text-xs">PPG</div><div class="font-semibold">${one(p.ppg)}</div>${line(cmpPPG[i])}</div>
              <div><div class="text-[#F3F4F6]/60 text-xs">RPG</div><div class="font-semibold">${one(p.rpg)}</div>${line(cmpRPG[i])}</div>
              <div><div class="text-[#F3F4F6]/60 text-xs">APG</div><div class="font-semibold">${one(p.apg)}</div>${line(cmpAPG[i])}</div>
              <div><div class="text-[#F3F4F6]/60 text-xs">SPG</div><div class="font-semibold">${one(p.spg)}</div>${line(cmpSPG[i])}</div>
              <div><div class="text-[#F3F4F6]/60 text-xs">BPG</div><div class="font-semibold">${one(p.bpg)}</div>${line(cmpBPG[i])}</div>
              <div><div class="text-[#F3F4F6]/60 text-xs">TOV</div><div class="font-semibold">${one(p.tpg)}</div>${line(cmpTOV[i])}</div>
              <div><div class="text-[#F3F4F6]/60 text-xs">MPG</div><div class="font-semibold">${one(p.mpg)}</div>${line(cmpMPG[i])}</div>
              <div><div class="text-[#F3F4F6]/60 text-xs">FG%</div><div class="font-semibold">${pct(p.fg_pct)}</div>${line(cmpFG[i])}</div>
              <div><div class="text-[#F3F4F6]/60 text-xs">3P%</div><div class="font-semibold">${pct(p.tp_pct)}</div>${line(cmpTP[i])}</div>
              <div><div class="text-[#F3F4F6]/60 text-xs">FT%</div><div class="font-semibold">${pct(p.ft_pct)}</div>${line(cmpFT[i])}</div>
            </div>

            <div class="mt-3 text-xs text-gray-300">G: ${p.games} • U/Z: ${p.wins ?? '—'}–${p.losses ?? '—'}</div>
          </article>
        `;
      }).join('');

      compareLoading.classList.add('hidden');
      compareArea.classList.remove('hidden');
    }, 150);
  });
</script>
@endpush
