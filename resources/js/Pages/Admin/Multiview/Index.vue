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

    // Usamos nextTick para asegurar que el elemento <video> ya esté renderizado en el DOM
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
                            ventana.hlsInstance.startLoad();
                            break;
                        case Hls.ErrorTypes.MEDIA_ERROR:
                            ventana.hlsInstance.recoverMediaError();
                            break;
                        default:
                            ventana.hlsInstance.destroy();
                            break;
                    }
                }
            });
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
        // Si hay un canal disponible para este índice y la ventana está vacía
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
    // 1. Asignar automáticamente los primeros canales a las ventanas iniciales
    autocompletarCanales();

    // 2. Sincronización instantánea entre pestañas del mismo navegador
    if (typeof window !== 'undefined' && 'BroadcastChannel' in window) {
        broadcastChannel = new BroadcastChannel('multiview_sync');
        broadcastChannel.onmessage = (event) => {
            if (event.data.type === 'CANAL_CREADO_O_ACTUALIZADO') {
                fetchCanalesActualizados();
            }
        };
    }

    // 3. Polling silencioso cada 6 segundos para multi-dispositivo
    pollingInterval = setInterval(() => {
        fetchCanalesActualizados();
    }, 6000);
});

// Cambiar dinámicamente el diseño de la cuadrícula (1, 2, 4, 6, 8) y rellenar si faltan canales
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

    // Al cambiar la cuadrícula, autocompletar las nuevas ventanas con los canales que sigan libres en orden
    nextTick(() => {
        autocompletarCanales();
    });
};

// Clases CSS dinámicas para la cuadrícula
const gridClass = computed(() => {
    switch (gridSize.value) {
        case 1: return 'grid-cols-1';
        case 2: return 'grid-cols-1 md:grid-cols-2';
        case 4: return 'grid-cols-1 md:grid-cols-2'; // 2x2
        case 6: return 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3'; // 3 columnas
        case 8: return 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4'; // 4 columnas
        default: return 'grid-cols-1 md:grid-cols-2';
    }
});

// Selector manual de canal desde el combobox de cada ventana
const cambiarCanalSeleccionado = (ventana, url) => {
    iniciarReproductor(ventana, url);
};

onBeforeUnmount(() => {
    if (pollingInterval) clearInterval(pollingInterval);
    if (broadcastChannel) broadcastChannel.close();

    ventanas.value.forEach(v => {
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
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-100 leading-tight">
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

        <div class="py-6 bg-gray-950 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Barra superior con botones de diseño y contador -->
                <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 shadow-xl flex flex-col md:flex-row items-center justify-between gap-4">

                    <div class="flex items-center space-x-2">
                        <span class="text-gray-300 text-sm font-medium mr-2">Diseño:</span>
                        <button
                            v-for="size in [1, 2, 4, 6, 8]"
                            :key="size"
                            @click="cambiarGrid(size)"
                            :class="[
                                'px-3 py-1.5 text-xs font-semibold rounded-md transition-all',
                                gridSize === size
                                    ? 'bg-indigo-600 text-white shadow-md shadow-indigo-900/50'
                                    : 'bg-gray-900 text-gray-400 hover:bg-gray-700 hover:text-white border border-gray-700'
                            ]"
                        >
                            {{ size }} {{ size === 1 ? 'Ventana' : 'Ventanas' }}
                        </button>
                    </div>

                    <span class="text-xs text-indigo-400 bg-indigo-950 px-3 py-1.5 rounded-full border border-indigo-800">
                        {{ canalesLista.length }} Canales Disponibles
                    </span>

                </div>

                <!-- Cuadrícula dinámica de Multiview -->
                <div :class="['grid gap-6', gridClass]">
                    <div
                        v-for="ventana in ventanas"
                        :key="ventana.id"
                        class="bg-gray-800 border border-gray-700 rounded-lg p-4 shadow-xl flex flex-col space-y-3"
                    >
                        <!-- Cabecera de la ventana con selector de canal -->
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-semibold text-white">Ventana {{ ventana.id }}</span>
                            <select
                                v-model="ventana.urlSeleccionada"
                                @change="cambiarCanalSeleccionado(ventana, ventana.urlSeleccionada)"
                                class="bg-gray-900 border border-gray-700 text-gray-200 text-xs rounded-md px-3 py-1.5 focus:border-indigo-500 focus:ring-indigo-500 max-w-[160px]"
                            >
                                <option value="" disabled>Seleccionar Canal...</option>
                                <option v-for="canal in canalesLista" :key="canal.id" :value="canal.enlace_streaming">
                                    {{ canal.nombre }}
                                </option>
                            </select>
                        </div>

                        <!-- Contenedor del reproductor de video -->
                        <div class="bg-black aspect-video rounded-lg overflow-hidden border border-gray-700 flex items-center justify-center relative">
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
