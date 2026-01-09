@extends('layouts.app')

@section('content')
<div class="bg-white">
    <div class="mx-auto max-w-7xl px-6 py-24 sm:py-32 lg:px-8">
        <div class="mx-auto max-w-2xl">
            <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Contact Us</h2>
            <p class="mt-4 text-lg leading-8 text-gray-600">
                Ready to plan your event? Get in touch with our team.
            </p>

            <form action="{{ route('inquiry.store') }}" method="POST" class="mt-10">
                @csrf
                
                <div class="grid grid-cols-1 gap-x-8 gap-y-6 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="name" class="block text-sm font-semibold leading-6 text-gray-900">Name</label>
                        <div class="mt-2.5">
                            <input type="text" name="name" id="name" autocomplete="name" required
                                class="block w-full rounded-md border-0 px-3.5 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary sm:text-sm sm:leading-6">
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-semibold leading-6 text-gray-900">Email</label>
                        <div class="mt-2.5">
                            <input type="email" name="email" id="email" autocomplete="email" required
                                class="block w-full rounded-md border-0 px-3.5 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary sm:text-sm sm:leading-6">
                        </div>
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-semibold leading-6 text-gray-900">Phone</label>
                        <div class="mt-2.5">
                            <input type="tel" name="phone" id="phone" autocomplete="tel"
                                class="block w-full rounded-md border-0 px-3.5 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary sm:text-sm sm:leading-6">
                        </div>
                    </div>

                    <div>
                        <label for="event_type" class="block text-sm font-semibold leading-6 text-gray-900">Event Type</label>
                        <div class="mt-2.5">
                            <select name="event_type" id="event_type"
                                class="block w-full rounded-md border-0 px-3.5 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary sm:text-sm sm:leading-6">
                                <option value="">Select event type</option>
                                <option value="wedding">Wedding</option>
                                <option value="corporate">Corporate Event</option>
                                <option value="conference">Conference</option>
                                <option value="birthday">Birthday</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="event_date" class="block text-sm font-semibold leading-6 text-gray-900">Preferred Date</label>
                        <div class="mt-2.5">
                            <input type="date" name="event_date" id="event_date"
                                class="block w-full rounded-md border-0 px-3.5 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary sm:text-sm sm:leading-6">
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="message" class="block text-sm font-semibold leading-6 text-gray-900">Message</label>
                        <div class="mt-2.5">
                            <textarea name="message" id="message" rows="4" required
                                class="block w-full rounded-md border-0 px-3.5 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary sm:text-sm sm:leading-6"></textarea>
                        </div>
                    </div>
                </div>

                <div class="mt-10">
                    <button type="submit" class="block w-full rounded-md px-3.5 py-2.5 text-center text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2" style="background-color: var(--color-primary);">
                        Submit Inquiry
                    </button>
                </div>

                @if(session('success'))
                <div class="mt-6 rounded-md bg-green-50 p-4">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection
