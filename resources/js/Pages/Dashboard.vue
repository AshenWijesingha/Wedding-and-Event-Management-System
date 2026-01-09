<script setup>
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />
    
    <div class="min-h-screen bg-gray-100">
        <nav class="bg-white border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <span class="font-semibold text-xl text-gray-800">EventPro Dashboard</span>
                    </div>
                </div>
            </div>
        </nav>

        <main>
            <div class="py-12">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <h1 class="text-2xl font-bold mb-4">Welcome to EventPro</h1>
                            <p class="text-gray-600">
                                Your event management dashboard is ready. 
                                Start managing venues, packages, bookings, and more.
                            </p>
                            
                            <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="bg-blue-50 rounded-lg p-6">
                                    <h3 class="font-semibold text-blue-900">Venues</h3>
                                    <p class="text-blue-600 text-sm mt-2">Manage your venues and availability</p>
                                </div>
                                <div class="bg-green-50 rounded-lg p-6">
                                    <h3 class="font-semibold text-green-900">Bookings</h3>
                                    <p class="text-green-600 text-sm mt-2">Track and manage event bookings</p>
                                </div>
                                <div class="bg-purple-50 rounded-lg p-6">
                                    <h3 class="font-semibold text-purple-900">Reports</h3>
                                    <p class="text-purple-600 text-sm mt-2">View analytics and reports</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
