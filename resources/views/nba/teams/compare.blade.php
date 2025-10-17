@extends('layouts.nba')
@section('title','Komandu salīdzināšana')

@section('content')
  <main class="max-w-7xl mx-auto px-4 py-6 space-y-8">

    <section class="bg-[#1f2937] border border-[#374151] rounded-xl p-4 sm:p-5">
      <form class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 items-end" method="GET">
        <div>
          <label class="block text-xs text-gray-400 mb-1">No sezonas</label>
          <select name="from" class="w-full bg-[#0f172a] border border-[#374151] rounded-lg px-3 py-2 focus:outline-none">
            @foreach($seasons as $s)
              <option value="{{ $s }}" {{ (int)$from === (int)$s ? 'selected' : '' }}>{{ $s }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs text-gray-400 mb-1">Līdz sezonai</label>
          <select name="to" class="w-full bg-[#0f172a] border border-[#374151] rounded-lg px-3 py-2 focus:outline-none">
            @foreach($seasons as $s)
              <option value="{{ $s }}" {{ (int)$to === (int)$s ? 'selected' : '' }}>{{ $s }}</option>
            @endforeach
          </select>
        </div>

        <div class="sm:col-span-2 lg:col-span-1">
          <label class="block text-xs text-gray-400 mb-1">Komanda (nosaukums vai saīsinājums)</label>
          <input name="team" value="{{ $teamQuery }}"
                 placeholder="piem., BOS vai Celtics"
                 class="w-full bg-[#0f172a] border border-[#374151] rounded-lg px-3 py-2 focus:outline-none" />
        </div>

        <div class="lg:col-span-1 sm:col-span-2 flex items-end gap-3">
          <button type="submit"
                  class="px-4 py-2 rounded-lg bg-[#84CC16] text-[#111827] font-semibold hover:bg-[#a3e635]">
            Piemērot filtrus
          </button>
          <a href="{{ url()->current() }}"
             class="px-4 py-2 rounded-lg bg-white/5 text-white hover:bg-white/10 border border-white/10">
            Atiestatīt
          </a>
        </div>
      </form>

      <div class="mt-4 lg:mt-6 flex flex-wrap items-center gap-3">
        <input id="q" type="text" placeholder="Ātrā meklēšana tabulā…"
               class="flex-1 min-w-[220px] bg-[#0f172a] border border-[#374151] rounded-lg px-3 py-2 focus:outline-none" />
      </div>
    </section>

    <section class="bg-[#1f2937] border border-[#374151] rounded-xl p-4 sm:p-5">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="text-sm text-gray-300">
          Atlasiet rindas ar izvēles rūtiņu, lai salīdzinātu (līdz 5).
        </div>
        <div class="flex gap-2">
          <button id="compareBtn" class="px-3 py-2 rounded-lg bg-white/10 text-white hover:bg-white/20 disabled:opacity-40" disabled>
            Salīdzināt atlasītās
          </button>
          <button id="clearSelBtn" class="px-3 py-2 rounded-lg bg-white/10 text-white hover:bg-white/20">
            Notīrīt atlasi
          </button>
        </div>
      </div>

      <div id="compareArea" class="mt-4 hidden">
        <h3 class="text-white font-semibold mb-3">Salīdzinājums</h3>
        <div id="compareGrid" class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(220px,1fr))]"></div>
      </div>
    </section>

    <section class="bg-[#1f2937] border border-[#374151] rounded-xl overflow-hidden">
      <div class="overflow-x-auto">
        <table id="standingsTable" class="min-w-[1000px] w-full text-sm">
          <thead class="bg-[#0f172a] text-gray-300 sticky top-0 z-10">
            <tr>
              <th class="px-3 py-2 w-10"></th>
              <th data-sort="season" class="px-3 py-2 text-left cursor-pointer select-none hover:text-white">Sezona</th>
              <th data-sort="team_name" class="px-3 py-2 text-left cursor-pointer select-none hover:text-white">Komanda</th>
              <th data-sort="abbreviation" class="px-3 py-2 text-center cursor-pointer select-none hover:text-white">Saīsn.</th>
              <th data-sort="wins" class="px-3 py-2 text-right cursor-pointer select-none hover:text-white">U</th>
              <th data-sort="losses" class="px-3 py-2 text-right cursor-pointer select-none hover:text-white">Z</th>
              <th data-sort="win_percent" class="px-3 py-2 text-right cursor-pointer select-none hover:text-white">Uzv.%</th>
              <th data-sort="playoff_seed" class="px-3 py-2 text-right cursor-pointer select-none hover:text-white">Sēkla</th>
              <th data-sort="games_behind" class="px-3 py-2 text-right cursor-pointer select-none hover:text-white">GB</th>
              <th data-sort="avg_points_for" class="px-3 py-2 text-right cursor-pointer select-none hover:text-white">PPG</th>
              <th data-sort="avg_points_against" class="px-3 py-2 text-right cursor-pointer select-none hover:text-white">OPP PPG</th>
              <th data-sort="point_differential" class="px-3 py-2 text-right cursor-pointer select-none hover:text-white">Starpl.</th>
              <th data-sort="home_record" class="px-3 py-2 text-center cursor-pointer select-none hover:text-white">Mājās</th>
              <th data-sort="road_record" class="px-3 py-2 text-center cursor-pointer select-none hover:text-white">Izbraukumā</th>
              <th data-sort="last_ten" class="px-3 py-2 text-center cursor-pointer select-none hover:text-white">Pēd.10</th>
              <th data-sort="streak" class="px-3 py-2 text-center cursor-pointer select-none hover:text-white">Sērija</th>
              <th data-sort="clincher" class="px-3 py-2 text-center cursor-pointer select-none hover:text-white">Apzīm.</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#374151] text-[#F3F4F6]">
            @foreach($rows as $r)
              <tr class="odd:bg-[#1f2937] even:bg-[#111827] hover:bg-[#374151]/60 transition"
                  data-season="{{ $r['season'] }}"
                  data-team="{{ $r['data_team'] }}">
                <td class="px-3 py-2 align-middle">
                  <input type="checkbox" class="rowSel accent-[#84CC16]"
                         data-payload='{{ $r['payload'] }}'>
                </td>
                <td class="px-3 py-2">{{ $r['season'] }}</td>

                <td class="px-3 py-2">
                  <a class="flex items-center gap-2 hover:text-[#84CC16]" href="{{ route('nba.team.show', $r['team_id']) }}">
                    @if(!empty($r['team_logo']))
                      <img src="{{ $r['team_logo'] }}" alt="{{ $r['team_name'] }} logo"
                           class="h-5 w-5 sm:h-6 sm:w-6 object-contain rounded bg-white p-[2px]" />
                    @else
                      <span class="inline-flex items-center justify-center h-5 w-5 sm:h-6 sm:w-6 rounded bg-white/10 text-[10px]">
                        {{ $r['abbreviation'] ?? '—' }}
                      </span>
                    @endif
                    <span class="truncate max-w-[180px] sm:max-w-[240px]">{{ $r['team_name'] }}</span>
                  </a>
                </td>

                <td class="px-3 py-2 text-center">{{ $r['abbreviation'] }}</td>
                <td class="px-3 py-2 text-right">{{ $r['wins'] }}</td>
                <td class="px-3 py-2 text-right">{{ $r['losses'] }}</td>
                <td class="px-3 py-2 text-right">{{ $r['win_percent_fmt'] }}</td>
                <td class="px-3 py-2 text-right">{{ $r['playoff_seed'] ?? '—' }}</td>
                <td class="px-3 py-2 text-right">{{ $r['games_behind'] ?? '—' }}</td>
                <td class="px-3 py-2 text-right">{{ $r['ppg_fmt'] }}</td>
                <td class="px-3 py-2 text-right">{{ $r['opp_ppg_fmt'] }}</td>
                <td class="px-3 py-2 text-right">
                  <span class="{{ $r['diff_class'] }}">{{ $r['diff_txt'] }}</span>
                </td>
                <td class="px-3 py-2 text-center">{{ $r['home_record'] ?? '—' }}</td>
                <td class="px-3 py-2 text-center">{{ $r['road_record'] ?? '—' }}</td>
                <td class="px-3 py-2 text-center">{{ $r['last_ten'] ?? '—' }}</td>
                <td class="px-3 py-2 text-center">{{ $r['streak_txt'] }}</td>

                <td class="px-3 py-2 text-center">
                  @if(empty($r['clincher_badges']))
                    —
                  @else
                    <div class="flex flex-wrap gap-1 justify-center">
                      @foreach($r['clincher_badges'] as $b)
                        <span class="px-1.5 py-0.5 text-[11px] rounded-full border {{ $b['cls'] }}"
                              title="{{ $b['label'] }}">
                          {{ strtoupper($b['code']) }}
                        </span>
                      @endforeach
                    </div>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <p class="px-4 pb-4 pt-2 text-xs text-gray-400 sm:hidden">Padoms: velciet uz sāniem, lai redzētu visas kolonnas.</p>
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

  @php
  // If the controller doesn't provide $clincherLegend, use default values.
  $clincherLegend = $clincherLegend ?? [
    ['*',  'Labākais rekords līgā',            'bg-amber-500/20 text-amber-300 border-amber-500/30'],
    ['Z',  'Labākais rekords konforencē',        'bg-purple-500/20 text-purple-300 border-purple-500/30'],
    ['Y',  'Iegūts divīzijas tituls',          'bg-teal-500/20 text-teal-300 border-teal-500/30'],
    ['X',  'Nodrošināta vieta play-off',           'bg-green-500/20 text-green-300 border-green-500/30'],
    ['PB', 'Nodrošināts Play-In',                 'bg-blue-500/20 text-blue-300 border-blue-500/30'],
    ['PI', 'Tiesīga piedalīties Play-In',                 'bg-blue-500/20 text-blue-300 border-blue-500/30'],
    ['E',  'Izslēgta no play-off','bg-red-500/20 text-red-300 border-red-500/30'],
  ];
@endphp


  <div class="mt-6">
    <h3 class="text-lg font-semibold mb-2">Apzīmējumu kodi</h3>
    <div class="grid gap-3 sm:gap-4 [grid-template-columns:repeat(auto-fit,minmax(220px,1fr))]">
      @foreach($clincherLegend as [$code, $label, $cls])
        <div class="bg-[#1f2937] border border-[#374151] rounded-xl p-3 flex items-center gap-3">
          <span class="px-2 py-0.5 text-xs rounded-full border {{ $cls }}">{{ $code }}</span>
          <div class="text-sm text-gray-200">{{ $label }}</div>
        </div>
      @endforeach
    </div>
  </div>
</section>


  </main>

  <script>
    const q = document.getElementById('q');
    const tableRows = Array.from(document.querySelectorAll('#standingsTable tbody tr'));

    function applyFilters() {
      const term = (q?.value || '').trim().toLowerCase();
      tableRows.forEach(r => {
        const hay = (r.dataset.team + ' ' + r.dataset.season).toLowerCase();
        r.style.display = hay.includes(term) ? '' : 'none';
      });
    }

    (function seedFromQuery() {
      try {
        const params = new URLSearchParams(window.location.search);
        const seed = params.get('team');
        if (seed && q) {
          q.value = seed;
          applyFilters();
        }
      } catch {}
    })();

    q?.addEventListener('input', applyFilters);

    const headers = document.querySelectorAll('#standingsTable thead th[data-sort]');
    headers.forEach(h => {
      h.addEventListener('click', () => {
        const idx   = Array.from(h.parentElement.children).indexOf(h);
        const tbody = document.querySelector('#standingsTable tbody');
        const asc   = !(h.dataset.asc === 'true');
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
          const A  = a.children[idx].innerText.trim();
          const B  = b.children[idx].innerText.trim();
          const An = num(A), Bn = num(B);
          const both = isFinite(An) && isFinite(Bn);
          if (both) return asc ? (An - Bn) : (Bn - An);
          return asc ? A.localeCompare(B) : B.localeCompare(A);
        });

        tbody.append(...visible);
      });
    });

    const selBoxes   = document.querySelectorAll('.rowSel');
    const compareBtn = document.getElementById('compareBtn');
    theClearBtn = document.getElementById('clearSelBtn');
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

    theClearBtn.addEventListener('click', () => {
      selBoxes.forEach(x => x.checked = false);
      compareBtn.disabled = true;
      compareGrid.innerHTML = '';
      compareArea.classList.add('hidden');
    });

    const numVal = (v) => (v===null||v===undefined||v==='—') ? NaN : Number(v);

    function vsLeader(sel, field, higherIsBetter = true) {
      const values = sel.map(p => numVal(p[field]));
      const valid  = values.filter(v => isFinite(v));
      if (!valid.length) return sel.map(_ => ({ label: '—', cls: 'text-gray-300' }));

      const leader = higherIsBetter ? Math.max(...valid) : Math.min(...valid);

      return values.map(v => {
        if (!isFinite(v)) return { label: '—', cls: 'text-gray-300' };
        let behindPct;
        if (leader === 0) behindPct = 0;
        else if (higherIsBetter) behindPct = ((leader - v) / Math.abs(leader)) * 100;
        else behindPct = ((v - leader) / Math.abs(leader)) * 100;

        if (Math.abs(behindPct) < 0.5) return { label: 'Līderis', cls: 'text-[#84CC16]' };
        return { label: `-${Math.round(behindPct)}% pret līderi`, cls: 'text-[#F97316]' };
      });
    }

    const lineLeader = (c) => `<div class="text-xs mt-0.5 ${c.cls}">${c.label}</div>`;

    compareBtn.addEventListener('click', () => {
      const sel = selectedPayloads();
      if (!sel.length) { compareArea.classList.add('hidden'); return; }

      const cmpWin  = vsLeader(sel, 'win_percent', true);
      const cmpPPG  = vsLeader(sel, 'ppg',         true);
      const cmpOPP  = vsLeader(sel, 'opp_ppg',     false);
      const cmpDiff = vsLeader(sel, 'diff',        true);

      compareGrid.innerHTML = sel.map((p, idx) => {
        const winPct   = (p.win_percent ?? null) !== null ? `${Math.round(Number(p.win_percent) * 100)}%` : '—';
        const diffTxt  = (p.diff ?? null) === null ? '—' : (p.diff >= 0 ? ('+'+p.diff) : p.diff);
        const streakTxt= (p.streak ?? null) === null ? '—' : (p.streak > 0 ? 'U'+p.streak : (p.streak < 0 ? 'Z'+Math.abs(p.streak) : '—'));
        const logoImg  = p.logo
          ? `<img src="${p.logo}" alt="${p.team} logo" class="h-6 w-6 object-contain rounded bg-white p-[2px]" />`
          : `<span class="inline-flex items-center justify-center h-6 w-6 rounded bg-white/10 text-[10px]">${p.abbr ?? '—'}</span>`;

        return `
          <article class="bg-[#0f172a]/60 border border-[#374151] rounded-xl p-4">
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center gap-2">
                ${logoImg}
                <div class="text-white font-semibold">${p.team} (${p.abbr ?? '—'})</div>
              </div>
              <div class="text-xs text-[#F3F4F6]/70">${p.season}</div>
            </div>

            <div class="grid grid-cols-3 gap-3 text-sm">
              <div>
                <div class="text-[#F3F4F6]/60 text-xs">U/Z</div>
                <div class="font-semibold">${p.wins ?? '—'}–${p.losses ?? '—'}</div>
                ${lineLeader(cmpWin[idx])}
              </div>
              <div>
                <div class="text-[#F3F4F6]/60 text-xs">Uzv.%</div>
                <div class="font-semibold">${winPct}</div>
                ${lineLeader(cmpWin[idx])}
              </div>
              <div>
                <div class="text-[#F3F4F6]/60 text-xs">Sēkla</div>
                <div class="font-semibold">${p.seed ?? '—'}</div>
              </div>
              <div>
                <div class="text-[#F3F4F6]/60 text-xs">PPG</div>
                <div class="font-semibold">${p.ppg ?? '—'}</div>
                ${lineLeader(cmpPPG[idx])}
              </div>
              <div>
                <div class="text-[#F3F4F6]/60 text-xs">Pretinieku PPG</div>
                <div class="font-semibold">${p.opp_ppg ?? '—'}</div>
                ${lineLeader(cmpOPP[idx])}
              </div>
              <div>
                <div class="text-[#F3F4F6]/60 text-xs">Starpl.</div>
                <div class="font-semibold ${p.diff==null?'':(p.diff>=0?'text-[#84CC16]':'text-[#F97316]')}">${diffTxt}</div>
                ${lineLeader(cmpDiff[idx])}
              </div>
            </div>

            <div class="mt-3 flex flex-wrap gap-2 text-xs">
              <span class="px-2.5 py-1 rounded-full bg-white/5 border border-white/10">Mājās: ${p.home ?? '—'}</span>
              <span class="px-2.5 py-1 rounded-full bg-white/5 border border-white/10">Izbraukumā: ${p.road ?? '—'}</span>
              <span class="px-2.5 py-1 rounded-full bg-white/5 border border-white/10">Pēd.10: ${p.l10 ?? '—'}</span>
              <span class="px-2.5 py-1 rounded-full bg-white/5 border border-white/10">Sērija: ${streakTxt}</span>
              ${p.clincher_human ? `<span class="px-2.5 py-1 rounded-full bg-white/5 border border-white/10">Apzīm.: ${p.clincher_human}</span>` : ''}
            </div>
          </article>
        `;
      }).join('');

      compareArea.classList.toggle('hidden', sel.length === 0);
    });
  </script>
@endsection
