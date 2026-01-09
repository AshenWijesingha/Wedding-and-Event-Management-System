@extends('layouts.app')

@section('content')
<div class="bg-white">
    <!-- Hero Section -->
    <div class="relative isolate overflow-hidden bg-gradient-to-b from-primary/20">
        <div class="mx-auto max-w-7xl px-6 py-24 sm:py-32 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-6xl">
                    Create Unforgettable Events
                </h1>
                <p class="mt-6 text-lg leading-8 text-gray-600">
                    {{ $branding['tagline'] ?? 'Professional event management for weddings, corporate events, and special occasions.' }}
                </p>
                <div class="mt-10 flex items-center justify-center gap-x-6">
                    <a href="{{ route('venues.index') }}" class="rounded-md bg-primary px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary" style="background-color: var(--color-primary);">
                        Explore Venues
                    </a>
                    <a href="{{ route('contact') }}" class="text-sm font-semibold leading-6 text-gray-900">
                        Contact Us <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="mx-auto max-w-7xl px-6 py-24 sm:py-32 lg:px-8">
        <div class="mx-auto max-w-2xl lg:text-center">
            <h2 class="text-base font-semibold leading-7" style="color: var(--color-primary);">Everything You Need</h2>
            <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                Plan Your Perfect Event
            </p>
            <p class="mt-6 text-lg leading-8 text-gray-600">
                From intimate gatherings to grand celebrations, we provide everything you need to create memorable experiences.
            </p>
        </div>
        <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none">
            <dl class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-16 lg:max-w-none lg:grid-cols-3">
                <div class="flex flex-col">
                    <dt class="flex items-center gap-x-3 text-base font-semibold leading-7 text-gray-900">
                        <svg class="h-5 w-5 flex-none" style="color: var(--color-primary);" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.674 2.075a.75.75 0 01.652 0l7.25 3.5A.75.75 0 0117 6.957V16.5h.25a.75.75 0 110 1.5H2.75a.75.75 0 110-1.5H3V6.957a.75.75 0 01-.576-1.382l7.25-3.5z" clip-rule="evenodd" />
                        </svg>
                        Beautiful Venues
                    </dt>
                    <dd class="mt-4 flex flex-auto flex-col text-base leading-7 text-gray-600">
                        <p class="flex-auto">Choose from our stunning collection of venues, each designed to accommodate different event sizes and styles.</p>
                    </dd>
                </div>
                <div class="flex flex-col">
                    <dt class="flex items-center gap-x-3 text-base font-semibold leading-7 text-gray-900">
                        <svg class="h-5 w-5 flex-none" style="color: var(--color-primary);" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 9a3 3 0 100-6 3 3 0 000 6zM6 8a2 2 0 11-4 0 2 2 0 014 0zM1.49 15.326a.78.78 0 01-.358-.442 3 3 0 014.308-3.516 6.484 6.484 0 00-1.905 3.959c-.023.222-.014.442.025.654a4.97 4.97 0 01-2.07-.655zM16.44 15.98a4.97 4.97 0 002.07-.654.78.78 0 00.357-.442 3 3 0 00-4.308-3.517 6.484 6.484 0 011.907 3.96 2.32 2.32 0 01-.026.654zM18 8a2 2 0 11-4 0 2 2 0 014 0zM5.304 16.19a.844.844 0 01-.277-.71 5 5 0 019.947 0 .843.843 0 01-.277.71A6.975 6.975 0 0110 18a6.974 6.974 0 01-4.696-1.81z" />
                        </svg>
                        Expert Planning
                    </dt>
                    <dd class="mt-4 flex flex-auto flex-col text-base leading-7 text-gray-600">
                        <p class="flex-auto">Our experienced team handles all the details, from vendor coordination to day-of execution.</p>
                    </dd>
                </div>
                <div class="flex flex-col">
                    <dt class="flex items-center gap-x-3 text-base font-semibold leading-7 text-gray-900">
                        <svg class="h-5 w-5 flex-none" style="color: var(--color-primary);" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                        All-Inclusive Packages
                    </dt>
                    <dd class="mt-4 flex flex-auto flex-col text-base leading-7 text-gray-600">
                        <p class="flex-auto">Choose from customizable packages that include catering, decoration, entertainment, and more.</p>
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="bg-gray-50">
        <div class="mx-auto max-w-7xl px-6 py-24 sm:py-32 lg:flex lg:items-center lg:justify-between lg:px-8">
            <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                Ready to plan your event?
                <br>
                <span class="text-xl font-normal text-gray-600">Get in touch with us today.</span>
            </h2>
            <div class="mt-10 flex items-center gap-x-6 lg:mt-0 lg:flex-shrink-0">
                <a href="{{ route('contact') }}" class="rounded-md px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2" style="background-color: var(--color-primary);">
                    Get Started
                </a>
                <a href="{{ route('venues.index') }}" class="text-sm font-semibold leading-6 text-gray-900">
                    View Venues <span aria-hidden="true">→</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
