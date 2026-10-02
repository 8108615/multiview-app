<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import Hls from 'hls.js';
import axios from 'axios';

const props = defineProps({
    canales: Array,
});

const canalesLista = ref([...props.canales]);
const gridSize = ref(4);
const mostrarControlesMovil = ref(false); // Menú desplegable flotante lateral para móviles

// Arreglo reactivo de ventanas de monitoreo
const ventanas = ref([
    { id: 1, urlSeleccionada: '', videoRef: null, hlsInstance: null, stallInterval: null },
    { id: 2, urlSeleccionada: '', videoRef: null, hlsInstance: null, stallInterval: null },
    { id: 3, urlSeleccionada: '', videoRef: null, hlsInstance: null, stallInterval: null },
    { id: 4, urlSeleccionada: '', videoRef: null, hlsInstance: null, stallInterval: null },
]);

let broadcastChannel = null;
let pollingInterval = null;

// Función para inicializar/reproducir un stream HLS en una ventana específica
const iniciarReproductor = (ventana, urlOriginal) => {
    if (!urlOriginal) return;
    ventana.urlSeleccionada = urlOriginal;

    // TRANSICIÓN AL PROXY: Si el enlace es HTTP externo, lo ruteamos a través de nuestro proxy de Laravel
    let url = urlOriginal;
    if (urlOriginal.startsWith('http://')) {
        url = '/stream-proxy/index.m3u8';
    }

    nextTick(() => {
        const video = ventana.videoRef;
        if (!video) return;

        // 1. LIMPIEZA TOTAL: Destruir instancia anterior para evitar congelamientos o caché vieja
        if (ventana.hlsInstance) {
            ventana.hlsInstance.destroy();
            ventana.hlsInstance = null;
        }

        if (ventana.stallInterval) {
            clearInterval(ventana.stallInterval);
            ventana.stallInterval = null;
        }

        // Limpiar elementos de video
        video.pause();
        video.src = '';
        video.load();

        if (Hls.isSupported()) {
            ventana.hlsInstance = new Hls({
                autoStartLoad: true,
                startLevel: -1,
                fragLoadingTimeOut: 30000,
                manifestLoadingTimeOut: 30000,
                levelLoadingTimeOut: 30000,
                maxBufferLength: 15,
                maxMaxBufferLength: 30,
                maxBufferSize: 30 * 1000 * 1000,
                liveSyncDurationCount: 3,
                fragLoadingMaxRetry: 4,
                manifestLoadingMaxRetry: 4,
            });

            ventana.hlsInstance.loadSource(url);
            ventana.hlsInstance.attachMedia(video);

            ventana.hlsInstance.on(Hls.Events.MANIFEST_PARSED, () => {
                video.play().catch(err => console.log("Play prevenido:", err));
            });

            ventana.hlsInstance.on(Hls.Events.ERROR, (event, data) => {
                if (data.fatal) {
                    switch (data.type) {
                        case Hls.ErrorTypes.NETWORK_ERROR:
                            setTimeout(() => {
                                if (ventana.hlsInstance) ventana.hlsInstance.startLoad();
                            }, 3000);
                            break;
                        case Hls.ErrorTypes.MEDIA_ERROR:
                            ventana.hlsInstance.recoverMediaError();
                            break;
                        default:
                            ventana.hlsInstance.destroy();
                            setTimeout(() => iniciarReproductor(ventana, urlOriginal), 4000);
                            break;
                    }
                }
            });

            let lastCurrentTime = 0;
            let stallCounter = 0;
            const checkStall = () => {
                if (video.currentTime === lastCurrentTime && !video.paused && video.readyState > 2) {
                    stallCounter++;
                    if (stallCounter > 5) {
                        stallCounter = 0;
                        if (ventana.hlsInstance) {
                            ventana.hlsInstance.stopLoad();
                            ventana.hlsInstance.startLoad();
                        }
                        video.play().catch(() => {});
                    }
                } else {
                    stallCounter = 0;
                    lastCurrentTime = video.currentTime;
                }
            };

            ventana.stallInterval = setInterval(checkStall, 1000);

        } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
            video.src = url;
            video.addEventListener('loadedmetadata', () => {
                video.play().catch(err => console.log("Play prevenido:", err));
            });
        }
    });
};

const autocompletarCanales = () => {
    ventanas.value.forEach((ventana, index) => {
        if (canalesLista.value[index] && !ventana.urlSeleccionada) {
            const urlCanal = canalesLista.value[index].enlace_streaming;
            setTimeout(() => {
                if (!ventana.urlSeleccionada) {
                    iniciarReproductor(ventana, urlCanal);
                }
            }, index * 300);
        }
    });
};

const fetchCanalesActualizados = async () => {
    try {
        const response = await axios.get('/canales/activos');
        canalesLista.value = response.data;
    } catch (error) {
        console.error('Error sincronizando canales:', error);
    }
};

onMounted(() => {
    autocompletarCanales();

    if (typeof window !== 'undefined' && 'BroadcastChannel' in window) {
        broadcastChannel = new BroadcastChannel('multiview_sync');
        broadcastChannel.onmessage = (event) => {
            if (event.data.type === 'CANAL_CREADO_O_ACTUALIZADO') {
                fetchCanalesActualizados();
            }
        };
    }

    pollingInterval = setInterval(() => {
        fetchCanalesActualizados();
    }, 6000);
});

const cambiarGrid = (nuevoTamanio) => {
    if (nuevoTamanio < ventanas.value.length) {
        for (let i = nuevoTamanio; i < ventanas.value.length; i++) {
            if (ventanas.value[i].hlsInstance) {
                ventanas.value[i].hlsInstance.destroy();
            }
            if (ventanas.value[i].stallInterval) {
                clearInterval(ventanas.value[i].stallInterval);
            }
        }
    }

    const nuevasVentanas = [];
    for (let i = 1; i <= nuevoTamanio; i++) {
        const existente = ventanas.value[i - 1];
        if (existente) {
            nuevasVentanas.push(existente);
        } else {
            nuevasVentanas.push({ id: i, urlSeleccionada: '', videoRef: null, hlsInstance: null, stallInterval: null });
        }
    }

    gridSize.value = nuevoTamanio;
    ventanas.value = nuevasVentanas;
    mostrarControlesMovil.value = false;

    nextTick(() => {
        autocompletarCanales();
    });
};

const gridClass = computed(() => {
    switch (gridSize.value) {
        case 1: return 'grid-cols-1 grid-rows-1';
        case 2: return 'grid-cols-1 sm:grid-cols-2 grid-rows-1';
        case 4: return 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 grid-rows-none sm:grid-rows-2';
        case 6: return 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 grid-rows-none';
        case 8: return 'grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-4 grid-rows-none';
        default: return 'grid-cols-1 sm:grid-cols-2';
    }
});

const cambiarCanalSeleccionado = (ventana, url) => {
    iniciarReproductor(ventana, url);
};

onBeforeUnmount(() => {
    if (pollingInterval) clearInterval(pollingInterval);
    if (broadcastChannel) broadcastChannel.close();

    ventanas.value.forEach(v => {
        if (v.stallInterval) clearInterval(v.stallInterval);
        if (v.hlsInstance) {
            v.hlsInstance.destroy();
        }
    });
});
</script>

<template>
    <Head title="Pantalla Multiview" />

    <AuthenticatedLayout>
        <!-- HEADER DE ESCRITORIO -->
        <template #header>
            <div class="hidden lg:flex justify-between items-center py-1">
                <h2 class="font-semibold text-lg text-gray-100 leading-tight">
                    Sala de Monitoreo - Multiview
                </h2>
                <button
                    @click="fetchCanalesActualizados"
                    class="text-xs bg-gray-700 hover:bg-gray-600 text-gray-200 px-3 py-1.5 rounded-md transition"
                >
                    Actualizar Lista Manual
                </button>
            </div>
        </template>

        <!-- CONTENEDOR PRINCIPAL -->
        <div class="min-h-screen lg:min-h-[calc(100vh-8.5rem)] bg-gray-950 p-1.5 sm:p-4 relative flex flex-col">

            <!-- BOTÓN FLOTANTE LATERAL PARA MÓVILES -->
            <div class="lg:hidden fixed left-2 top-2 z-50">
                <button
                    @click="mostrarControlesMovil = !mostrarControlesMovil"
                    class="bg-indigo-600 hover:bg-indigo-500 text-white text-[11px] font-bold px-2.5 py-1.5 rounded-md shadow-xl border border-indigo-400 flex items-center space-x-1 transition-all"
                >
                    <span>Diseño: {{ gridSize }}v</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>

                <!-- MENÚ DESPLEGABLE FLOTANTE -->
                <div v-if="mostrarControlesMovil" class="absolute left-0 top-full mt-1.5 bg-gray-900 border border-gray-700 rounded-lg shadow-2xl p-2 w-44 z-50 flex flex-col space-y-1.5">
                    <span class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider px-1">Seleccionar Ventanas:</span>
                    <button
                        v-for="size in [1, 2, 4, 6, 8]"
                        :key="size"
                        @click="cambiarGrid(size)"
                        :class="[
                            'text-left px-2.5 py-1.5 text-xs font-semibold rounded transition',
                            gridSize === size ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800'
                        ]"
                    >
                        {{ size }} {{ size === 1 ? 'Ventana' : 'Ventanas' }}
                    </button>
                    <div class="border-t border-gray-800 pt-1"></div>
                    <button
                        @click="fetchCanalesActualizados(); mostrarControlesMovil = false;"
                        class="text-left px-2.5 py-1.5 text-xs text-indigo-300 hover:bg-gray-800 rounded font-medium"
                    >
                        🔄 Actualizar Canales
                    </button>
                </div>
            </div>

            <div class="max-w-7xl w-full mx-auto flex flex-col h-full space-y-2">

                <!-- BARRA DE DISEÑO TRADICIONAL -->
                <div class="hidden lg:flex bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 shadow-xl items-center justify-between gap-3 shrink-0">
                    <div class="flex items-center space-x-2">
                        <span class="text-gray-300 text-xs font-medium mr-1">Diseño:</span>
                        <button
                            v-for="size in [1, 2, 4, 6, 8]"
                            :key="size"
                            @click="cambiarGrid(size)"
                            :class="[
                                'px-2.5 py-1 text-xs font-semibold rounded-md transition-all shrink-0',
                                gridSize === size
                                    ? 'bg-indigo-600 text-white shadow-md shadow-indigo-900/50'
                                    : 'bg-gray-900 text-gray-400 hover:bg-gray-700 hover:text-white border border-gray-700'
                            ]"
                        >
                            {{ size }} {{ size === 1 ? 'Ventana' : 'Ventanas' }}
                        </button>
                    </div>

                    <span class="text-xs text-indigo-400 bg-indigo-950 px-3 py-1 rounded-full border border-indigo-800 shrink-0">
                        {{ canalesLista.length }} Canales Disponibles
                    </span>
                </div>

                <!-- CUADRÍCULA DE VIDEOS -->
                <div :class="['grid gap-2 sm:gap-3', gridClass]">
                    <div
                        v-for="ventana in ventanas"
                        :key="ventana.id"
                        class="bg-gray-800 border border-gray-700 rounded-lg p-2 shadow-xl flex flex-col justify-between h-[260px] sm:h-[300px] lg:h-[350px]"
                    >
                        <!-- Cabecera de la ventana con selector de canal -->
                        <div class="flex justify-between items-center mb-1.5 shrink-0 gap-2">
                            <span class="text-[11px] sm:text-xs font-semibold text-white shrink-0">Ventana {{ ventana.id }}</span>
                            <select
                                v-model="ventana.urlSeleccionada"
                                @change="cambiarCanalSeleccionado(ventana, ventana.urlSeleccionada)"
                                class="bg-gray-900 border border-gray-700 text-gray-200 text-[11px] sm:text-xs rounded px-2 py-0.5 focus:border-indigo-500 focus:ring-indigo-500 w-full max-w-[160px] sm:max-w-[200px]"
                            >
                                <option value="" disabled>Seleccionar Canal...</option>
                                <option v-for="canal in canalesLista" :key="canal.id" :value="canal.enlace_streaming">
                                    {{ canal.nombre }}
                                </option>
                            </select>
                        </div>

                        <!-- Reproductor de video adaptado -->
                        <div class="bg-black flex-1 rounded-lg overflow-hidden border border-gray-700 flex items-center justify-center relative min-h-0">
                            <video
                                :ref="el => ventana.videoRef = el"
                                controls
                                playsinline
                                muted
                                class="w-full h-full object-contain"
                            ></video>
                            <div v-if="!ventana.urlSeleccionada" class="absolute text-gray-500 text-xs pointer-events-none">
                                Sin señal asignada
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
