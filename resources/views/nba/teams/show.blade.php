
@extends('layouts.nba')
@section('title','Team show')

@section('content')
  <main class="pt-24 max-w-7xl mx-auto px-4 space-y-10">

    <section class="bg-[#1f2937] rounded-xl p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center gap-4">
      <div class="flex items-center gap-4">
        @if($team->logo)
          <img src="{{ $team->logo }}" alt="{{ $team->name }}" class="h-16 w-16 sm:h-20 sm:w-20 object-contain" loading="lazy">
        @else
          <div class="h-16 w-16 sm:h-20 sm:w-20 bg-[#0b1220] rounded grid place-items-center text-xs text-gray-400">No Logo</div>
        @endif

        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-white leading-tight">
            {{ $team->name }}
          </h1>
          <p class="text-gray-400">Abbreviation: {{ $team->abbreviation ?? '—' }}</p>
        </div>
      </div>

      @if($team->url)
        <div class="sm:ml-auto">
          <a href="{{ $team->url }}" target="_blank"
             class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-[#84CC16] text-[#111827] font-semibold hover:bg-[#a3e635] transition">
            Skatīt iekš ESPN
          </a>
        </div>
      @endif
    </section>

    {{-- Sticky section nav --}}
<nav id="sectionNav"
     class="sticky top-16 z-20 bg-transparent backdrop-blur border-t border-white/10">
  <div class="max-w-7xl mx-auto px-4">
    <ul class="flex flex-wrap gap-2 py-2">
      @php
        $tabs = [
          ['#roster','Sastāvs'],
          ['#upcoming','Tuvākās spēles'],
          ['#seasons','Sezonas'],
          ['#legend','Statistikas skaidrojums'],
          ['#past-games','Pagājušās spēles'],
        ];
      @endphp
      @foreach($tabs as [$href,$label])
        <li>
          <a href="{{ $href }}"
             class="section-link inline-flex items-center gap-2 px-3 sm:px-4 py-2 rounded-full
                    text-sm font-medium text-gray-200 hover:text-white
                    bg-white/5 hover:bg-white/10 border border-white/10
                    transition">
            {{ $label }}
          </a>
        </li>
      @endforeach
    </ul>
  </div>
</nav>


    <section id="roster" class="scroll-mt-28">
      <h2 class="text-xl sm:text-2xl font-semibold mb-4 text-white">Sastāvs</h2>

      <div class="grid gap-4 sm:gap-5 [grid-template-columns:repeat(auto-fit,minmax(150px,1fr))]">
        @forelse($players as $player)
          <a href="{{ route('nba.player.show', $player->external_id) }}"
             class="bg-[#1f2937] rounded-xl p-4 flex flex-col items-center hover:bg-[#374151] transition">
            @if($player->image)
              <img src="{{ $player->image }}" alt="{{ $player->full_name }}"
                   class="h-16 w-16 rounded-full mb-2 object-cover ring-2 ring-[#84CC16]" loading="lazy">
            @else
              <div class="h-16 w-16 rounded-full mb-2 grid place-items-center bg-gray-700 text-gray-400 text-xs">Nav bildes</div>
            @endif
            <h3 class="text-sm font-semibold text-gray-200 text-center line-clamp-2">
              {{ $player->full_name }}
            </h3>
          </a>
        @empty
          <p class="text-gray-400">Nav atrasti spēlētāji</p>
        @endforelse
      </div>
    </section>

    
<section id="upcoming" class="scroll-mt-28">
    <h2 class="text-xl sm:text-2xl font-semibold mb-4 text-white">Tuvākās spēles</h2>
<div class="overflow-x-auto bg-[#1f2937] rounded-xl border border-[#374151]">
  <table class="min-w-full text-left text-sm">
    <thead class="bg-[#0f172a] text-gray-400">
      <tr>
        <th class="px-4 py-2">Datums</th>
        <th class="px-4 py-2">Mājās</th>
        <th class="px-4 py-2">Izbraukumā</th>
        <th class="px-4 py-2">Notikuma vieta</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-[#374151] text-[#F3F4F6]">
      @forelse($upcomingGames as $game)
        <tr class="odd:bg-[#1f2937] even:bg-[#111827] hover:bg-[#374151] transition">
          <td class="px-4 py-2 whitespace-nowrap">
            {{ $game->tipoff ? \Carbon\Carbon::parse($game->tipoff)->format('M d, H:i') : '—' }}
          </td>
          <td class="px-4 py-2">
            <a href="{{ route('nba.team.show', $game->home_team_id) }}" class="flex items-center gap-2 hover:text-[#84CC16]">
              @if($game->home_team_logo)
                <img src="{{ $game->home_team_logo }}" class="h-6 w-6" loading="lazy">
              @endif
              <span class="truncate">{{ $game->home_team_name }}</span>
            </a>
          </td>
          <td class="px-4 py-2">
            <a href="{{ route('nba.team.show', $game->away_team_id) }}" class="flex items-center gap-2 hover:text-[#84CC16]">
              @if($game->away_team_logo)
                <img src="{{ $game->away_team_logo }}" class="h-6 w-6" loading="lazy">
              @endif
              <span class="truncate">{{ $game->away_team_name }}</span>
            </a>
          </td>
          <td class="px-4 py-2">
            <span class="block truncate">{{ $game->venue }} @if($game->city) — {{ $game->city }} @endif</span>
          </td>
        </tr>
      @empty
        <tr><td colspan="4" class="px-4 py-3 text-gray-400">Neviena spēle nav tuvumā</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<p class="mt-2 text-xs text-gray-400 sm:hidden">Ieteikums: šo tabulu var pašķirstīt uz sāniem ja skatieties no telefona.</p>

    </section>

    <section id="seasons" class="scroll-mt-28">
      <h2 class="text-xl sm:text-2xl font-semibold mb-4 text-white">Sezonas (2021 →)</h2>

      @if($standingsHistory->isEmpty())
        <div class="bg-[#1f2937] rounded-xl p-6 text-gray-400">
          Nav atrasta statistikas vēsture.
        </div>
      @else
        <div class="grid gap-5 [grid-template-columns:repeat(auto-fit,minmax(260px,1fr))]">
          @foreach($standingsHistory as $row)
            @php
              $record = ($row->wins ?? 0).'–'.($row->losses ?? 0);
              $winPct = $row->win_percent !== null ? number_format($row->win_percent, 3) : '—';
              $ppg    = $row->avg_points_for !== null ? number_format($row->avg_points_for, 1) : '—';
              $opp    = $row->avg_points_against !== null ? number_format($row->avg_points_against, 1) : '—';
              $diff   = $row->point_differential;
              $diffTxt = $diff === null ? '—' : ($diff >= 0 ? "+$diff" : (string)$diff);
              $diffClass = $diff === null ? 'text-gray-300' : ($diff >= 0 ? 'text-[#84CC16]' : 'text-[#F97316]');
              $seed  = $row->playoff_seed ?? '—';
              $gb    = $row->games_behind ?? '—';
              $home  = $row->home_record ?? '—';
              $road  = $row->road_record ?? '—';
              $l10   = $row->last_ten ?? '—';
              $clin  = $row->clincher; // e.g. *, z, x (can be null)
              $streakBadge = null;
              if (is_int($row->streak)) {
                $streakBadge = $row->streak > 0 ? "W{$row->streak}" : ($row->streak < 0 ? "L".abs($row->streak) : "—");
              }
            @endphp

            <article class="group bg-[#1f2937] border border-[#374151] rounded-2xl shadow transition hover:-translate-y-0.5 hover:shadow-xl p-5">
              {{-- Header --}}
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                  <span class="inline-flex items-center gap-2">
                    @if(!empty($team->logo))
                      <img src="{{ $team->logo }}" class="h-5 w-5 object-contain" alt="{{ $team->name }}" loading="lazy">
                    @endif
                    <span class="text-white font-semibold">{{ $row->season }}</span>
                  </span>
                  @if($clin)
                    <span class="ml-2 text-[10px] px-2 py-0.5 rounded-full bg-white/10 text-white uppercase tracking-wide">
                      {{ $clin }}
                    </span>
                  @endif
                </div>

                @if($streakBadge)
                  <span class="text-[10px] px-2 py-0.5 rounded-full
                               {{ str_starts_with($streakBadge,'W') ? 'bg-[#84CC16]/20 text-[#84CC16]' : 'bg-[#F97316]/20 text-[#F97316]' }}">
                    {{ $streakBadge }}
                  </span>
                @endif
              </div>

              <div class="flex items-end justify-between mb-4 gap-3">
                <div>
                  <div class="text-xs text-[#F3F4F6]/70">Rekords</div>
                  <div class="text-2xl font-extrabold text-white">{{ $record }}</div>
                  <div class="text-xs text-[#F3F4F6]/60">Uzvaras%: {{ $winPct }}</div>
                </div>
                <div class="text-right">
                  <div class="text-xs text-[#F3F4F6]/70">Sniegums</div>
                  <div class="text-xl font-bold text-white">{{ $seed }}</div>
                  <div class="text-xs text-[#F3F4F6]/60">SK: {{ $gb }}</div>
                </div>
              </div>

              <dl class="grid grid-cols-3 gap-3">
                <div class="rounded-xl bg-[#0f172a]/40 border border-[#374151] p-3 text-center">
                  <dt class="text-[11px] text-[#F3F4F6]/60">PPG</dt>
                  <dd class="text-lg font-bold text-white">{{ $ppg }}</dd>
                </div>
                <div class="rounded-xl bg-[#0f172a]/40 border border-[#374151] p-3 text-center">
                  <dt class="text-[11px] text-[#F3F4F6]/60">Prenieku PPG</dt>
                  <dd class="text-lg font-bold text-white">{{ $opp }}</dd>
                </div>
                <div class="rounded-xl bg-[#0f172a]/40 border border-[#374151] p-3 text-center">
                  <dt class="text-[11px] text-[#F3F4F6]/60">Starpība</dt>
                  <dd class="text-lg font-bold {{ $diffClass }}">{{ $diffTxt }}</dd>
                </div>
              </dl>

              <div class="mt-4 flex flex-wrap gap-2 text-[11px] sm:text-xs">
                <span class="px-2.5 py-1 rounded-full bg-white/5 text-[#F3F4F6]/80 border border-white/10">Mājās: {{ $home }}</span>
                <span class="px-2.5 py-1 rounded-full bg-white/5 text-[#F3F4F6]/80 border border-white/10">izbraukumā: {{ $road }}</span>
                <span class="px-2.5 py-1 rounded-full bg-white/5 text-[#F3F4F6]/80 border border-white/10">P10: {{ $l10 }}</span>
              </div>
            </article>
          @endforeach
        </div>
      @endif
    </section>

    <section id="legend" class="scroll-mt-28 pb-10">
      <h2 class="text-xl sm:text-2xl font-semibold mb-4 text-white">Statistikas izskaidrojumi</h2>

      <div class="grid gap-3 sm:gap-4 [grid-template-columns:repeat(auto-fit,minmax(180px,1fr))]">
        <div class="bg-[#1f2937] border border-[#374151] rounded-xl p-3">
          <div class="text-sm font-semibold text-white mb-1">Rkords</div>
          <p class="text-xs text-gray-300">Uzvaras pret zaudēm atiecīgajā sezonā</p>
        </div>
        <div class="bg-[#1f2937] border border-[#374151] rounded-xl p-3">
          <div class="text-sm font-semibold text-white mb-1">Uzvaras%</div>
          <p class="text-xs text-gray-300">Uzvaras koeficents (uzvaras ÷ zaudējumi).</p>
        </div>
        <div class="bg-[#1f2937] border border-[#374151] rounded-xl p-3">
          <div class="text-sm font-semibold text-white mb-1">Sniegums</div>
          <p class="text-xs text-gray-300">Pašreizējais/paliekošais playoff Sniegums.</p>
        </div>
        <div class="bg-[#1f2937] border border-[#374151] rounded-xl p-3">
          <div class="text-sm font-semibold text-white mb-1">SK</div>
          <p class="text-xs text-gray-300">Cik ļoti šī komanda atpaliek kopējā spēļu skaitā salīdzinot ar komandu kura ir izspēlējusi visvairāk spēļu.</p>
        </div>
        <div class="bg-[#1f2937] border border-[#374151] rounded-xl p-3">
          <div class="text-sm font-semibold text-white mb-1">PPG</div>
          <p class="text-xs text-gray-300">Vidējais punktu skaits spēlē.</p>
        </div>
        <div class="bg-[#1f2937] border border-[#374151] rounded-xl p-3">
          <div class="text-sm font-semibold text-white mb-1">Pretinieku PPG</div>
          <p class="text-xs text-gray-300">Vidējie pieļautie punkti spēlē.</p>
        </div>
        <div class="bg-[#1f2937] border border-[#374151] rounded-xl p-3">
          <div class="text-sm font-semibold text-white mb-1">Starpība</div>
          <p class="text-xs text-gray-300">Punktu pārsvars (cik punktus iemet un cik pieļauj).</p>
        </div>
        <div class="bg-[#1f2937] border border-[#374151] rounded-xl p-3">
          <div class="text-sm font-semibold text-white mb-1">Mājās / Izbraukumā</div>
          <p class="text-xs text-gray-300">Uzvaras–zaudējumu statistika mājas spēlēs un izbraukuma spēlēs.</p>
        </div>
        <div class="bg-[#1f2937] border border-[#374151] rounded-xl p-3">
          <div class="text-sm font-semibold text-white mb-1">P10</div>
          <p class="text-xs text-gray-300">Uzvaras–zaudējumu statistika pēdējās 10 spēlēs.</p>
        </div>
        <div class="bg-[#1f2937] border border-[#374151] rounded-xl p-3">
          <div class="text-sm font-semibold text-white mb-1">Uzvaras vai zaudējumi pēc kārtas</div>
          <p class="text-xs text-gray-300">Pašreizējā par uzvarētām spēlēm pēc kārtas vai zaudētām spēlēm pēc kārtas apzīmēts ar W/L (piemēram W8).</p>
        </div>
      </div>
    </section>

    {{-- Past games (from logs) --}}
    <section id="past-games" class="scroll-mt-28">
  <h2 class="text-xl sm:text-2xl font-semibold mb-4 text-white">Azivadītās spēles</h2>

  @if(($pastGames ?? collect())->isEmpty())
    <div class="bg-[#1f2937] rounded-xl p-6 text-gray-400">
      Nav atrastas aizvadītās spēles
    </div>
  @else
    <div class="overflow-x-auto bg-[#1f2937] rounded-xl border border-[#374151]">
      <table class="min-w-full text-left text-sm">
        <thead class="bg-[#0f172a] text-gray-400">
          <tr>
            <th class="px-4 py-2">Datums</th>
            <th class="px-4 py-2">Pretinieks</th>
            <th class="px-4 py-2">Rezultāts</th>
            <th class="px-4 py-2">Punkti</th>
            <th class="px-4 py-2 text-right">Detalizēts skats</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#374151] text-[#F3F4F6]">
          @foreach($pastGames as $g)
            <tr class="odd:bg-[#1f2937] even:bg-[#111827] hover:bg-[#374151] transition">
              <td class="px-4 py-2 whitespace-nowrap">
                {{ \Carbon\Carbon::parse($g->game_date)->format('M d, Y') }}
              </td>
              <td class="px-4 py-2">
                <div class="flex items-center gap-2">
                  @if(!empty($g->opponent_logo))
                    <img src="{{ $g->opponent_logo }}" class="h-6 w-6 object-contain rounded bg-white p-[2px]" loading="lazy">
                  @endif
                  <span class="truncate">{{ $g->opponent_name ?? '—' }}</span>
                </div>
              </td>
              <td class="px-4 py-2">
                @php $r = strtoupper((string)($g->result ?? '')); @endphp
                <span class="{{ $r==='W' ? 'text-[#84CC16]' : ($r==='L' ? 'text-[#F97316]' : 'text-gray-300') }}">
                  {{ $r ?: '—' }}
                </span>
              </td>
              <td class="px-4 py-2">{{ $g->score ?? '—' }}</td>
              <td class="px-4 py-2 text-right">
                <a href="{{ route('nba.games.show', ['eventId' => $g->event_id]) }}"
                   class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20">
                  Skatīt detalizētāk
                </a>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <p class="mt-2 text-xs text-gray-400 sm:hidden">Ieteikums: šo tabulu var pašķirstīt uz sāniem ja skatieties no telefona.</p>
  @endif
</section>


  </main>
</body>
</html>

<script>
  // Smooth-scroll with slight offset (handled by scroll-mt*)
  document.querySelectorAll('a.section-link[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
      const id = a.getAttribute('href');
      const target = document.querySelector(id);
      if (!target) return;
      e.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      history.replaceState(null, '', id); // update hash without jump
    });
  });

  // Active state on scroll
  (function() {
    const links = Array.from(document.querySelectorAll('a.section-link[href^="#"]'));
    const map   = new Map(links.map(l => [l.getAttribute('href'), l]));
    const io = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        const id = '#'+entry.target.id;
        const link = map.get(id);
        if (!link) return;
        if (entry.isIntersecting) {
          links.forEach(x => x.classList.remove('ring-2','ring-[#84CC16]','text-white','bg-white/10'));
          link.classList.add('ring-2','ring-[#84CC16]','text-white','bg-white/10');
        }
      });
    }, { rootMargin: '-45% 0px -45% 0px', threshold: 0.01 });

    ['roster','upcoming','past-games','seasons','legend']
      .forEach(id => { const el = document.getElementById(id); if (el) io.observe(el); });
  })();

  // Optional: tiny shadow when the nav sticks
  (function() {
    const nav = document.getElementById('sectionNav');
    if (!nav) return;
    const onScroll = () => {
      const stuck = nav.getBoundingClientRect().top <= 16; // 16px (top-16)
      nav.classList.toggle('shadow-lg', stuck);
      nav.classList.toggle('shadow-black/30', stuck);
    };
    onScroll();
    document.addEventListener('scroll', onScroll, { passive: true });
  })();
</script>

@endsection