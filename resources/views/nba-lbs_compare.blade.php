@extends('layouts.app')
@section('title','NBA vs LBS — Salīdzināt spēlētājus')

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
      placeholder="Meklēt (vārds vai komanda)"
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
        Atzīmē katrā tabulā līdz 5 spēlētājiem. Pēc tam — “Salīdzināt izvēlētos”.
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
        <h2 class="font-semibold select-none">NBA spēlētāji</h2>
        <button
          type="button"
          class="panel-expand px-3 py-1.5 rounded bg-white/10 hover:bg-white/20"
          data-target="#nbaPanel"
        >
          Palielināt
        </button>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-[980px] w-full text-sm clickable-body" data-target="#nbaPanel">
          <thead class="bg-[#0f172a] text-gray-300">
            <tr>
              <th class="px-3 py-2 w-12"></th>
              <th class="px-3 py-2 text-left">Sezona</th>
              <th class="px-3 py-2 text-left">Spēlētājs</th>
              <th class="px-3 py-2 text-left">Komanda</th>
              <th class="px-3 py-2 text-right">G</th>
              <th class="px-3 py-2 text-right">PPG</th>
              <th class="px-3 py-2 text-right">RPG</th>
              <th class="px-3 py-2 text-right">APG</th>
            </tr>
          </thead>

          <tbody id="nbaRows" class="divide-y divide-[#1f2937] text-[#F3F4F6]">
            @foreach($nba as $r)
              @php
                $rowKey = $r->_key ?? "NBA:{$r->player_id}:{$r->season}";

                $headshotUrl = null;
                if (!empty($r->headshot)) {
                  $headshotUrl = preg_match('/^https?:\/\//i', $r->headshot)
                    ? $r->headshot
                    : asset('storage/' . ltrim($r->headshot, '/'));
                }

                $teamLogoUrl = null;
                if (!empty($r->team_logo)) {
                  $teamLogoUrl = preg_match('/^https?:\/\//i', $r->team_logo)
                    ? $r->team_logo
                    : asset('storage/' . ltrim($r->team_logo, '/'));
                }

                $payloadNba = [
                  'src'       => 'NBA',
                  'key'       => $rowKey,
                  'player_id' => $r->player_id,
                  'season'    => $r->season,
                  'player'    => $r->player_name,
                  'headshot'  => $headshotUrl,
                  'team'      => $r->team_name,
                  'logo'      => $teamLogoUrl,

                  'ppg'    => $r->_raw_ppg,
                  'rpg'    => $r->_raw_rpg,
                  'apg'    => $r->_raw_apg,
                  'spg'    => $r->_raw_spg,
                  'bpg'    => $r->_raw_bpg,
                  'tpg'    => $r->_raw_tpg,
                  'fg_pct' => $r->_raw_fg,
                  'tp_pct' => $r->_raw_tp,
                  'ft_pct' => $r->_raw_ft,

                  'games'  => $r->g,
                  'wins'   => $r->wins,
                  'losses' => max($r->g - $r->wins, 0),
                ];
              @endphp

              <tr class="odd:bg-[#111827] even:bg-[#0b1220] hover:bg-[#1f2937]">
                <td class="select-cell not-expand p-0 w-12">
                  <label class="select-hit flex items-center justify-center w-full h-full px-3 py-2 cursor-pointer">
                    <input
                      type="checkbox"
                      class="pick-nba accent-[#84CC16]"
                      data-payload='@json($payloadNba, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)'
                    >
                  </label>
                </td>

                <td class="px-3 py-2">{{ $r->season }}</td>

                <td class="px-3 py-2">
                  <div class="flex items-center gap-2">
                    @if($headshotUrl)
                      <img src="{{ $headshotUrl }}" class="h-6 w-6 rounded-full object-cover ring-1 ring-white/10" alt="">
                    @else
                      <div class="h-6 w-6 rounded-full bg-white/10"></div>
                    @endif
                    {{ $r->player_name }}
                  </div>
                </td>

                <td class="px-3 py-2">
                  <div class="flex items-center gap-2">
                    @if($teamLogoUrl)
                      <img src="{{ $teamLogoUrl }}" class="h-5 w-5 object-contain rounded bg-white p-[2px]" alt="">
                    @endif
                    {{ $r->team_name }}
                  </div>
                </td>

                <td class="px-3 py-2 text-right">{{ $r->g }}</td>
                <td class="px-3 py-2 text-right">{{ $r->ppg }}</td>
                <td class="px-3 py-2 text-right">{{ $r->rpg }}</td>
                <td class="px-3 py-2 text-right">{{ $r->apg }}</td>
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
        <h2 class="font-semibold select-none">LBS spēlētāji</h2>
        <button
          type="button"
          class="panel-expand px-3 py-1.5 rounded bg-white/10 hover:bg-white/20"
          data-target="#lbsPanel"
        >
          Palielināt
        </button>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-[980px] w-full text-sm clickable-body" data-target="#lbsPanel">
          <thead class="bg-[#0f172a] text-gray-300">
            <tr>
              <th class="px-3 py-2 w-12"></th>
              <th class="px-3 py-2 text-left">Sezona</th>
              <th class="px-3 py-2 text-left">Spēlētājs</th>
              <th class="px-3 py-2 text-left">Komanda</th>
              <th class="px-3 py-2 text-right">G</th>
              <th class="px-3 py-2 text-right">PPG</th>
              <th class="px-3 py-2 text-right">RPG</th>
              <th class="px-3 py-2 text-right">APG</th>
            </tr>
          </thead>

          <tbody id="lbsRows" class="divide-y divide-[#1f2937] text-[#F3F4F6]">
            @foreach($lbs as $r)
              @php
                $rowKey = $r->_key ?? "LBS:{$r->player_id}:{$r->season}";

                $headshotUrl = null;
                if (!empty($r->headshot)) {
                  $headshotUrl = preg_match('/^https?:\/\//i', $r->headshot)
                    ? $r->headshot
                    : asset('storage/' . ltrim($r->headshot, '/'));
                }

                $teamLogoUrl = null;
                if (!empty($r->team_logo)) {
                  $teamLogoUrl = preg_match('/^https?:\/\//i', $r->team_logo)
                    ? $r->team_logo
                    : asset('storage/' . ltrim($r->team_logo, '/'));
                }

                $payloadLbs = [
                  'src'       => 'LBS',
                  'key'       => $rowKey,
                  'player_id' => $r->player_id,
                  'season'    => $r->season,
                  'player'    => $r->player_name,
                  'headshot'  => $headshotUrl,
                  'team'      => $r->team_name,
                  'logo'      => $teamLogoUrl,

                  'ppg'    => $r->_raw_ppg,
                  'rpg'    => $r->_raw_rpg,
                  'apg'    => $r->_raw_apg,
                  'spg'    => $r->_raw_spg,
                  'bpg'    => $r->_raw_bpg,
                  'tpg'    => $r->_raw_tpg,
                  'fg_pct' => $r->_raw_fg,
                  'tp_pct' => $r->_raw_tp,
                  'ft_pct' => $r->_raw_ft,

                  'games'  => $r->g,
                  'wins'   => $r->wins,
                  'losses' => max($r->g - $r->wins, 0),
                ];
              @endphp

              <tr class="odd:bg-[#111827] even:bg-[#0b1220] hover:bg-[#1f2937]">
                <td class="select-cell not-expand p-0 w-12">
                  <label class="select-hit flex items-center justify-center w-full h-full px-3 py-2 cursor-pointer">
                    <input
                      type="checkbox"
                      class="pick-lbs accent-[#84CC16]"
                      data-payload='@json($payloadLbs, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)'
                    >
                  </label>
                </td>

                <td class="px-3 py-2">{{ $r->season }}</td>

                <td class="px-3 py-2">
                  <div class="flex items-center gap-2">
                    @if($headshotUrl)
                      <img src="{{ $headshotUrl }}" class="h-6 w-6 rounded-full object-cover ring-1 ring-white/10" alt="">
                    @else
                      <div class="h-6 w-6 rounded-full bg-white/10"></div>
                    @endif
                    {{ $r->player_name }}
                  </div>
                </td>

                <td class="px-3 py-2">
                  <div class="flex items-center gap-2">
                    @if($teamLogoUrl)
                      <img src="{{ $teamLogoUrl }}" class="h-5 w-5 object-contain rounded bg-white p-[2px]" alt="">
                    @endif
                    {{ $r->team_name }}
                  </div>
                </td>

                <td class="px-3 py-2 text-right">{{ $r->g }}</td>
                <td class="px-3 py-2 text-right">{{ $r->ppg }}</td>
                <td class="px-3 py-2 text-right">{{ $r->rpg }}</td>
                <td class="px-3 py-2 text-right">{{ $r->apg }}</td>
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

  .clickable-body tbody tr {
    cursor: pointer;
  }

  .select-cell {
    width: 56px;
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

  .panel.is-in-overlay > .flex.items-center.justify-between {
    flex: 0 0 auto;
  }

  .panel.is-in-overlay .overflow-x-auto {
    flex: 1 1 auto;
    min-height: 0;
    overflow: auto;
    overscroll-behavior: contain;
  }

  .panel.is-in-overlay > .p-4.flex {
    flex: 0 0 auto;
    border-top: 1px solid #1f2937;
    background: #111827;
  }

  .panel.is-in-overlay thead th {
    position: sticky;
    top: 0;
    z-index: 3;
    background: #0f172a;
  }

  .overflow-x-auto {
    scrollbar-width: thin;
    scrollbar-color: #64748b #0b1220;
  }

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

  @media (max-width: 768px) {
    #panelDrawer {
      width: 95vw;
      height: 92vh;
      border-radius: 14px;
    }
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
    host.scrollTop = 0;

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

    const dataCell = e.target.closest('.clickable-body tbody td:not(.select-cell)');
    if (dataCell) {
      if (isInteractive(e.target)) return;

      const table = dataCell.closest('.clickable-body');
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
  const STORAGE_KEY  = 'cross_compare_players_v2';
  const STORAGE_BASE = @json(asset('storage'));
  const compareBtn   = document.getElementById('compareBtn');
  const clearBtn     = document.getElementById('clearSelBtn');
  const compareArea  = document.getElementById('compareArea');
  const compareGrid  = document.getElementById('compareGrid');

  const selNba = new Map();
  const selLbs = new Map();

  function parsePayload(el) {
    try { return JSON.parse(el.dataset.payload); } catch (_) { return null; }
  }

  function keyOf(p) {
    return p?.key ?? `${p?.src}:${p?.player_id}:${p?.season}`;
  }

  function toUrl(v) {
    if (!v) return null;
    return /^https?:\/\//i.test(String(v))
      ? v
      : `${STORAGE_BASE}/${String(v).replace(/^\/+/, '')}`;
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
      lbs: Object.fromEntries(selLbs),
    }));
  }

  function syncButton() {
    compareBtn.disabled = (selNba.size + selLbs.size) === 0;
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

  const num = (v) => (v === null || v === undefined) ? NaN : Number(v);

  function vsLeader(list, field, high = true) {
    const vals = list.map(p => num(p[field]));
    const valid = vals.filter(v => isFinite(v));

    if (!valid.length) {
      return list.map(() => ({ label: '—', cls: 'text-gray-300' }));
    }

    const leader = high ? Math.max(...valid) : Math.min(...valid);

    return vals.map(v => {
      if (!isFinite(v)) return { label: '—', cls: 'text-gray-300' };

      let behindPct;
      if (leader === 0) behindPct = 0;
      else if (high) behindPct = ((leader - v) / Math.abs(leader)) * 100;
      else behindPct = ((v - leader) / Math.abs(leader)) * 100;

      if (Math.abs(behindPct) < .5) {
        return { label: 'Līderis', cls: 'text-[#84CC16]' };
      }

      return {
        label: `-${Math.round(behindPct)}% sal. ar līderi`,
        cls: 'text-[#F97316]'
      };
    });
  }

  const pct = (v) => (v == null ? '—' : `${(Number(v) * 100).toFixed(1)}%`);
  const one = (v) => (v == null ? '—' : Number(v).toFixed(1));

  function renderCompare() {
    const sel = [...selNba.values(), ...selLbs.values()];
    if (!sel.length) {
      compareGrid.innerHTML = '';
      compareArea.classList.add('hidden');
      return;
    }

    const cmpPPG = vsLeader(sel, 'ppg', true);
    const cmpRPG = vsLeader(sel, 'rpg', true);
    const cmpAPG = vsLeader(sel, 'apg', true);
    const cmpSPG = vsLeader(sel, 'spg', true);
    const cmpBPG = vsLeader(sel, 'bpg', true);
    const cmpTOV = vsLeader(sel, 'tpg', false);
    const cmpFG  = vsLeader(sel, 'fg_pct', true);
    const cmpTP  = vsLeader(sel, 'tp_pct', true);
    const cmpFT  = vsLeader(sel, 'ft_pct', true);

    const line = (c) => `<div class="text-xs mt-0.5 ${c.cls}">${c.label}</div>`;

    compareGrid.innerHTML = sel.map((p, i) => {
      const head = p.headshot
        ? `<img src="${toUrl(p.headshot)}" class="h-7 w-7 rounded-full object-cover ring-1 ring-white/10" alt="">`
        : `<div class="h-7 w-7 rounded-full bg-white/10"></div>`;

      const logo = p.logo
        ? `<img src="${toUrl(p.logo)}" class="h-6 w-6 object-contain rounded bg-white p-[2px]" alt="">`
        : '';

      return `
        <article class="bg-[#0f172a]/60 border border-[#374151] rounded-xl p-4">
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
              ${head}
              <div>
                <div class="text-white font-semibold">${p.player}</div>
                <div class="text-xs text-gray-400">${p.src}</div>
              </div>
            </div>
            <div class="flex items-center gap-2">
              ${logo}
              <span class="text-xs text-gray-300">${p.team ?? ''}</span>
            </div>
          </div>

          <div class="grid grid-cols-3 gap-3 text-sm">
            <div><div class="text-[#F3F4F6]/60 text-xs">PPG</div><div class="font-semibold">${one(p.ppg)}</div>${line(cmpPPG[i])}</div>
            <div><div class="text-[#F3F4F6]/60 text-xs">RPG</div><div class="font-semibold">${one(p.rpg)}</div>${line(cmpRPG[i])}</div>
            <div><div class="text-[#F3F4F6]/60 text-xs">APG</div><div class="font-semibold">${one(p.apg)}</div>${line(cmpAPG[i])}</div>
            <div><div class="text-[#F3F4F6]/60 text-xs">SPG</div><div class="font-semibold">${one(p.spg)}</div>${line(cmpSPG[i])}</div>
            <div><div class="text-[#F3F4F6]/60 text-xs">BPG</div><div class="font-semibold">${one(p.bpg)}</div>${line(cmpBPG[i])}</div>
            <div><div class="text-[#F3F4F6]/60 text-xs">TOV</div><div class="font-semibold">${one(p.tpg)}</div>${line(cmpTOV[i])}</div>
            <div><div class="text-[#F3F4F6]/60 text-xs">FG%</div><div class="font-semibold">${pct(p.fg_pct)}</div>${line(cmpFG[i])}</div>
            <div><div class="text-[#F3F4F6]/60 text-xs">3P%</div><div class="font-semibold">${pct(p.tp_pct)}</div>${line(cmpTP[i])}</div>
            <div><div class="text-[#F3F4F6]/60 text-xs">FT%</div><div class="font-semibold">${pct(p.ft_pct)}</div>${line(cmpFT[i])}</div>
          </div>

          <div class="mt-3 text-xs text-gray-300">
            Sezona: ${p.season ?? '—'} • G: ${p.games ?? '—'} • W/L: ${p.wins ?? '—'}–${p.losses ?? '—'}
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

    const p = parsePayload(cb);
    if (!p) return;

    if (cb.classList.contains('pick-nba')) {
      upsert(selNba, 5, p, cb);
    } else {
      upsert(selLbs, 5, p, cb);
    }

    if (!compareArea.classList.contains('hidden')) {
      renderCompare();
    }
  });

  clearBtn.addEventListener('click', () => {
    document.querySelectorAll('.pick-nba,.pick-lbs').forEach(x => x.checked = false);
    selNba.clear();
    selLbs.clear();
    save();
    syncButton();
    compareGrid.innerHTML = '';
    compareArea.classList.add('hidden');
  });

  compareBtn.addEventListener('click', renderCompare);

  window.__playersCompareUI = {
    hydrateCheckboxes,
    renderCompare,
    storageBase: STORAGE_BASE,
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

  const STORAGE_BASE = window.__playersCompareUI?.storageBase || '';

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

  function toUrl(v) {
    if (!v) return null;
    return /^https?:\/\//i.test(String(v))
      ? v
      : `${STORAGE_BASE}/${String(v).replace(/^\/+/, '')}`;
  }

  function buildMeta(list, per, page) {
    const total = list.length;
    const last = Math.max(Math.ceil(Math.max(total, 1) / per), 1);
    const safePage = Math.min(Math.max(page, 1), last);
    const start = (safePage - 1) * per;

    return { total, per, page: safePage, last, start };
  }

  function buildPayload(panelName, row) {
    const fallbackKey = `${panelName.toUpperCase()}:${row.player_id}:${row.season}`;

    const headshotUrl = row.headshot
      ? toUrl(row.headshot)
      : null;

    const teamLogoUrl = row.team_logo
      ? toUrl(row.team_logo)
      : null;

    return {
      src: panelName.toUpperCase(),
      key: row._key ?? fallbackKey,
      player_id: row.player_id,
      season: row.season,
      player: row.player_name,
      headshot: headshotUrl,
      team: row.team_name,
      logo: teamLogoUrl,

      ppg: row._raw_ppg,
      rpg: row._raw_rpg,
      apg: row._raw_apg,
      spg: row._raw_spg,
      bpg: row._raw_bpg,
      tpg: row._raw_tpg,
      fg_pct: row._raw_fg,
      tp_pct: row._raw_tp,
      ft_pct: row._raw_ft,

      games: row.g,
      wins: row.wins,
      losses: Math.max((row.g || 0) - (row.wins || 0), 0),
    };
  }

  function rowHtml(panelName, row) {
    const payload = buildPayload(panelName, row);
    const payloadStr = esc(JSON.stringify(payload));
    const checkboxClass = panelName === 'nba' ? 'pick-nba' : 'pick-lbs';

    const headshotHtml = payload.headshot
      ? `<img src="${esc(payload.headshot)}" class="h-6 w-6 rounded-full object-cover ring-1 ring-white/10" alt="">`
      : `<div class="h-6 w-6 rounded-full bg-white/10"></div>`;

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
            ${headshotHtml}
            ${esc(row.player_name)}
          </div>
        </td>

        <td class="px-3 py-2">
          <div class="flex items-center gap-2">
            ${logoHtml}
            ${esc(row.team_name)}
          </div>
        </td>

        <td class="px-3 py-2 text-right">${esc(row.g ?? '—')}</td>
        <td class="px-3 py-2 text-right">${esc(row.ppg ?? '—')}</td>
        <td class="px-3 py-2 text-right">${esc(row.rpg ?? '—')}</td>
        <td class="px-3 py-2 text-right">${esc(row.apg ?? '—')}</td>
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
      : `<tr><td colspan="8" class="px-3 py-6 text-center text-gray-400">Nav atrastu ierakstu</td></tr>`;

    state.infoEl.textContent = `Lapa ${meta.page} no ${meta.last} • ${meta.total} ieraksti`;

    setDisabled(state.prevBtn, meta.page <= 1);
    setDisabled(state.nextBtn, meta.page >= meta.last);

    updateUrl(panelName, meta.page, state.per);
    window.__playersCompareUI?.hydrateCheckboxes?.();
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