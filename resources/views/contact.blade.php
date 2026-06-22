@extends('layouts.app')

@section('content')
<div class="relative isolate overflow-hidden bg-[#f7f3ec] text-[#2b211b]">
    <div aria-hidden="true" class="au-noise pointer-events-none absolute inset-0 opacity-[0.04]"></div>
    <div aria-hidden="true" class="pointer-events-none absolute -top-24 right-0 h-72 w-[34rem] rounded-full blur-3xl" style="background: radial-gradient(closest-side, rgba(200,169,106,0.18), transparent);"></div>

    <div class="relative mx-auto max-w-6xl px-6 py-24 sm:py-28 lg:px-8">
        <div class="mx-auto max-w-2xl text-center lg:mx-0 lg:max-w-2xl lg:text-left">
            <p class="text-[0.72rem] font-medium uppercase tracking-[0.3em] text-[#c8a96a]">Get in touch</p>
            <h2 class="font-display mt-4 text-4xl font-light leading-tight text-[#1a1512] sm:text-5xl">
                Let&rsquo;s plan something <span class="italic">unforgettable</span>
            </h2>
            <p class="mt-5 text-lg leading-relaxed text-[#5c5246]">
                Tell us about your event &mdash; our team will be in touch within one business day.
            </p>
        </div>

        <div class="mt-14 grid gap-12 lg:grid-cols-5">
            <!-- Contact details -->
            <aside class="lg:col-span-2">
                <h3 class="text-[0.7rem] font-semibold uppercase tracking-[0.25em] text-[#c8a96a]">Reach us</h3>
                <ul class="mt-6 space-y-5 text-[15px] text-[#5c5246]">
                    @if(($branding['contact']['email'] ?? null))
                        <li>
                            <span class="block text-xs font-medium uppercase tracking-wider text-[#9a8f80]">Email</span>
                            <a href="mailto:{{ $branding['contact']['email'] }}" class="mt-1 inline-block font-medium text-[#1a1512] hover:text-[#9a7b3f]">{{ $branding['contact']['email'] }}</a>
                        </li>
                    @endif
                    @if(($branding['contact']['phone'] ?? null))
                        <li>
                            <span class="block text-xs font-medium uppercase tracking-wider text-[#9a8f80]">Phone</span>
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $branding['contact']['phone']) }}" class="mt-1 inline-block font-medium text-[#1a1512] hover:text-[#9a7b3f]">{{ $branding['contact']['phone'] }}</a>
                        </li>
                    @endif
                    @if(($branding['contact']['address'] ?? null))
                        <li>
                            <span class="block text-xs font-medium uppercase tracking-wider text-[#9a8f80]">Office</span>
                            <span class="mt-1 block leading-relaxed">
                                {{ $branding['contact']['address'] }}@if(($branding['contact']['city'] ?? null)), {{ $branding['contact']['city'] }}@endif
                                @if(($branding['contact']['country'] ?? null))<br>{{ $branding['contact']['country'] }}@endif
                            </span>
                        </li>
                    @endif
                </ul>

                <div class="mt-8 border-t border-[#2b211b]/10 pt-6">
                    <span class="block text-xs font-medium uppercase tracking-wider text-[#9a8f80]">Cities we serve</span>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach(['Colombo', 'Kandy', 'Galle', 'Dambulla'] as $city)
                            <span class="rounded-full border border-[#2b211b]/12 bg-white/60 px-3 py-1 text-xs font-medium text-[#5c5246]">{{ $city }}</span>
                        @endforeach
                    </div>
                </div>
            </aside>

            <!-- Inquiry form -->
            <div class="lg:col-span-3">
            <form
                x-data="{
                    submitting: false,
                    submitted: false,
                    errors: {},
                    validate(field, value) {
                        if (field === 'name' && !value.trim()) { this.errors.name = 'Name is required.'; return; }
                        if (field === 'email') {
                            if (!value.trim()) { this.errors.email = 'Email is required.'; return; }
                            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) { this.errors.email = 'Please enter a valid email address.'; return; }
                        }
                        if (field === 'message' && !value.trim()) { this.errors.message = 'Message is required.'; return; }
                        delete this.errors[field];
                    },
                    async submit(event) {
                        this.errors = {};
                        const form = event.target;
                        const name = form.name.value;
                        const email = form.email.value;
                        const message = form.message.value;
                        this.validate('name', name);
                        this.validate('email', email);
                        this.validate('message', message);
                        if (Object.keys(this.errors).length) return;
                        this.submitting = true;
                        form.submit();
                    }
                }"
                @submit.prevent="submit"
                action="{{ route('inquiry.store') }}" method="POST" class="mt-10">
                @csrf

                @if($errors->any())
                <div class="mb-6 rounded-md bg-red-50 p-4">
                    <ul class="list-disc pl-5 text-sm text-red-700">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div class="grid grid-cols-1 gap-x-8 gap-y-6 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="name" class="block text-sm font-semibold leading-6 text-gray-900">Name <span class="text-red-500">*</span></label>
                        <div class="mt-2.5">
                            <input type="text" name="name" id="name" autocomplete="name" required
                                value="{{ old('name') }}"
                                @blur="validate('name', $event.target.value)"
                                class="block w-full rounded-md border-0 px-3.5 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary sm:text-sm sm:leading-6"
                                :class="errors.name ? 'ring-red-500' : ''">
                        </div>
                        <p x-show="errors.name" x-text="errors.name" class="mt-1 text-sm text-red-600"></p>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-semibold leading-6 text-gray-900">Email <span class="text-red-500">*</span></label>
                        <div class="mt-2.5">
                            <input type="email" name="email" id="email" autocomplete="email" required
                                value="{{ old('email') }}"
                                @blur="validate('email', $event.target.value)"
                                class="block w-full rounded-md border-0 px-3.5 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary sm:text-sm sm:leading-6"
                                :class="errors.email ? 'ring-red-500' : ''">
                        </div>
                        <p x-show="errors.email" x-text="errors.email" class="mt-1 text-sm text-red-600"></p>
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-semibold leading-6 text-gray-900">Phone</label>
                        <div class="mt-2.5">
                            <input type="tel" name="phone" id="phone" autocomplete="tel"
                                value="{{ old('phone') }}"
                                class="block w-full rounded-md border-0 px-3.5 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary sm:text-sm sm:leading-6">
                        </div>
                    </div>

                    <div>
                        <label for="event_type" class="block text-sm font-semibold leading-6 text-gray-900">Event Type</label>
                        <div class="mt-2.5">
                            <select name="event_type" id="event_type"
                                class="block w-full rounded-md border-0 px-3.5 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary sm:text-sm sm:leading-6">
                                <option value="">Select event type</option>
                                <option value="wedding" {{ old('event_type') === 'wedding' ? 'selected' : '' }}>Wedding</option>
                                <option value="corporate" {{ old('event_type') === 'corporate' ? 'selected' : '' }}>Corporate Event</option>
                                <option value="conference" {{ old('event_type') === 'conference' ? 'selected' : '' }}>Conference</option>
                                <option value="birthday" {{ old('event_type') === 'birthday' ? 'selected' : '' }}>Birthday</option>
                                <option value="other" {{ old('event_type') === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="event_date" class="block text-sm font-semibold leading-6 text-gray-900">Preferred Date</label>
                        <div class="mt-2.5">
                            <input type="date" name="event_date" id="event_date"
                                value="{{ old('event_date') }}"
                                min="{{ now()->addDay()->toDateString() }}"
                                class="block w-full rounded-md border-0 px-3.5 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary sm:text-sm sm:leading-6">
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="message" class="block text-sm font-semibold leading-6 text-gray-900">Message <span class="text-red-500">*</span></label>
                        <div class="mt-2.5">
                            <textarea name="message" id="message" rows="4" required
                                @blur="validate('message', $event.target.value)"
                                class="block w-full rounded-md border-0 px-3.5 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary sm:text-sm sm:leading-6"
                                :class="errors.message ? 'ring-red-500' : ''">{{ old('message') }}</textarea>
                        </div>
                        <p x-show="errors.message" x-text="errors.message" class="mt-1 text-sm text-red-600"></p>
                    </div>
                </div>

                <div class="mt-10">
                    <button type="submit"
                        :disabled="submitting"
                        class="block w-full rounded-md px-3.5 py-2.5 text-center text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 disabled:opacity-60 disabled:cursor-not-allowed"
                        style="background-color: var(--color-primary);">
                        <span x-show="!submitting">Submit Inquiry</span>
                        <span x-show="submitting">Submitting…</span>
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
</div>
@endsection
