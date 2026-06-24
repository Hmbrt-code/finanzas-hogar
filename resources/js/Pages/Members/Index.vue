<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useConfirm } from '@/composables/useConfirm';

const { confirm } = useConfirm();

const props = defineProps({
    members:     Array,
    inviteCode:  String,
    inviteUrl:   String,
    currentRole: String,
    household:   Object,
});

const isAdmin = props.currentRole === 'admin';

// ── Copiar enlace ─────────────────────────────────────────────
const copied = ref(false);
function copyLink() {
    navigator.clipboard.writeText(props.inviteUrl).then(() => {
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    });
}

// ── Cambiar rol ───────────────────────────────────────────────
function changeRole(member, role) {
    if (member.role === role) return;
    useForm({ role }).patch(route('members.updateRole', member.id), {
        preserveScroll: true,
    });
}

// ── Eliminar miembro ──────────────────────────────────────────
async function removeMember(member) {
    const ok = await confirm({
        title:        member.is_self ? 'Salir del hogar' : 'Eliminar miembro',
        message:      member.is_self
            ? '¿Salir de este hogar? Serás desconectado.'
            : `¿Eliminar a "${member.name}" del hogar?`,
        confirmLabel: member.is_self ? 'Salir' : 'Eliminar',
        danger:       true,
    });
    if (!ok) return;
    useForm({}).delete(route('members.destroy', member.id), {
        preserveScroll: true,
    });
}

// ── Regenerar código ──────────────────────────────────────────
async function regenerateCode() {
    const ok = await confirm({
        title:        'Regenerar código',
        message:      '¿Generar un nuevo código? El enlace anterior dejará de funcionar.',
        confirmLabel: 'Generar',
        danger:       false,
    });
    if (!ok) return;
    useForm({}).post(route('members.regenerateCode'), {
        preserveScroll: true,
    });
}

// ── Helpers de badge ──────────────────────────────────────────
const roleBadge = {
    admin:    'bg-indigo-100 text-indigo-700',
    member:   'bg-gray-100 text-gray-600',
    readonly: 'bg-yellow-100 text-yellow-700',
};
const roleLabel = {
    admin:    'Admin',
    member:   'Miembro',
    readonly: 'Solo lectura',
};
</script>

<template>
    <Head title="Miembros" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-lg font-semibold text-gray-800">
                Miembros — {{ household.name }}
            </h1>
        </template>

        <div class="space-y-6">

            <!-- ── Lista de miembros ──────────────────────────────── -->
            <section class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-700">Integrantes</h2>
                    <span class="text-xs text-gray-400 font-medium">
                        {{ members.length }} {{ members.length === 1 ? 'persona' : 'personas' }}
                    </span>
                </div>

                <ul class="divide-y divide-gray-50">
                    <li
                        v-for="m in members"
                        :key="m.id"
                        class="flex items-center gap-4 px-5 py-4"
                    >
                        <!-- Avatar inicial -->
                        <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-sm shrink-0 select-none">
                            {{ m.name.charAt(0).toUpperCase() }}
                        </div>

                        <!-- Nombre + email -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">
                                {{ m.name }}
                                <span v-if="m.is_self" class="ml-1 text-xs text-gray-400 font-normal">(tú)</span>
                            </p>
                            <p v-if="m.user_email" class="text-xs text-gray-400 truncate">{{ m.user_email }}</p>
                            <p v-else class="text-xs text-gray-400 italic">Sin cuenta vinculada</p>
                        </div>

                        <!-- Rol: selector para admin (sobre otros), badge de lo contrario -->
                        <div class="shrink-0">
                            <select
                                v-if="isAdmin && !m.is_self"
                                :value="m.role"
                                @change="e => changeRole(m, e.target.value)"
                                class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-400 cursor-pointer"
                            >
                                <option value="admin">Admin</option>
                                <option value="member">Miembro</option>
                                <option value="readonly">Solo lectura</option>
                            </select>
                            <span
                                v-else
                                class="inline-block text-xs font-medium px-2.5 py-1 rounded-full"
                                :class="roleBadge[m.role]"
                            >
                                {{ roleLabel[m.role] }}
                            </span>
                        </div>

                        <!-- Botón eliminar / salir -->
                        <button
                            v-if="isAdmin || m.is_self"
                            @click="removeMember(m)"
                            class="shrink-0 p-1.5 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                            :title="m.is_self ? 'Salir del hogar' : 'Eliminar miembro'"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1" />
                            </svg>
                        </button>
                    </li>
                </ul>
            </section>

            <!-- ── Sección invitación (solo admin) ───────────────── -->
            <section v-if="isAdmin" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Invitar al hogar</h2>

                <!-- Código + botón regenerar -->
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex-1 bg-gray-50 border border-gray-200 rounded-xl px-4 py-3">
                        <p class="text-xs text-gray-400 mb-0.5">Código de invitación</p>
                        <p class="text-xl font-mono font-bold text-indigo-700 tracking-widest">
                            {{ inviteCode }}
                        </p>
                    </div>
                    <button
                        @click="regenerateCode"
                        class="shrink-0 p-2.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-colors"
                        title="Generar nuevo código"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>

                <!-- Enlace completo + copiar -->
                <div class="flex items-center gap-2">
                    <input
                        :value="inviteUrl"
                        readonly
                        class="flex-1 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-400 cursor-text"
                        @click="e => e.target.select()"
                    />
                    <button
                        @click="copyLink"
                        class="shrink-0 inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                        :class="copied
                            ? 'bg-emerald-100 text-emerald-700'
                            : 'bg-indigo-600 hover:bg-indigo-700 text-white'"
                    >
                        <svg v-if="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ copied ? 'Copiado' : 'Copiar' }}
                    </button>
                </div>

                <p class="text-xs text-gray-400 mt-3 leading-relaxed">
                    Comparte este enlace con quien quieras agregar al hogar.
                    Al unirse, saldrán de su hogar actual.
                </p>
            </section>

            <!-- Mensaje para no-admins -->
            <section v-else class="bg-gray-50 rounded-xl border border-gray-200 px-5 py-4">
                <p class="text-sm text-gray-500 text-center">
                    Solo los administradores pueden invitar nuevos integrantes al hogar.
                </p>
            </section>

        </div>
    </AuthenticatedLayout>
</template>
