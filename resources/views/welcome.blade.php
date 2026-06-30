@extends('layouts.app')

@push('styles')
<style>
    .lp-mesh {
        background:
            radial-gradient(40rem 28rem at 82% -8%, color-mix(in srgb, var(--color-primary) 16%, transparent), transparent 70%),
            radial-gradient(34rem 24rem at -6% 12%, rgba(200,169,106,0.22), transparent 70%),
            #f7f3ec;
    }
    .lp-card { transition: transform .4s cubic-bezier(.2,.7,.2,1), box-shadow .4s ease, border-color .4s ease; }
    .lp-card:hover { transform: translateY(-6px); box-shadow: 0 28px 60px -28px rgba(43,33,27,.35); border-color: rgba(200,169,106,.55); }
    .lp-cta { transition: transform .3s cubic-bezier(.2,.7,.2,1), box-shadow .3s ease; }
    .lp-cta:hover { transform: translateY(-2px); box-shadow: 0 18px 40px -16px color-mix(in srgb, var(--color-primary) 60%, transparent); }
    .lp-arch { border-radius: 999px 999px 0 0; }
</style>
@endpush

@section('content')
<div class="bg-[#f7f3ec] text-[#2b211b]">

    <!-- ===== HERO ===== -->
    <section class="lp-mesh relative isolate overflow-hidden">
        <div aria-hidden="true" class="ft-noise pointer-events-none absolute inset-0 opacity-[0.05]"></div>

        <div class="relative mx-auto max-w-7xl px-6 pb-24 pt-24 lg:px-8 lg:pt-32">
            <div class="grid grid-cols-1 items-center gap-14 lg:grid-cols-12">
                <!-- copy -->
                <div class="lg:col-span-7">
                    <p class="ft-rise text-[0.72rem] font-medium uppercase tracking-[0.35em] text-[#c8a96a]" style="animation-delay:.05s">
                        Weddings &middot; Corporate &middot; Celebrations
                    </p>

                    <h1 class="ft-rise font-display mt-6 text-5xl font-light leading-[1.02] text-[#1a1512] sm:text-6xl lg:text-7xl" style="animation-delay:.12s">
                        Create <span class="italic text-[#9a7b3f]">unforgettable</span><br>moments.
                    </h1>

                    <p class="ft-rise mt-7 max-w-xl text-lg leading-relaxed text-[#5c5246]" style="animation-delay:.2s">
                        Partnering with Sri Lanka&rsquo;s leading hotels across Colombo, Kandy, Galle &amp; Dambulla &mdash; refined event management for weddings, corporate gatherings, and the celebrations that matter most.
                    </p>

                    <div class="ft-rise mt-10 flex flex-wrap items-center gap-5" style="animation-delay:.28s">
                        <a href="{{ route('venues.index') }}"
                            class="lp-cta inline-flex items-center gap-3 rounded-full px-7 py-3.5 text-sm font-semibold text-white shadow-lg shadow-[#2b211b]/15"
                            style="background-color: var(--color-primary);">
                            Explore Venues
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </a>
                        <a href="{{ route('contact') }}" class="group inline-flex items-center gap-2 text-sm font-semibold text-[#2b211b]">
                            Talk to our team
                            <span class="transition-transform duration-300 group-hover:translate-x-1" aria-hidden="true">&rarr;</span>
                        </a>
                    </div>
                </div>

                <!-- hero artwork -->
                <div class="ft-rise lg:col-span-5" style="animation-delay:.34s">
                    <div class="relative mx-auto max-w-sm lg:max-w-none">
                        <div aria-hidden="true" class="lp-arch pointer-events-none absolute -right-6 -top-6 hidden h-full w-full border border-[#c8a96a]/35 lg:block"></div>
                        <img src="{{ asset('images/hero-event.svg') }}" alt="An elegant ballroom set for a celebration"
                             class="lp-arch relative w-full shadow-2xl shadow-[#2b211b]/25 ring-1 ring-[#2b211b]/10">
                    </div>
                </div>
            </div>

            <!-- stats -->
            @php
                $stats = [
                    [($hotelCount ?? 8).'', 'Partner hotels'],
                    [($hallCount ?? 24).'', 'Reception halls'],
                    ['4.9', 'Average couple rating'],
                ];
            @endphp
            <dl class="ft-rise mt-20 grid max-w-3xl grid-cols-1 divide-y divide-[#2b211b]/10 border-t border-[#2b211b]/10 sm:grid-cols-3 sm:divide-x sm:divide-y-0" style="animation-delay:.42s">
                @foreach($stats as [$num,$label])
                    <div class="px-0 py-6 sm:px-8 sm:py-4 sm:first:pl-0">
                        <dt class="font-display text-4xl font-light text-[#1a1512]">{{ $num }}</dt>
                        <dd class="mt-1 text-sm text-[#7a6f61]">{{ $label }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    <!-- ===== FEATURES ===== -->
    <section class="relative mx-auto max-w-7xl px-6 py-24 lg:px-8 lg:py-32">
        <div class="max-w-2xl">
            <p class="text-[0.72rem] font-medium uppercase tracking-[0.3em] text-[#c8a96a]">Everything you need</p>
            <h2 class="font-display mt-4 text-4xl font-light leading-tight text-[#1a1512] sm:text-5xl">
                Plan your <span class="italic">perfect</span> event
            </h2>
            <p class="mt-5 text-lg leading-relaxed text-[#5c5246]">
                From intimate gatherings to grand celebrations, every detail is considered &mdash; so your day unfolds exactly as imagined.
            </p>
        </div>

        <div class="mt-16 grid grid-cols-1 gap-6 lg:grid-cols-3">
            @php
                $features = [
                    ['01','Beautiful Venues','Choose from a curated collection of venues, each suited to a distinct scale and style.',
                     'M9.674 2.075a.75.75 0 01.652 0l7.25 3.5A.75.75 0 0117 6.957V16.5h.25a.75.75 0 110 1.5H2.75a.75.75 0 110-1.5H3V6.957a.75.75 0 01-.576-1.382l7.25-3.5z'],
                    ['02','Expert Planning','An experienced team handles every detail, from vendor coordination to day-of execution.',
                     'M10 9a3 3 0 100-6 3 3 0 000 6zM6 8a2 2 0 11-4 0 2 2 0 014 0zM1.49 15.326a.78.78 0 01-.358-.442 3 3 0 014.308-3.516 6.484 6.484 0 00-1.905 3.959c-.023.222-.014.442.025.654a4.97 4.97 0 01-2.07-.655zM16.44 15.98a4.97 4.97 0 002.07-.654.78.78 0 00.357-.442 3 3 0 00-4.308-3.517 6.484 6.484 0 011.907 3.96 2.32 2.32 0 01-.026.654zM18 8a2 2 0 11-4 0 2 2 0 014 0zM5.304 16.19a.844.844 0 01-.277-.71 5 5 0 019.947 0 .843.843 0 01-.277.71A6.975 6.975 0 0110 18a6.974 6.974 0 01-4.696-1.81z'],
                    ['03','All-Inclusive Packages','Customizable packages spanning catering, decor, entertainment and beyond.',
                     'M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z'],
                ];
            @endphp
            @foreach($features as [$n,$heading,$body,$path])
                <article class="lp-card group relative overflow-hidden rounded-2xl border border-[#2b211b]/10 bg-[#fffdf9] p-8">
                    <span aria-hidden="true" class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-[#c8a96a] to-transparent"></span>
                    <div class="flex items-center justify-between">
                        <span class="grid h-12 w-12 place-items-center rounded-full bg-[#f3ece0] text-[#9a7b3f]" style="color: var(--color-primary);">
                            <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="{{ $path }}" clip-rule="evenodd" /></svg>
                        </span>
                        <span class="font-display text-2xl font-light text-[#d8cdb9]">{{ $n }}</span>
                    </div>
                    <h3 class="font-display mt-7 text-2xl font-normal text-[#1a1512]">{{ $heading }}</h3>
                    <p class="mt-3 text-[15px] leading-relaxed text-[#5c5246]">{{ $body }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <!-- ===== FEATURED VENUES ===== -->
    @if(!empty($featuredVenues) && count($featuredVenues))
    <section class="relative bg-[#fffdf9]">
        <div class="mx-auto max-w-7xl px-6 py-24 lg:px-8 lg:py-28">
            <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                <div class="max-w-2xl">
                    <p class="text-[0.72rem] font-medium uppercase tracking-[0.3em] text-[#c8a96a]">Sri Lanka&rsquo;s leading hotels</p>
                    <h2 class="font-display mt-4 text-4xl font-light leading-tight text-[#1a1512] sm:text-5xl">
                        Featured <span class="italic">venues</span>
                    </h2>
                    <p class="mt-5 text-lg leading-relaxed text-[#5c5246]">
                        Iconic ballrooms and reception halls from the hotels we partner with island-wide.
                    </p>
                </div>
                <a href="{{ route('venues.index') }}" class="group inline-flex shrink-0 items-center gap-2 text-sm font-semibold text-[#9a7b3f]">
                    View all venues
                    <span class="transition-transform duration-300 group-hover:translate-x-1" aria-hidden="true">&rarr;</span>
                </a>
            </div>

            <div class="mt-14 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($featuredVenues as $venue)
                    <a href="{{ route('venues.show', $venue) }}"
                       class="lp-card group flex flex-col overflow-hidden rounded-2xl border border-[#2b211b]/10 bg-white">
                        <div class="relative aspect-[4/3] overflow-hidden bg-[#f3ece0]">
                            @if($venue->images && count($venue->images))
                                <img src="{{ $venue->images[0] }}" alt="{{ $venue->name }}"
                                     class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                            @endif
                        </div>
                        <div class="flex flex-1 flex-col p-5">
                            <h3 class="font-display text-lg font-normal leading-snug text-[#1a1512]">{{ $venue->name }}</h3>
                            <div class="mt-3 flex items-center justify-between text-sm">
                                <span class="text-[#7a6f61]">
                                    @if($venue->capacity_max)Up to {{ number_format($venue->capacity_max) }} guests @endif
                                </span>
                            </div>
                            @if($venue->base_price)
                                <p class="mt-1 text-sm font-semibold text-[#1a1512]">From Rs {{ number_format($venue->base_price) }}</p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- ===== QUOTE BAND (bridges into footer) ===== -->
    <section class="relative isolate overflow-hidden bg-[#1a1512] text-[#cdbfae]">
        <div aria-hidden="true" class="ft-noise pointer-events-none absolute inset-0 opacity-[0.04] mix-blend-screen"></div>
        <div aria-hidden="true" class="pointer-events-none absolute left-1/2 top-0 h-64 w-[40rem] -translate-x-1/2 rounded-full blur-3xl" style="background: radial-gradient(closest-side, color-mix(in srgb, var(--color-primary) 20%, transparent), transparent);"></div>

        <div class="relative mx-auto max-w-4xl px-6 py-28 text-center lg:px-8">
            <span class="font-display text-6xl leading-none text-[#c8a96a]">&ldquo;</span>
            <blockquote class="font-display -mt-6 text-3xl font-light leading-snug text-[#f5efe7] sm:text-4xl">
                They turned our vision into a day we&rsquo;ll never forget &mdash; every detail, considered, every moment, effortless.
            </blockquote>
            <p class="mt-8 text-[0.72rem] font-medium uppercase tracking-[0.3em] text-[#c8a96a]">A recent couple &middot; Spring wedding</p>
        </div>
    </section>

</div>
@endsection
