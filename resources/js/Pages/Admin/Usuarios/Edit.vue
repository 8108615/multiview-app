<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import Swal from 'sweetalert2';

// Recibimos la prop 'usuario' que nos manda el controlador
const props = defineProps({
    usuario: Object,
});

// Definimos el formulario con los datos actuales del usuario
const form = useForm({
    name: props.usuario.name || '',
    email: props.usuario.email || '',
    password: '',
    password_confirmation: '',
    foto: null,
});

// Referencia para la previsualización de la imagen (inicializada con la foto actual si existe)
const photoPreview = ref(props.usuario.foto ? `/storage/${props.usuario.foto}` : null);
const fileInput = ref(null);

// Capturar el nuevo archivo y generar la previsualización
const updatePhotoPreview = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    form.foto = file;

    const reader = new FileReader();
    reader.onload = (e) => {
        photoPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
};

// Enviar el formulario por POST (usando _method: PUT implícito o post directo según Laravel/Inertia)
const submit = () => {
    // Como enviamos un archivo (FormData), con Inertia es recomendable usar post y simular el PUT si es necesario,
    // o simplemente usar form.post apuntando a la ruta update.
    form.post(route('usuarios.update', props.usuario.id), {
        onSuccess: () => {
            Swal.fire({
                title: '¡Actualizado!',
                text: 'El usuario ha sido modificado exitosamente.',
                icon: 'success',
                timer: 4000,
                timerProgressBar: true,
                showConfirmButton: false,
                background: '#1f2937',
                color: '#f9fafb',
            });
        },
    });
};
</script>

<template>
    <Head title="Editar Usuario" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-100 leading-tight">
                    Editar Usuario: {{ usuario.name }}
                </h2>
                <Link
                    :href="route('usuarios.index')"
                    class="inline-flex items-center px-4 py-2 bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-600 transition ease-in-out duration-150"
                >
                    Volver
                </Link>
            </div>
        </template>

        <div class="py-12 bg-gray-900 min-h-screen">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 sm:p-8 bg-gray-800 shadow sm:rounded-lg border border-gray-700">

                    <form @submit.prevent="submit" class="space-y-6">

                        <!-- Sección de Foto de Perfil con Previsualización -->
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Foto de Perfil</label>
                            <div class="flex items-center gap-6">
                                <!-- Contenedor de la previsualización -->
                                <div class="shrink-0">
                                    <img
                                        v-if="photoPreview"
                                        :src="photoPreview"
                                        class="h-20 w-20 object-cover rounded-lg border-2 border-gray-600 shadow-md"
                                        alt="Vista previa"
                                    />
                                    <div
                                        v-else
                                        class="h-20 w-20 rounded-lg bg-gray-900 border-2 border-gray-700 flex items-center justify-center text-gray-500 shadow-md"
                                    >
                                        <!-- Icono por defecto de usuario -->
                                        <svg class="w-10 h-10 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                    </div>
                                </div>

                                <!-- Input oculto y botón personalizado -->
                                <div class="flex flex-col">
                                    <input
                                        type="file"
                                        ref="fileInput"
                                        @change="updatePhotoPreview"
                                        accept="image/png, image/jpeg, image/jpg"
                                        class="hidden"
                                    />
                                    <button
                                        type="button"
                                        @click="$refs.fileInput.click()"
                                        class="inline-flex items-center px-4 py-2.5 bg-white text-gray-800 font-semibold text-sm rounded-lg shadow hover:bg-gray-100 transition ease-in-out duration-150"
                                    >
                                        <!-- Icono de nube -->
                                        <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        Cambiar Foto
                                    </button>
                                    <span class="text-xs text-gray-400 mt-1 italic truncate max-w-xs">
                                        {{ form.foto ? form.foto.name : 'Dejar en blanco para conservar la actual' }}
                                    </span>
                                </div>
                            </div>
                            <div v-if="form.errors.foto" class="text-red-400 text-xs mt-1">{{ form.errors.foto }}</div>
                        </div>

                        <!-- Grid para los demás campos -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Nombre -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-300">Nombre Completo (*)</label>
                                <input
                                    id="name"
                                    type="text"
                                    v-model="form.name"
                                    required
                                    class="mt-1 block w-full bg-gray-900 border border-gray-700 text-gray-100 text-sm rounded-md shadow-sm p-2.5 focus:ring-indigo-500 focus:border-indigo-500"
                                />
                                <div v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</div>
                            </div>

                            <!-- Correo Electrónico -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-300">Correo Electrónico (*)</label>
                                <input
                                    id="email"
                                    type="email"
                                    v-model="form.email"
                                    required
                                    class="mt-1 block w-full bg-gray-900 border border-gray-700 text-gray-100 text-sm rounded-md shadow-sm p-2.5 focus:ring-indigo-500 focus:border-indigo-500"
                                />
                                <div v-if="form.errors.email" class="text-red-400 text-xs mt-1">{{ form.errors.email }}</div>
                            </div>

                            <!-- Contraseña -->
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-300">Nueva Contraseña (Opcional)</label>
                                <input
                                    id="password"
                                    type="password"
                                    v-model="form.password"
                                    class="mt-1 block w-full bg-gray-900 border border-gray-700 text-gray-100 text-sm rounded-md shadow-sm p-2.5 focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Dejar en blanco para no cambiar"
                                />
                                <div v-if="form.errors.password" class="text-red-400 text-xs mt-1">{{ form.errors.password }}</div>
                            </div>

                            <!-- Confirmar Contraseña -->
                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-300">Confirmar Nueva Contraseña</label>
                                <input
                                    id="password_confirmation"
                                    type="password"
                                    v-model="form.password_confirmation"
                                    class="mt-1 block w-full bg-gray-900 border border-gray-700 text-gray-100 text-sm rounded-md shadow-sm p-2.5 focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Repita la nueva contraseña"
                                />
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-700">
                            <Link
                                :href="route('usuarios.index')"
                                class="px-5 py-2.5 bg-gray-700 text-gray-300 rounded-lg text-sm font-semibold hover:bg-gray-600 transition"
                            >
                                Cancelar
                            </Link>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center px-5 py-2.5 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-25"
                            >
                                Actualizar Usuario
                            </button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
