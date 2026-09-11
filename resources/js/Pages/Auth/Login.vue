<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Iniciar Sesión - IPTV Multiview" />

    <div class="min-h-screen flex bg-gray-950 text-gray-100">

        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-black items-center justify-center">
            <img
                src="/image/FONDO IPTV.jpg"
                alt="Fondo IPTV"
                class="absolute inset-0 w-full h-full object-cover opacity-40 mix-blend-luminosity hover:mix-blend-normal transition-all duration-700"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-gray-950 via-gray-950/40 to-transparent"></div>

            <div class="relative z-10 p-12 flex flex-col items-center text-center space-y-6">
                <img src="/image/LOGO.png" alt="Logo IPTV Multiview" class="w-64 drop-shadow-[0_10px_10px_rgba(0,0,0,0.8)]" />
                <p class="text-sm text-gray-400 tracking-wider uppercase font-medium">
                    Monitoreo • Control • Calidad en Tiempo Real
                </p>
            </div>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center p-8 sm:p-12 bg-gray-900 border-l border-gray-800">
            <div class="w-full max-w-md space-y-8">

                <div class="flex lg:hidden justify-center mb-6">
                    <img src="/image/LOGO.png" alt="Logo" class="w-48" />
                </div>

                <div class="space-y-2">
                    <h2 class="text-2xl font-bold tracking-tight text-white">Bienvenido de nuevo</h2>
                    <p class="text-sm text-gray-400">Ingresa tus credenciales para acceder a la Sala de Monitoreo.</p>
                </div>

                <div v-if="status" class="mb-4 text-sm font-medium text-emerald-400 bg-emerald-950/50 border border-emerald-800 p-3 rounded-md">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <InputLabel for="email" value="Correo Electrónico" class="text-gray-300" />
                        <TextInput
                            id="email"
                            type="email"
                            class="mt-1 block w-full bg-gray-950 border-gray-700 text-gray-100 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="admin@multiview.test"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div>
                        <InputLabel for="password" value="Contraseña" class="text-gray-300" />
                        <TextInput
                            id="password"
                            type="password"
                            class="mt-1 block w-full bg-gray-950 border-gray-700 text-gray-100 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                        />
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <Checkbox name="remember" v-model:checked="form.remember" class="bg-gray-950 border-gray-700 text-emerald-600 focus:ring-emerald-500 rounded" />
                            <span class="ms-2 text-sm text-gray-400">Recordarme</span>
                        </label>

                        <Link
                            v-if="canResetPassword"
                            :route="route('password.request')"
                            class="text-sm text-emerald-400 hover:text-emerald-300 underline focus:outline-none"
                        >
                            ¿Olvidaste tu contraseña?
                        </Link>
                    </div>

                    <div>
                        <PrimaryButton
                            class="w-full justify-center bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-gray-950 font-bold py-3 transition-all shadow-lg shadow-emerald-950/50"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            Ingresar al Sistema
                        </PrimaryButton>
                    </div>
                </form>

                <div class="text-center pt-4 border-t border-gray-800 text-xs text-gray-500">
                    IPTV Multiview • Santa Cruz - Bolivia
                </div>

            </div>
        </div>

    </div>
</template>
