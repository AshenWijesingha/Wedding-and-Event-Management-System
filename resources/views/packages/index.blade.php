@extends('layouts.app')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <!-- Hero -->
    <div class="bg-white border-b border-gray-200">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 text-center">
            <h1 class="text-4xl font-bold tracking-tight text-gray-900">Event Packages</h1>
            <p class="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">
                All-inclusive packages for every occasion, designed to make planning effortless.
            </p>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-6 py-12 lg:px-8">
        @if($packages->isEmpty())
            <div class="text-center py-16">
                <p class="text-gray-500">No packages available at this time. Please check back soon.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($packages as $package)
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden flex flex-col border border-gray-100 hover:border-indigo-200 hover:shadow-md transition-all">
                    <!-- Header -->
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="text-xl font-bold text-gray-900">{{ $package->name }}</h3>
                        @if($package->description)
                            <p class="text-sm text-gray-500 mt-1">{{ $package->description }}</p>
                        @endif
                    </div>

                    <!-- Price -->
                    <div class="px-6 py-5 bg-indigo-50">
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl font-bold" style="color: var(--color-primary);">${{ number_format($package->base_price) }}</span>
                            <span class="text-gray-500 text-sm">starting price</span>
                        </div>
                        @if($package->min_guests || $package->max_guests)
                            <p class="text-xs text-gray-500 mt-1">
                                @if($package->min_guests && $package->max_guests)
                                    {{ number_format($package->min_guests) }}–{{ number_format($package->max_guests) }} guests
                                @elseif($package->max_guests)
                                    Up to {{ number_format($package->max_guests) }} guests
                                @else
                                    From {{ number_format($package->min_guests) }} guests
                                @endif
                            </p>
                        @endif
                    </div>

                    <!-- Included services -->
                    @if($package->included_services && count($package->included_services) > 0)
                    <div class="px-6 py-5 flex-1">
                        <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3">What's Included</h4>
                        <ul class="space-y-2">
                            @foreach($package->included_services as $service)
                            <li class="flex items-center gap-2 text-sm text-gray-600">
                                <svg class="w-4 h-4 flex-shrink-0" style="color: var(--color-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ $service }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <!-- CTA -->
                    <div class="p-6 border-t border-gray-100">
                        <a href="{{ route('contact') }}?package={{ $package->slug }}"
                            class="block w-full text-center py-2.5 px-4 rounded-lg text-sm font-semibold text-white transition-opacity hover:opacity-90"
                            style="background-color: var(--color-primary);"
                        >
                            Get a Quote
                        </a>
                        <a href="{{ route('venues.index') }}" class="block w-full text-center mt-2 py-2 text-sm text-gray-500 hover:text-gray-700">
                            Browse Venues
                        </a>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Price calculator CTA -->
            <div class="mt-16 bg-white rounded-2xl p-8 text-center shadow-sm border border-gray-100">
                <h2 class="text-2xl font-bold text-gray-900">Not sure which package fits?</h2>
                <p class="text-gray-500 mt-2 max-w-lg mx-auto">
                    Use our price calculator to get an instant estimate based on your guest count, venue preference, and date.
                </p>
                <a href="{{ route('contact') }}" class="inline-block mt-4 px-6 py-3 rounded-lg text-sm font-semibold text-white" style="background-color: var(--color-primary);">
                    Get a Custom Quote
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
