<template>
    <div class="relative isolate flex min-h-screen flex-col items-center justify-center overflow-hidden bg-[#1a1512] px-6 py-12 text-[#cdbfae]">
        <!-- Atmosphere -->
        <div aria-hidden="true" class="au-noise pointer-events-none absolute inset-0 opacity-[0.05] mix-blend-screen"></div>
        <div aria-hidden="true" class="pointer-events-none absolute -top-32 left-1/2 h-80 w-[44rem] -translate-x-1/2 rounded-full blur-3xl"
            :style="{ background: 'radial-gradient(closest-side, color-mix(in srgb, var(--color-primary) 24%, transparent), transparent)' }"></div>
        <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-[#c8a96a]/45 to-transparent"></div>

        <!-- Wordmark -->
        <Link href="/" class="au-rise relative mb-8 inline-flex flex-col items-center gap-1" style="animation-delay:.05s">
            <span class="font-display text-3xl font-light tracking-tight text-[#f5efe7]">EventPro</span>
            <span class="text-[0.62rem] uppercase tracking-[0.4em] text-[#c8a96a]">Events &amp; Celebrations</span>
        </Link>

        <!-- Card -->
        <div class="au-rise relative w-full max-w-md rounded-2xl border border-black/5 bg-[#faf7f1] p-8 text-[#2b211b] shadow-2xl shadow-black/40 sm:p-10" style="animation-delay:.14s">
            <span aria-hidden="true" class="absolute inset-x-8 top-0 h-px bg-gradient-to-r from-transparent via-[#c8a96a]/60 to-transparent"></span>
            <slot />
        </div>

        <!-- Back link -->
        <Link href="/" class="au-rise relative mt-8 inline-flex items-center gap-2 text-xs text-[#80766a] transition-colors hover:text-[#cdbfae]" style="animation-delay:.22s">
            <span aria-hidden="true">&larr;</span> Back to site
        </Link>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
</script>
