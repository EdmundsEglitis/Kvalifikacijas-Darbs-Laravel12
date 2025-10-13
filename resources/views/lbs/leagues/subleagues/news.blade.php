@extends('layouts.app')
@section('title', $subLeague->name . ' – Jaunumi')

@push('head')
  <style>.fade-in-section{transition:opacity .6s ease-out, transform .6s ease-out;}</style>
@endpush

@section('subnav')
  <x-lbs-subnav :subLeague="$subLeague" />
@endsection

@section('content')
  @if(!empty($heroImage))
    <section
      id="hero"
      class="relative w-full h-[60vh] sm:h-[70vh] lg:h-[80vh] bg-fixed bg-cover bg-center"
      style="background-image: url('{{ Storage::url($heroImage->image_path) }}');"
    >
      <div class="absolute inset-0 bg-black/60"></div>
      <div class="relative z-10 flex h-full items-center justify-center px-6 text-center">
        <div class="max-w-3xl space-y-6 fade-in-section opacity-0 translate-y-6">
          @if($heroImage->title)
            <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-extrabold text-white drop-shadow-lg">
              {{ $heroImage->title }}
            </h1>
          @else
            <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-extrabold text-white drop-shadow-lg">
              {{ $subLeague->name }}
            </h1>
          @endif
          <a href="#news"
             class="inline-block mt-4 px-8 py-3 rounded-full bg-[#84CC16] text-[#111827]
                    font-semibold uppercase tracking-wide hover:bg-[#a6e23a] transition">
            Skatīt jaunākās ziņas
          </a>
        </div>
      </div>
    </section>
  @endif

  <section id="news" class="py-16 bg-[#111827]">
  <div class="max-w-7xl mx-auto px-4 space-y-12">
    <h2 class="text-3xl font-bold text-white text-center reveal" data-aos>
      {{ $subLeague->name }} – Jaunākās ziņas
    </h2>

    {{-- Big pair (secondary-1 / secondary-2) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 stagger" data-aos>
      @foreach(['secondary-1','secondary-2'] as $slot)
        @if(($bySlot[$slot] ?? null))
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

              @if(!empty($item->excerpt))
                <p class="flex-1 text-[#F3F4F6]/90 line-clamp-3">
                  {{ $item->excerpt }}
                </p>
              @endif

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


    {{-- Empty state --}}
    @if(empty($bySlot['secondary-1']) && empty($bySlot['secondary-2'])
        && empty($bySlot['slot-1']) && empty($bySlot['slot-2']) && empty($bySlot['slot-3']))
      <p class="text-center text-[#F3F4F6]/70">Šeit šobrīd nav jaunumu.</p>
    @endif
  </div>
</section>

  <footer class="py-8 bg-[#111827] text-[#F3F4F6]/70 text-center text-sm fade-in-section opacity-0 translate-y-6">
    &copy; {{ date('Y') }} LBS. Visas tiesības aizsargātas.
  </footer>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const els = document.querySelectorAll('.fade-in-section');
    if (!('IntersectionObserver' in window)) {
      els.forEach(el => { el.style.opacity = 1; el.style.transform = 'none'; });
      return;
    }
    const obs = new IntersectionObserver((entries) => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          e.target.classList.remove('opacity-0','translate-y-6');
          e.target.classList.add('opacity-100','translate-y-0');
          obs.unobserve(e.target);
        }
      });
    }, { threshold: 0.15 });
    els.forEach(el => obs.observe(el));
  });
</script>
@endpush
