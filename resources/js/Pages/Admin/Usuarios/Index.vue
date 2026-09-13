<script setup>
import { ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    usuarios: Object,
    filters: Object,
});

// Controladores reactivos para búsqueda y paginación
const search = ref(props.filters.search || '');
const perPage = ref(props.usuarios.per_page || 10);

// Búsqueda fluida con retraso (debounce)
let searchTimeout = null;
watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            route('usuarios.index'),
            { search: value, per_page: perPage.value },
            { preserveState: true, replace: true }
        );
    }, 300);
});

// Selector de registros por página
const cambiarPorPagina = () => {
    router.get(
        route('usuarios.index'),
        { search: search.value, per_page: perPage.value },
        { preserveState: true, replace: true }
    );
};

// Navegar a la vista de creación
const irACrear = () => {
    router.get(route('usuarios.create'));
};

// Navegar a la vista de edición
const irAEditar = (usuario) => {
    router.get(route('usuarios.edit', usuario.id));
};

// Eliminar usuario con SweetAlert2
const eliminarUsuario = (usuario) => {
    Swal.fire({
        title: '¿Estás seguro?',
        text: `¡No podrás revertir esto! El usuario "${usuario.name}" será eliminado permanentemente.`,
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
            router.delete(route('usuarios.destroy', usuario.id), {
                onSuccess: () => {
                    Swal.fire({
                        title: '¡Eliminado!',
                        text: 'El usuario ha sido eliminado exitosamente.',
                        icon: 'success',
                        timer: 4000,
                        timerProgressBar: true,
                        showConfirmButton: false,
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
    <Head title="Gestión de Usuarios" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-100 leading-tight">
                    Gestión de Usuarios
                </h2>
                <button
                    @click="irACrear"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 transition ease-in-out duration-150"
                >
                    + Nuevo Usuario
                </button>
            </div>
        </template>

        <div class="py-12 bg-gray-900 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Listado -->
                <div class="p-4 sm:p-8 bg-gray-800 shadow sm:rounded-lg border border-gray-700">

                    <!-- Cabecera de la tabla con Buscador y Selector -->
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                        <h3 class="text-lg font-medium text-gray-100 w-full md:w-auto">Usuarios Registrados</h3>

                        <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto justify-end">
                            <div class="relative w-full sm:w-64">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </span>
                                <input
                                    type="text"
                                    v-model="search"
                                    placeholder="Buscar usuario..."
                                    class="w-full bg-gray-900 border border-gray-700 text-gray-100 text-sm rounded-md pl-9 pr-4 py-1.5 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                />
                            </div>

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

                    <!-- Tabla -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-700">
                            <thead class="bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Nº</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Foto</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Nombre</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Correo</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-gray-800 divide-y divide-gray-700">
                                <tr v-for="(usuario, index) in usuarios.data" :key="usuario.id" class="hover:bg-gray-700/30 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        {{ (usuarios.current_page - 1) * usuarios.per_page + index + 1 }}
                                    </td>

                                    <!-- Celda para la Foto -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        <div v-if="usuario.foto" class="flex-shrink-0 h-10 w-10">
                                            <img :src="`/storage/${usuario.foto}`" alt="Foto de perfil" class="h-10 w-10 rounded-full object-cover border border-gray-700 shadow" />
                                        </div>
                                        <div v-else class="flex-shrink-0 h-10 w-10 bg-gray-700 rounded-full flex items-center justify-center text-gray-400 text-xs font-bold border border-gray-600 shadow">
                                            Sin Foto
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-100">{{ usuario.name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">{{ usuario.email }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-2">
                                            <button @click="irAEditar(usuario)" class="p-2 bg-green-600 text-white hover:bg-green-500 rounded-md transition-colors shadow">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </button>
                                            <button @click="eliminarUsuario(usuario)" class="p-2 bg-red-600 text-white hover:bg-red-500 rounded-md transition-colors shadow">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="usuarios.data.length === 0">
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-400">
                                        No se encontraron usuarios.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div v-if="usuarios.data.length > 0" class="flex flex-col sm:flex-row justify-between items-center mt-6 pt-4 border-t border-gray-700 gap-4">
                        <div class="text-sm text-gray-400">
                            Mostrando del <span class="font-medium text-gray-200">{{ usuarios.from }}</span> al <span class="font-medium text-gray-200">{{ usuarios.to }}</span> de <span class="font-medium text-gray-200">{{ usuarios.total }}</span> resultados
                        </div>
                        <div class="flex flex-wrap gap-1">
                            <template v-for="(link, key) in usuarios.links" :key="key">
                                <div v-if="link.url === null" v-html="link.label" class="px-3 py-1.5 text-xs text-gray-500 bg-gray-900 border border-gray-700 rounded cursor-not-allowed"></div>
                                <Link v-else :href="link.url" v-html="link.label" preserve-scroll class="px-3 py-1.5 text-xs border rounded transition-colors" :class="link.active ? 'bg-indigo-600 text-white border-indigo-600 font-semibold' : 'bg-gray-900 text-gray-300 border-gray-700 hover:bg-gray-700'" />
                            </template>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
