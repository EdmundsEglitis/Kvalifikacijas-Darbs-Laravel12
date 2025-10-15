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
    <input name="q" value="{{ $q }}" placeholder="Meklēt komandu"
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
        Atzīmē katrā tabulā līdz 5 komandām. Pēc tam — “Salīdzināt izvēlētos”.
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
        <h2 class="font-semibold select-none">NBA komandas</h2>
        <button class="panel-expand px-3 py-1.5 rounded bg-white/10 hover:bg-white/20" data-target="#nbaPanel">
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
              <th class="px-3 py-2 text-right">W</th>
              <th class="px-3 py-2 text-right">L</th>
              <th class="px-3 py-2 text-right">Uzvaras%</th>
              <th class="px-3 py-2 text-right">PPG</th>
              <th class="px-3 py-2 text-right">Pretinieku PPG</th>
              <th class="px-3 py-2 text-right">Starpība</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#1f2937] text-[#F3F4F6]">
            @foreach($nba as $r)
              @php
                $payload = [
                  'src'=>'NBA','key'=>$r->_key,
                  'season'=>$r->season,'team'=>$r->team_name,'logo'=>$r->team_logo,
                  'wins'=>$r->wins,'losses'=>$r->losses,
                  'win_percent'=>$r->win_percent,'ppg'=>$r->ppg,'opp_ppg'=>$r->opp_ppg,'diff'=>$r->diff,
                ];
              @endphp
              <tr class="odd:bg-[#111827] even:bg-[#0b1220] hover:bg-[#1f2937]">
                <td class="px-3 py-2">
                  <input type="checkbox" class="pick-nba accent-[#84CC16] not-expand"
                         data-payload='@json($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)'>
                </td>
                <td class="px-3 py-2">{{ $r->season }}</td>
                <td class="px-3 py-2">
                  <div class="flex items-center gap-2">
                    @if($r->team_logo)
                      <img src="{{ $r->team_logo }}" class="h-5 w-5 object-contain rounded bg-white p-[2px]">
                    @endif
                    {{ $r->team_name }}
                  </div>
                </td>
                <td class="px-3 py-2 text-right">{{ $r->wins }}</td>
                <td class="px-3 py-2 text-right">{{ $r->losses }}</td>
                <td class="px-3 py-2 text-right">{{ $r->win_percent_fmt }}</td>
                <td class="px-3 py-2 text-right">{{ $r->ppg_fmt }}</td>
                <td class="px-3 py-2 text-right">{{ $r->opp_ppg_fmt }}</td>
                <td class="px-3 py-2 text-right"><span class="{{ $r->diff_class }}">{{ $r->diff_txt }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="p-4 flex flex-wrap items-center gap-2 justify-between text-sm">
        <div class="text-gray-400">Lapa {{ $nbaMeta['page'] }} no {{ $nbaMeta['last'] }} • {{ $nbaMeta['total'] }} ieraksti</div>
        <div class="flex gap-2">
          @php $base = request()->query(); @endphp
          <a class="px-3 py-1 rounded bg-white/10 hover:bg-white/20 {{ $nbaMeta['page']<=1?'pointer-events-none opacity-40':'' }}"
             href="{{ url()->current() . '?' . http_build_query(array_merge($base,['nba_page'=>max($nbaMeta['page']-1,1)])) }}">‹</a>
          <a class="px-3 py-1 rounded bg-white/10 hover:bg-white/20 {{ $nbaMeta['page']>=$nbaMeta['last']?'pointer-events-none opacity-40':'' }}"
             href="{{ url()->current() . '?' . http_build_query(array_merge($base,['nba_page'=>min($nbaMeta['page']+1,$nbaMeta['last'])])) }}">›</a>
        </div>
      </div>
    </section>

    <section id="lbsPanel" class="panel bg-[#111827] border border-[#1f2937] rounded-2xl overflow-hidden">
      <div class="flex items-center justify-between px-4 py-3 bg-[#0f172a] border-b border-[#1f2937]">
        <h2 class="font-semibold select-none">LBS komandas</h2>
        <button class="panel-expand px-3 py-1.5 rounded bg-white/10 hover:bg-white/20" data-target="#lbsPanel">
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
              <th class="px-3 py-2 text-right">W</th>
              <th class="px-3 py-2 text-right">L</th>
              <th class="px-3 py-2 text-right">Uzvaras%</th>
              <th class="px-3 py-2 text-right">PPG</th>
              <th class="px-3 py-2 text-right">Pretinieku PPG</th>
              <th class="px-3 py-2 text-right">Starpība</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#1f2937] text-[#F3F4F6]">
            @foreach($lbs as $r)
              @php
                $logo = \Illuminate\Support\Str::startsWith($r->team_logo,['http://','https://'])
                        ? $r->team_logo : asset('storage/'.ltrim($r->team_logo ?? '','/'));
                $payload = [
                  'src'=>'LBS','key'=>"LBS:T:{$r->team_id}:{$r->season}",
                  'season'=>$r->season,'team'=>$r->team_name,'logo'=>$logo,
                  'wins'=>$r->wins,'losses'=>$r->losses,
                  'win_percent'=>$r->win_percent,'ppg'=>$r->ppg,'opp_ppg'=>$r->opp_ppg,'diff'=>$r->diff,
                ];
              @endphp
              <tr class="odd:bg-[#111827] even:bg-[#0b1220] hover:bg-[#1f2937]">
                <td class="px-3 py-2">
                  <input type="checkbox" class="pick-lbs accent-[#84CC16] not-expand"
                         data-payload='@json($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)'>
                </td>
                <td class="px-3 py-2">{{ $r->season }}</td>
                <td class="px-3 py-2">
                  <div class="flex items-center gap-2">
                    @if($logo)
                      <img src="{{ $logo }}" class="h-5 w-5 object-contain rounded bg-white p-[2px]">
                    @endif
                    {{ $r->team_name }}
                  </div>
                </td>
                <td class="px-3 py-2 text-right">{{ $r->wins }}</td>
                <td class="px-3 py-2 text-right">{{ $r->losses }}</td>
                <td class="px-3 py-2 text-right">{{ $r->win_percent_fmt }}</td>
                <td class="px-3 py-2 text-right">{{ $r->ppg_fmt }}</td>
                <td class="px-3 py-2 text-right">{{ $r->opp_ppg_fmt }}</td>
                <td class="px-3 py-2 text-right"><span class="{{ $r->diff_class }}">{{ $r->diff_txt }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="p-4 flex flex-wrap items-center gap-2 justify-between text-sm">
        <div class="text-gray-400">Lapa {{ $lbsMeta['page'] }} no {{ $lbsMeta['last'] }} • {{ $lbsMeta['total'] }} ieraksti</div>
        <div class="flex gap-2">
          @php $base = request()->query(); @endphp
          <a class="px-3 py-1 rounded bg-white/10 hover:bg-white/20 {{ $lbsMeta['page']<=1?'pointer-events-none opacity-40':'' }}"
             href="{{ url()->current() . '?' . http_build_query(array_merge($base,['lbs_page'=>max($lbsMeta['page']-1,1)])) }}">‹</a>
          <a class="px-3 py-1 rounded bg-white/10 hover:bg-white/20 {{ $lbsMeta['page']>=$lbsMeta['last']?'pointer-events-none opacity-40':'' }}"
             href="{{ url()->current() . '?' . http_build_query(array_merge($base,['lbs_page'=>min($lbsMeta['page']+1,$lbsMeta['last'])])) }}">›</a>
        </div>
      </div>
    </section>
  </div>
</main>

<style>
  #panelOverlay { display:none; position:fixed; inset:0; z-index:999; }
  #panelOverlay.active { display:block; }
  #panelBackdrop { position:absolute; inset:0; background:rgba(0,0,0,.5); backdrop-filter:blur(2px); }
  #panelDrawer {
    position:absolute; top:50%; left:50%; transform:translate(-50%,-50%) scale(.98);
    width:min(1100px,92vw); height:min(88vh,900px);
    background:#0f172a; border:1px solid #1f2937; border-radius:16px;
    box-shadow:0 20px 60px rgba(0,0,0,.45);
    display:flex; flex-direction:column; opacity:0; pointer-events:none;
    transition:opacity .18s ease, transform .18s ease;
  }
  #panelOverlay.active #panelDrawer { opacity:1; transform:translate(-50%,-50%) scale(1); pointer-events:auto; }
  #panelDrawer .modal-header { background:#0b1220; border-bottom:1px solid #1f2937; border-top-left-radius:16px; border-top-right-radius:16px; }
  #panelHost { flex:1; overflow:auto; }
  @media (max-width:768px){ #panelDrawer{ width:95vw; height:92vh; border-radius:14px; } }
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
      el.setAttribute('disabled','disabled');
      el.classList.add('not-expand');
    });

    clone.querySelectorAll('.clickable-body').forEach(tbl => {
      tbl.addEventListener('click', e => e.stopPropagation());
    });

    const hdrBtn = clone.querySelector('.panel-expand');
    if (hdrBtn) {
      hdrBtn.textContent = '—';
      hdrBtn.setAttribute('disabled','disabled');
      hdrBtn.classList.add('opacity-50','cursor-default');
    }
    return clone;
  }

  function openPanel(panel){
    if (!panel) return;
    if (sourcePanel === panel && currentClone) return;
    if (currentClone) closePanel();

    sourcePanel  = panel;
    currentClone = makeClone(panel);
    host.innerHTML = '';
    host.appendChild(currentClone);

    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closePanel(){
    if (!currentClone) return;
    currentClone.remove();
    currentClone = null; sourcePanel = null;
    overlay.classList.remove('active');
    document.body.style.overflow = '';
  }

  document.querySelectorAll('.panel-expand').forEach(btn=>{
    btn.addEventListener('click', e=>{
      e.stopPropagation();
      const panel = document.querySelector(btn.dataset.target);
      openPanel(panel);
    });
  });

  document.querySelectorAll('.clickable-body').forEach(tbl=>{
    tbl.addEventListener('click', e=>{
      const isInteractive = e.target.closest('.not-expand, a, button, input, select, label, textarea');
      if (isInteractive) return;
      const panel = document.querySelector(tbl.getAttribute('data-target'));
      openPanel(panel);
    });
  });

  closeBtn.addEventListener('click', closePanel);
  backdrop.addEventListener('click', closePanel);
  window.addEventListener('keydown', e=>{ if(e.key==='Escape') closePanel(); });
})();
</script>

<script>
(function(){
  const STORAGE_KEY  = 'compare_teams_nba_lbs_v1';
  const compareBtn   = document.getElementById('compareBtn');
  const clearBtn     = document.getElementById('clearSelBtn');
  const compareArea  = document.getElementById('compareArea');
  const compareGrid  = document.getElementById('compareGrid');

  const selNba = new Map();
  const selLbs = new Map();

  const parse = el => { try { return JSON.parse(el.dataset.payload); } catch(_) { return null; } };
  const keyOf = p  => p?.key ?? `${p?.src}:${p?.team}:${p?.season}`;

  function load(){ try{
    const raw = localStorage.getItem(STORAGE_KEY); if(!raw) return;
    const obj = JSON.parse(raw);
    for (const [k,v] of Object.entries(obj.nba || {})) selNba.set(k,v);
    for (const [k,v] of Object.entries(obj.lbs || {})) selLbs.set(k,v);
  }catch(_){ } }
  function save(){ localStorage.setItem(STORAGE_KEY, JSON.stringify({ nba:Object.fromEntries(selNba), lbs:Object.fromEntries(selLbs) })); }
  function sync(){ compareBtn.disabled = (selNba.size + selLbs.size) === 0; }

  function hydrate(){
    document.querySelectorAll('.pick-nba').forEach(cb=>{ const p=parse(cb); if(p) cb.checked = selNba.has(keyOf(p)); });
    document.querySelectorAll('.pick-lbs').forEach(cb=>{ const p=parse(cb); if(p) cb.checked = selLbs.has(keyOf(p)); });
    sync();
  }
  function upsert(map, max, payload, cb){
    const k = keyOf(payload);
    if (cb.checked) { if (!map.has(k) && map.size >= max) { cb.checked=false; return; } map.set(k,payload); }
    else { map.delete(k); }
    save(); sync();
  }

  load(); hydrate();

  document.querySelectorAll('.pick-nba').forEach(cb=>cb.addEventListener('change',()=>{ const p=parse(cb); if(p) upsert(selNba,5,p,cb); }));
  document.querySelectorAll('.pick-lbs').forEach(cb=>cb.addEventListener('change',()=>{ const p=parse(cb); if(p) upsert(selLbs,5,p,cb); }));

  clearBtn.addEventListener('click', ()=>{
    document.querySelectorAll('.pick-nba,.pick-lbs').forEach(x=>x.checked=false);
    selNba.clear(); selLbs.clear(); save(); sync();
    compareGrid.innerHTML=''; compareArea.classList.add('hidden');
  });

  const one = v => v==null ? '—' : Number(v).toFixed(1);
  const pct = v => v==null ? '—' : (Number(v)*100).toFixed(1)+'%';

  compareBtn.addEventListener('click', ()=>{
    const sel = [...selNba.values(), ...selLbs.values()];
    if (!sel.length) { compareArea.classList.add('hidden'); return; }

    const lead = (arr,field,high=true)=>{
      const vals = arr.map(x=>Number(x[field]??NaN)).filter(v=>isFinite(v));
      if (!vals.length) return null;
      return high ? Math.max(...vals) : Math.min(...vals);
    };
    const mk = (v,ld,high=true)=>{
      if(v==null||!isFinite(v)) return '<div class="text-xs mt-0.5 text-gray-300">—</div>';
      const d = high ? ((ld-v)/Math.abs(ld||1))*100 : ((v-ld)/Math.abs(ld||1))*100;
      if(Math.abs(d)<.5) return '<div class="text-xs mt-0.5 text-[#84CC16]">Līderis</div>';
      return `<div class="text-xs mt-0.5 text-[#F97316]">-${Math.round(d)}% sal. ar līderi</div>`;
    };

    const LWIN = lead(sel,'win_percent',true);
    const LPPG = lead(sel,'ppg',true);
    const LOPP = lead(sel,'opp_ppg',false);
    const LDIFF= lead(sel,'diff',true);

    compareGrid.innerHTML = sel.map(p=>{
      const logo = p.logo ? `<img src="${p.logo}" class="h-7 w-7 object-contain rounded bg-white p-[2px]" />` : '';
      return `
        <article class="bg-[#0f172a]/60 border border-[#374151] rounded-xl p-4">
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">${logo}<div><div class="text-white font-semibold">${p.team}</div><div class="text-xs text-gray-400">${p.src}</div></div></div>
            <div class="text-xs text-gray-300">Sezona: ${p.season ?? '—'}</div>
          </div>
          <div class="grid grid-cols-4 gap-3 text-sm">
            <div><div class="text-[#F3F4F6]/60 text-xs">Win%</div><div class="font-semibold">${pct(p.win_percent)}</div>${mk(p.win_percent,LWIN,true)}</div>
            <div><div class="text-[#F3F4F6]/60 text-xs">PPG</div><div class="font-semibold">${one(p.ppg)}</div>${mk(p.ppg,LPPG,true)}</div>
            <div><div class="text-[#F3F4F6]/60 text-xs">OPP PPG</div><div class="font-semibold">${one(p.opp_ppg)}</div>${mk(p.opp_ppg,LOPP,false)}</div>
            <div><div class="text-[#F3F4F6]/60 text-xs">Diff</div><div class="font-semibold">${p.diff==null?'—':(p.diff>=0?'+':'')+p.diff}</div>${mk(p.diff,LDIFF,true)}</div>
          </div>
          <div class="mt-3 text-xs text-gray-300">W/L: ${p.wins ?? '—'}–${p.losses ?? '—'}</div>
        </article>`;
    }).join('');

    compareArea.classList.remove('hidden');
  });
})();
</script>
@endpush
@endsection
