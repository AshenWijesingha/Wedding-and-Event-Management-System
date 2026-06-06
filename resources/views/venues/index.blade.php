@extends('layouts.app')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <!-- Hero -->
    <div class="bg-white border-b border-gray-200">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 text-center">
            <h1 class="text-4xl font-bold tracking-tight text-gray-900">Our Venues</h1>
            <p class="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">
                Discover stunning venues for weddings, galas, and every special occasion.
            </p>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-6 py-10 lg:px-8">
        <!-- Filters -->
        <form method="GET" action="{{ route('venues.index') }}" class="bg-white rounded-xl shadow-sm p-4 mb-8 flex flex-col sm:flex-row gap-3">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search venues..."
                class="flex-1 rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            <select name="capacity" class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Any capacity</option>
                <option value="50"  @selected(request('capacity') == '50')>50+ guests</option>
                <option value="100" @selected(request('capacity') == '100')>100+ guests</option>
                <option value="200" @selected(request('capacity') == '200')>200+ guests</option>
                <option value="500" @selected(request('capacity') == '500')>500+ guests</option>
            </select>
            <select name="max_price" class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Any price</option>
                <option value="5000"  @selected(request('max_price') == '5000')>Up to $5,000</option>
                <option value="10000" @selected(request('max_price') == '10000')>Up to $10,000</option>
                <option value="25000" @selected(request('max_price') == '25000')>Up to $25,000</option>
                <option value="50000" @selected(request('max_price') == '50000')>Up to $50,000</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
                Search
            </button>
            @if(request()->hasAny(['search', 'capacity', 'max_price']))
                <a href="{{ route('venues.index') }}" class="px-4 py-2 border border-gray-300 text-gray-600 text-sm rounded-lg hover:bg-gray-50 text-center">
                    Clear
                </a>
            @endif
        </form>

        <!-- Results -->
        @if($venues->isEmpty())
            <div class="text-center py-16">
                <svg class="mx-auto w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <p class="text-gray-500">No venues match your search.</p>
                <a href="{{ route('venues.index') }}" class="mt-2 inline-block text-indigo-600 hover:underline text-sm">View all venues</a>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($venues as $venue)
                <a href="{{ route('venues.show', $venue) }}" class="group bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                    <!-- Image placeholder -->
                    <div class="aspect-video bg-gradient-to-br from-indigo-100 to-purple-100 relative overflow-hidden">
                        @if($venue->images && count($venue->images) > 0)
                            <img src="{{ $venue->images[0] }}" alt="{{ $venue->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        @else
                            <div class="absolute inset-0 flex items-center justify-center">
                                <svg class="w-16 h-16 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                        @endif
                    </div>

                    <div class="p-5">
                        <h3 class="text-lg font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors">
                            {{ $venue->name }}
                        </h3>

                        @if($venue->description)
                            <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ $venue->description }}</p>
                        @endif

                        <div class="mt-4 flex items-center justify-between text-sm">
                            <div class="flex items-center gap-1 text-gray-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                @if($venue->capacity_min && $venue->capacity_max)
                                    {{ number_format($venue->capacity_min) }}–{{ number_format($venue->capacity_max) }} guests
                                @elseif($venue->capacity_max)
                                    Up to {{ number_format($venue->capacity_max) }} guests
                                @else
                                    Capacity varies
                                @endif
                            </div>
                            @if($venue->base_price)
                                <div class="font-semibold text-gray-900">
                                    From ${{ number_format($venue->base_price) }}
                                </div>
                            @endif
                        </div>

                        @if($venue->amenities && count($venue->amenities) > 0)
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                @foreach(array_slice($venue->amenities, 0, 3) as $amenity)
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded-full">{{ $amenity }}</span>
                                @endforeach
                                @if(count($venue->amenities) > 3)
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-500 text-xs rounded-full">+{{ count($venue->amenities) - 3 }} more</span>
                                @endif
                            </div>
                        @endif
                    </div>
                </a>
                @endforeach
            </div>

            <!-- Pagination -->
            @if($venues->hasPages())
                <div class="mt-8">
                    {{ $venues->withQueryString()->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
