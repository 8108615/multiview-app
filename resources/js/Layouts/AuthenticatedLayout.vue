<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link } from '@inertiajs/vue3';

const showingSidebarMobile = ref(false);
</script>

<template>
    <div class="min-h-screen bg-gray-900 text-gray-100 flex">
        <!-- Sidebar para Escritorio y Móvil -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 w-64 bg-gray-800 border-r border-gray-700 flex flex-col transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-auto',
                showingSidebarMobile ? 'translate-x-0' : '-translate-x-full'
            ]"
        >
            <!-- Cabecera del Sidebar (Logo y Título) -->
            <div class="h-16 flex items-center px-6 border-b border-gray-700 shrink-0">
                <Link :href="route('dashboard')" class="flex items-center gap-3">
                    <ApplicationLogo class="block h-8 w-auto fill-current text-white" />
                    <span class="font-bold text-lg tracking-wider text-white">MULTIVIEW</span>
                </Link>
            </div>

            <!-- Menú de Navegación Lateral -->
            <div class="flex-1 overflow-y-auto px-4 py-6 space-y-6">
                <div>
                    <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">
                        Plataforma
                    </p>
                    <nav class="space-y-1">
                        <!-- Enlace Dashboard -->
                        <Link
                            :href="route('dashboard')"
                            :class="[
                                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                                route().current('dashboard')
                                    ? 'bg-indigo-600 text-white shadow-md'
                                    : 'text-gray-300 hover:bg-gray-700/60 hover:text-white'
                            ]"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                            </svg>
                            Dashboard
                        </Link>

                        <!-- Enlace Canales -->
                        <Link
                            :href="route('canales.index')"
                            :class="[
                                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                                route().current('canales.*')
                                    ? 'bg-indigo-600 text-white shadow-md'
                                    : 'text-gray-300 hover:bg-gray-700/60 hover:text-white'
                            ]"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            Canales
                        </Link>
                        <!-- Enlace Multiview -->
                        <Link
                            :href="route('multiview.index')"
                            :class="[
                                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                                route().current('multiview.*')
                                    ? 'bg-indigo-600 text-white shadow-md'
                                    : 'text-gray-300 hover:bg-gray-700/60 hover:text-white'
                            ]"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-2zM14 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-2z"></path>
                            </svg>
                            Multiview
                        </Link>
                    </nav>
                </div>
            </div>

            <!-- Perfil de Usuario Abajo en el Sidebar -->
            <div class="p-4 border-t border-gray-700 shrink-0">
                <Dropdown align="top" width="48">
                    <template #trigger>
                        <button class="w-full flex items-center justify-between p-2 rounded-lg hover:bg-gray-700/50 transition-colors text-left focus:outline-none">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-full bg-indigo-600 flex items-center justify-center font-bold text-white shrink-0">
                                    {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-white truncate">{{ $page.props.auth.user.name }}</p>
                                    <p class="text-xs text-gray-400 truncate">{{ $page.props.auth.user.email }}</p>
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-gray-400 shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                            </svg>
                        </button>
                    </template>

                    <template #content>
                        <DropdownLink :href="route('profile.edit')">
                            Profile
                        </DropdownLink>
                        <DropdownLink :href="route('logout')" method="post" as="button">
                            Log Out
                        </DropdownLink>
                    </template>
                </Dropdown>
            </div>
        </aside>

        <!-- Capa oscura de fondo para móviles cuando el menú está abierto -->
        <div
            v-if="showingSidebarMobile"
            @click="showingSidebarMobile = false"
            class="fixed inset-0 z-40 bg-black/50 lg:hidden"
        ></div>

        <!-- Contenido Principal -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Barra superior móvil / responsive (botón hamburguesa) -->
            <header class="bg-gray-800 border-b border-gray-700 h-16 flex items-center justify-between px-4 lg:hidden shrink-0">
                <button
                    @click="showingSidebarMobile = !showingSidebarMobile"
                    class="p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <span class="font-bold text-white tracking-wider">MULTIVIEW</span>
                <div class="w-6"></div> <!-- Espaciador para centrar título -->
            </header>

            <!-- Cabecera de la página opcional (si la vista usa slots de header) -->
            <header class="bg-gray-800 shadow px-6 py-6 border-b border-gray-700" v-if="$slots.header">
                <div class="max-w-7xl mx-auto">
                    <slot name="header" />
                </div>
            </header>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto bg-gray-900 p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
