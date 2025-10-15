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

    <input name="q" value="{{ $q }}" placeholder="Meklēt (vārds vai komanda)"
           class="bg-[#0f172a] border border-[#374151] rounded px-3 py-2 sm:col-span-1 sm:col-start-5" />

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
        <button id="compareBtn"
                class="px-3 py-2 rounded-lg bg-white/10 text-white hover:bg-white/20 disabled:opacity-40"
                disabled>Salīdzināt izvēlētos</button>
        <button id="clearSelBtn"
                class="px-3 py-2 rounded-lg bg-white/10 text-white hover:bg-white/20">Notīrīt</button>
      </div>
    </div>

    <div id="compareArea" class="mt-4 hidden">
      <h3 class="text-white font-semibold mb-3">Salīdzinājums</h3>
      <div id="compareGrid" class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(240px,1fr))]"></div>
    </div>
  </section>

  <div class="grid gap-6 lg:grid-cols-2">
    <section id="nbaPanel" class="panel bg-[#111827] border border-[#1f2937] rounded-2xl overflow-hidden">
      <div class="flex items-center justify-between px-4 py-3 bg-[#0f172a] border-b border-[#1f2937]">
        <h2 class="font-semibold select-none">NBA spēlētāji</h2>
        <button class="panel-expand px-3 py-1.5 rounded bg-white/10 hover:bg-white/20" data-target="#nbaPanel">
          Palielināt
        </button>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-[900px] w-full text-sm clickable-body" data-target="#nbaPanel">
          <thead class="bg-[#0f172a] text-gray-300">
            <tr>
              <th class="px-3 py-2 w-8"></th>
              <th class="px-3 py-2 text-left">Sezona</th>
              <th class="px-3 py-2 text-left">Spēlētājs</th>
              <th class="px-3 py-2 text-left">Komanda</th>
              <th class="px-3 py-2 text-right">G</th>
              <th class="px-3 py-2 text-right">PPG</th>
              <th class="px-3 py-2 text-right">RPG</th>
              <th class="px-3 py-2 text-right">APG</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#1f2937] text-[#F3F4F6]">
            @foreach($nba as $r)
              @php
                $payloadNba = [
                  'src'      => 'NBA',
                  'key'      => "NBA:{$r->player_id}:{$r->season}",
                  'player_id'=> $r->player_id,
                  'season'   => $r->season,
                  'player'   => $r->player_name,
                  'headshot' => $r->headshot,
                  'team'     => $r->team_name,
                  'logo'     => $r->team_logo,
                  'ppg'=>$r->_raw_ppg,'rpg'=>$r->_raw_rpg,'apg'=>$r->_raw_apg,
                  'spg'=>$r->_raw_spg,'bpg'=>$r->_raw_bpg,'tpg'=>$r->_raw_tpg,
                  'fg_pct'=>$r->_raw_fg,'tp_pct'=>$r->_raw_tp,'ft_pct'=>$r->_raw_ft,
                  'games'=>$r->g,'wins'=>$r->wins,'losses'=>max($r->g - $r->wins, 0),
                ];
              @endphp
              <tr class="odd:bg-[#111827] even:bg-[#0b1220] hover:bg-[#1f2937]">
                <td class="px-3 py-2">
                  <input type="checkbox"
                         class="pick-nba accent-[#84CC16] not-expand"
                         data-payload='@json($payloadNba, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)'>
                </td>
                <td class="px-3 py-2">{{ $r->season }}</td>
                <td class="px-3 py-2">{{ $r->player_name }}</td>
                <td class="px-3 py-2">{{ $r->team_name }}</td>
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
        <div class="text-gray-400">
          Lapa {{ $nbaMeta['page'] }} no {{ $nbaMeta['last'] }} • {{ $nbaMeta['total'] }} ieraksti
        </div>
        <div class="flex gap-2">
          @php
            $base = request()->query();
          @endphp
          <a class="px-3 py-1 rounded bg-white/10 hover:bg-white/20 {{ $nbaMeta['page']<=1?'pointer-events-none opacity-40':'' }}"
             href="{{ url()->current() . '?' . http_build_query(array_merge($base,['nba_page'=>max($nbaMeta['page']-1,1)])) }}">
            ‹
          </a>
          <a class="px-3 py-1 rounded bg-white/10 hover:bg-white/20 {{ $nbaMeta['page']>=$nbaMeta['last']?'pointer-events-none opacity-40':'' }}"
             href="{{ url()->current() . '?' . http_build_query(array_merge($base,['nba_page'=>min($nbaMeta['page']+1,$nbaMeta['last'])])) }}">
            ›
          </a>
        </div>
      </div>
    </section>

    <section id="lbsPanel" class="panel bg-[#111827] border border-[#1f2937] rounded-2xl overflow-hidden">
      <div class="flex items-center justify-between px-4 py-3 bg-[#0f172a] border-b border-[#1f2937]">
        <h2 class="font-semibold select-none">LBS spēlētāji</h2>
        <button class="panel-expand px-3 py-1.5 rounded bg-white/10 hover:bg-white/20" data-target="#lbsPanel">
          Palielināt
        </button>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-[900px] w-full text-sm clickable-body" data-target="#lbsPanel">
          <thead class="bg-[#0f172a] text-gray-300">
            <tr>
              <th class="px-3 py-2 w-8"></th>
              <th class="px-3 py-2 text-left">Sezona</th>
              <th class="px-3 py-2 text-left">Spēlētājs</th>
              <th class="px-3 py-2 text-left">Komanda</th>
              <th class="px-3 py-2 text-right">G</th>
              <th class="px-3 py-2 text-right">PPG</th>
              <th class="px-3 py-2 text-right">RPG</th>
              <th class="px-3 py-2 text-right">APG</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#1f2937] text-[#F3F4F6]">
            @foreach($lbs as $r)
              @php
                $payloadLbs = [
                  'src'      => 'LBS',
                  'key'      => "LBS:{$r->player_id}:{$r->season}",
                  'player_id'=> $r->player_id,
                  'season'   => $r->season,
                  'player'   => $r->player_name,
                  'headshot' => $r->headshot,
                  'team'     => $r->team_name,
                  'logo'     => $r->team_logo,
                  'ppg'=>$r->_raw_ppg,'rpg'=>$r->_raw_rpg,'apg'=>$r->_raw_apg,
                  'spg'=>$r->_raw_spg,'bpg'=>$r->_raw_bpg,'tpg'=>$r->_raw_tpg,
                  'fg_pct'=>$r->_raw_fg,'tp_pct'=>$r->_raw_tp,'ft_pct'=>$r->_raw_ft,
                  'games'=>$r->g,'wins'=>$r->wins,'losses'=>max($r->g - $r->wins, 0),
                ];
              @endphp
              <tr class="odd:bg-[#111827] even:bg-[#0b1220] hover:bg-[#1f2937]">
                <td class="px-3 py-2">
                  <input type="checkbox"
                         class="pick-lbs accent-[#84CC16] not-expand"
                         data-payload='@json($payloadLbs, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)'>
                </td>
                <td class="px-3 py-2">{{ $r->season }}</td>
                <td class="px-3 py-2">{{ $r->player_name }}</td>
                <td class="px-3 py-2">{{ $r->team_name }}</td>
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
        <div class="text-gray-400">
          Lapa {{ $lbsMeta['page'] }} no {{ $lbsMeta['last'] }} • {{ $lbsMeta['total'] }} ieraksti
        </div>
        <div class="flex gap-2">
          @php $base = request()->query(); @endphp
          <a class="px-3 py-1 rounded bg-white/10 hover:bg-white/20 {{ $lbsMeta['page']<=1?'pointer-events-none opacity-40':'' }}"
             href="{{ url()->current() . '?' . http_build_query(array_merge($base,['lbs_page'=>max($lbsMeta['page']-1,1)])) }}">
            ‹
          </a>
          <a class="px-3 py-1 rounded bg-white/10 hover:bg-white/20 {{ $lbsMeta['page']>=$lbsMeta['last']?'pointer-events-none opacity-40':'' }}"
             href="{{ url()->current() . '?' . http_build_query(array_merge($base,['lbs_page'=>min($lbsMeta['page']+1,$lbsMeta['last'])])) }}">
            ›
          </a>
        </div>
      </div>
    </section>
  </div>
</main>

<style>
  #panelOverlay {
    display: none;
    position: fixed; inset: 0;
    z-index: 999;
  }
  #panelOverlay.active { display: block; }

  #panelBackdrop {
    position: absolute; inset: 0;
    background: rgba(0,0,0,.5);
    backdrop-filter: blur(2px);
  }

  #panelDrawer {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%) scale(.98);
    width: min(1100px, 92vw);
    height: min(88vh, 900px);
    background: #0f172a;
    border: 1px solid #1f2937;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,.45);
    display: flex; flex-direction: column;
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
  }

  #panelHost { flex: 1; overflow: auto; }

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
  <button id="panelClose" class="px-3 py-1.5 rounded bg-white/10 hover:bg-white/20">✕</button>
</div>
    <div id="panelHost" class="flex-1 overflow-auto"></div>
  </div>
</div>

@push('scripts')
<script>
(function(){
  const overlay  = document.getElementById('panelOverlay');
  const host     = document.getElementById('panelHost');
  const closeBtn = document.getElementById('panelClose');
  const backdrop = document.getElementById('panelBackdrop');

  let currentClone = null;   
  let sourcePanel  = null;   

  function makeClone(panel){
    const clone = panel.cloneNode(true);
    clone.dataset.cloned = 'true';

    clone.querySelectorAll('input,select,textarea,button').forEach(el => {
      el.setAttribute('disabled', 'disabled');
      el.classList.add('not-expand');
    });

    clone.querySelectorAll('.clickable-body').forEach(tbl => {
      tbl.addEventListener('click', (e)=> e.stopPropagation());
    });

    const hdrBtn = clone.querySelector('.panel-expand');
    if (hdrBtn) {
      hdrBtn.textContent = '—';
      hdrBtn.setAttribute('disabled', 'disabled');
      hdrBtn.classList.add('opacity-50','cursor-default');
    }

    return clone;
  }

  function openPanel(panel){
    if (!panel) return;
    if (sourcePanel === panel && currentClone) return;

    if (currentClone) closePanel();

    sourcePanel = panel;
    currentClone = makeClone(panel);
    host.innerHTML = '';             
    host.appendChild(currentClone);  

    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closePanel(){
    if (!currentClone) return;
    currentClone.remove();
    currentClone = null;
    sourcePanel  = null;

    overlay.classList.remove('active');
    document.body.style.overflow = '';
  }

  document.querySelectorAll('.panel-expand').forEach(btn=>{
    btn.addEventListener('click', (e)=>{
      e.stopPropagation();
      const panel = document.querySelector(btn.dataset.target);
      openPanel(panel);
    });
  });

  document.querySelectorAll('.clickable-body').forEach(tbl=>{
    tbl.addEventListener('click', (e)=>{
      const isInteractive = e.target.closest('.not-expand, a, button, input, select, label, textarea');
      if (isInteractive) return;
      const panel = document.querySelector(tbl.getAttribute('data-target'));
      openPanel(panel);
    });
  });

  closeBtn.addEventListener('click', closePanel);
  backdrop.addEventListener('click', closePanel);
  window.addEventListener('keydown', (e)=>{ if(e.key==='Escape') closePanel(); });
})();
</script>


<script>
(function(){
  const STORAGE_KEY  = 'cross_compare_sel_v1';
  const STORAGE_BASE = @json(asset('storage'));
  const compareBtn   = document.getElementById('compareBtn');
  const clearBtn     = document.getElementById('clearSelBtn');
  const compareArea  = document.getElementById('compareArea');
  const compareGrid  = document.getElementById('compareGrid');

  const selNba = new Map(); 
  const selLbs = new Map();

  function parsePayload(el){
    try { return JSON.parse(el.dataset.payload); } catch(_) { return null; }
  }
  function keyOf(p){ return p?.key ?? `${p?.src}:${p?.player_id}:${p?.season}`; }

  function load(){
    try{
      const raw = localStorage.getItem(STORAGE_KEY);
      if(!raw) return;
      const obj = JSON.parse(raw);
      for(const [k,v] of Object.entries(obj.nba||{})) selNba.set(k, v);
      for(const [k,v] of Object.entries(obj.lbs||{})) selLbs.set(k, v);
    }catch(_){}
  }
  function save(){
    localStorage.setItem(
      STORAGE_KEY,
      JSON.stringify({
        nba: Object.fromEntries(selNba),
        lbs: Object.fromEntries(selLbs)
      })
    );
  }
  function syncButton(){
    compareBtn.disabled = (selNba.size + selLbs.size) === 0;
  }
  function hydrateCheckboxes(){
    document.querySelectorAll('.pick-nba').forEach(cb=>{
      const p = parsePayload(cb); if(!p) return;
      cb.checked = selNba.has(keyOf(p));
    });
    document.querySelectorAll('.pick-lbs').forEach(cb=>{
      const p = parsePayload(cb); if(!p) return;
      cb.checked = selLbs.has(keyOf(p));
    });
    syncButton();
  }

  function upsert(leagueMap, max, payload, cb){
    const k = keyOf(payload);
    if(cb.checked){
      if(!leagueMap.has(k) && leagueMap.size >= max){
        cb.checked = false; return;
      }
      leagueMap.set(k, payload);
    }else{
      leagueMap.delete(k);
    }
    save(); syncButton();
  }

  load();
  hydrateCheckboxes();

  document.querySelectorAll('.pick-nba').forEach(cb=>{
    cb.addEventListener('change', ()=>{
      const p = parsePayload(cb); if(!p) return;
      upsert(selNba, 5, p, cb);
    });
  });
  document.querySelectorAll('.pick-lbs').forEach(cb=>{
    cb.addEventListener('change', ()=>{
      const p = parsePayload(cb); if(!p) return;
      upsert(selLbs, 5, p, cb);
    });
  });

  clearBtn.addEventListener('click', ()=>{
    document.querySelectorAll('.pick-nba,.pick-lbs').forEach(x=>x.checked=false);
    selNba.clear(); selLbs.clear(); save(); syncButton();
    compareGrid.innerHTML=''; compareArea.classList.add('hidden');
  });

  const num = (v)=>(v===null||v===undefined)?NaN:Number(v);
  function vsLeader(list, field, high=true){
    const vals = list.map(p=>num(p[field]));
    const valid= vals.filter(v=>isFinite(v));
    if (!valid.length) return list.map(_=>({label:'—',cls:'text-gray-300'}));
    const leader = high ? Math.max(...valid) : Math.min(...valid);
    return vals.map(v=>{
      if(!isFinite(v)) return {label:'—',cls:'text-gray-300'};
      let behindPct;
      if (leader===0) behindPct=0;
      else if (high)  behindPct=((leader - v)/Math.abs(leader))*100;
      else            behindPct=((v - leader)/Math.abs(leader))*100;
      if (Math.abs(behindPct) < .5) return {label:'Līderis',cls:'text-[#84CC16]'};
      return {label:`-${Math.round(behindPct)}% sal. ar līderi`,cls:'text-[#F97316]'};
    });
  }
  const pct = (v)=> (v==null ? '—' : `${(Number(v)*100).toFixed(1)}%`);
  const one = (v)=> (v==null ? '—' : Number(v).toFixed(1));
  const toUrl = (v)=>{ if(!v) return null; return /^https?:\/\//i.test(String(v))? v : `${STORAGE_BASE}/${String(v).replace(/^\/+/,'')}`; };

  compareBtn.addEventListener('click', ()=>{
    const sel = [...selNba.values(), ...selLbs.values()];
    if (!sel.length) { compareArea.classList.add('hidden'); return; }

    const cmpPPG = vsLeader(sel,'ppg',true);
    const cmpRPG = vsLeader(sel,'rpg',true);
    const cmpAPG = vsLeader(sel,'apg',true);
    const cmpSPG = vsLeader(sel,'spg',true);
    const cmpBPG = vsLeader(sel,'bpg',true);
    const cmpTOV = vsLeader(sel,'tpg',false);
    const cmpFG  = vsLeader(sel,'fg_pct',true);
    const cmpTP  = vsLeader(sel,'tp_pct',true);
    const cmpFT  = vsLeader(sel,'ft_pct',true);
    const line = (c)=>`<div class="text-xs mt-0.5 ${c.cls}">${c.label}</div>`;

    compareGrid.innerHTML = sel.map((p,i)=>{
      const head = p.headshot ? `<img src="${toUrl(p.headshot)}" class="h-7 w-7 rounded-full object-cover ring-1 ring-white/10" />`
                              : `<div class="h-7 w-7 rounded-full bg-white/10"></div>`;
      const logo = p.logo ? `<img src="${toUrl(p.logo)}" class="h-6 w-6 object-contain rounded bg-white p-[2px]" />` : '';
      return `
        <article class="bg-[#0f172a]/60 border border-[#374151] rounded-xl p-4">
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
              ${head}
              <div><div class="text-white font-semibold">${p.player}</div><div class="text-xs text-gray-400">${p.src}</div></div>
            </div>
            <div class="flex items-center gap-2">${logo}<span class="text-xs text-gray-300">${p.team ?? ''}</span></div>
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
          <div class="mt-3 text-xs text-gray-300">Sezona: ${p.season ?? '—'} • G: ${p.games ?? '—'} • W/L: ${p.wins ?? '—'}–${p.losses ?? '—'}</div>
        </article>`;
    }).join('');

    compareArea.classList.remove('hidden');
  });
})();
</script>
@endpush
@endsection
