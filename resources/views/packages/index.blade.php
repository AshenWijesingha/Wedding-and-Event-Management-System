@extends('layouts.app')

@section('content')
<div class="bg-white">
    <div class="mx-auto max-w-7xl px-6 py-24 sm:py-32 lg:px-8">
        <div class="mx-auto max-w-2xl lg:text-center">
            <h2 class="text-base font-semibold leading-7" style="color: var(--color-primary);">Our Packages</h2>
            <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                All-Inclusive Event Packages
            </p>
            <p class="mt-6 text-lg leading-8 text-gray-600">
                Choose from our carefully curated packages designed to make your event planning stress-free.
            </p>
        </div>

        <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                <!-- Package cards will be dynamically loaded here -->
                <div class="bg-gray-100 rounded-lg p-6 text-center">
                    <p class="text-gray-500">Loading packages...</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
