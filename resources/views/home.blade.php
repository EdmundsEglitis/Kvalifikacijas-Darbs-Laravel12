<!DOCTYPE html>
<html lang="lv">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Basketbola Portāls</title>
  <script src="https://cdn.tailwindcss.com"></script>

  <style>
    :root { --ease: cubic-bezier(.22,.9,.26,1); }

    .loader-enter { opacity: 1; }
    .loader-exit  { opacity: 0; transition: opacity .45s var(--ease); pointer-events:none; }

    .spin {
      animation: spin 1s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    .fade-up { opacity: 0; transform: translateY(10px); animation: fadeup .45s var(--ease) forwards; }
    @keyframes fadeup { to { opacity: 1; transform: none; } }

    .reveal { opacity: 0; transform: translateY(12px); transition: opacity .5s var(--ease), transform .5s var(--ease); will-change: transform, opacity; }
    .reveal.aos-in { opacity: 1; transform: none; }

    .stagger > * { opacity: 0; transform: translateY(10px); transition: opacity .5s var(--ease), transform .5s var(--ease); }
    .stagger.aos-in > * { opacity: 1; transform: none; }
    .stagger.aos-in > *:nth-child(1)  { transition-delay: .04s; }
    .stagger.aos-in > *:nth-child(2)  { transition-delay: .08s; }
    .stagger.aos-in > *:nth-child(3)  { transition-delay: .12s; }
    .stagger.aos-in > *:nth-child(4)  { transition-delay: .16s; }
    .stagger.aos-in > *:nth-child(5)  { transition-delay: .20s; }
    .stagger.aos-in > *:nth-child(6)  { transition-delay: .24s; }

    .img-fade { opacity: 0; transition: opacity .35s var(--ease); }
    .img-fade.loaded { opacity: 1; }

    .shimmer {
      background: linear-gradient(90deg, rgba(255,255,255,0.05) 25%, rgba(255,255,255,0.12) 37%, rgba(255,255,255,0.05) 63%);
      background-size: 400% 100%;
      animation: shimmer 1.4s infinite;
    }
    @keyframes shimmer { from { background-position: -200% 0; } to { background-position: 200% 0; } }

    @media (prefers-reduced-motion: reduce) {
      .fade-up, .reveal, .stagger > *, .img-fade { animation: none !important; transition: none !important; opacity: 1 !important; transform: none !important; }
      .spin { animation: none !important; }
    }
  </style>
</head>
<body class="bg-[#0B1220] text-[#F3F4F6] min-h-screen">

  <div id="pageLoader" class="fixed inset-0 z-50 grid place-items-center bg-[#0B1220] loader-enter">
    <div class="flex items-center gap-3">
      <div class="h-8 w-8 rounded-full border-2 border-white/20 border-t-white spin"></div>
      <div class="h-3 w-28 rounded shimmer"></div>
    </div>
  </div>

  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14 space-y-10">

    <header class="space-y-2 fade-up" style="animation-delay:60ms">
      <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">Izvēlies sadaļu</h1>
      <p class="text-sm text-gray-400">Ātrā piekļuve NBA un LBS informācijai</p>
    </header>

    <section class="grid grid-cols-1 sm:grid-cols-2 gap-5 reveal" data-aos data-aos-delay="100">
      <a href="{{ route('nba.home') }}"
         class="bg-white/5 border border-white/10 rounded-2xl p-6 hover:bg-white/10 transition block">
        <div class="flex items-center gap-4">
          <img src="{{ asset('nba-logo-png-transparent.png') }}" class="w-10 h-10 img-fade" alt="" onload="this.classList.add('loaded')">
          <div>
            <h2 class="text-2xl font-semibold text-white">NBA</h2>
            <p class="text-gray-400">Amerikas basketbola līga</p>
          </div>
        </div>

        @if($nba)
          <div class="mt-5 rounded-xl bg-[#0f172a] border border-[#1f2937] p-4 reveal" data-aos>
            <div class="text-xs text-gray-400 mb-2">
              Pēdējā spēle — {{ \Carbon\Carbon::parse($nba['date'])->format('Y-m-d') }}
            </div>
            <div class="flex items-center justify-between gap-3">
              <div class="flex items-center gap-2 min-w-0">
                @if($nba['team1']['logo'])
                  <img src="{{ $nba['team1']['logo'] }}" class="h-7 w-7 object-contain rounded bg-white p-[2px] img-fade" onload="this.classList.add('loaded')" />
                @endif
                <div class="truncate">{{ $nba['team1']['name'] }}</div>
              </div>
              <div class="text-xl font-bold tabular-nums">
                {{ $nba['score1'] }}–{{ $nba['score2'] }}
              </div>
              <div class="flex items-center gap-2 min-w-0 justify-end">
                @if($nba['team2']['logo'])
                  <img src="{{ $nba['team2']['logo'] }}" class="h-7 w-7 object-contain rounded bg-white p-[2px] img-fade" onload="this.classList.add('loaded')" />
                @endif
                <div class="truncate text-right">{{ $nba['team2']['name'] }}</div>
              </div>
            </div>
          </div>
        @else
          <p class="mt-4 text-sm text-gray-400">Pēdējās spēles informācija tiks pievienota.</p>
        @endif
      </a>

      <a href="{{ route('lbs.home') }}"
         class="bg-white/5 border border-white/10 rounded-2xl p-6 hover:bg-white/10 transition block">
        <div class="flex items-center gap-4">
          <img src="{{ asset('415986933_1338154883529529_7481933183149808416_n.jpg') }}" class="w-10 h-10 rounded img-fade" alt="" onload="this.classList.add('loaded')">
          <div>
            <h2 class="text-2xl font-semibold text-white">LBS</h2>
            <p class="text-gray-400">Latvijas Basketbola Savienība</p>
          </div>
        </div>

        @if($lbs)
          <div class="mt-5 rounded-xl bg-[#0f172a] border border-[#1f2937] p-4 reveal" data-aos>
            <div class="text-xs text-gray-400 mb-1">
              Pēdējā spēle — {{ \Carbon\Carbon::parse($lbs['date'])->format('Y-m-d') }}
            </div>
            <div class="text-[11px] text-gray-500 mb-3">
              Līga: <span class="text-gray-300">{{ $lbs['league'] }}</span>
            </div>

            <div class="flex items-center justify-between gap-3">
              <div class="flex items-center gap-2 min-w-0">
                @if($lbs['team1']['logo'])
                  <img src="{{ $lbs['team1']['logo'] }}" class="h-7 w-7 object-contain rounded bg-white p-[2px] img-fade" onload="this.classList.add('loaded')" />
                @endif
                <div class="truncate">{{ $lbs['team1']['name'] }}</div>
              </div>
              <div class="text-xl font-bold tabular-nums">
                {{ $lbs['score1'] }}–{{ $lbs['score2'] }}
              </div>
              <div class="flex items-center gap-2 min-w-0 justify-end">
                @if($lbs['team2']['logo'])
                  <img src="{{ $lbs['team2']['logo'] }}" class="h-7 w-7 object-contain rounded bg-white p-[2px] img-fade" onload="this.classList.add('loaded')" />
                @endif
                <div class="truncate text-right">{{ $lbs['team2']['name'] }}</div>
              </div>
            </div>
          </div>
        @else
          <p class="mt-4 text-sm text-gray-400">Pēdējās spēles informācija tiks pievienota.</p>
        @endif
      </a>
    </section>

    <div class="reveal" data-aos data-aos-delay="80">
  <div class="flex flex-col sm:flex-row gap-4">
    <a href="{{ route('compare.nba-lbs') }}"
       class="group inline-flex items-center gap-2 px-5 py-3 rounded-full 
              bg-gradient-to-b from-[#9AE735] to-[#84CC16] text-[#0B1220] font-semibold
              shadow-[0_8px_20px_rgba(132,204,22,0.25)] ring-1 ring-black/10
              hover:from-[#aef255] hover:to-[#93e234]
              hover:shadow-[0_10px_28px_rgba(132,204,22,0.35)]
              active:translate-y-[1px] transition">
      <span class="text-lg"></span>
      <span>Salīdzināt NBA vs LBS spēlētājus</span>
      <span class="ml-1 opacity-80 group-hover:opacity-100 transition"></span>
    </a>

    <a href="{{ route('compare.nba-lbs-teams') }}"
       class="group inline-flex items-center gap-2 px-5 py-3 rounded-full 
              bg-gradient-to-b from-[#9AE735] to-[#84CC16] text-[#0B1220] font-semibold
              shadow-[0_8px_20px_rgba(132,204,22,0.25)] ring-1 ring-black/10
              hover:from-[#aef255] hover:to-[#93e234]
              hover:shadow-[0_10px_28px_rgba(132,204,22,0.35)]
              active:translate-y-[1px] transition">
      <span class="text-lg"></span>
      <span>Salīdzināt NBA vs LBS komandas</span>
      <span class="ml-1 opacity-80 group-hover:opacity-100 transition"></span>
    </a>
  </div>
</div>


    <section id="news" class="py-16 bg-[#111827]">
      <div class="max-w-7xl mx-auto px-4 space-y-12">
        <h2 class="text-3xl font-bold text-white text-center reveal" data-aos>
          Jaunākās Ziņas
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 stagger" data-aos>
          @foreach(['secondary-1','secondary-2'] as $slot)
            @if(!empty($bySlot[$slot]))
              @php($item = $bySlot[$slot])
              <article
                class="group bg-[#0f172a] rounded-2xl overflow-hidden shadow-lg border border-[#1f2937]/60 flex flex-col hover:shadow-2xl transition">
                <div class="relative w-full h-[260px] bg-[#0b1220]">
                  @if(!empty($item->preview_image))
                    <img
                      loading="lazy"
                      src="{{ $item->preview_image }}"
                      alt="{{ $item->title }}"
                      class="absolute inset-0 m-auto max-h-full max-w-full object-contain img-fade"
                      onload="this.classList.add('loaded')"
                    />
                  @endif
                  <div class="absolute inset-0 bg-gradient-to-t from-[#0b1220] via-transparent to-transparent"></div>
                </div>

                <div class="p-6 flex flex-col flex-1">
                  <h3 class="text-2xl font-semibold text-white mb-2 line-clamp-2">
                    {{ $item->title }}
                  </h3>
                  <p class="flex-1 text-[#F3F4F6]/90 line-clamp-3">{{ $item->excerpt }}</p>
                  <div class="mt-4 flex items-center justify-between">
                    <time class="text-sm text-[#F3F4F6]/60">
                      {{ optional($item->created_at)->format('Y-m-d') }}
                    </time>
                    <a href="{{ route('lbs.news.show', $item->id) }}"
                      class="inline-flex items-center gap-2 text-[#84CC16] font-medium hover:underline text-2xl">
                      Lasīt vairāk <span>→</span>
                    </a>
                  </div>
                </div>
              </article>
            @endif
          @endforeach
        </div>
      </div>
    </section>

    @if($bestOverall && $worstOverall)
      <section class="reveal" data-aos data-aos-delay="120">
        <h3 class="text-xl font-semibold mb-3">🏆 Labākais vs sliktākais (kopējais rādītājs)</h3>
        <div class="bg-[#111827] border border-[#1f2937] rounded-2xl p-4 sm:p-6">
          <div class="grid grid-cols-1 sm:grid-cols-3 items-center gap-6">
            {{-- Best overall --}}
            <div class="flex items-center gap-4">
              <a
                href="{{ isset($bestOverall->player_id)
                          ? route('nba.player.show', $bestOverall->player_id)
                          : (isset($bestOverall->id) ? route('nba.player.show', $bestOverall->id) : '#') }}"
                class="shrink-0"
              >
                @if(!empty($bestOverall->headshot))
                  <img src="{{ $bestOverall->headshot }}" class="h-14 w-14 rounded-full object-cover ring-1 ring-white/10 img-fade" onload="this.classList.add('loaded')" alt="">
                @else
                  <div class="h-14 w-14 rounded-full bg-white/10"></div>
                @endif
              </a>
              <div>
                <div class="text-xs text-gray-400">Vispārīgi labākais</div>
                <a
                  href="{{ isset($bestOverall->player_id)
                            ? route('nba.player.show', $bestOverall->player_id)
                            : (isset($bestOverall->id) ? route('nba.player.show', $bestOverall->id) : '#') }}"
                  class="font-semibold hover:text-[#84CC16]"
                >
                  {{ $bestOverall->name }}
                </a>
                <div class="text-sm text-gray-400">{{ $bestOverall->team }}</div>
                <div class="text-[#84CC16] font-bold">{{ number_format($bestOverall->overall, 1) }}</div>
                <div class="text-[11px] text-gray-400">PTS + 2*REB + 2*AST + 1.5*STL + 1.5*BLK − 1.5*TOV</div>
              </div>
            </div>

            <div class="hidden sm:flex items-center justify-center">
              <span class="px-3 py-1 rounded-full bg-white/10 border border-white/10 text-sm">VS</span>
            </div>

            <div class="flex items-center gap-4 sm:justify-end">
              <a
                href="{{ isset($worstOverall->player_id)
                          ? route('nba.player.show', $worstOverall->player_id)
                          : (isset($worstOverall->id) ? route('nba.player.show', $worstOverall->id) : '#') }}"
                class="shrink-0"
              >
                @if(!empty($worstOverall->headshot))
                  <img src="{{ $worstOverall->headshot }}" class="h-14 w-14 rounded-full object-cover ring-1 ring-white/10 img-fade" onload="this.classList.add('loaded')" alt="">
                @else
                  <div class="h-14 w-14 rounded-full bg-white/10"></div>
                @endif
              </a>
              <div class="text-right">
                <div class="text-xs text-gray-400">Vispārīgi sliktākais</div>
                <a
                  href="{{ isset($worstOverall->player_id)
                            ? route('nba.player.show', $worstOverall->player_id)
                            : (isset($worstOverall->id) ? route('nba.player.show', $worstOverall->id) : '#') }}"
                  class="font-semibold hover:text-[#84CC16]"
                >
                  {{ $worstOverall->name }}
                </a>
                <div class="text-sm text-gray-400">{{ $worstOverall->team }}</div>
                <div class="text-[#F97316] font-bold">{{ number_format($worstOverall->overall, 1) }}</div>
              </div>
            </div>
          </div>
          <div class="mt-3 text-xs text-gray-400">* Vismaz 10 spēles {{ date('Y') }}. sezonā.</div>
        </div>
      </section>
    @endif

  </main>

  <script>
    window.addEventListener('load', () => {
      const el = document.getElementById('pageLoader');
      if (!el) return;
      el.classList.add('loader-exit');
      el.addEventListener('transitionend', () => el.remove(), { once: true });
    });

    (function () {
      const prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      const $reveal = document.querySelectorAll('[data-aos], .reveal, .stagger');

      if (prefersReduced || !('IntersectionObserver' in window)) {
        $reveal.forEach(el => el.classList.add('aos-in'));
        return;
      }

      const obs = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('aos-in');
            obs.unobserve(entry.target);
          }
        });
      }, { rootMargin: '0px 0px -10% 0px', threshold: 0.12 });

      $reveal.forEach(el => {
        const d = el.getAttribute('data-aos-delay');
        if (d) el.style.transitionDelay = `${parseInt(d, 10)}ms`;
        obs.observe(el);
      });
    })();

    document.querySelectorAll('img[loading="lazy"], .img-fade').forEach(img => {
      if (img.complete) img.classList.add('loaded');
      else img.addEventListener('load', () => img.classList.add('loaded'), { once: true });
    });
  </script>
</body>
</html>
