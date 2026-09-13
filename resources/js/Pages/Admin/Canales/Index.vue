<script setup>
import { ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    canales: Object,
    filters: Object,
});

// Controladores reactivos para búsqueda y paginación
const search = ref(props.filters.search || '');
const perPage = ref(props.canales.per_page || 5);

// Búsqueda fluida con retraso (debounce)
let searchTimeout = null;
watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            route('canales.index'),
            { search: value, per_page: perPage.value },
            { preserveState: true, replace: true }
        );
    }, 300);
});

// Selector de registros por página
const cambiarPorPagina = () => {
    router.get(
        route('canales.index'),
        { search: search.value, per_page: perPage.value },
        { preserveState: true, replace: true }
    );
};

// Control del Modal y modo de edición
const showingModal = ref(false);
const editingCanal = ref(null);

const form = useForm({
    nombre: '',
    enlace_streaming: '',
    logo: '',
    estado: 'Activo',
});

// Abrir modal para Crear
const abrirModalCrear = () => {
    editingCanal.value = null;
    form.reset();
    form.clearErrors();
    showingModal.value = true;
};

// Abrir modal para Editar
const abrirModalEditar = (canal) => {
    editingCanal.value = canal;
    form.nombre = canal.nombre;
    form.enlace_streaming = canal.enlace_streaming;
    form.logo = canal.logo || '';
    form.estado = canal.estado;
    form.clearErrors();
    showingModal.value = true;
};

// Guardar (Crear o Actualizar)
const guardarCanal = () => {
    if (editingCanal.value) {
        form.put(route('canales.update', editingCanal.value.id), {
            onSuccess: () => {
                form.reset();
                showingModal.value = false;
                editingCanal.value = null;
                Swal.fire({
                    title: '¡Actualizado!',
                    text: 'El canal ha sido actualizado exitosamente.',
                    icon: 'success',
                    timer: 4000,               // <- Se cierra en 4 segundos
                    timerProgressBar: true,    // <- Muestra barra de tiempo
                    showConfirmButton: false,  // <- Opcional: Oculta el botón OK ya que se auto-cierra
                    background: '#1f2937',
                    color: '#f9fafb',
                });
            },
        });
    } else {
        form.post(route('canales.store'), {
            onSuccess: () => {
                form.reset();
                showingModal.value = false;
                Swal.fire({
                    title: '¡Creado!',
                    text: 'El nuevo canal ha sido registrado exitosamente.',
                    icon: 'success',
                    timer: 4000,               // <- Se cierra en 4 segundos
                    timerProgressBar: true,    // <- Muestra barra de tiempo
                    showConfirmButton: false,  // <- Opcional: Oculta el botón OK
                    background: '#1f2937',
                    color: '#f9fafb',
                });
            },
        });
    }
};

// Función de eliminación con SweetAlert2
const eliminarCanal = (canal) => {
    Swal.fire({
        title: '¿Estás seguro?',
        text: `¡No podrás revertir esto! El canal "${canal.nombre}" será eliminado permanentemente.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#4b5563',
        confirmButtonText: 'Sí, ¡eliminar!',
        cancelButtonText: 'Cancelar',
        background: '#1f2937',
        color: '#f9fafb',
    }).then((result) => {
        if (result.isConfirmed) {
            useForm().delete(route('canales.destroy', canal.id), {
                onSuccess: () => {
                    Swal.fire({
                        title: '¡Eliminado!',
                        text: 'El canal ha sido eliminado exitosamente.',
                        icon: 'success',
                        timer: 4000,               // <- Se cierra en 4 segundos
                        timerProgressBar: true,    // <- Muestra barra de tiempo
                        showConfirmButton: false,  // <- Opcional: Oculta el botón OK
                        background: '#1f2937',
                        color: '#f9fafb',
                    });
                }
            });
        }
    });
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
                <button
                    @click="abrirModalCrear"
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

                    <!-- Cabecera de la tabla con Buscador y Selector -->
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                        <h3 class="text-lg font-medium text-gray-100 w-full md:w-auto">Canales Registrados</h3>

                        <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto justify-end">

                            <!-- Buscador Elegante y Compacto -->
                            <div class="relative w-full sm:w-64">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </span>
                                <input
                                    type="text"
                                    v-model="search"
                                    placeholder="Buscar canal..."
                                    class="w-full bg-gray-900 border border-gray-700 text-gray-100 text-sm rounded-md pl-9 pr-4 py-1.5 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                />
                            </div>

                            <!-- Selector de Registros por Página -->
                            <div class="flex items-center space-x-2 text-sm text-gray-300 whitespace-nowrap">
                                <span>Mostrar</span>
                                <select
                                    v-model="perPage"
                                    @change="cambiarPorPagina"
                                    class="bg-gray-900 border border-gray-700 text-gray-100 text-sm rounded-md focus:ring-indigo-500 focus:border-indigo-500 px-3 py-1.5 pr-8"
                                >
                                    <option value="5">5</option>
                                    <option value="10">10</option>
                                    <option value="15">15</option>
                                    <option value="20">20</option>
                                </select>
                                <span>registros</span>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-700">
                            <thead class="bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Nº</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Nombre</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Enlace</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-gray-800 divide-y divide-gray-700">
                                <tr v-for="(canal, index) in canales.data" :key="canal.id" class="hover:bg-gray-700/30 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        {{ (canales.current_page - 1) * canales.per_page + index + 1 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-100">{{ canal.nombre }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400 truncate max-w-xs">{{ canal.enlace_streaming }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span :class="canal.estado === 'Activo' ? 'text-green-400 bg-green-900/50 px-2 py-1 rounded text-xs font-semibold' : 'text-red-400 bg-red-900/50 px-2 py-1 rounded text-xs font-semibold'">
                                            {{ canal.estado }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-2">
                                            <button
                                                @click="abrirModalEditar(canal)"
                                                title="Editar canal"
                                                class="p-2 bg-green-600 text-white hover:bg-green-500 rounded-md transition-colors shadow"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </button>
                                            <button
                                                @click="eliminarCanal(canal)"
                                                title="Eliminar canal"
                                                class="p-2 bg-red-600 text-white hover:bg-red-500 rounded-md transition-colors shadow"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="canales.data.length === 0">
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-400">
                                        No se encontraron canales que coincidan con la búsqueda.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación y Resumen -->
                    <div v-if="canales.data.length > 0" class="flex flex-col sm:flex-row justify-between items-center mt-6 pt-4 border-t border-gray-700 gap-4">
                        <div class="text-sm text-gray-400">
                            Mostrando del <span class="font-medium text-gray-200">{{ canales.from }}</span> al <span class="font-medium text-gray-200">{{ canales.to }}</span> de <span class="font-medium text-gray-200">{{ canales.total }}</span> resultados
                        </div>

                        <!-- Enlaces de Paginación -->
                        <div class="flex flex-wrap gap-1">
                            <template v-for="(link, key) in canales.links" :key="key">
                                <div
                                    v-if="link.url === null"
                                    v-html="link.label"
                                    class="px-3 py-1.5 text-xs text-gray-500 bg-gray-900 border border-gray-700 rounded cursor-not-allowed"
                                ></div>
                                <Link
                                    v-else
                                    :href="link.url"
                                    v-html="link.label"
                                    preserve-scroll
                                    class="px-3 py-1.5 text-xs border rounded transition-colors"
                                    :class="link.active ? 'bg-indigo-600 text-white border-indigo-600 font-semibold' : 'bg-gray-900 text-gray-300 border-gray-700 hover:bg-gray-700'"
                                />
                            </template>
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <!-- MODAL DINÁMICO (CREAR / EDITAR) -->
        <div v-if="showingModal" class="fixed inset-0 z-50 overflow-y-auto bg-black/70 flex items-center justify-center p-4">
            <div class="bg-gray-800 border border-gray-700 rounded-lg max-w-lg w-full p-6 shadow-xl relative">
                <div class="flex justify-between items-center pb-3 border-b border-gray-700 mb-4">
                    <h3 class="text-lg font-medium text-gray-100">
                        {{ editingCanal ? 'Editar Canal: ' + editingCanal.nombre : 'Registrar Nuevo Canal' }}
                    </h3>
                    <button @click="showingModal = false" class="text-gray-400 hover:text-gray-200 text-xl font-bold">&times;</button>
                </div>

                <form @submit.prevent="guardarCanal" class="space-y-4">
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
                        <div v-if="form.errors.logo" class="text-red-400 text-sm mt-1">{{ form.errors.logo }}</div>
                    </div>

                    <div>
                        <label class="block font-medium text-sm text-gray-300">Estado</label>
                        <select
                            v-model="form.estado"
                            class="mt-1 block w-full bg-gray-900 border-gray-700 text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        >
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                        <div v-if="form.errors.estado" class="text-red-400 text-sm mt-1">{{ form.errors.estado }}</div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-705">
                        <button
                            type="button"
                            @click="showingModal = false"
                            class="px-4 py-2 bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-300 uppercase tracking-widest hover:bg-gray-600 transition"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 transition"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            {{ editingCanal ? 'Actualizar Canal' : 'Guardar Canal' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
