<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { useConfirm } from '@/composables/useConfirm';

const { confirm } = useConfirm();

const props = defineProps({
    pending:           Array,   // egresos pendientes de confirmar
    transactions:      Array,   // confirmados + ingresos
    members:           Array,
    incomeCategories:  Array,
    expenseCategories: Array,
    filters:           Object,
});

// ── Filtro de mes ────────────────────────────────────────────
const year  = ref(props.filters.year);
const month = ref(props.filters.month);

const months = [
    { value: 1,  label: 'Enero' },
    { value: 2,  label: 'Febrero' },
    { value: 3,  label: 'Marzo' },
    { value: 4,  label: 'Abril' },
    { value: 5,  label: 'Mayo' },
    { value: 6,  label: 'Junio' },
    { value: 7,  label: 'Julio' },
    { value: 8,  label: 'Agosto' },
    { value: 9,  label: 'Septiembre' },
    { value: 10, label: 'Octubre' },
    { value: 11, label: 'Noviembre' },
    { value: 12, label: 'Diciembre' },
];

const currentYear = new Date().getFullYear();
const years = Array.from({ length: 4 }, (_, i) => currentYear - i);

function navigate() {
    router.get(route('transactions.index'), { year: year.value, month: month.value }, { preserveState: false });
}

// ── Totales (solo confirmados) ───────────────────────────────
const totalIncome  = computed(() => props.transactions.filter(t => t.type === 'income').reduce((s, t) => s + parseFloat(t.amount), 0));
const totalExpense = computed(() => props.transactions.filter(t => t.type === 'expense').reduce((s, t) => s + parseFloat(t.amount), 0));
const balance      = computed(() => totalIncome.value - totalExpense.value);
const totalPending = computed(() => props.pending.reduce((s, t) => s + parseFloat(t.amount), 0));

// ── Filtro por miembro ───────────────────────────────────────
const filterMember = ref('all');
const filtered = computed(() =>
    filterMember.value === 'all'
        ? props.transactions
        : props.transactions.filter(t => t.member.id === parseInt(filterMember.value))
);

// ── Modal ────────────────────────────────────────────────────
const showModal = ref(false);
const editing   = ref(null);
const modalType = ref('expense');

const form = useForm({
    type:                 'expense',
    member_id:            '',
    category_id:          '',
    amount:               '',
    date:                 new Date().toISOString().slice(0, 10),
    description:          '',
    is_recurring:         false,
    recurrence_frequency: '',
});

const currentCategories = computed(() =>
    modalType.value === 'income' ? props.incomeCategories : props.expenseCategories
);

function openCreate(type) {
    modalType.value = type;
    editing.value   = null;
    form.reset();
    form.type        = type;
    form.date        = new Date().toISOString().slice(0, 10);
    form.member_id   = props.members[0]?.id ?? '';
    showModal.value  = true;
}

onMounted(() => {
    if (props.filters.quick_category_id) {
        const type = props.filters.quick_type || 'expense';
        openCreate(type);
        form.category_id = props.filters.quick_category_id;
    }
});

function openEdit(t) {
    modalType.value           = t.type;
    editing.value             = t;
    form.type                 = t.type;
    form.member_id            = t.member.id;
    form.category_id          = t.category.id;
    form.amount               = t.amount;
    form.date                 = t.date;
    form.description          = t.description ?? '';
    form.is_recurring         = t.is_recurring;
    form.recurrence_frequency = t.recurrence_frequency ?? '';
    showModal.value           = true;
}

function save() {
    if (editing.value) {
        form.put(route('transactions.update', editing.value.id), {
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post(route('transactions.store'), {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    }
}

function confirmTransaction(t) {
    useForm({}).patch(route('transactions.confirm', t.id));
}

async function destroy(t) {
    const ok = await confirm({
        title:        'Eliminar transacción',
        message:      `¿Eliminar esta transacción de ${fmt(t.amount)}?`,
        confirmLabel: 'Eliminar',
        danger:       true,
    });
    if (ok) {
        useForm({}).delete(route('transactions.destroy', t.id));
    }
}

function fmt(amount) {
    return new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(amount);
}
function fmtDate(d) {
    const [y, m, day] = d.split('-');
    return `${day}/${m}/${y}`;
}
</script>

<template>
    <Head title="Transacciones" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-lg font-semibold text-gray-800">Transacciones</h1>
        </template>

        <!-- Selector de mes + botones -->
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-2">
                <select
                    v-model.number="month"
                    @change="navigate"
                    class="text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg px-3 py-2 pr-8 appearance-none cursor-pointer hover:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-300 transition-colors"
                >
                    <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                </select>
                <select
                    v-model.number="year"
                    @change="navigate"
                    class="text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg px-3 py-2 pr-8 appearance-none cursor-pointer hover:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-300 transition-colors"
                >
                    <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button @click="openCreate('income')"
                    class="inline-flex items-center gap-1.5 bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-medium px-3 py-2 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Ingreso
                </button>
                <button @click="openCreate('expense')"
                    class="inline-flex items-center gap-1.5 bg-red-500 hover:bg-red-600 text-white text-sm font-medium px-3 py-2 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Egreso
                </button>
            </div>
        </div>

        <!-- ── Egresos pendientes de confirmar ───────────────────────── -->
        <div v-if="pending.length" class="mb-5">
            <div class="bg-amber-50 border border-amber-200 rounded-xl overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-amber-200">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-sm font-semibold text-amber-800">
                            Egresos pendientes de confirmar
                        </span>
                        <span class="text-xs bg-amber-200 text-amber-800 font-bold px-2 py-0.5 rounded-full">{{ pending.length }}</span>
                    </div>
                    <span class="text-sm font-bold text-amber-700">{{ fmt(totalPending) }}</span>
                </div>

                <div class="divide-y divide-amber-100">
                    <div v-for="t in pending" :key="t.id"
                        class="flex items-center gap-3 px-4 py-3 hover:bg-amber-100/50 transition-colors group">
                        <!-- Categoría -->
                        <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs font-bold shrink-0"
                            :style="{ backgroundColor: t.category.color ?? '#6366f1' }">
                            {{ t.category.icon || t.category.name.charAt(0) }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800">{{ t.category.name }}</p>
                            <p class="text-xs text-gray-500">{{ t.member.name }} · {{ fmtDate(t.date) }}
                                <span v-if="t.description"> · {{ t.description }}</span>
                            </p>
                        </div>
                        <span class="text-sm font-bold text-amber-700 shrink-0">{{ fmt(t.amount) }}</span>

                        <!-- Acciones -->
                        <div class="flex items-center gap-1 shrink-0">
                            <!-- Confirmar -->
                            <button @click="confirmTransaction(t)"
                                class="flex items-center gap-1 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-medium px-2.5 py-1.5 rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                Confirmar
                            </button>
                            <!-- Editar -->
                            <button @click="openEdit(t)"
                                class="p-1.5 text-gray-400 hover:text-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity rounded">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.5-6.5a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H9v-2a2 2 0 01.586-1.414z" />
                                </svg>
                            </button>
                            <!-- Eliminar -->
                            <button @click="destroy(t)"
                                class="p-1.5 text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity rounded">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m-7 0a1 1 0 01-1-1V5a1 1 0 011-1h8a1 1 0 011 1v1a1 1 0 01-1 1H9z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Tarjetas resumen (solo confirmados) ───────────────────── -->
        <div class="grid grid-cols-3 gap-3 mb-5">
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <p class="text-xs text-gray-500 mb-1">Ingresos confirmados</p>
                <p class="text-xl font-bold text-emerald-600">{{ fmt(totalIncome) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <p class="text-xs text-gray-500 mb-1">Egresos confirmados</p>
                <p class="text-xl font-bold text-red-500">{{ fmt(totalExpense) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <p class="text-xs text-gray-500 mb-1">Balance real</p>
                <p class="text-xl font-bold" :class="balance >= 0 ? 'text-gray-800' : 'text-red-600'">{{ fmt(balance) }}</p>
            </div>
        </div>

        <!-- Filtro por miembro -->
        <div class="flex gap-2 mb-3 flex-wrap">
            <button @click="filterMember = 'all'"
                :class="filterMember === 'all' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200'"
                class="text-xs font-medium px-3 py-1.5 rounded-full transition-colors">Todos</button>
            <button v-for="m in members" :key="m.id" @click="filterMember = String(m.id)"
                :class="filterMember === String(m.id) ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200'"
                class="text-xs font-medium px-3 py-1.5 rounded-full transition-colors">{{ m.name }}</button>
        </div>

        <!-- ── Tabla historial confirmado ────────────────────────────── -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div v-if="filtered.length === 0" class="p-8 text-center text-sm text-gray-400 italic">
                Sin transacciones confirmadas en este período.
            </div>
            <table v-else class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Fecha</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Categoría</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide hidden sm:table-cell">Miembro</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide hidden md:table-cell">Descripción</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Monto</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="t in filtered" :key="t.id" class="hover:bg-gray-50 group">
                        <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">{{ fmtDate(t.date) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-md flex items-center justify-center text-white text-xs font-bold shrink-0"
                                    :style="{ backgroundColor: t.category.color ?? '#6366f1' }">
                                    {{ t.category.icon || t.category.name.charAt(0) }}
                                </span>
                                <span class="text-gray-700">{{ t.category.name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-500 hidden sm:table-cell">{{ t.member.name }}</td>
                        <td class="px-4 py-3 text-gray-400 hidden md:table-cell">{{ t.description || '—' }}</td>
                        <td class="px-4 py-3 text-right font-semibold whitespace-nowrap"
                            :class="t.type === 'income' ? 'text-emerald-600' : 'text-red-500'">
                            {{ t.type === 'income' ? '+' : '-' }}{{ fmt(t.amount) }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity justify-end">
                                <button @click="openEdit(t)" class="p-1 text-gray-400 hover:text-indigo-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.5-6.5a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H9v-2a2 2 0 01.586-1.414z" />
                                    </svg>
                                </button>
                                <button @click="destroy(t)" class="p-1 text-gray-400 hover:text-red-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m-7 0a1 1 0 01-1-1V5a1 1 0 011-1h8a1 1 0 011 1v1a1 1 0 01-1 1H9z" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40" @click="showModal = false" />
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                    <div class="flex items-center gap-3 mb-5">
                        <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white shrink-0"
                            :class="modalType === 'income' ? 'bg-emerald-500' : 'bg-red-500'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    :d="modalType === 'income' ? 'M12 19V5m0 0l-7 7m7-7l7 7' : 'M12 5v14m0 0l7-7m-7 7l-7-7'" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-base font-semibold text-gray-900">
                                {{ editing ? 'Editar' : 'Nuevo' }} {{ modalType === 'income' ? 'ingreso' : 'egreso' }}
                            </h2>
                            <p v-if="modalType === 'expense' && !editing" class="text-xs text-amber-600">
                                Quedará pendiente hasta que lo confirmes
                            </p>
                        </div>
                    </div>

                    <form @submit.prevent="save" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Miembro</label>
                            <select v-model="form.member_id" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="" disabled>Seleccionar…</option>
                                <option v-for="m in members" :key="m.id" :value="m.id">{{ m.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                            <select v-model="form.category_id" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="" disabled>Seleccionar…</option>
                                <option v-for="c in currentCategories" :key="c.id" :value="c.id">
                                    {{ c.icon ? c.icon + ' ' : '' }}{{ c.name }}
                                </option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Monto</label>
                                <input v-model="form.amount" type="number" min="1" step="1" placeholder="0" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
                                <input v-model="form.date" type="date" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descripción <span class="text-gray-400">(opcional)</span></label>
                            <input v-model="form.description" type="text" placeholder="Nota adicional…"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                        </div>

                        <div>
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input v-model="form.is_recurring" type="checkbox"
                                    class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                <span class="text-sm text-gray-700">¿Es recurrente?</span>
                            </label>
                            <select v-if="form.is_recurring" v-model="form.recurrence_frequency"
                                class="mt-2 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Seleccionar frecuencia…</option>
                                <option value="daily">Diaria</option>
                                <option value="weekly">Semanal</option>
                                <option value="monthly">Mensual</option>
                                <option value="yearly">Anual</option>
                            </select>
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showModal = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="form.processing"
                                class="px-4 py-2 text-sm font-medium text-white rounded-lg transition-colors disabled:opacity-50"
                                :class="modalType === 'income' ? 'bg-emerald-500 hover:bg-emerald-600' : 'bg-red-500 hover:bg-red-600'">
                                {{ editing ? 'Guardar' : (modalType === 'income' ? 'Registrar ingreso' : 'Registrar egreso') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
