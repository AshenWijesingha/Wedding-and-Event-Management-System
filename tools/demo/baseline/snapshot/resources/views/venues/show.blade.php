@extends('layouts.app')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="mx-auto max-w-7xl px-6 py-10 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="mb-6 flex items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('home') }}" class="hover:text-gray-700">Home</a>
            <span>/</span>
            <a href="{{ route('venues.index') }}" class="hover:text-gray-700">Venues</a>
            <span>/</span>
            <span class="text-gray-900 font-medium">{{ $venue->name }}</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Image gallery -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    @if($venue->images && count($venue->images) > 0)
                        <div class="aspect-video">
                            <img
                                id="main-image"
                                src="{{ $venue->images[0] }}"
                                alt="{{ $venue->name }}"
                                class="w-full h-full object-cover"
                            />
                        </div>
                        @if(count($venue->images) > 1)
                            <div class="p-3 flex gap-2 overflow-x-auto">
                                @foreach($venue->images as $i => $img)
                                    <img
                                        src="{{ $img }}"
                                        alt="{{ $venue->name }} {{ $i + 1 }}"
                                        class="w-20 h-14 object-cover rounded cursor-pointer border-2 {{ $i === 0 ? 'border-indigo-500' : 'border-transparent' }} hover:border-indigo-300 flex-shrink-0"
                                        onclick="document.getElementById('main-image').src='{{ $img }}'; this.parentElement.querySelectorAll('img').forEach(el=>el.classList.remove('border-indigo-500')); this.classList.add('border-indigo-500');"
                                    />
                                @endforeach
                            </div>
                        @endif
                    @else
                        <div class="aspect-video bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center">
                            <svg class="w-24 h-24 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                    @endif
                </div>

                <!-- Details -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h1 class="text-3xl font-bold text-gray-900">{{ $venue->name }}</h1>

                    <div class="mt-3 flex flex-wrap gap-4 text-sm text-gray-600">
                        @if($venue->capacity_min || $venue->capacity_max)
                            <div class="flex items-center gap-1">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                @if($venue->capacity_min && $venue->capacity_max)
                                    {{ number_format($venue->capacity_min) }}&ndash;{{ number_format($venue->capacity_max) }} guests
                                @else
                                    Up to {{ number_format($venue->capacity_max ?? $venue->capacity_min) }} guests
                                @endif
                            </div>
                        @endif
                        @if($venue->base_price)
                            <div class="flex items-center gap-1 font-semibold text-gray-900">
                                From Rs {{ number_format($venue->base_price) }}
                            </div>
                        @endif
                        @if($venue->weekend_surcharge)
                            <div class="flex items-center gap-1 text-amber-600">
                                + Rs {{ number_format($venue->weekend_surcharge) }} weekend surcharge
                            </div>
                        @endif
                    </div>

                    @if($venue->description)
                        <div class="mt-6 prose prose-gray max-w-none">
                            <p>{{ $venue->description }}</p>
                        </div>
                    @endif

                    @if($venue->amenities && count($venue->amenities) > 0)
                        <div class="mt-6">
                            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-3">Amenities</h3>
                            <div class="flex flex-wrap gap-2">
                                @foreach($venue->amenities as $amenity)
                                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-indigo-50 text-indigo-700 text-sm rounded-full">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        {{ $amenity }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Availability mini-widget -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Check Availability</h3>
                    <p class="text-sm text-gray-500 mb-3">Select your preferred date to check if this venue is available.</p>
                    <form action="{{ route('inquiry.store') }}" method="POST" class="flex gap-3">
                        @csrf
                        <input type="hidden" name="venue_id" value="{{ $venue->id }}">
                        <input
                            type="date"
                            name="preferred_date"
                            min="{{ now()->addDay()->toDateString() }}"
                            class="flex-1 rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <button type="submit" style="background-color: var(--color-primary);" class="px-4 py-2 text-white text-sm font-medium rounded-lg hover:opacity-90">
                            Check
                        </button>
                    </form>
                    @if(count($unavailableDates) > 0)
                        <p class="text-xs text-gray-400 mt-2">{{ count($unavailableDates) }} date(s) already booked this year.</p>
                    @endif
                </div>
            </div>

            <!-- Sidebar - Inquiry form -->
            <div class="space-y-4">
                <div class="bg-white rounded-xl shadow-sm p-6 sticky top-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Make an Inquiry</h3>
                    <p class="text-sm text-gray-500 mb-4">Interested in this venue? Send us a message.</p>

                    @if(session('success'))
                        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form action="{{ route('inquiry.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <input type="hidden" name="venue_id" value="{{ $venue->id }}">
                        <input type="hidden" name="event_type" value="general">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Your Name *</label>
                            <input type="text" name="name" required value="{{ old('name') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Jane Smith" />
                            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <input type="email" name="email" required value="{{ old('email') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="jane@example.com" />
                            @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                            <input type="tel" name="phone" value="{{ old('phone') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="+1 555 000 0000" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Preferred Date</label>
                            <input type="date" name="event_date" min="{{ now()->addDay()->toDateString() }}" value="{{ old('event_date') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
                            <textarea name="message" required rows="4" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 resize-none" placeholder="Tell us about your event...">{{ old('message') }}</textarea>
                            @error('message')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <button type="submit" style="background-color: var(--color-primary);" class="w-full py-2.5 text-white text-sm font-semibold rounded-lg hover:opacity-90 transition-opacity">
                            Send Inquiry
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
