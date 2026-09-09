<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    canales: Array,
});

// Estado para controlar la visibilidad del Modal
const showingModal = ref(false);

const form = useForm({
    nombre: '',
    enlace_streaming: '',
    logo: '',
    estado: 'Activo',
});

const registrarCanal = () => {
    form.post(route('canales.store'), {
        onSuccess: () => {
            form.reset();
            showingModal.value = false; // Cierra el modal al guardar con éxito
        },
    });
};

const eliminarCanal = (id) => {
    if (confirm('¿Estás seguro de que deseas eliminar este canal?')) {
        useForm().delete(route('canales.destroy', id));
    }
};
</script>

<template>
    <Head title="Gestión de Canales" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-100 leading-tight">
                    Gestión de Canales de Televisión
                </h2>
                <!-- Botón para abrir el Modal -->
                <button
                    @click="showingModal = true"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 transition ease-in-out duration-150"
                >
                    + Nuevo Canal
                </button>
            </div>
        </template>

        <div class="py-12 bg-gray-900 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Listado de Canales Registrados -->
                <div class="p-4 sm:p-8 bg-gray-800 shadow sm:rounded-lg border border-gray-700">
                    <h3 class="text-lg font-medium text-gray-100 mb-4">Canales Registrados</h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-700">
                            <thead class="bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Nombre</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Enlace</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-gray-800 divide-y divide-gray-700">
                                <tr v-for="canal in canales" :key="canal.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{{ canal.id }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-100">{{ canal.nombre }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400 truncate max-w-xs">{{ canal.enlace_streaming }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span :class="canal.estado === 'Activo' ? 'text-green-400 bg-green-900/50 px-2 py-1 rounded text-xs font-semibold' : 'text-red-400 bg-red-900/50 px-2 py-1 rounded text-xs font-semibold'">
                                            {{ canal.estado }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button
                                            @click="eliminarCanal(canal.id)"
                                            class="text-red-400 hover:text-red-300 transition-colors"
                                        >
                                            Eliminar
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="canales.length === 0">
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-400">
                                        No hay canales registrados todavía. ¡Haz clic en "+ Nuevo Canal" para agregar el primero!
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- MODAL PARA CREAR NUEVO CANAL -->
        <div v-if="showingModal" class="fixed inset-0 z-50 overflow-y-auto bg-black/70 flex items-center justify-center p-4">
            <div class="bg-gray-800 border border-gray-700 rounded-lg max-w-lg w-full p-6 shadow-xl relative">

                <div class="flex justify-between items-center pb-3 border-b border-gray-700 mb-4">
                    <h3 class="text-lg font-medium text-gray-100">Registrar Nuevo Canal</h3>
                    <button @click="showingModal = false" class="text-gray-400 hover:text-gray-200 text-xl font-bold">&times;</button>
                </div>

                <form @submit.prevent="registrarCanal" class="space-y-4">
                    <div>
                        <label class="block font-medium text-sm text-gray-300">Nombre del Canal</label>
                        <input
                            type="text"
                            v-model="form.nombre"
                            class="mt-1 block w-full bg-gray-900 border-gray-700 text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            placeholder="Ej. Red Uno, Unitel, Bolivia TV"
                            required
                        />
                        <div v-if="form.errors.nombre" class="text-red-400 text-sm mt-1">{{ form.errors.nombre }}</div>
                    </div>

                    <div>
                        <label class="block font-medium text-sm text-gray-300">Enlace de Streaming (.m3u8 o URL)</label>
                        <input
                            type="text"
                            v-model="form.enlace_streaming"
                            class="mt-1 block w-full bg-gray-900 border-gray-700 text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            placeholder="https://tu-servidor.com/live/canal.m3u8"
                            required
                        />
                        <div v-if="form.errors.enlace_streaming" class="text-red-400 text-sm mt-1">{{ form.errors.enlace_streaming }}</div>
                    </div>

                    <div>
                        <label class="block font-medium text-sm text-gray-300">Logo (Opcional URL)</label>
                        <input
                            type="text"
                            v-model="form.logo"
                            class="mt-1 block w-full bg-gray-900 border-gray-700 text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            placeholder="https://dominio.com/logo.png"
                        />
                    </div>

                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-700">
                        <button
                            type="button"
                            @click="showingModal = false"
                            class="px-4 py-2 bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-300 uppercase tracking-widest hover:bg-gray-600 transition"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 transition"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            Guardar Canal
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
