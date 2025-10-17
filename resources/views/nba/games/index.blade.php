@extends('layouts.nba')
@section('title','Gaidāmās spēles')

@section('content')
<main class="max-w-7xl mx-auto px-4 pt-10 pb-10 space-y-6">
  <header class="space-y-1">
    <h1 class="text-2xl sm:text-3xl font-bold">Gaidāmās NBA spēles</h1>
    <p class="text-sm text-gray-400">Laiki rādīti jūsu vietējā laika joslā.</p>
  </header>

  @if($games->count() > 0)
    <div id="gamesGrid" class="grid gap-4 sm:gap-5 grid-cols-1 sm:grid-cols-2 xl:grid-cols-3">
      @foreach($games as $i => $game)
        @php
          $tip = $game->tipoff ? \Carbon\Carbon::parse($game->tipoff) : null;
          $iso = $tip ? $tip->toIso8601String() : null;
        @endphp

        <article
          class="group bg-[#1f2937] border border-[#374151] rounded-2xl p-4 sm:p-5 shadow-sm hover:shadow-xl hover:-translate-y-0.5 transition duration-200"
          style="animation-delay: {{ ($i%12)*25 }}ms"
          data-iso="{{ $iso ?? '' }}"
          data-home="{{ $game->home_team_name ?? 'Mājinieki' }}"
          data-away="{{ $game->away_team_name ?? 'Viesi' }}"
          data-venue="{{ $game->venue ?? '' }}"
          data-city="{{ $game->city ?? '' }}"
        >
          <div class="mb-4">
            <div class="flex items-center gap-2 text-xs text-gray-300 whitespace-nowrap">
              <span class="inline-flex items-center gap-1">
                <svg class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor"><path d="M6 2a1 1 0 011 1v1h6V3a1 1 0 112 0v1h1a2 2 0 012 2v9a2 2 0 01-2 2H3a2 2 0 01-2-2V6a2 2 0 012-2h1V3a1 1 0 112 0v1zm-3 5v8h14V7H3z"/></svg>
                {{ $tip ? $tip->format('d.m.Y · H:i') : 'Tiks precizēts' }}
              </span>
              @if($tip)
                <span class="text-[#84CC16]" data-countdown="{{ $iso }}"></span>
              @endif
            </div>
            <div class="text-xs text-gray-400 truncate mt-1">
              {{ $game->venue ?? 'Arēna nav zināma' }}{{ $game->city ? ' · '.$game->city : '' }}
            </div>
          </div>

          <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-3">
            <a href="{{ route('nba.team.show', $game->home_team_id) }}"
               class="flex items-center gap-2 min-w-0 hover:opacity-90 transition"
               aria-label="Atvērt {{ $game->home_team_name }} komandas lapu">
              @if($game->home_team_logo)
                <img src="{{ $game->home_team_logo }}"
                     class="h-9 w-9 object-contain rounded bg-white p-[2px] flex-shrink-0"
                     alt="{{ $game->home_team_name }} emblēma">
              @else
                <div class="h-9 w-9 rounded bg-white/10 flex-shrink-0"></div>
              @endif
              <div class="min-w-0">
                <div class="font-semibold text-[#84CC16] truncate">{{ $game->home_team_name ?? 'Mājinieki' }}</div>
                <div class="text-[10px] text-gray-400">MĀJINIEKI</div>
              </div>
            </a>

            <div class="text-gray-400 font-semibold select-none px-1">pret</div>

            <a href="{{ route('nba.team.show', $game->away_team_id) }}"
               class="flex items-center gap-2 min-w-0 justify-end hover:opacity-90 transition"
               aria-label="Atvērt {{ $game->away_team_name }} komandas lapu">
              <div class="min-w-0 text-right">
                <div class="font-semibold text-[#84CC16] truncate">{{ $game->away_team_name ?? 'Viesi' }}</div>
                <div class="text-[10px] text-gray-400">VIESI</div>
              </div>
              @if($game->away_team_logo)
                <img src="{{ $game->away_team_logo }}"
                     class="h-9 w-9 object-contain rounded bg-white p-[2px] flex-shrink-0"
                     alt="{{ $game->away_team_name }} emblēma">
              @else
                <div class="h-9 w-9 rounded bg-white/10 flex-shrink-0"></div>
              @endif
            </a>
          </div>

          <div class="mt-4 flex justify-center">
            <button
              type="button"
              class="inline-flex items-center gap-2 rounded-xl bg-[#84CC16] text-[#111827] hover:bg-[#a3e635] px-4 py-2 font-semibold transition focus:outline-none focus:ring-2 focus:ring-[#84CC16]/40"
              onclick="openGCal(this)"
              {{ $tip ? '' : 'disabled' }}
            >
              Pievienot atgādinājumu
            </button>
          </div>
        </article>
      @endforeach
    </div>
  @endif
</main>
@endsection

@push('scripts')
<script>
  (function () {
    const nodes = Array.from(document.querySelectorAll('[data-countdown]'));
    if (!nodes.length) return;
    function tick() {
      const now = Date.now();
      nodes.forEach(n => {
        const iso = n.getAttribute('data-countdown');
        const t = new Date(iso).getTime();
        if (isNaN(t)) { n.textContent = ''; return; }
        const diff = t - now;
        if (diff <= 0) { n.textContent = 'sākas'; return; }
        const hours = Math.floor(diff / 36e5);
        n.textContent = `pēc ${hours}h`;
      });
    }
    tick();
    const iv = setInterval(tick, 60_000);
    addEventListener('beforeunload', () => clearInterval(iv));
  })();

  function openGCal(btn) {
    const card = btn.closest('article');
    if (!card) return;
    const iso   = card.getAttribute('data-iso');
    if (!iso) return;
    const home  = card.getAttribute('data-home') || 'Mājinieki';
    const away  = card.getAttribute('data-away') || 'Viesi';
    const venue = card.getAttribute('data-venue') || '';
    const city  = card.getAttribute('data-city')  || '';
    const start = new Date(iso);
    if (isNaN(start)) return;
    const end   = new Date(start.getTime() + 2 * 60 * 60 * 1000);
    const fmt = d => d.toISOString().replace(/[-:]/g,'').replace(/\.\d{3}Z$/,'Z');
    const title = `${home} pret ${away}`;
    const location = [venue, city].filter(Boolean).join(' · ');
    const details  = 'Pievienots no jūsu NBA kalendāra skata.';
    const url =
      'https://calendar.google.com/calendar/render?action=TEMPLATE'
      + '&text='     + encodeURIComponent(title)
      + '&dates='    + fmt(start) + '/' + fmt(end)
      + '&details='  + encodeURIComponent(details)
      + '&location=' + encodeURIComponent(location);
    window.open(url, '_blank', 'noopener');
    btn.classList.add('scale-95');
    setTimeout(() => btn.classList.remove('scale-95'), 120);
  }
</script>
@endpush
