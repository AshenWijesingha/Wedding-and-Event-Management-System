<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $branding = app(\App\Services\BrandingService::class)->getBranding();
    @endphp

    <title>{{ $title ?? $branding['business_name'] }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ $branding['favicon'] }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|fraunces:300,400,500,600,7..144,9..144" rel="stylesheet" />

    <!-- Dynamic CSS Variables -->
    <style>
        {!! app(\App\Services\BrandingService::class)->getCssVariables() !!}

        /* Footer aesthetic utilities */
        .font-display { font-family: 'Fraunces', ui-serif, Georgia, 'Times New Roman', serif; }
        .ft-noise {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='240' height='240'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
            background-size: 200px 200px;
        }
        @keyframes ftRise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
        @media (prefers-reduced-motion: no-preference) {
            .ft-rise { opacity: 0; animation: ftRise .7s cubic-bezier(.2,.7,.2,1) forwards; }
        }
    </style>

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    @stack('styles')
</head>
<body class="font-sans antialiased bg-[#f7f3ec] text-[#2b211b]">
    <!-- Header -->
    <header class="sticky top-0 z-40 border-b border-[#2b211b]/10 bg-[#f7f3ec]/80 backdrop-blur-md">
        <nav class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="transition-opacity hover:opacity-70">
                        <img src="{{ $branding['logo'] }}" alt="{{ $branding['business_name'] }}" class="h-8 w-auto">
                    </a>
                </div>

                <!-- Navigation -->
                <div class="hidden items-center gap-9 md:flex">
                    @foreach([['home','Home'],['venues.index','Venues'],['packages.index','Packages'],['contact','Contact']] as [$r,$label])
                        <a href="{{ route($r) }}" class="group relative text-sm font-medium text-[#5c5246] transition-colors hover:text-[#1a1512]">
                            {{ $label }}
                            <span aria-hidden="true" class="absolute -bottom-1.5 left-0 h-px w-0 bg-[#c8a96a] transition-all duration-300 group-hover:w-full"></span>
                        </a>
                    @endforeach
                </div>

                <!-- Auth Links -->
                <div class="flex items-center gap-4">
                    @auth
                        <a href="{{ route('portal.dashboard') }}"
                            class="inline-flex items-center rounded-full px-5 py-2 text-sm font-semibold text-white transition-transform duration-300 hover:-translate-y-0.5"
                            style="background-color: var(--color-primary);">My Account</a>
                    @else
                        <a href="{{ route('register') }}" class="hidden text-sm font-medium text-[#5c5246] transition-colors hover:text-[#1a1512] sm:inline">Create account</a>
                        <a href="{{ route('login') }}"
                            class="inline-flex items-center gap-2 rounded-full border border-[#2b211b]/15 bg-white/40 px-5 py-2 text-sm font-semibold text-[#1a1512] backdrop-blur-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-[#c8a96a] hover:bg-white/70">
                            <svg class="h-4 w-4 text-[#9a7b3f]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14M3 12a9 9 0 1018 0 9 9 0 00-18 0z"/></svg>
                            Sign In
                        </a>
                    @endauth
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="relative isolate mt-auto overflow-hidden bg-[#1a1512] text-[#cdbfae]">
        <!-- Atmosphere -->
        <div aria-hidden="true" class="ft-noise pointer-events-none absolute inset-0 opacity-[0.04] mix-blend-screen"></div>
        <div aria-hidden="true" class="pointer-events-none absolute -top-24 left-1/2 h-72 w-[42rem] -translate-x-1/2 rounded-full blur-3xl" style="background: radial-gradient(closest-side, color-mix(in srgb, var(--color-primary) 22%, transparent), transparent);"></div>
        <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-[#c8a96a]/45 to-transparent"></div>

        <div class="relative mx-auto max-w-7xl px-6 lg:px-8">
            <!-- CTA band -->
            <div class="ft-rise flex flex-col gap-8 border-b border-white/5 py-16 md:flex-row md:items-end md:justify-between" style="animation-delay:.05s">
                <div>
                    <p class="text-[0.7rem] font-medium uppercase tracking-[0.35em] text-[#c8a96a]">Let&rsquo;s begin</p>
                    <h2 class="font-display mt-4 text-4xl font-light leading-[1.05] text-[#f5efe7] sm:text-5xl">
                        Ready to plan<br><span class="italic">your perfect event?</span>
                    </h2>
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-4">
                    @if(config('eventpro.demo.enabled'))
                        {{-- One-click public demo: provisions a throwaway tenant and logs the visitor in. --}}
                        <form method="POST" action="{{ route('demo.start') }}">
                            @csrf
                            <button type="submit"
                                class="group inline-flex items-center gap-2 text-sm font-semibold text-[#cdbfae] transition-colors hover:text-[#f5efe7]">
                                Try the demo
                                <span class="transition-transform duration-300 group-hover:translate-x-1" aria-hidden="true">&rarr;</span>
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('contact') }}"
                        class="group inline-flex items-center gap-3 rounded-full px-7 py-3.5 text-sm font-semibold text-white shadow-lg shadow-black/30 transition-transform duration-300 hover:-translate-y-0.5"
                        style="background-color: var(--color-primary);">
                        Get Started
                        <svg class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                </div>
            </div>

            <!-- Columns -->
            <div class="ft-rise grid grid-cols-2 gap-x-8 gap-y-12 py-16 lg:grid-cols-4" style="animation-delay:.15s">
                <!-- Brand -->
                <div class="col-span-2 lg:col-span-1">
                    <img src="{{ $branding['logo'] }}" alt="{{ $branding['business_name'] }}" class="h-9 w-auto brightness-0 invert">
                    <p class="mt-5 max-w-xs text-sm leading-relaxed text-[#a99e8f]">{{ $branding['tagline'] ?? 'Professional event management for unforgettable celebrations.' }}</p>

                    @php $socials = array_filter([
                        'facebook'  => $branding['social']['facebook'] ?? null,
                        'instagram' => $branding['social']['instagram'] ?? null,
                        'linkedin'  => $branding['social']['linkedin'] ?? null,
                    ]); @endphp
                    @if(count($socials))
                        <div class="mt-7 flex gap-3">
                            @if($socials['facebook'] ?? null)
                                <a href="{{ $socials['facebook'] }}" target="_blank" rel="noopener"
                                    class="grid h-10 w-10 place-items-center rounded-full border border-white/12 text-[#cdbfae] transition duration-300 hover:-translate-y-0.5 hover:border-[#c8a96a] hover:text-[#f5efe7]">
                                    <span class="sr-only">Facebook</span>
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
                                </a>
                            @endif
                            @if($socials['instagram'] ?? null)
                                <a href="{{ $socials['instagram'] }}" target="_blank" rel="noopener"
                                    class="grid h-10 w-10 place-items-center rounded-full border border-white/12 text-[#cdbfae] transition duration-300 hover:-translate-y-0.5 hover:border-[#c8a96a] hover:text-[#f5efe7]">
                                    <span class="sr-only">Instagram</span>
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z"/></svg>
                                </a>
                            @endif
                            @if($socials['linkedin'] ?? null)
                                <a href="{{ $socials['linkedin'] }}" target="_blank" rel="noopener"
                                    class="grid h-10 w-10 place-items-center rounded-full border border-white/12 text-[#cdbfae] transition duration-300 hover:-translate-y-0.5 hover:border-[#c8a96a] hover:text-[#f5efe7]">
                                    <span class="sr-only">LinkedIn</span>
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                                </a>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Explore -->
                <div>
                    <h3 class="text-[0.7rem] font-semibold uppercase tracking-[0.25em] text-[#c8a96a]">Explore</h3>
                    <ul class="mt-5 space-y-3.5">
                        @foreach([['venues.index','Our Venues'],['packages.index','Packages'],['contact','Contact Us']] as [$r,$label])
                            <li>
                                <a href="{{ route($r) }}" class="group inline-flex items-center gap-2.5 text-sm text-[#a99e8f] transition-colors duration-200 hover:text-[#f5efe7]">
                                    <span class="h-px w-0 bg-[#c8a96a] transition-all duration-300 group-hover:w-4"></span>
                                    {{ $label }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h3 class="text-[0.7rem] font-semibold uppercase tracking-[0.25em] text-[#c8a96a]">Contact</h3>
                    <ul class="mt-5 space-y-3.5 text-sm text-[#a99e8f]">
                        @if(($branding['contact']['email'] ?? null))
                            <li><a href="mailto:{{ $branding['contact']['email'] }}" class="transition-colors duration-200 hover:text-[#f5efe7]">{{ $branding['contact']['email'] }}</a></li>
                        @endif
                        @if(($branding['contact']['phone'] ?? null))
                            <li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $branding['contact']['phone']) }}" class="transition-colors duration-200 hover:text-[#f5efe7]">{{ $branding['contact']['phone'] }}</a></li>
                        @endif
                        @if(($branding['contact']['address'] ?? null))
                            <li class="leading-relaxed">
                                {{ $branding['contact']['address'] }}
                                @if(($branding['contact']['city'] ?? null))<br>{{ $branding['contact']['city'] }}@endif
                                @if(($branding['contact']['country'] ?? null))<br>{{ $branding['contact']['country'] }}@endif
                            </li>
                        @endif
                    </ul>
                </div>

                <!-- Service areas -->
                <div>
                    <h3 class="text-[0.7rem] font-semibold uppercase tracking-[0.25em] text-[#c8a96a]">Where we work</h3>
                    <ul class="mt-5 space-y-3.5 text-sm text-[#a99e8f]">
                        @foreach(['Colombo', 'Kandy', 'Galle', 'Dambulla'] as $area)
                            <li class="flex items-center gap-2.5">
                                <span class="h-px w-3 bg-[#c8a96a]"></span>{{ $area }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <!-- Bottom bar -->
            <div class="flex flex-col items-center justify-between gap-3 border-t border-white/5 py-7 text-xs text-[#80766a] sm:flex-row">
                <p>&copy; {{ date('Y') }} {{ $branding['business_name'] }}. All rights reserved.</p>
                <p class="flex items-center gap-2"><span class="font-display italic text-[#c8a96a]">Crafted with care</span> &middot; Every detail, considered.</p>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
