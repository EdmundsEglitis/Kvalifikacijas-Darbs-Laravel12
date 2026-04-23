@extends('layouts.app')
@section('title','LBS — Komandu salīdzinātājs')

@section('subnav')
@isset ($subLeague)
    <x-lbs-subnav :subLeague="$subLeague" />
@endisset
@endsection

@section('content')
  <main class="max-w-7xl mx-auto px-4 pb-16 pt-24 space-y-8">

    <section class="bg-[#1f2937] border border-[#374151] rounded-2xl p-4 sm:p-5">
      <form method="GET" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-8 items-end">
        <div class="lg:col-span-2">
          <label class="block text-xs text-gray-400 mb-1">No sezonas</label>
          <select id="fromSelect" name="from" class="w-full bg-[#0f172a] border border-[#374151] rounded-lg px-3 py-2">
            @foreach($seasons as $s)
              <option value="{{ $s }}" @selected((int)$from === (int)$s)>{{ $s }}</option>
            @endforeach
          </select>
        </div>

        <div class="lg:col-span-2">
          <label class="block text-xs text-gray-400 mb-1">Līdz sezonai</label>
          <select id="toSelect" name="to" class="w-full bg-[#0f172a] border border-[#374151] rounded-lg px-3 py-2">
            @foreach($seasons as $s)
              <option value="{{ $s }}" @selected((int)$to === (int)$s)>{{ $s }}</option>
            @endforeach
          </select>
        </div>

        <div class="lg:col-span-2">
          <label class="block text-xs text-gray-400 mb-1">Vecāklīga</label>
          <select id="parentSelect" name="league" class="w-full bg-[#0f172a] border border-[#374151] rounded-lg px-3 py-2">
            <option value="">— Visas —</option>
            @foreach($parents as $p)
              <option value="{{ $p->id }}" @selected(!empty($selectedParent) && (int)$selectedParent === (int)$p->id)>
                {{ $p->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="lg:col-span-2">
          <label class="block text-xs text-gray-400 mb-1">Apakšlīga</label>
          <select id="subSelect" name="sub" class="w-full bg-[#0f172a] border border-[#374151] rounded-lg px-3 py-2">
            <option value="">— Visas —</option>
            @foreach($subs as $s)
              <option value="{{ $s->id }}" data-parent="{{ $s->parent_id }}"
                @selected(!empty($selectedSub) && (int)$selectedSub === (int)$s->id)>
                {{ $s->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="lg:col-span-8 flex flex-wrap items-center gap-3 pt-1">
          <input id="q" type="text" placeholder="Ātrā meklēšana tabulā…"
                 class="flex-1 min-w-[220px] bg-[#0f172a] border border-[#374151] rounded-lg px-3 py-2" />
        </div>
      </form>
    </section>

    <section class="bg-[#1f2937] border border-[#374151] rounded-2xl p-4 sm:p-5">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="text-sm text-gray-300">Atzīmē rindas (līdz 5) un salīdzini.</div>
        <div class="flex gap-2">
          <button id="compareBtn" class="px-3 py-2 rounded-lg bg-white/10 text-white hover:bg-white/20 disabled:opacity-40" disabled>
            Salīdzināt izvēlētos
          </button>
          <button id="clearSelBtn" class="px-3 py-2 rounded-lg bg-white/10 text-white hover:bg-white/20">
            Notīrīt
          </button>
        </div>
      </div>

      <div id="compareArea" class="mt-4 hidden">
        <h3 class="text-white font-semibold mb-3">Salīdzinājums</h3>
        <div id="compareGrid" class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(220px,1fr))]"></div>
      </div>
    </section>

    <section class="bg-[#1f2937] border border-[#374151] rounded-2xl overflow-hidden">
      <div class="overflow-x-auto">
        <table id="standingsTable" class="min-w-[900px] w-full text-sm">
          <thead class="bg-[#0f172a] text-gray-300 sticky top-0 z-10">
            <tr>
              <th class="px-3 py-2 w-10"></th>
              <th data-sort="season" class="px-3 py-2 text-left cursor-pointer select-none hover:text-white">Sezona</th>
              <th data-sort="team_name" class="px-3 py-2 text-left cursor-pointer select-none hover:text-white">Komanda</th>
              <th data-sort="wins" class="px-3 py-2 text-right cursor-pointer select-none hover:text-white">W</th>
              <th data-sort="losses" class="px-3 py-2 text-right cursor-pointer select-none hover:text-white">L</th>
              <th data-sort="win_percent_fmt" class="px-3 py-2 text-right cursor-pointer select-none hover:text-white">Win%</th>
              <th data-sort="ppg_fmt" class="px-3 py-2 text-right cursor-pointer select-none hover:text-white">PPG</th>
              <th data-sort="opp_ppg_fmt" class="px-3 py-2 text-right cursor-pointer select-none hover:text-white">OPP PPG</th>
              <th data-sort="diff_txt" class="px-3 py-2 text-right cursor-pointer select-none hover:text-white">Diff</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#374151] text-[#F3F4F6]">
            @foreach($rows as $r)
              <tr class="odd:bg-[#1f2937] even:bg-[#111827] hover:bg-[#374151]/60 transition"
                  data-season="{{ $r['season'] }}"
                  data-team="{{ $r['data_team'] }}"
                  data-parent="{{ $r['parent_league_id'] ?? '' }}"
                  data-sub="{{ $r['subleague_id'] ?? '' }}">
                <td class="px-3 py-2 align-middle">
                  <input
                    type="checkbox"
                    class="rowSel accent-[#84CC16]"
                    data-payload='{{ json_encode([
                        "season"      => $r["season"],
                        "team"        => $r["team_name"],
                        "logo"        => $r["team_logo"],
                        "wins"        => $r["wins"],
                        "losses"      => $r["losses"],
                        "win_percent" => $r["win_percent"] ?? null,
                        "ppg"         => $r["ppg"] ?? null,
                        "opp_ppg"     => $r["opp_ppg"] ?? null,
                        "diff"        => $r["diff"] ?? null,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}'>
                </td>
                <td class="px-3 py-2">{{ $r['season'] }}</td>
                <td class="px-3 py-2">
                  <a class="flex items-center gap-2 hover:text-[#84CC16]" href="{{ route('lbs.team.show', $r['team_id']) }}">
                    @if(!empty($r['team_logo']))
                      <img src="{{ asset('storage/' . $r['team_logo']) }}" alt="{{ $r['team_name'] }} logo"
                           class="h-6 w-6 object-contain rounded bg-white p-[2px]" />
                    @else
                      <span class="inline-flex items-center justify-center h-6 w-6 rounded bg-white/10 text-[10px]">—</span>
                    @endif
                    <span class="truncate max-w-[240px]">{{ $r['team_name'] }}</span>
                  </a>
                </td>
                <td class="px-3 py-2 text-right">{{ $r['wins'] }}</td>
                <td class="px-3 py-2 text-right">{{ $r['losses'] }}</td>
                <td class="px-3 py-2 text-right">{{ $r['win_percent_fmt'] }}</td>
                <td class="px-3 py-2 text-right">{{ $r['ppg_fmt'] }}</td>
                <td class="px-3 py-2 text-right">{{ $r['opp_ppg_fmt'] }}</td>
                <td class="px-3 py-2 text-right">
                  <span class="{{ $r['diff_class'] }}">{{ $r['diff_txt'] }}</span>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <p class="px-4 pb-4 pt-2 text-xs text-gray-400 sm:hidden">Padoms: pavelc horizontāli, lai redzētu visas kolonnas.</p>
    </section>

    <section class="pb-8">
      <h2 class="text-xl sm:text-2xl font-semibold mb-3">Statistikas skaidrojumi</h2>
      <div class="grid gap-3 sm:gap-4 [grid-template-columns:repeat(auto-fit,minmax(180px,1fr))]">
        @foreach($legend as $item)
          <div class="bg-[#1f2937] border border-[#374151] rounded-xl p-3">
            <div class="text-sm font-semibold text-white mb-1">{{ $item[0] }}</div>
            <p class="text-xs text-gray-300">{{ $item[1] }}</p>
          </div>
        @endforeach
      </div>
    </section>

  </main>

  <script>
    const STORAGE_BASE = @json(asset('storage'));

    (function () {
      const parentSel = document.getElementById('parentSelect');
      const subSel    = document.getElementById('subSelect');
      if (!parentSel || !subSel) return;

      function syncSubOptions() {
        const pid = parentSel.value;
        Array.from(subSel.options).forEach(opt => {
          if (!opt.value) { opt.hidden = false; return; }
          const p = opt.getAttribute('data-parent');
          opt.hidden = !!pid && p !== pid;
        });

        const current = subSel.options[subSel.selectedIndex];
        if (current && current.hidden) subSel.value = '';
      }

      parentSel.addEventListener('change', () => {
        syncSubOptions();
        applyFilters();
      });

      subSel.addEventListener('change', applyFilters);

      syncSubOptions();
    })();

    const q         = document.getElementById('q');
    const rows      = Array.from(document.querySelectorAll('#standingsTable tbody tr'));
    const parentSel = document.getElementById('parentSelect');
    const subSel    = document.getElementById('subSelect');
    const fromSel   = document.getElementById('fromSelect');
    const toSel     = document.getElementById('toSelect');

    function seasonNumber(value) {
      if (value === null || value === undefined) return NaN;
      const s = String(value).trim();

      // works for values like "2023" or "2023/2024"
      const match = s.match(/\d{4}/);
      return match ? parseInt(match[0], 10) : NaN;
    }

    function applyFilters() {
      const term = (q?.value || '').trim().toLowerCase();
      const pSel = parentSel?.value || '';
      const sSel = subSel?.value || '';
      const from = seasonNumber(fromSel?.value);
      const to   = seasonNumber(toSel?.value);

      rows.forEach(r => {
        const hay = ((r.dataset.team || '') + ' ' + (r.dataset.season || '')).toLowerCase();
        const okQ = hay.includes(term);

        const rp = r.getAttribute('data-parent') || '';
        const rs = r.getAttribute('data-sub') || '';
        const rowSeason = seasonNumber(r.dataset.season);

        const okP = !pSel || rp === pSel;
        const okS = !sSel || rs === sSel;
        const okFrom = !isFinite(from) || (isFinite(rowSeason) && rowSeason >= from);
        const okTo   = !isFinite(to)   || (isFinite(rowSeason) && rowSeason <= to);

        r.style.display = (okQ && okP && okS && okFrom && okTo) ? '' : 'none';
      });
    }

    q?.addEventListener('input', applyFilters);
    fromSel?.addEventListener('change', applyFilters);
    toSel?.addEventListener('change', applyFilters);

    document.addEventListener('DOMContentLoaded', applyFilters);

    const headers = document.querySelectorAll('#standingsTable thead th[data-sort]');
    headers.forEach(h => {
      h.addEventListener('click', () => {
        const idx = Array.from(h.parentElement.children).indexOf(h);
        const tbody = document.querySelector('#standingsTable tbody');
        const asc = !(h.dataset.asc === 'true');
        headers.forEach(x => x.removeAttribute('data-asc'));
        h.dataset.asc = asc;

        const visible = Array.from(tbody.querySelectorAll('tr')).filter(tr => tr.style.display !== 'none');

        const num = (txt) => {
          if (!txt) return NaN;
          const t = txt.replace('%','').replace('+','').replace('—','').trim();
          const n = parseFloat(t);
          return isNaN(n) ? NaN : n;
        };

        visible.sort((a,b) => {
          const A = a.children[idx].innerText.trim();
          const B = b.children[idx].innerText.trim();
          const An = num(A), Bn = num(B);
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

    const numVal = (v) => (v===null||v===undefined||v==='—') ? NaN : Number(v);
    function vsLeader(sel, field, higherIsBetter=true) {
      const values = sel.map(p => numVal(p[field]));
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
        return {label:`-${Math.round(behindPct)}% sal. ar līderi`, cls:'text-[#F97316]'};
      });
    }
    const line = (c) => `<div class="text-xs mt-0.5 ${c.cls}">${c.label}</div>`;

    document.getElementById('compareBtn').addEventListener('click', () => {
      const sel = selectedPayloads();
      if (!sel.length) { compareArea.classList.add('hidden'); return; }

      const cmpWin  = vsLeader(sel, 'win_percent', true);
      const cmpPPG  = vsLeader(sel, 'ppg',         true);
      const cmpOPP  = vsLeader(sel, 'opp_ppg',     false);
      const cmpDiff = vsLeader(sel, 'diff',        true);

      compareGrid.innerHTML = sel.map((p,i) => {
        const winPct = p.win_percent==null ? '—' : `${(Number(p.win_percent)*100).toFixed(1)}%`;
        const diffTxt= p.diff==null ? '—' : (p.diff>=0?('+'+p.diff):p.diff);

        const toUrl = (v) => {
          if (!v) return null;
          const s = String(v);
          if (/^https?:\/\//i.test(s)) return s;
          return `${STORAGE_BASE}/${s.replace(/^\/+/, '')}`;
        };

        const logoUrl = toUrl(p.logo);

        const logo = logoUrl
          ? `<img src="${logoUrl}" class="h-6 w-6 object-contain rounded bg-white p-[2px]" alt="">`
          : `<span class="inline-flex items-center justify-center h-6 w-6 rounded bg-white/10"></span>`;

        const one = (v) => v==null ? '—' : Number(v).toFixed(1);

        return `
          <article class="bg-[#0f172a]/60 border border-[#374151] rounded-xl p-4">
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center gap-2">
                ${logo}
                <div class="text-white font-semibold">${p.team}</div>
              </div>
              <div class="text-xs text-[#F3F4F6]/70">${p.season}</div>
            </div>
            <div class="grid grid-cols-3 gap-3 text-sm">
              <div><div class="text-[#F3F4F6]/60 text-xs">W/L</div><div class="font-semibold">${p.wins ?? '—'}–${p.losses ?? '—'}</div>${line(cmpWin[i])}</div>
              <div><div class="text-[#F3F4F6]/60 text-xs">Win%</div><div class="font-semibold">${winPct}</div>${line(cmpWin[i])}</div>
              <div><div class="text-[#F3F4F6]/60 text-xs">Diff</div><div class="font-semibold ${p.diff==null?'':(p.diff>=0?'text-[#84CC16]':'text-[#F97316]')}">${diffTxt}</div>${line(cmpDiff[i])}</div>
              <div><div class="text-[#F3F4F6]/60 text-xs">PPG</div><div class="font-semibold">${one(p.ppg)}</div>${line(cmpPPG[i])}</div>
              <div><div class="text-[#F3F4F6]/60 text-xs">OPP PPG</div><div class="font-semibold">${one(p.opp_ppg)}</div>${line(cmpOPP[i])}</div>
            </div>
          </article>
        `;
      }).join('');

      compareArea.classList.remove('hidden');
    });
  </script>
@endsection