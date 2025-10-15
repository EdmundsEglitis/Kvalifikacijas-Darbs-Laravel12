@extends('layouts.nba')
@section('title','NBA — Home')

@push('head')
<style>
  #pageLoader {
    backdrop-filter: blur(3px);
  }
  .spin { animation: spin 1s linear infinite; }
  @keyframes spin { to { transform: rotate(360deg); } }
  .bar {
    position: relative; height: 3px; overflow: hidden; border-radius: 9999px;
    background: rgba(255,255,255,.12);
  }
  .bar::after{
    content:""; position:absolute; inset:0; transform:translateX(-60%);
    animation: loadbar 1.2s cubic-bezier(.22,1,.36,1) infinite;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.8), transparent);
  }
  @keyframes loadbar { 0%{transform:translateX(-60%)} 100%{transform:translateX(160%)} }

  .reveal {
    opacity: 0; transform: translateY(18px) scale(.98);
    filter: saturate(.9);
    transition:
      opacity .6s cubic-bezier(.22,1,.36,1),
      transform .6s cubic-bezier(.22,1,.36,1),
      filter .6s ease;
    will-change: transform, opacity, filter;
  }
  .reveal.is-visible {
    opacity: 1; transform: translateY(0) scale(1);
    filter: saturate(1);
  }
  [data-stagger] > * { opacity: 0; transform: translateY(24px) scale(.98); }
  [data-stagger].is-visible > * {
    animation: rise .7s cubic-bezier(.22,1,.36,1) forwards;
  }
  [data-stagger].is-visible > *:nth-child(1){ animation-delay:.04s }
  [data-stagger].is-visible > *:nth-child(2){ animation-delay:.10s }
  [data-stagger].is-visible > *:nth-child(3){ animation-delay:.16s }
  [data-stagger].is-visible > *:nth-child(4){ animation-delay:.22s }
  [data-stagger].is-visible > *:nth-child(5){ animation-delay:.28s }
  [data-stagger].is-visible > *:nth-child(6){ animation-delay:.34s }
  @keyframes rise { to { opacity:1; transform: none; } }

  .accent-underline {
    position: relative; display:inline-block;
  }
  .accent-underline::after{
    content:""; position:absolute; left:0; right:0; bottom:-6px; height:3px; border-radius:9999px;
    background: linear-gradient(90deg,#84CC16, #22d3ee, #a78bfa);
    filter: drop-shadow(0 2px 6px rgba(132,204,22,.45));
    transform-origin: left;
    transform: scaleX(0);
    transition: transform .6s cubic-bezier(.22,1,.36,1);
  }
  .accent-underline.in-view::after { transform: scaleX(1); }

  .tilt {
    transform: perspective(900px) rotateX(0) rotateY(0) translateY(0);
    transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease, filter .2s ease;
    will-change: transform;
  }
  .tilt:hover {
    transform: perspective(900px) rotateX(2deg) rotateY(.8deg) translateY(-4px);
    box-shadow: 0 18px 50px rgba(0,0,0,.35);
    filter: saturate(1.05);
  }

  .parallax {
    transform: translateY(0);
    will-change: transform;
    transition: transform .12s linear;
  }

  .ring-glow { box-shadow: 0 0 0 0 rgba(132,204,22,.0); transition: box-shadow .25s ease; }
  .ring-glow:hover { box-shadow: 0 0 0 6px rgba(132,204,22,.15); }

  @media (prefers-reduced-motion: reduce) {
    .reveal, [data-stagger] > *, .tilt, .parallax, .accent-underline::after { transition: none !important; animation: none !important; }
  }
</style>
@endpush

@section('content')
  {{-- ===== Loader overlay ===== --}}
  <div id="pageLoader" class="fixed inset-0 z-[999] grid place-items-center bg-[#0B1220] opacity-100 transition-opacity duration-300">
    <div class="w-[220px] max-w-[90vw] text-[#F3F4F6] space-y-3">
      <div class="flex items-center gap-3">
        <div class="h-6 w-6 rounded-full border-2 border-white/20 border-t-white spin"></div>
        <span class="text-sm">Loading NBA Hub…</span>
      </div>
      <div class="bar"></div>
    </div>
  </div>
  <script>
  (function () {
    var el = document.getElementById('pageLoader');
    if (!el) return;
    function hide() {
      if (!el) return;
      el.style.opacity = '0';
      el.style.pointerEvents = 'none';
      var done = false;
      function rm(){ if(done) return; done = true; el.remove(); el=null; }
      el.addEventListener('transitionend', rm, { once: true });
      setTimeout(rm, 900);
    }
    if (document.readyState === 'complete') hide();
    else {
      window.addEventListener('load', hide, { once: true });
      document.addEventListener('DOMContentLoaded', function(){ setTimeout(function(){ if (el) hide(); }, 250); }, { once: true });
    }
  })();
  </script>
  <noscript><style>#pageLoader{display:none!important}</style></noscript>

  <section class="relative -mt-16 w-full h-[64vh] sm:h-[70vh] overflow-hidden">
    <div class="absolute inset-0 parallax"
         id="heroLayer"
         style="
           background-image:
             linear-gradient(to bottom, rgba(0,0,0,.55), rgba(0,0,0,.55)),
             url('{{ asset('storage/hero/3playerSplit_07d.webp') }}');
           background-size: cover;
           background-position: top center;
           background-repeat: no-repeat;">
    </div>
    <div class="relative z-10 max-w-7xl mx-auto h-full flex items-center px-4">
      <div class="space-y-6 reveal">
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white drop-shadow">
          NBA Hub
        </h1>
        <p class="text-[#F3F4F6]/90 max-w-xl">
          Players, teams, standings, schedules, and deep dive comparisons—built on your NBA data.
        </p>
        <div class="flex flex-wrap gap-3">
          <a href="{{ route('nba.players') }}"
             class="px-6 py-3 rounded-full bg-[#84CC16] text-[#111827] font-semibold hover:bg-[#a6e23a] transition tilt">
            Explore Players
          </a>
          <a href="{{ route('nba.teams') }}"
             class="px-6 py-3 rounded-full bg-white/10 text-white border border-white/20 hover:bg-white/20 transition tilt">
            Browse Teams
          </a>
        </div>
      </div>
    </div>
  </section>

  <div class="max-w-7xl mx-auto px-4 space-y-16 pt-10">

    <section class="reveal" data-stagger>
      <h2 class="text-2xl font-bold text-white mb-4">
        <span class="accent-underline">Quick Navigation</span>
      </h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <a href="{{ route('nba.games.upcoming') }}"
           class="group rounded-2xl p-6 bg-[#0f172a] border border-[#1f2937]/70 hover:border-[#84CC16] transition shadow tilt">
          <div class="text-sm text-[#9CA3AF]">Games</div>
          <div class="mt-2 text-2xl font-bold text-white">Upcoming</div>
          <div class="mt-3 text-[#F3F4F6]/80">See tonight’s slate and what’s next.</div>
          <div class="mt-4 text-[#84CC16] font-semibold">Open →</div>
        </a>

        <a href="{{ route('nba.standings.explorer') }}"
           class="group rounded-2xl p-6 bg-[#0f172a] border border-[#1f2937]/70 hover:border-[#84CC16] transition shadow tilt">
          <div class="text-sm text-[#9CA3AF]">Standings</div>
          <div class="mt-2 text-2xl font-bold text-white">Explorer</div>
          <div class="mt-3 text-[#F3F4F6]/80">Compare teams across seasons and metrics.</div>
          <div class="mt-4 text-[#84CC16] font-semibold">Open →</div>
        </a>

        <a href="{{ route('nba.compare') }}"
           class="group rounded-2xl p-6 bg-[#0f172a] border border-[#1f2937]/70 hover:border-[#84CC16] transition shadow tilt">
          <div class="text-sm text-[#9CA3AF]">Players</div>
          <div class="mt-2 text-2xl font-bold text-white">Compare</div>
          <div class="mt-3 text-[#F3F4F6]/80">Side-by-side season summaries.</div>
          <div class="mt-4 text-[#84CC16] font-semibold">Open →</div>
        </a>

        <a href="{{ route('nba.teams') }}"
           class="group rounded-2xl p-6 bg-[#0f172a] border border-[#1f2937]/70 hover:border-[#84CC16] transition shadow tilt">
          <div class="text-sm text-[#9CA3AF]">Teams</div>
          <div class="mt-2 text-2xl font-bold text-white">Directory</div>
          <div class="mt-3 text-[#F3F4F6]/80">Logos, rosters, and schedule.</div>
          <div class="mt-4 text-[#84CC16] font-semibold">Open →</div>
        </a>
      </div>
    </section>

    @if($upcomingGames->isNotEmpty())
      <section class="reveal">
        <h2 class="text-2xl font-bold text-white mb-4">
          <span class="accent-underline">Upcoming Games</span>
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" data-stagger>
          @foreach($upcomingGames as $g)
            <article class="bg-[#0f172a] border border-[#1f2937]/70 rounded-2xl p-5 shadow hover:shadow-xl transition tilt">
              <div class="text-sm text-[#9CA3AF]">
                {{ \Carbon\Carbon::parse($g->tipoff)->format('M d, Y H:i') }}
              </div>

              <div class="mt-3 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2 min-w-0">
                  <div class="h-8 w-8 rounded bg-white/90 grid place-items-center overflow-hidden p-1 ring-glow">
                    @if($g->home_team_logo)
                      <img src="{{ $g->home_team_logo }}" class="h-full w-full object-contain" alt="">
                    @endif
                  </div>
                  <div class="truncate">{{ $g->home_team_name ?? 'Home' }}</div>
                </div>

                <div class="text-[#9CA3AF]">vs</div>

                <div class="flex items-center gap-2 min-w-0 justify-end">
                  <div class="h-8 w-8 rounded bg-white/90 grid place-items-center overflow-hidden p-1 ring-glow">
                    @if($g->away_team_logo)
                      <img src="{{ $g->away_team_logo }}" class="h-full w-full object-contain" alt="">
                    @endif
                  </div>
                  <div class="truncate text-right">{{ $g->away_team_name ?? 'Away' }}</div>
                </div>
              </div>

              <a href="{{ route('nba.games.upcoming') }}"
                 class="mt-4 inline-flex items-center justify-center w-full px-4 py-2 rounded-lg bg-[#84CC16] text-[#111827] font-semibold hover:bg-[#a3e635] transition tilt">
                view all upcomming games
              </a>
            </article>
          @endforeach
        </div>
      </section>
    @endif

    @if($topPpg->isNotEmpty())
      <section class="reveal">
        <h2 class="text-2xl font-bold text-white mb-4">
          <span class="accent-underline">Top Scorers ({{ date('Y') }})</span>
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" data-stagger>
          @foreach($topPpg as $row)
            <a href="{{ route('nba.player.show', $row->player_external_id) }}"
               class="group block bg-[#0f172a] border border-[#1f2937]/70 rounded-2xl p-5 shadow
                      transition transform hover:-translate-y-0.5 hover:shadow-2xl
                      hover:border-[#84CC16]/60 focus:outline-none focus-visible:ring-2
                      focus-visible:ring-[#84CC16]/40 tilt">
              <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-white/10 grid place-items-center overflow-hidden ring-glow">
                  @if(!empty($row->player_photo))
                    <img src="{{ $row->player_photo }}" alt="" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105">
                  @endif
                </div>
                <div>
                  <span class="block font-semibold text-white transition-colors group-hover:text-[#84CC16]">
                  </span>
                  <div class="text-xs text-[#9CA3AF]">{{ $row->g }} games</div>
                </div>
              </div>

              <div class="mt-4 flex items-end gap-2">
                <div class="text-3xl font-extrabold text-[#84CC16] transition-transform duration-300 group-hover:-translate-y-0.5">
                  {{ number_format($row->ppg,1) }}
                </div>
                <div class="text-xs text-[#9CA3AF] mb-1">PPG</div>
              </div>
            </a>
          @endforeach
        </div>
      </section>
    @endif

    @if($standings->isNotEmpty())
      <section class="reveal">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-2xl font-bold text-white accent-underline">Standings Snapshot ({{ $latestSeason }})</h2>
          <a href="{{ route('nba.standings.explorer') }}" class="text-[#84CC16] font-medium hover:underline">Open Explorer →</a>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-[#1f2937]/70 shadow">
          <table class="min-w-[720px] w-full">
            <thead class="bg-[#0f172a] text-[#F3F4F6]/70 text-xs uppercase">
              <tr>
                <th class="px-4 py-3 text-left">Team</th>
                <th class="px-4 py-3 text-right">W</th>
                <th class="px-4 py-3 text-right">L</th>
                <th class="px-4 py-3 text-right">Win%</th>
                <th class="px-4 py-3 text-right">PPG</th>
                <th class="px-4 py-3 text-right">OPP PPG</th>
                <th class="px-4 py-3 text-right">Diff</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#1f2937] bg-[#0b1220]">
              @foreach($standings as $s)
                <tr class="group cursor-pointer transition hover:bg-[#0f172a] focus-within:bg-[#0f172a]" data-href="{{ route('nba.team.show', $s->team_id) }}" tabindex="0">
                  <td class="relative px-4 py-3">
                    <span class="pointer-events-none absolute inset-y-0 left-0 w-1 rounded-r bg-[#84CC16]/70 opacity-0 group-hover:opacity-100 transition"></span>
                    <div class="flex items-center gap-3">
                      <span class="relative h-8 w-8 rounded bg-white/95 grid place-items-center overflow-hidden p-1 ring-glow">
                        @if($s->team_logo)
                          <img src="{{ $s->team_logo }}" class="h-full w-full object-contain transition-transform duration-300 group-hover:scale-105" alt="">
                        @endif
                      </span>
                      <a href="{{ route('nba.team.show', $s->team_id) }}" class="font-medium text-white transition-colors group-hover:text-[#84CC16]">
                        {{ $s->team_name }}
                      </a>
                      <svg class="ml-auto opacity-0 -translate-x-1 transition-all duration-200 ease-out group-hover:opacity-100 group-hover:translate-x-0" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M9 18l6-6-6-6" stroke="#a3e635" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </div>
                  </td>
                  <td class="px-4 py-3 text-right tabular-nums">{{ $s->wins }}</td>
                  <td class="px-4 py-3 text-right tabular-nums">{{ $s->losses }}</td>
                  <td class="px-4 py-3 text-right tabular-nums">
                    @if(!is_null($s->win_percent))
                      {{ number_format($s->win_percent * 100, 1) }}%
                    @else
                      —
                    @endif
                  </td>
                  <td class="px-4 py-3 text-right tabular-nums">{{ $s->avg_points_for !== null ? number_format($s->avg_points_for,1) : '—' }}</td>
                  <td class="px-4 py-3 text-right tabular-nums">{{ $s->avg_points_against !== null ? number_format($s->avg_points_against,1) : '—' }}</td>
                  <td class="px-4 py-3 text-right tabular-nums">
                    <span class="{{ ($s->point_differential ?? 0) >= 0 ? 'text-[#84CC16]' : 'text-[#F97316]' }}">
                      {{ $s->point_differential >= 0 ? '+' : '' }}{{ $s->point_differential ?? '—' }}
                    </span>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </section>
    @endif

    @if($teams->isNotEmpty())
      <section class="reveal">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-2xl font-bold text-white accent-underline">Featured Teams</h2>
          <a href="{{ route('nba.teams') }}" class="text-[#84CC16] font-medium hover:underline">Redzēt visas →</a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-5" data-stagger>
          @foreach($teams as $t)
            @php
              $logo = $t->logo;
              if ($logo && strpos($logo, 'combiner/i?img=') !== false) {
                $logo = preg_replace('/w=\d+/', 'w=200', $logo);
                $logo = preg_replace('/h=\d+/', 'h=200', $logo);
                $logo = str_replace('scale=crop', 'scale=fit', $logo);
              }
            @endphp

            <a href="{{ route('nba.team.show', $t->external_id) }}"
               class="group rounded-2xl p-4 bg-[#0f172a] border border-[#1f2937]/70 hover:border-[#84CC16] transition text-center shadow tilt">
              <div class="h-20 sm:h-24 w-full rounded bg-white/95 flex items-center justify-center ring-glow">
                @if($logo)
                  <img src="{{ $logo }}" alt="{{ $t->name }} logo" class="block max-h-16 sm:max-h-20 w-auto object-contain">
                @endif
              </div>
              <div class="mt-3 text-sm font-semibold text-white group-hover:text-[#84CC16] truncate">{{ $t->name }}</div>
            </a>
          @endforeach
        </div>
      </section>
    @endif

    <footer class="py-10 text-center text-sm text-[#F3F4F6]/60">&copy; {{ date('Y') }} NBA Hub.</footer>
  </div>
@endsection

@push('scripts')
<script>
  document.querySelectorAll('tr[data-href]').forEach(row => {
    row.addEventListener('click', () => {
      const href = row.getAttribute('data-href');
      if (href) window.location.href = href;
    });
    row.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        const href = row.getAttribute('data-href');
        if (href) window.location.href = href;
      }
    });
  });

  (function(){
    const layer = document.getElementById('heroLayer');
    if (!layer) return;
    let lastY = window.scrollY;
    const onScroll = () => {
      const y = window.scrollY;
      const t = Math.min(60, Math.max(-60, (y * 0.05)));
      layer.style.transform = `translateY(${t}px)`;
      lastY = y;
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  })();

  (function(){
    const reveals = document.querySelectorAll('.reveal, [data-stagger]');
    const headings = document.querySelectorAll('.accent-underline');
    if (!('IntersectionObserver' in window)) {
      reveals.forEach(el => el.classList.add('is-visible'));
      headings.forEach(h => h.classList.add('in-view'));
      return;
    }
    const obs = new IntersectionObserver((entries,o) => {
      entries.forEach(e => {
        if (e.isIntersecting){
          e.target.classList.add('is-visible');
          if (e.target.matches('.accent-underline')) {
            e.target.classList.add('in-view');
          }
          o.unobserve(e.target);
        }
      });
    }, { rootMargin: '0px 0px -10% 0px', threshold: 0.12 });

    reveals.forEach(el => obs.observe(el));
    headings.forEach(h => obs.observe(h));
  })();
</script>
@endpush
