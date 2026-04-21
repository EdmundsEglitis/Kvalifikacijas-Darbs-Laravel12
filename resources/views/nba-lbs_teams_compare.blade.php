@extends('layouts.app')
@section('title','NBA vs LBS — Salīdzināt komandas')

@section('content')
<main class="max-w-7xl mx-auto px-4 py-6 space-y-6">
  <br>

  <form method="GET" class="mb-2 grid gap-3 sm:grid-cols-5">
    <select name="from" class="bg-[#0f172a] border border-[#374151] rounded px-3 py-2">
      @foreach($seasons as $s)
        <option value="{{ $s }}" @selected((int)$from === (int)$s)>{{ $s }}</option>
      @endforeach
    </select>

    <select name="to" class="bg-[#0f172a] border border-[#374151] rounded px-3 py-2">
      @foreach($seasons as $s)
        <option value="{{ $s }}" @selected((int)$to === (int)$s)>{{ $s }}</option>
      @endforeach
    </select>

    <select name="nba_per" class="bg-[#0f172a] border border-[#374151] rounded px-3 py-2">
      @foreach([10,25,50,100,200] as $n)
        <option value="{{ $n }}" @selected((int)request('nba_per',25)===$n)>NBA: {{ $n }}/p</option>
      @endforeach
    </select>

    <select name="lbs_per" class="bg-[#0f172a] border border-[#374151] rounded px-3 py-2">
      @foreach([10,25,50,100,200] as $n)
        <option value="{{ $n }}" @selected((int)request('lbs_per',25)===$n)>LBS: {{ $n }}/p</option>
      @endforeach
    </select>

    <input
      name="q"
      value="{{ $q }}"
      placeholder="Meklēt komandu"
      class="bg-[#0f172a] border border-[#374151] rounded px-3 py-2 sm:col-span-1 sm:col-start-5"
    />

    <div class="sm:col-span-5">
      <button class="mt-1 px-4 py-2 bg-[#84CC16] text-[#111827] rounded font-semibold hover:bg-[#a3e635]">
        Meklēt
      </button>
    </div>
  </form>

  <section class="bg-[#111827] border border-[#1f2937] rounded-2xl p-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="text-sm text-gray-300">
        Atzīmē katrā tabulā līdz 5 komandām. Pēc tam — “Salīdzināt izvēlētos”.
      </div>

      <div class="flex gap-2">
        <button
          id="compareBtn"
          type="button"
          class="px-3 py-2 rounded-lg bg-white/10 text-white hover:bg-white/20 disabled:opacity-40"
          disabled
        >
          Salīdzināt izvēlētos
        </button>

        <button
          id="clearSelBtn"
          type="button"
          class="px-3 py-2 rounded-lg bg-white/10 text-white hover:bg-white/20"
        >
          Notīrīt
        </button>
      </div>
    </div>

    <div id="compareArea" class="mt-4 hidden">
      <h3 class="text-white font-semibold mb-3">Salīdzinājums</h3>
      <div id="compareGrid" class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(240px,1fr))]"></div>
    </div>
  </section>

  <div class="grid gap-6 lg:grid-cols-2">
    <section id="nbaPanel" class="panel bg-[#111827] border border-[#1f2937] rounded-2xl overflow-hidden" data-panel="nba">
      <div class="flex items-center justify-between px-4 py-3 bg-[#0f172a] border-b border-[#1f2937]">
        <h2 class="font-semibold select-none">NBA komandas</h2>
        <button
          type="button"
          class="panel-expand px-3 py-1.5 rounded bg-white/10 hover:bg-white/20"
          data-target="#nbaPanel"
        >
          Palielināt
        </button>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-[950px] w-full text-sm clickable-body" data-target="#nbaPanel">
          <thead class="bg-[#0f172a] text-gray-300">
            <tr>
              <th class="px-3 py-2 w-8"></th>
              <th class="px-3 py-2 text-left">Sezona</th>
              <th class="px-3 py-2 text-left">Komanda</th>
              <th class="px-3 py-2 text-right">G</th>
              <th class="px-3 py-2 text-right">W</th>
              <th class="px-3 py-2 text-right">L</th>
              <th class="px-3 py-2 text-right">Uzvaras%</th>
              <th class="px-3 py-2 text-right">PPG</th>
              <th class="px-3 py-2 text-right">Pretinieku PPG</th>
              <th class="px-3 py-2 text-right">Starpība</th>
            </tr>
          </thead>

          <tbody id="nbaRows" class="divide-y divide-[#1f2937] text-[#F3F4F6]">
            @foreach($nba as $r)
              @php
                $rowKey = $r->_key ?? "NBA:T:{$r->team_id}:{$r->season}";
                $logoUrl = null;

                if (!empty($r->team_logo)) {
                  $logoUrl = preg_match('/^https?:\/\//i', $r->team_logo)
                    ? $r->team_logo
                    : asset('storage/' . ltrim($r->team_logo, '/'));
                }

                $payload = [
                  'src'         => 'NBA',
                  'key'         => $rowKey,
                  'team_id'     => $r->team_id,
                  'season'      => $r->season,
                  'team'        => $r->team_name,
                  'logo'        => $logoUrl,
                  'games'       => $r->games,
                  'wins'        => $r->wins,
                  'losses'      => $r->losses,
                  'win_percent' => $r->win_percent,
                  'ppg'         => $r->ppg,
                  'opp_ppg'     => $r->opp_ppg,
                  'diff'        => $r->diff,
                ];
              @endphp

              <tr class="odd:bg-[#111827] even:bg-[#0b1220] hover:bg-[#1f2937]">
                <td class="select-cell not-expand p-0 w-12">
                  <label class="select-hit flex items-center justify-center w-full h-full px-3 py-2 cursor-pointer">
                    <input
                      type="checkbox"
                      class="pick-nba accent-[#84CC16]"
                      data-payload='@json($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)'
                    >
                  </label>
                </td>

                <td class="px-3 py-2">{{ $r->season }}</td>

                <td class="px-3 py-2">
                  <div class="flex items-center gap-2">
                    @if($logoUrl)
                      <img src="{{ $logoUrl }}" class="h-5 w-5 object-contain rounded bg-white p-[2px]" alt="">
                    @endif
                    {{ $r->team_name }}
                  </div>
                </td>

                <td class="px-3 py-2 text-right">{{ $r->games }}</td>
                <td class="px-3 py-2 text-right">{{ $r->wins }}</td>
                <td class="px-3 py-2 text-right">{{ $r->losses }}</td>
                <td class="px-3 py-2 text-right">{{ $r->win_percent_fmt }}</td>
                <td class="px-3 py-2 text-right">{{ $r->ppg_fmt }}</td>
                <td class="px-3 py-2 text-right">{{ $r->opp_ppg_fmt }}</td>
                <td class="px-3 py-2 text-right">
                  <span class="{{ $r->diff_class }}">{{ $r->diff_txt }}</span>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="p-4 flex flex-wrap items-center gap-2 justify-between text-sm">
        <div id="nbaPageInfo" class="text-gray-400">
          Lapa {{ $nbaMeta['page'] }} no {{ $nbaMeta['last'] }} • {{ $nbaMeta['total'] }} ieraksti
        </div>

        <div class="flex gap-2">
          <button
            type="button"
            id="nbaPrevBtn"
            data-panel="nba"
            data-page-action="prev"
            class="px-3 py-1 rounded bg-white/10 hover:bg-white/20 {{ $nbaMeta['page']<=1 ? 'pointer-events-none opacity-40' : '' }}"
            @disabled($nbaMeta['page']<=1)
          >
            ‹
          </button>

          <button
            type="button"
            id="nbaNextBtn"
            data-panel="nba"
            data-page-action="next"
            class="px-3 py-1 rounded bg-white/10 hover:bg-white/20 {{ $nbaMeta['page']>=$nbaMeta['last'] ? 'pointer-events-none opacity-40' : '' }}"
            @disabled($nbaMeta['page']>=$nbaMeta['last'])
          >
            ›
          </button>
        </div>
      </div>
    </section>

    <section id="lbsPanel" class="panel bg-[#111827] border border-[#1f2937] rounded-2xl overflow-hidden" data-panel="lbs">
      <div class="flex items-center justify-between px-4 py-3 bg-[#0f172a] border-b border-[#1f2937]">
        <h2 class="font-semibold select-none">LBS komandas</h2>
        <button
          type="button"
          class="panel-expand px-3 py-1.5 rounded bg-white/10 hover:bg-white/20"
          data-target="#lbsPanel"
        >
          Palielināt
        </button>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-[950px] w-full text-sm clickable-body" data-target="#lbsPanel">
          <thead class="bg-[#0f172a] text-gray-300">
            <tr>
              <th class="px-3 py-2 w-8"></th>
              <th class="px-3 py-2 text-left">Sezona</th>
              <th class="px-3 py-2 text-left">Komanda</th>
              <th class="px-3 py-2 text-right">G</th>
              <th class="px-3 py-2 text-right">W</th>
              <th class="px-3 py-2 text-right">L</th>
              <th class="px-3 py-2 text-right">Uzvaras%</th>
              <th class="px-3 py-2 text-right">PPG</th>
              <th class="px-3 py-2 text-right">Pretinieku PPG</th>
              <th class="px-3 py-2 text-right">Starpība</th>
            </tr>
          </thead>

          <tbody id="lbsRows" class="divide-y divide-[#1f2937] text-[#F3F4F6]">
            @foreach($lbs as $r)
              @php
                $rowKey = $r->_key ?? "LBS:T:{$r->team_id}:{$r->season}";
                $logoUrl = null;

                if (!empty($r->team_logo)) {
                  $logoUrl = preg_match('/^https?:\/\//i', $r->team_logo)
                    ? $r->team_logo
                    : asset('storage/' . ltrim($r->team_logo, '/'));
                }

                $payload = [
                  'src'         => 'LBS',
                  'key'         => $rowKey,
                  'team_id'     => $r->team_id,
                  'season'      => $r->season,
                  'team'        => $r->team_name,
                  'logo'        => $logoUrl,
                  'games'       => $r->games,
                  'wins'        => $r->wins,
                  'losses'      => $r->losses,
                  'win_percent' => $r->win_percent,
                  'ppg'         => $r->ppg,
                  'opp_ppg'     => $r->opp_ppg,
                  'diff'        => $r->diff,
                ];
              @endphp

              <tr class="odd:bg-[#111827] even:bg-[#0b1220] hover:bg-[#1f2937]">
                  <td class="select-cell not-expand p-0 w-12">
                    <label class="select-hit flex items-center justify-center w-full h-full px-3 py-2 cursor-pointer">
                      <input
                        type="checkbox"
                        class="pick-lbs accent-[#84CC16]"
                        data-payload='@json($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)'
                      >
                    </label>
                  </td>

                <td class="px-3 py-2">{{ $r->season }}</td>

                <td class="px-3 py-2">
                  <div class="flex items-center gap-2">
                    @if($logoUrl)
                      <img src="{{ $logoUrl }}" class="h-5 w-5 object-contain rounded bg-white p-[2px]" alt="">
                    @endif
                    {{ $r->team_name }}
                  </div>
                </td>

                <td class="px-3 py-2 text-right">{{ $r->games }}</td>
                <td class="px-3 py-2 text-right">{{ $r->wins }}</td>
                <td class="px-3 py-2 text-right">{{ $r->losses }}</td>
                <td class="px-3 py-2 text-right">{{ $r->win_percent_fmt }}</td>
                <td class="px-3 py-2 text-right">{{ $r->ppg_fmt }}</td>
                <td class="px-3 py-2 text-right">{{ $r->opp_ppg_fmt }}</td>
                <td class="px-3 py-2 text-right">
                  <span class="{{ $r->diff_class }}">{{ $r->diff_txt }}</span>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="p-4 flex flex-wrap items-center gap-2 justify-between text-sm">
        <div id="lbsPageInfo" class="text-gray-400">
          Lapa {{ $lbsMeta['page'] }} no {{ $lbsMeta['last'] }} • {{ $lbsMeta['total'] }} ieraksti
        </div>

        <div class="flex gap-2">
          <button
            type="button"
            id="lbsPrevBtn"
            data-panel="lbs"
            data-page-action="prev"
            class="px-3 py-1 rounded bg-white/10 hover:bg-white/20 {{ $lbsMeta['page']<=1 ? 'pointer-events-none opacity-40' : '' }}"
            @disabled($lbsMeta['page']<=1)
          >
            ‹
          </button>

          <button
            type="button"
            id="lbsNextBtn"
            data-panel="lbs"
            data-page-action="next"
            class="px-3 py-1 rounded bg-white/10 hover:bg-white/20 {{ $lbsMeta['page']>=$lbsMeta['last'] ? 'pointer-events-none opacity-40' : '' }}"
            @disabled($lbsMeta['page']>=$lbsMeta['last'])
          >
            ›
          </button>
        </div>
      </div>
    </section>
  </div>
</main>

<style>
  #panelOverlay {
  display: none;
  position: fixed;
  inset: 0;
  z-index: 999;
}

#panelOverlay.active {
  display: block;
}

#panelBackdrop {
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,.55);
  backdrop-filter: blur(3px);
}

#panelDrawer {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) scale(.98);
  width: min(1100px, 92vw);
  height: min(88vh, 900px);
  background: #0f172a;
  border: 1px solid #1f2937;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0,0,0,.45);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  opacity: 0;
  pointer-events: none;
  transition: opacity .18s ease, transform .18s ease;
}

#panelOverlay.active #panelDrawer {
  opacity: 1;
  transform: translate(-50%, -50%) scale(1);
  pointer-events: auto;
}

#panelDrawer .modal-header {
  background: #0b1220;
  border-bottom: 1px solid #1f2937;
  border-top-left-radius: 16px;
  border-top-right-radius: 16px;
  flex: 0 0 auto;
}

#panelHost {
  flex: 1 1 auto;
  min-height: 0;
  overflow: hidden;
}

/* base row feel */
.clickable-body tbody tr {
  cursor: pointer;
}

/* expanded panel layout */
.panel.is-in-overlay {
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 0;
  border: 0;
  border-radius: 0;
  overflow: hidden;
  background: transparent;
}

/* title/header inside moved panel */
.panel.is-in-overlay > .flex.items-center.justify-between {
  flex: 0 0 auto;
}

/* THIS is the only scroll area */
.panel.is-in-overlay .overflow-x-auto {
  flex: 1 1 auto;
  min-height: 0;
  overflow: auto;
  overscroll-behavior: contain;
}

/* footer/pagination stays fixed at bottom */
.panel.is-in-overlay > .p-4.flex {
  flex: 0 0 auto;
  border-top: 1px solid #1f2937;
  background: #111827;
}

/* sticky table head looks much better in popup */
.panel.is-in-overlay thead th {
  position: sticky;
  top: 0;
  z-index: 3;
  background: #0f172a;
}

/* nicer scrollbar */
.panel.is-in-overlay .overflow-x-auto::-webkit-scrollbar {
  width: 10px;
  height: 10px;
}

.panel.is-in-overlay .overflow-x-auto::-webkit-scrollbar-track {
  background: #0b1220;
}

.panel.is-in-overlay .overflow-x-auto::-webkit-scrollbar-thumb {
  background: #334155;
  border-radius: 999px;
  border: 2px solid #0b1220;
}

.panel.is-in-overlay .overflow-x-auto::-webkit-scrollbar-thumb:hover {
  background: #475569;
}

@media (max-width: 768px) {
  #panelDrawer {
    width: 95vw;
    height: 92vh;
    border-radius: 14px;
  }
}

.select-cell {
  width: 48px;
}

.select-hit {
  user-select: none;
}

.select-hit:hover {
  background: rgba(255,255,255,.04);
}

.select-hit input[type="checkbox"] {
  width: 16px;
  height: 16px;
  cursor: pointer;
}
/* all table scroll areas */
.overflow-x-auto {
  scrollbar-width: thin;                 /* Firefox */
  scrollbar-color: #64748b #0b1220;     /* thumb track */
}

/* Chrome / Edge / Safari */
.overflow-x-auto::-webkit-scrollbar {
  height: 10px;
  width: 10px;
}

.overflow-x-auto::-webkit-scrollbar-track {
  background: #0b1220;
  border-radius: 999px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
  background: #64748b;
  border-radius: 999px;
  border: 2px solid #0b1220;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

.overflow-x-auto::-webkit-scrollbar-corner {
  background: #0b1220;
}
</style>

<div id="panelOverlay">
  <div id="panelBackdrop"></div>
  <div id="panelDrawer">
    <div class="flex items-center justify-between px-4 py-3 modal-header">
      <h3 class="font-semibold">Pārlūks</h3>
      <button id="panelClose" type="button" class="px-3 py-1.5 rounded bg-white/10 hover:bg-white/20">✕</button>
    </div>
    <div id="panelHost" class="flex-1 overflow-auto"></div>
  </div>
</div>

@push('scripts')
<script>
(function () {
  const overlay = document.getElementById('panelOverlay');
  const host = document.getElementById('panelHost');
  const closeBtn = document.getElementById('panelClose');
  const backdrop = document.getElementById('panelBackdrop');

  let activePanel = null;
  let placeholder = null;
  let lastFocused = null;
  let panelScrollTop = 0;

  function isInteractive(target) {
    return target.closest('.not-expand, a, button, input, select, label, textarea');
  }

  function setExpandedButtonState(panel, expanded) {
    const btn = panel?.querySelector('.panel-expand');
    if (!btn) return;

    btn.textContent = expanded ? 'Samazināt' : 'Palielināt';
    btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
  }

  function openPanel(panel) {
    if (!panel || activePanel === panel) return;
    if (activePanel) closePanel();

    lastFocused = document.activeElement;

    const scrollWrap = panel.querySelector('.overflow-x-auto');
    panelScrollTop = scrollWrap ? scrollWrap.scrollTop : 0;

    placeholder = document.createElement('div');
    placeholder.className = 'panel-placeholder';
    placeholder.style.display = 'none';

    panel.parentNode.insertBefore(placeholder, panel);

    activePanel = panel;
    host.innerHTML = '';
    host.appendChild(panel);

    panel.classList.add('is-in-overlay');
    setExpandedButtonState(panel, true);

    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';

    closeBtn.focus();
  }

  function closePanel() {
    if (!activePanel || !placeholder) return;

    placeholder.parentNode.insertBefore(activePanel, placeholder);
    placeholder.remove();

    const scrollWrap = activePanel.querySelector('.overflow-x-auto');
    if (scrollWrap) scrollWrap.scrollTop = panelScrollTop;

    activePanel.classList.remove('is-in-overlay');
    setExpandedButtonState(activePanel, false);

    overlay.classList.remove('active');
    document.body.style.overflow = '';

    const prevFocused = lastFocused;
    activePanel = null;
    placeholder = null;
    lastFocused = null;

    if (prevFocused && typeof prevFocused.focus === 'function') {
      prevFocused.focus();
    }
  }

  document.addEventListener('click', (e) => {
    const expandBtn = e.target.closest('.panel-expand');
    if (expandBtn) {
      e.preventDefault();
      e.stopPropagation();

      const panel = document.querySelector(expandBtn.dataset.target);
      if (!panel) return;

      if (activePanel === panel) closePanel();
      else openPanel(panel);

      return;
    }

    const row = e.target.closest('.clickable-body tbody tr');
    if (row) {
      if (isInteractive(e.target)) return;

      const table = row.closest('.clickable-body');
      const panel = document.querySelector(table.dataset.target);
      if (!panel) return;

      if (activePanel !== panel) {
        openPanel(panel);
      }
    }
  });

  closeBtn.addEventListener('click', closePanel);
  backdrop.addEventListener('click', closePanel);
  window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closePanel();
  });
})();
</script>

<script>
(function () {
  const STORAGE_KEY = 'compare_teams_nba_lbs_v2';
  const compareBtn  = document.getElementById('compareBtn');
  const clearBtn    = document.getElementById('clearSelBtn');
  const compareArea = document.getElementById('compareArea');
  const compareGrid = document.getElementById('compareGrid');

  const selNba = new Map();
  const selLbs = new Map();

  function parsePayload(el) {
    try { return JSON.parse(el.dataset.payload); } catch (_) { return null; }
  }

  function keyOf(p) {
    return p?.key ?? `${p?.src}:${p?.team_id}:${p?.season}`;
  }

  function load() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return;

      const obj = JSON.parse(raw);
      for (const [k, v] of Object.entries(obj.nba || {})) selNba.set(k, v);
      for (const [k, v] of Object.entries(obj.lbs || {})) selLbs.set(k, v);
    } catch (_) {}
  }

  function save() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify({
      nba: Object.fromEntries(selNba),
      lbs: Object.fromEntries(selLbs)
    }));
  }

  function syncButton() {
    const total = selNba.size + selLbs.size;
    compareBtn.disabled = total === 0;
  }

  function hydrateCheckboxes() {
    document.querySelectorAll('.pick-nba').forEach(cb => {
      const p = parsePayload(cb);
      if (!p) return;
      cb.checked = selNba.has(keyOf(p));
    });

    document.querySelectorAll('.pick-lbs').forEach(cb => {
      const p = parsePayload(cb);
      if (!p) return;
      cb.checked = selLbs.has(keyOf(p));
    });

    syncButton();
  }

  function upsert(map, max, payload, cb) {
    const key = keyOf(payload);

    if (cb.checked) {
      if (!map.has(key) && map.size >= max) {
        cb.checked = false;
        return;
      }
      map.set(key, payload);
    } else {
      map.delete(key);
    }

    save();
    syncButton();
  }

  const one = v => v == null ? '—' : Number(v).toFixed(1);
  const pct = v => v == null ? '—' : (Number(v) * 100).toFixed(1) + '%';
  const signed = v => v == null ? '—' : ((Number(v) >= 0 ? '+' : '') + Number(v).toFixed(1));

  function lead(arr, field, high = true) {
    const vals = arr.map(x => Number(x[field] ?? NaN)).filter(v => Number.isFinite(v));
    if (!vals.length) return null;
    return high ? Math.max(...vals) : Math.min(...vals);
  }

  function mkLeaderLabel(value, leader, high = true) {
    if (value == null || !Number.isFinite(Number(value)) || leader == null) {
      return '<div class="text-xs mt-0.5 text-gray-300">—</div>';
    }

    const v = Number(value);
    const base = Math.abs(leader || 1);
    const diffPct = high
      ? ((leader - v) / base) * 100
      : ((v - leader) / base) * 100;

    if (Math.abs(diffPct) < 0.5) {
      return '<div class="text-xs mt-0.5 text-[#84CC16]">Līderis</div>';
    }

    return `<div class="text-xs mt-0.5 text-[#F97316]">-${Math.round(diffPct)}% sal. ar līderi</div>`;
  }

  function renderCompare() {
    const selected = [...selNba.values(), ...selLbs.values()];
    if (!selected.length) {
      compareGrid.innerHTML = '';
      compareArea.classList.add('hidden');
      return;
    }

    const lWin  = lead(selected, 'win_percent', true);
    const lPpg  = lead(selected, 'ppg', true);
    const lOpp  = lead(selected, 'opp_ppg', false);
    const lDiff = lead(selected, 'diff', true);

    compareGrid.innerHTML = selected.map(item => {
      const logo = item.logo
        ? `<img src="${item.logo}" class="h-7 w-7 object-contain rounded bg-white p-[2px]" alt="">`
        : '';

      return `
        <article class="bg-[#0f172a]/60 border border-[#374151] rounded-xl p-4">
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
              ${logo}
              <div>
                <div class="text-white font-semibold">${item.team ?? '—'}</div>
                <div class="text-xs text-gray-400">${item.src ?? '—'}</div>
              </div>
            </div>
            <div class="text-xs text-gray-300">Sezona: ${item.season ?? '—'}</div>
          </div>

          <div class="grid grid-cols-4 gap-3 text-sm">
            <div>
              <div class="text-[#F3F4F6]/60 text-xs">Win%</div>
              <div class="font-semibold">${pct(item.win_percent)}</div>
              ${mkLeaderLabel(item.win_percent, lWin, true)}
            </div>

            <div>
              <div class="text-[#F3F4F6]/60 text-xs">PPG</div>
              <div class="font-semibold">${one(item.ppg)}</div>
              ${mkLeaderLabel(item.ppg, lPpg, true)}
            </div>

            <div>
              <div class="text-[#F3F4F6]/60 text-xs">OPP PPG</div>
              <div class="font-semibold">${one(item.opp_ppg)}</div>
              ${mkLeaderLabel(item.opp_ppg, lOpp, false)}
            </div>

            <div>
              <div class="text-[#F3F4F6]/60 text-xs">Diff</div>
              <div class="font-semibold">${signed(item.diff)}</div>
              ${mkLeaderLabel(item.diff, lDiff, true)}
            </div>
          </div>

          <div class="mt-3 text-xs text-gray-300">
            G: ${item.games ?? '—'} • W/L: ${item.wins ?? '—'}–${item.losses ?? '—'}
          </div>
        </article>
      `;
    }).join('');

    compareArea.classList.remove('hidden');
  }

  load();
  hydrateCheckboxes();

  document.addEventListener('change', (e) => {
    const cb = e.target.closest('.pick-nba, .pick-lbs');
    if (!cb) return;

    const payload = parsePayload(cb);
    if (!payload) return;

    if (cb.classList.contains('pick-nba')) {
      upsert(selNba, 5, payload, cb);
    } else {
      upsert(selLbs, 5, payload, cb);
    }

    if (!compareArea.classList.contains('hidden')) {
      renderCompare();
    }
  });

  clearBtn.addEventListener('click', () => {
    document.querySelectorAll('.pick-nba, .pick-lbs').forEach(el => el.checked = false);
    selNba.clear();
    selLbs.clear();
    save();
    syncButton();
    compareGrid.innerHTML = '';
    compareArea.classList.add('hidden');
  });

  compareBtn.addEventListener('click', renderCompare);

  window.__teamsCompareUI = {
    hydrateCheckboxes,
    renderCompare,
  };
})();
</script>

<script>
(function () {
  const datasets = {
    nba: @json($nbaAll->values(), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
    lbs: @json($lbsAll->values(), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
  };

  const states = {
    nba: {
      rowsEl: document.getElementById('nbaRows'),
      infoEl: document.getElementById('nbaPageInfo'),
      prevBtn: document.getElementById('nbaPrevBtn'),
      nextBtn: document.getElementById('nbaNextBtn'),
      page: {{ (int) $nbaMeta['page'] }},
      per: {{ (int) $nbaMeta['per'] }},
    },
    lbs: {
      rowsEl: document.getElementById('lbsRows'),
      infoEl: document.getElementById('lbsPageInfo'),
      prevBtn: document.getElementById('lbsPrevBtn'),
      nextBtn: document.getElementById('lbsNextBtn'),
      page: {{ (int) $lbsMeta['page'] }},
      per: {{ (int) $lbsMeta['per'] }},
    },
  };

  function esc(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function setDisabled(btn, disabled) {
    btn.disabled = !!disabled;
    btn.classList.toggle('pointer-events-none', !!disabled);
    btn.classList.toggle('opacity-40', !!disabled);
  }

  function buildMeta(list, per, page) {
    const total = list.length;
    const last = Math.max(Math.ceil(Math.max(total, 1) / per), 1);
    const safePage = Math.min(Math.max(page, 1), last);
    const start = (safePage - 1) * per;

    return { total, per, page: safePage, last, start };
  }

  function buildPayload(panelName, row) {
    const fallbackKey = `${panelName.toUpperCase()}:T:${row.team_id}:${row.season}`;

    let logoUrl = null;
    if (row.team_logo) {
      logoUrl = /^https?:\/\//i.test(String(row.team_logo))
        ? row.team_logo
        : `{{ asset('storage') }}/${String(row.team_logo).replace(/^\/+/, '')}`;
    }

    return {
      src: panelName.toUpperCase(),
      key: row._key ?? fallbackKey,
      team_id: row.team_id,
      season: row.season,
      team: row.team_name,
      logo: logoUrl,
      games: row.games,
      wins: row.wins,
      losses: row.losses,
      win_percent: row.win_percent,
      ppg: row.ppg,
      opp_ppg: row.opp_ppg,
      diff: row.diff,
    };
  }

  function rowHtml(panelName, row) {
    const payload = buildPayload(panelName, row);
    const payloadStr = esc(JSON.stringify(payload));
    const checkboxClass = panelName === 'nba' ? 'pick-nba' : 'pick-lbs';

    const logoHtml = payload.logo
      ? `<img src="${esc(payload.logo)}" class="h-5 w-5 object-contain rounded bg-white p-[2px]" alt="">`
      : '';

    return `
      <tr class="odd:bg-[#111827] even:bg-[#0b1220] hover:bg-[#1f2937]">
        <td class="select-cell not-expand p-0 w-12">
          <label class="select-hit flex items-center justify-center w-full h-full px-3 py-2 cursor-pointer">
            <input
              type="checkbox"
              class="${checkboxClass} accent-[#84CC16]"
              data-payload="${payloadStr}"
            >
          </label>
        </td>

        <td class="px-3 py-2">${esc(row.season)}</td>

        <td class="px-3 py-2">
          <div class="flex items-center gap-2">
            ${logoHtml}
            ${esc(row.team_name)}
          </div>
        </td>

        <td class="px-3 py-2 text-right">${esc(row.games ?? '—')}</td>
        <td class="px-3 py-2 text-right">${esc(row.wins ?? '—')}</td>
        <td class="px-3 py-2 text-right">${esc(row.losses ?? '—')}</td>
        <td class="px-3 py-2 text-right">${esc(row.win_percent_fmt ?? '—')}</td>
        <td class="px-3 py-2 text-right">${esc(row.ppg_fmt ?? '—')}</td>
        <td class="px-3 py-2 text-right">${esc(row.opp_ppg_fmt ?? '—')}</td>
        <td class="px-3 py-2 text-right">
          <span class="${esc(row.diff_class ?? 'text-gray-300')}">${esc(row.diff_txt ?? '—')}</span>
        </td>
      </tr>
    `;
  }

  function updateUrl(panelName, page, per) {
    const url = new URL(window.location.href);
    url.searchParams.set(`${panelName}_page`, page);
    url.searchParams.set(`${panelName}_per`, per);
    window.history.replaceState({}, '', url.toString());
  }

  function renderPanel(panelName, targetPage = null) {
    const state = states[panelName];
    const list = datasets[panelName] || [];

    const meta = buildMeta(list, state.per, targetPage ?? state.page);
    state.page = meta.page;

    const rows = list.slice(meta.start, meta.start + meta.per);

    state.rowsEl.innerHTML = rows.length
      ? rows.map(row => rowHtml(panelName, row)).join('')
      : `<tr><td colspan="10" class="px-3 py-6 text-center text-gray-400">Nav atrastu ierakstu</td></tr>`;

    state.infoEl.textContent = `Lapa ${meta.page} no ${meta.last} • ${meta.total} ieraksti`;

    setDisabled(state.prevBtn, meta.page <= 1);
    setDisabled(state.nextBtn, meta.page >= meta.last);

    updateUrl(panelName, meta.page, state.per);
    window.__teamsCompareUI?.hydrateCheckboxes?.();
  }

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-page-action]');
    if (!btn) return;

    const panelName = btn.dataset.panel;
    const action = btn.dataset.pageAction;
    const state = states[panelName];
    if (!state) return;

    if (action === 'prev') renderPanel(panelName, state.page - 1);
    if (action === 'next') renderPanel(panelName, state.page + 1);
  });

  const nbaPerSelect = document.querySelector('select[name="nba_per"]');
  const lbsPerSelect = document.querySelector('select[name="lbs_per"]');

  nbaPerSelect?.addEventListener('change', () => {
    states.nba.per = Math.min(Math.max(parseInt(nbaPerSelect.value || '25', 10), 10), 200);
    renderPanel('nba', 1);
  });

  lbsPerSelect?.addEventListener('change', () => {
    states.lbs.per = Math.min(Math.max(parseInt(lbsPerSelect.value || '25', 10), 10), 200);
    renderPanel('lbs', 1);
  });

  renderPanel('nba', states.nba.page);
  renderPanel('lbs', states.lbs.page);
})();
</script>
@endpush
@endsection