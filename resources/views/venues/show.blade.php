@extends('layouts.app')

@section('content')
<div class="bg-white">
    <div class="mx-auto max-w-7xl px-6 py-12 lg:px-8">
        <div class="mb-8">
            <a href="{{ route('venues.index') }}" class="text-sm font-semibold leading-6" style="color: var(--color-primary);">
                ← Back to Venues
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            <!-- Venue Image -->
            <div class="aspect-video bg-gray-200 rounded-lg overflow-hidden">
                <img src="{{ $venue->image_url ?? '/images/placeholder-venue.jpg' }}" alt="{{ $venue->name }}" class="w-full h-full object-cover">
            </div>

            <!-- Venue Details -->
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                    {{ $venue->name }}
                </h1>
                
                <div class="mt-4 flex items-center space-x-4 text-gray-600">
                    <span>Capacity: {{ $venue->capacity ?? 'N/A' }} guests</span>
                    <span>•</span>
                    <span>{{ $venue->type ?? 'General' }}</span>
                </div>

                <div class="mt-6 prose prose-gray">
                    <p>{{ $venue->description ?? 'No description available.' }}</p>
                </div>

                <div class="mt-8">
                    <a href="{{ route('contact') }}" class="rounded-md px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm" style="background-color: var(--color-primary);">
                        Request Information
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
