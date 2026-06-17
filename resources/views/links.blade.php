@extends('layouts.app')

@push('styles')
<style>
    .lk-mesh {
        background:
            radial-gradient(36rem 24rem at 88% -10%, color-mix(in srgb, var(--color-primary) 14%, transparent), transparent 70%),
            radial-gradient(30rem 22rem at -8% 8%, rgba(200,169,106,0.20), transparent 70%),
            #f7f3ec;
    }
    .lk-card { transition: transform .35s cubic-bezier(.2,.7,.2,1), box-shadow .35s ease, border-color .35s ease; }
    .lk-card:hover { transform: translateY(-5px); box-shadow: 0 24px 50px -26px rgba(43,33,27,.35); border-color: rgba(200,169,106,.55); }
</style>
@endpush

@section('content')
@php
    // Each user-facing public page, listed separately.
    $links = [
        ['label' => 'Home',           'desc' => 'Our main site and what we do.',            'route' => 'home',           'tag' => 'Overview'],
        ['label' => 'Venues',         'desc' => 'Browse every event space we offer.',       'route' => 'venues.index',   'tag' => 'Explore'],
        ['label' => 'Packages',       'desc' => 'Curated packages for every celebration.',   'route' => 'packages.index', 'tag' => 'Explore'],
        ['label' => 'Contact',        'desc' => 'Send an inquiry or talk to our team.',      'route' => 'contact',        'tag' => 'Get in touch'],
        ['label' => 'Sign In',        'desc' => 'Access your account and client portal.',    'route' => 'login',          'tag' => 'Account'],
        ['label' => 'Create Account', 'desc' => 'Start your client portal in a minute.',     'route' => 'register',       'tag' => 'Account'],
    ];
@endphp

<section class="lk-mesh relative isolate overflow-hidden">
    <div aria-hidden="true" class="ft-noise pointer-events-none absolute inset-0 opacity-[0.05]"></div>

    <div class="relative mx-auto max-w-5xl px-6 py-20 lg:px-8 lg:py-28">
        <div class="max-w-2xl">
            <p class="ft-rise text-[0.72rem] font-medium uppercase tracking-[0.35em] text-[#c8a96a]" style="animation-delay:.05s">
                Everything, one place
            </p>
            <h1 class="ft-rise font-display mt-5 text-4xl font-light leading-[1.05] text-[#1a1512] sm:text-5xl" style="animation-delay:.12s">
                Explore <span class="italic text-[#9a7b3f]">EventPro</span>
            </h1>
            <p class="ft-rise mt-5 text-lg leading-relaxed text-[#5c5246]" style="animation-delay:.2s">
                Every page you can visit, listed separately so you can jump straight to what you need.
            </p>
        </div>

        <div class="ft-rise mt-12 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3" style="animation-delay:.28s">
            @foreach($links as $link)
                <a href="{{ route($link['route']) }}"
                   class="lk-card group relative flex flex-col overflow-hidden rounded-2xl border border-[#2b211b]/10 bg-[#fffdf9] p-7">
                    <span aria-hidden="true" class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-[#c8a96a] to-transparent"></span>
                    <span class="text-[0.62rem] font-semibold uppercase tracking-[0.25em] text-[#c8a96a]">{{ $link['tag'] }}</span>
                    <h2 class="font-display mt-3 text-2xl font-normal text-[#1a1512]">{{ $link['label'] }}</h2>
                    <p class="mt-2 flex-1 text-[15px] leading-relaxed text-[#5c5246]">{{ $link['desc'] }}</p>
                    <span class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-[#9a7b3f]">
                        Visit
                        <span class="transition-transform duration-300 group-hover:translate-x-1" aria-hidden="true">&rarr;</span>
                    </span>
                </a>
            @endforeach

            @if(config('eventpro.demo.enabled'))
                {{-- Live demo provisions a throwaway workspace, so it posts rather than links. --}}
                <form method="POST" action="{{ route('demo.start') }}"
                      class="lk-card group relative flex flex-col overflow-hidden rounded-2xl border border-[#2b211b]/10 bg-[#1a1512] p-7 text-left">
                    @csrf
                    <span aria-hidden="true" class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-[#c8a96a] to-transparent"></span>
                    <span class="text-[0.62rem] font-semibold uppercase tracking-[0.25em] text-[#c8a96a]">Try it</span>
                    <h2 class="font-display mt-3 text-2xl font-normal text-[#f5efe7]">Live Demo</h2>
                    <p class="mt-2 flex-1 text-[15px] leading-relaxed text-[#a99e8f]">Explore a fully working workspace — no signup.</p>
                    <button type="submit" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-[#c8a96a]">
                        Launch demo
                        <span class="transition-transform duration-300 group-hover:translate-x-1" aria-hidden="true">&rarr;</span>
                    </button>
                </form>
            @endif
        </div>
    </div>
</section>
@endsection
