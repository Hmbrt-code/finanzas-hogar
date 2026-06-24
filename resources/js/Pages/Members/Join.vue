<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    household:   Object,
    inviteCode:  String,
    currentUser: Object,
});

const form = useForm({});

function join() {
    form.post(route('members.acceptJoin', props.inviteCode));
}
</script>

<template>
    <Head title="Unirse a un hogar" />

    <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
        <div class="w-full max-w-md">

            <!-- Logo / marca -->
            <div class="flex justify-center mb-8">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-white">
                            <path d="M11.25 4.533A9.707 9.707 0 006 3a9.735 9.735 0 00-3.25.555.75.75 0 00-.5.707v14.25a.75.75 0 001 .707A8.237 8.237 0 016 18.75c1.995 0 3.823.707 5.25 1.886V4.533zM12.75 20.636A8.214 8.214 0 0118 18.75c.966 0 1.89.166 2.75.47a.75.75 0 001-.708V4.262a.75.75 0 00-.5-.707A9.735 9.735 0 0018 3a9.707 9.707 0 00-5.25 1.533v16.103z" />
                        </svg>
                    </div>
                    <span class="text-lg font-bold text-gray-800">Finanzas Hogar</span>
                </div>
            </div>

            <!-- Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

                <!-- Encabezado -->
                <div class="bg-indigo-50 px-6 py-5 border-b border-indigo-100">
                    <p class="text-sm text-indigo-600 font-medium mb-1">Invitación al hogar</p>
                    <h1 class="text-xl font-bold text-indigo-900">{{ household.name }}</h1>
                    <p class="text-xs text-indigo-500 mt-1">Has sido invitado/a a unirte a este hogar.</p>
                </div>

                <!-- Cuerpo -->
                <div class="px-6 py-5 space-y-4">

                    <!-- Usuario actual -->
                    <div class="flex items-center gap-3 bg-gray-50 rounded-xl px-4 py-3">
                        <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-sm shrink-0">
                            {{ currentUser.name.charAt(0).toUpperCase() }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">{{ currentUser.name }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ currentUser.email }}</p>
                        </div>
                    </div>

                    <!-- Advertencia -->
                    <div class="flex gap-3 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
                        <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <p class="text-xs text-amber-700 leading-relaxed">
                            Al aceptar, saldrás de tu hogar actual. Si eras el único integrante,
                            ese hogar y todas sus categorías serán eliminados permanentemente.
                        </p>
                    </div>

                </div>

                <!-- Acciones -->
                <div class="px-6 pb-6 flex gap-3">
                    <a
                        :href="route('members.index')"
                        class="flex-1 text-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors"
                    >
                        Cancelar
                    </a>
                    <button
                        @click="join"
                        :disabled="form.processing"
                        class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-colors disabled:opacity-60"
                    >
                        <span v-if="form.processing">Uniéndose…</span>
                        <span v-else>Unirme al hogar</span>
                    </button>
                </div>
            </div>

            <p class="text-center text-xs text-gray-400 mt-4">
                Código: <span class="font-mono font-semibold tracking-wider">{{ inviteCode }}</span>
            </p>
        </div>
    </div>
</template>
