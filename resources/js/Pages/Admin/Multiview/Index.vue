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

// Arreglo reactivo de ventanas de monitoreo
const ventanas = ref([
    { id: 1, urlSeleccionada: '', videoRef: null, hlsInstance: null },
    { id: 2, urlSeleccionada: '', videoRef: null, hlsInstance: null },
    { id: 3, urlSeleccionada: '', videoRef: null, hlsInstance: null },
    { id: 4, urlSeleccionada: '', videoRef: null, hlsInstance: null },
]);

let broadcastChannel = null;
let pollingInterval = null;

// Función para inicializar/reproducir un stream HLS en una ventana específica
const iniciarReproductor = (ventana, url) => {
    if (!url) return;
    ventana.urlSeleccionada = url;

    nextTick(() => {
        const video = ventana.videoRef;
        if (!video) return;

        if (ventana.hlsInstance) {
            ventana.hlsInstance.destroy();
        }

        if (Hls.isSupported()) {
            ventana.hlsInstance = new Hls({
                autoStartLoad: true,
                startLevel: -1,
                fragLoadingTimeOut: 30000,
                manifestLoadingTimeOut: 30000,
                levelLoadingTimeOut: 30000,
                maxBufferLength: 15,
                maxMaxBufferLength: 30,
                maxBufferSize: 30 * 1000 * 1000, // 30 MB
                liveSyncDurationCount: 3,
                fragLoadingMaxRetry: 4,
                manifestLoadingMaxRetry: 4,
            });

            ventana.hlsInstance.loadSource(url);
            ventana.hlsInstance.attachMedia(video);

            ventana.hlsInstance.on(Hls.Events.MANIFEST_PARSED, () => {
                video.play().catch(err => console.log("Play prevenido por el navegador:", err));
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
                            setTimeout(() => iniciarReproductor(ventana, url), 4000);
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

            if (ventana.stallInterval) clearInterval(ventana.stallInterval);
            ventana.stallInterval = setInterval(checkStall, 1000);

        } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
            video.src = url;
            video.addEventListener('loadedmetadata', () => {
                video.play().catch(err => console.log("Play prevenido:", err));
            });
        }
    });
};

// Función para auto-llenar las ventanas con los primeros canales disponibles
const autocompletarCanales = () => {
    ventanas.value.forEach((ventana, index) => {
        if (canalesLista.value[index] && !ventana.urlSeleccionada) {
            const urlCanal = canalesLista.value[index].enlace_streaming;
            iniciarReproductor(ventana, urlCanal);
        }
    });
};

// Sincronizar canales desde el servidor
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

// Cambiar dinámicamente el diseño de la cuadrícula
const cambiarGrid = (nuevoTamanio) => {
    if (nuevoTamanio < ventanas.value.length) {
        for (let i = nuevoTamanio; i < ventanas.value.length; i++) {
            if (ventanas.value[i].hlsInstance) {
                ventanas.value[i].hlsInstance.destroy();
            }
        }
    }

    const nuevasVentanas = [];
    for (let i = 1; i <= nuevoTamanio; i++) {
        const existente = ventanas.value[i - 1];
        if (existente) {
            nuevasVentanas.push(existente);
        } else {
            nuevasVentanas.push({ id: i, urlSeleccionada: '', videoRef: null, hlsInstance: null });
        }
    }

    gridSize.value = nuevoTamanio;
    ventanas.value = nuevasVentanas;

    nextTick(() => {
        autocompletarCanales();
    });
};

// Clases CSS dinámicas para la cuadrícula optimizadas para ajustar pantallas (1, 2, 4, 6, 8)
const gridClass = computed(() => {
    switch (gridSize.value) {
        case 1: return 'grid-cols-1 grid-rows-1';
        case 2: return 'grid-cols-1 md:grid-cols-2 grid-rows-1';
        case 4: return 'grid-cols-1 md:grid-cols-2 grid-rows-2';
        case 6: return 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3 grid-rows-2';
        case 8: return 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4 grid-rows-2';
        default: return 'grid-cols-1 md:grid-cols-2';
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
        <template #header>
            <div class="flex justify-between items-center py-1">
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

        <!-- Contenedor principal que se ajusta a la altura exacta sin desbordar -->
        <div class="h-[calc(100vh-8.5rem)] bg-gray-950 p-4 flex flex-col overflow-hidden">
            <div class="max-w-7xl w-full mx-auto flex flex-col h-full space-y-3">

                <!-- Barra superior con botones de diseño y contador -->
                <div class="bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 shadow-xl flex flex-col md:flex-row items-center justify-between gap-2 shrink-0">
                    <div class="flex items-center space-x-2">
                        <span class="text-gray-300 text-xs font-medium mr-2">Diseño:</span>
                        <button
                            v-for="size in [1, 2, 4, 6, 8]"
                            :key="size"
                            @click="cambiarGrid(size)"
                            :class="[
                                'px-2.5 py-1 text-xs font-semibold rounded-md transition-all',
                                gridSize === size
                                    ? 'bg-indigo-600 text-white shadow-md shadow-indigo-900/50'
                                    : 'bg-gray-900 text-gray-400 hover:bg-gray-700 hover:text-white border border-gray-700'
                            ]"
                        >
                            {{ size }} {{ size === 1 ? 'Ventana' : 'Ventanas' }}
                        </button>
                    </div>

                    <span class="text-xs text-indigo-400 bg-indigo-950 px-3 py-1 rounded-full border border-indigo-800">
                        {{ canalesLista.length }} Canales Disponibles
                    </span>
                </div>

                <!-- Cuadrícula dinámica de Multiview con ajuste de altura flexible -->
                <div :class="['grid gap-3 flex-1 min-h-0', gridClass]">
                    <div
                        v-for="ventana in ventanas"
                        :key="ventana.id"
                        class="bg-gray-800 border border-gray-700 rounded-lg p-3 shadow-xl flex flex-col justify-between min-h-0"
                    >
                        <!-- Cabecera de la ventana con selector de canal -->
                        <div class="flex justify-between items-center mb-2 shrink-0">
                            <span class="text-xs font-semibold text-white">Ventana {{ ventana.id }}</span>
                            <select
                                v-model="ventana.urlSeleccionada"
                                @change="cambiarCanalSeleccionado(ventana, ventana.urlSeleccionada)"
                                class="bg-gray-900 border border-gray-700 text-gray-200 text-xs rounded-md px-2 py-1 focus:border-indigo-500 focus:ring-indigo-500 max-w-[150px]"
                            >
                                <option value="" disabled>Seleccionar Canal...</option>
                                <option v-for="canal in canalesLista" :key="canal.id" :value="canal.enlace_streaming">
                                    {{ canal.nombre }}
                                </option>
                            </select>
                        </div>

                        <!-- Contenedor del reproductor de video adaptado al espacio -->
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
