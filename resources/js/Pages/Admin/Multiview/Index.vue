<script setup>
import { ref, onBeforeUnmount } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import Hls from 'hls.js';

defineProps({
    canales: Array,
});

const videoRef = ref(null);
const canalSeleccionado = ref('');
let hlsInstance = null;

const cambiarCanal = (url) => {
    if (!url) return;

    const video = videoRef.value;
    if (!video) return;

    if (hlsInstance) {
        hlsInstance.destroy();
    }

    if (Hls.isSupported()) {
        hlsInstance = new Hls({
            xhrSetup: function (xhr, url) {
                // Configuración opcional para permitir credenciales si el servidor lo requiere
                xhr.withCredentials = false;
            },
            autoStartLoad: true,
            startLevel: -1,
        });

        hlsInstance.loadSource(url);
        hlsInstance.attachMedia(video);

        hlsInstance.on(Hls.Events.MANIFEST_PARSED, () => {
            video.play().catch(err => console.log("Play prevenido:", err));
        });

        hlsInstance.on(Hls.Events.ERROR, (event, data) => {
            console.warn("Detalle de error en HLS:", data);
            if (data.fatal) {
                switch (data.type) {
                    case Hls.ErrorTypes.NETWORK_ERROR:
                        hlsInstance.startLoad();
                        break;
                    case Hls.ErrorTypes.MEDIA_ERROR:
                        hlsInstance.recoverMediaError();
                        break;
                    default:
                        hlsInstance.destroy();
                        break;
                }
            }
        });
    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
        video.src = url;
        video.addEventListener('loadedmetadata', () => {
            video.play();
        });
    }
};

onBeforeUnmount(() => {
    if (hlsInstance) {
        hlsInstance.destroy();
    }
});
</script>

<template>
    <Head title="Pantalla Multiview" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-100 leading-tight">
                Sala de Monitoreo - Multiview
            </h2>
        </template>

        <div class="py-6 bg-gray-900 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 shadow-xl flex items-center justify-between">
                    <span class="text-gray-300 text-sm font-medium">Panel de Control de Ventanas</span>
                    <span class="text-xs text-indigo-400 bg-indigo-950 px-3 py-1 rounded-full border border-indigo-800">
                        {{ canales.length }} Canales Disponibles
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 shadow-xl flex flex-col space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-semibold text-white">Ventana 1</span>
                            <select
                                v-model="canalSeleccionado"
                                @change="cambiarCanal(canalSeleccionado)"
                                class="bg-gray-900 border border-gray-700 text-gray-200 text-xs rounded-md px-3 py-1.5 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="" disabled>Seleccionar Canal...</option>
                                <option v-for="canal in canales" :key="canal.id" :value="canal.enlace_streaming">
                                    {{ canal.nombre }}
                                </option>
                            </select>
                        </div>

                        <div class="bg-black aspect-video rounded-lg overflow-hidden border border-gray-700 flex items-center justify-center relative">
                            <video
                                ref="videoRef"
                                controls
                                playsinline
                                class="w-full h-full object-contain"
                            ></video>
                        </div>
                    </div>

                    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 shadow-xl flex flex-col space-y-3 opacity-60">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-semibold text-white">Ventana 2 (Próximamente)</span>
                        </div>
                        <div class="bg-black aspect-video rounded-lg border border-gray-700 flex items-center justify-center">
                            <span class="text-gray-500 font-medium text-sm">Sin configurar</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
