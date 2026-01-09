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
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    <!-- Dynamic CSS Variables -->
    <style>
        {!! app(\App\Services\BrandingService::class)->getCssVariables() !!}
    </style>

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <nav class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 justify-between items-center">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{ route('home') }}">
                        <img src="{{ $branding['logo'] }}" alt="{{ $branding['business_name'] }}" class="h-8 w-auto">
                    </a>
                </div>

                <!-- Navigation -->
                <div class="hidden md:flex md:space-x-8">
                    <a href="{{ route('home') }}" class="text-gray-700 hover:text-primary px-3 py-2 text-sm font-medium">Home</a>
                    <a href="{{ route('venues.index') }}" class="text-gray-700 hover:text-primary px-3 py-2 text-sm font-medium">Venues</a>
                    <a href="{{ route('packages.index') }}" class="text-gray-700 hover:text-primary px-3 py-2 text-sm font-medium">Packages</a>
                    <a href="{{ route('contact') }}" class="text-gray-700 hover:text-primary px-3 py-2 text-sm font-medium">Contact</a>
                </div>

                <!-- Auth Links -->
                <div class="flex items-center space-x-4">
                    @auth
                        <a href="{{ route('portal.dashboard') }}" class="text-gray-700 hover:text-primary text-sm font-medium">My Account</a>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-700 hover:text-primary text-sm font-medium">Login</a>
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
    <footer class="bg-gray-800 text-white mt-auto">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Brand -->
                <div class="col-span-1">
                    <img src="{{ $branding['logo'] }}" alt="{{ $branding['business_name'] }}" class="h-8 w-auto mb-4 brightness-0 invert">
                    <p class="text-gray-400 text-sm">{{ $branding['tagline'] ?? 'Professional Event Management' }}</p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="{{ route('venues.index') }}" class="text-gray-400 hover:text-white text-sm">Our Venues</a></li>
                        <li><a href="{{ route('packages.index') }}" class="text-gray-400 hover:text-white text-sm">Packages</a></li>
                        <li><a href="{{ route('contact') }}" class="text-gray-400 hover:text-white text-sm">Contact Us</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider mb-4">Contact</h4>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        @if(($branding['contact']['email'] ?? null))
                            <li>{{ $branding['contact']['email'] }}</li>
                        @endif
                        @if(($branding['contact']['phone'] ?? null))
                            <li>{{ $branding['contact']['phone'] }}</li>
                        @endif
                        @if(($branding['contact']['address'] ?? null))
                            <li>{{ $branding['contact']['address'] }}</li>
                        @endif
                    </ul>
                </div>

                <!-- Social -->
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider mb-4">Follow Us</h4>
                    <div class="flex space-x-4">
                        @if($branding['social']['facebook'] ?? false)
                            <a href="{{ $branding['social']['facebook'] }}" class="text-gray-400 hover:text-white" target="_blank">
                                <span class="sr-only">Facebook</span>
                                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
                            </a>
                        @endif
                        @if($branding['social']['instagram'] ?? false)
                            <a href="{{ $branding['social']['instagram'] }}" class="text-gray-400 hover:text-white" target="_blank">
                                <span class="sr-only">Instagram</span>
                                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z"/></svg>
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mt-8 border-t border-gray-700 pt-8 text-center">
                <p class="text-gray-400 text-sm">&copy; {{ date('Y') }} {{ $branding['business_name'] }}. All rights reserved.</p>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
