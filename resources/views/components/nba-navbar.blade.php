@props(['class' => ''])

@php
  $uid    = uniqid();
  $menuId = "nba-mobile-menu-{$uid}";
  $btnId  = "nba-mobile-btn-{$uid}";

  $link = function (string $name, string $label, ?string $active = null) {
      // exact route match only (NBA)
      $isActive = request()->routeIs($active ?? $name);
      // LBS-style link colors + NBA accent
      $textCls  = $isActive ? 'text-[#84CC16]' : 'text-[#F3F4F6]/90 hover:text-[#84CC16]';

      return ''
        . '<a href="'.e(route($name)).'" class="relative font-medium transition group '.$textCls.'">'
        .   e($label)
        .   '<span class="pointer-events-none absolute left-0 -bottom-1 h-[2px] w-0 bg-[#84CC16] transition-all group-hover:w-full '.($isActive ? 'w-full' : '').'"></span>'
        . '</a>';
  };
@endphp

{{-- LBS wrapper styles: width + smooth color transitions --}}
<nav {{ $attributes->merge(['class' => "w-full transition-colors duration-300 $class"]) }} data-navbar-root="{{ $uid }}">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    {{-- LBS header layout: justify-between, items-center, h-16 --}}
    <div class="flex justify-between items-center h-16">
      <div class="flex items-center gap-4">
        <a href="{{ route('home') }}" class="inline-flex items-center rounded focus:outline-none focus:ring-2 focus:ring-[#84CC16]/60">
          <img src="{{ asset('home-icon-silhouette-svgrepo-com.svg') }}" alt="Home" class="h-8 w-8 filter invert hover:opacity-80 transition" />
        </a>
        <a href="{{ route('nba.home') }}" class="inline-flex items-center rounded focus:outline-none focus:ring-2 focus:ring-[#84CC16]/60">
          {{-- keep NBA logo; LBS uses rounded image, so we add a subtle rounding to echo that style --}}
          <img src="{{ asset('nba-logo-black-transparent.png') }}" alt="NBA Logo" class="h-10 w-auto drop-shadow-lg rounded" />
        </a>
      </div>

      {{-- LBS desktop menu spacing (no text-sm wrapper; font-weight on links) --}}
      <div class="hidden md:flex items-center gap-8">
        {!! $link('nba.players', 'Players') !!}
        {!! $link('nba.games.upcoming', 'Upcoming Games', 'nba.games.upcoming') !!}
        @if (Route::has('nba.games.all'))
          {!! $link('nba.games.all', 'All Games', 'nba.games.all') !!}
        @endif
        {!! $link('nba.teams', 'Teams') !!}
        {!! $link('nba.standings.explorer', 'Compare teams') !!}
        {!! $link('nba.compare', 'Compare players') !!}
      </div>

      {{-- LBS-style mobile toggle button (kept NBA IDs) --}}
      <button
        id="{{ $btnId }}"
        type="button"
        data-mobile-btn
        data-target="{{ $menuId }}"
        aria-controls="{{ $menuId }}"
        aria-expanded="false"
        aria-label="Toggle menu"
        class="md:hidden inline-flex items-center justify-center rounded p-1 focus:outline-none focus:ring-2 focus:ring-[#84CC16]/60">
        <img src="{{ asset('burger-menu-svgrepo-com.svg') }}" alt="Menu" class="h-8 w-8 filter invert hover:opacity-80 transition" />
      </button>
    </div>
  </div>

  {{-- LBS mobile panel styles: simple, transparent bg, inner spacing --}}
  <div id="{{ $menuId }}" class="hidden md:hidden bg-transparent">
    <div class="px-4 py-3 space-y-2">
      <a href="{{ route('nba.players') }}" class="block rounded px-3 py-2 font-medium transition text-[#F3F4F6]/90 hover:text-[#111827] hover:bg-[#84CC16]">
        SPĒLĒTĀJI
      </a>
      <a href="{{ route('nba.games.upcoming') }}" class="block rounded px-3 py-2 font-medium transition text-[#F3F4F6]/90 hover:text-[#111827] hover:bg-[#84CC16]">
        TUVĀKĀS SPĒLES
      </a>
      @if(Route::has('nba.games.all'))
        <a href="{{ route('nba.games.all') }}" class="block rounded px-3 py-2 font-medium transition text-[#F3F4F6]/90 hover:text-[#111827] hover:bg-[#84CC16]">
          VISAS SPĒLES
        </a>
      @endif
      <a href="{{ route('nba.teams') }}" class="block rounded px-3 py-2 font-medium transition text-[#F3F4F6]/90 hover:text-[#111827] hover:bg-[#84CC16]">
        KOMANDAS
      </a>
      <a href="{{ route('nba.standings.explorer') }}" class="block rounded px-3 py-2 font-medium transition text-[#F3F4F6]/90 hover:text-[#111827] hover:bg-[#84CC16]">
        SALĪDZINĀT KOMANDAS
      </a>
      <a href="{{ route('nba.compare') }}" class="block rounded px-3 py-2 font-medium transition text-[#F3F4F6]/90 hover:text-[#111827] hover:bg-[#84CC16]">
        SALĪDZINĀT SPĒLĒTĀJUS
      </a>
    </div>
  </div>
</nav>

<script>

  (function () {
    const btn  = document.getElementById(@json($btnId));
    const menu = document.getElementById(@json($menuId));
    if (!btn || !menu) return;

    function openMenu() {
      menu.classList.remove('hidden');
      menu.classList.add('block'); 
      btn.setAttribute('aria-expanded', 'true');

      setTimeout(() => {
        document.addEventListener('click', onAway, { capture: false });
        document.addEventListener('touchend', onAway, { capture: false });
        document.addEventListener('keydown', onKey);
      }, 0);
    }

    function closeMenu() {
      menu.classList.add('hidden');
      menu.classList.remove('block');
      btn.setAttribute('aria-expanded', 'false');
      document.removeEventListener('click', onAway, { capture: false });
      document.removeEventListener('touchend', onAway, { capture: false });
      document.removeEventListener('keydown', onKey);
    }

    function toggleMenu() {
      menu.classList.contains('hidden') ? openMenu() : closeMenu();
    }

    const onKey  = (e) => { if (e.key === 'Escape') closeMenu(); };
    const onAway = (e) => {
      const t = e.target;
      if (btn.contains(t) || menu.contains(t)) return;
      closeMenu();
    };

    const handle = (e) => { e.preventDefault(); toggleMenu(); };
    btn.addEventListener('click', handle);
    btn.addEventListener('touchend', handle);


    const mq = window.matchMedia('(min-width: 768px)');
    mq.addEventListener?.('change', (e) => { if (e.matches) closeMenu(); });
  })();
</script>
