<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useConfirm } from '@/composables/useConfirm';

const { confirm } = useConfirm();

const props = defineProps({
    plans:             Array,
    members:           Array,
    expenseCategories: Array,
});

// ── Helpers ──────────────────────────────────────────────────
const fmt = (n) => new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(n);

const fmtDate = (d) => {
    if (!d) return '';
    const [y, m, day] = d.split('-');
    const months = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
    return `${parseInt(day)} ${months[parseInt(m) - 1]} ${y}`;
};

const typeConfig = {
    purchase: { label: 'Compra',  emoji: '🛒', color: 'bg-red-100 text-red-700',    bar: 'bg-red-400'   },
    debt:     { label: 'Deuda',   emoji: '💳', color: 'bg-amber-100 text-amber-700', bar: 'bg-amber-400' },
    saving:   { label: 'Ahorro',  emoji: '🐷', color: 'bg-emerald-100 text-emerald-700', bar: 'bg-emerald-400' },
};

// ── Listas computadas ─────────────────────────────────────────
const activePlans    = computed(() => props.plans.filter(p => p.status === 'active'));
const completedPlans = computed(() => props.plans.filter(p => p.status === 'completed'));
const cancelledPlans = computed(() => props.plans.filter(p => p.status === 'cancelled'));

const monthlyCommitment = computed(() =>
    activePlans.value.reduce((sum, p) => sum + p.installment_amount, 0)
);

// ── Toggle sección completados ────────────────────────────────
const showCompleted = ref(false);

// ── Modal Crear/Editar ────────────────────────────────────────
const showModal = ref(false);
const editingPlan = ref(null);

const form = useForm({
    name:               '',
    type:               'purchase',
    category_id:        null,
    member_id:          '',
    total_amount:       '',
    installment_amount: '',
    total_installments: '',
    paid_installments:  0,
    start_date:         new Date().toISOString().slice(0, 10),
    notes:              '',
});

function autoComputeInstallment() {
    if (form.total_amount > 0 && form.total_installments > 0) {
        form.installment_amount = Math.round(form.total_amount / form.total_installments);
    }
}

function openCreate() {
    editingPlan.value = null;
    form.reset();
    form.type       = 'purchase';
    form.member_id  = props.members[0]?.id ?? '';
    form.start_date = new Date().toISOString().slice(0, 10);
    showModal.value = true;
}

function openEdit(plan) {
    editingPlan.value = plan;
    form.name               = plan.name;
    form.type               = plan.type;
    form.category_id        = plan.category?.id ?? null;
    form.member_id          = plan.member.id;
    form.total_amount       = plan.total_amount;
    form.installment_amount = plan.installment_amount;
    form.total_installments = plan.total_installments;
    form.paid_installments  = plan.paid_installments;
    form.start_date         = plan.start_date;
    form.notes              = plan.notes ?? '';
    showModal.value         = true;
}

function savePlan() {
    if (editingPlan.value) {
        form.patch(route('plans.update', editingPlan.value.id), {
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post(route('plans.store'), {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    }
}

async function deletePlan(plan) {
    const ok = await confirm({
        title:        'Eliminar plan',
        message:      `¿Eliminar el plan "${plan.name}"? Las transacciones ya registradas permanecerán.`,
        confirmLabel: 'Eliminar',
        danger:       true,
    });
    if (!ok) return;
    useForm({}).delete(route('plans.destroy', plan.id));
}

// ── Modal Pagar cuota ─────────────────────────────────────────
const showPayModal  = ref(false);
const payingPlan    = ref(null);

const payForm = useForm({
    member_id:   '',
    date:        new Date().toISOString().slice(0, 10),
    description: '',
});

function openPay(plan) {
    payingPlan.value     = plan;
    payForm.member_id    = plan.member.id;
    payForm.date         = new Date().toISOString().slice(0, 10);
    payForm.description  = '';
    showPayModal.value   = true;
}

function submitPay() {
    payForm.post(route('plans.pay', payingPlan.value.id), {
        onSuccess: () => { showPayModal.value = false; },
    });
}
</script>

<template>
    <Head title="Planes en Cuotas" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-lg font-semibold text-gray-800">Planes en Cuotas</h1>
        </template>

        <!-- Header row -->
        <div class="flex items-center justify-between mb-5">
            <div class="flex gap-3 flex-wrap">
                <!-- Stat chips -->
                <div class="flex items-center gap-1.5 bg-white border border-gray-200 rounded-lg px-3 py-1.5 text-sm">
                    <span class="font-semibold text-indigo-600">{{ activePlans.length }}</span>
                    <span class="text-gray-500">activos</span>
                </div>
                <div class="flex items-center gap-1.5 bg-white border border-gray-200 rounded-lg px-3 py-1.5 text-sm">
                    <span class="font-semibold text-red-600">{{ fmt(monthlyCommitment) }}</span>
                    <span class="text-gray-500">/mes</span>
                </div>
                <div class="flex items-center gap-1.5 bg-white border border-gray-200 rounded-lg px-3 py-1.5 text-sm">
                    <span class="font-semibold text-emerald-600">{{ completedPlans.length }}</span>
                    <span class="text-gray-500">completados</span>
                </div>
            </div>

            <button
                @click="openCreate"
                class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nuevo plan
            </button>
        </div>

        <!-- Estado vacío -->
        <div v-if="activePlans.length === 0 && completedPlans.length === 0" class="bg-white rounded-xl border border-gray-100 shadow-sm py-16 text-center">
            <div class="text-4xl mb-3">💳</div>
            <p class="text-gray-500 text-sm">No hay planes registrados.</p>
            <button @click="openCreate" class="mt-4 text-indigo-600 text-sm font-medium hover:underline">Crear el primero</button>
        </div>

        <!-- Grid planes activos -->
        <div v-if="activePlans.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mb-6">
            <div
                v-for="plan in activePlans"
                :key="plan.id"
                class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex flex-col gap-3"
            >
                <!-- Fila superior: badge tipo + categoría -->
                <div class="flex items-start justify-between">
                    <span
                        class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full"
                        :class="typeConfig[plan.type].color"
                    >
                        {{ typeConfig[plan.type].emoji }} {{ typeConfig[plan.type].label }}
                    </span>
                    <span
                        v-if="plan.category"
                        class="w-6 h-6 rounded-md flex items-center justify-center text-white text-xs font-bold shrink-0"
                        :style="{ backgroundColor: plan.category.color ?? '#6366f1' }"
                        :title="plan.category.name"
                    >
                        {{ plan.category.icon || plan.category.name.charAt(0).toUpperCase() }}
                    </span>
                </div>

                <!-- Nombre -->
                <h3 class="text-sm font-semibold text-gray-800 leading-tight">{{ plan.name }}</h3>

                <!-- Progreso -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs text-gray-500">
                            Cuota <span class="font-semibold text-gray-700">{{ plan.paid_installments }}</span>
                            de {{ plan.total_installments }}
                        </span>
                        <span class="text-xs font-medium text-gray-600">{{ plan.progress_percent }}%</span>
                    </div>
                    <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :class="typeConfig[plan.type].bar"
                            :style="{ width: plan.progress_percent + '%' }"
                        />
                    </div>
                </div>

                <!-- Info fila -->
                <div class="flex items-center justify-between text-xs text-gray-500">
                    <span class="font-semibold text-gray-700">{{ fmt(plan.installment_amount) }}<span class="font-normal"> / cuota</span></span>
                    <span>Total: {{ fmt(plan.total_amount) }}</span>
                </div>

                <!-- Próximo pago + miembro -->
                <div class="flex items-center justify-between text-xs text-gray-400">
                    <span>
                        <span v-if="plan.remaining > 0">Próximo: {{ fmtDate(plan.next_payment_date) }}</span>
                        <span v-else class="text-emerald-600 font-medium">¡Listo para completar!</span>
                    </span>
                    <span class="text-gray-500">{{ plan.member.name }}</span>
                </div>

                <!-- Acciones -->
                <div class="flex items-center gap-2 pt-1 border-t border-gray-100">
                    <button
                        @click="openPay(plan)"
                        class="flex-1 inline-flex items-center justify-center gap-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-medium px-3 py-1.5 rounded-lg transition-colors"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Pagar cuota
                    </button>
                    <button
                        @click="openEdit(plan)"
                        class="p-1.5 text-gray-400 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors"
                        title="Editar"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.5-6.5a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H9v-2a2 2 0 01.586-1.414z" />
                        </svg>
                    </button>
                    <button
                        @click="deletePlan(plan)"
                        class="p-1.5 text-gray-400 hover:text-red-500 rounded-lg hover:bg-red-50 transition-colors"
                        title="Eliminar"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m-7 0a1 1 0 01-1-1V5a1 1 0 011-1h8a1 1 0 011 1v1a1 1 0 01-1 1H9z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Sección completados -->
        <div v-if="completedPlans.length > 0">
            <button
                @click="showCompleted = !showCompleted"
                class="flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 mb-3 font-medium"
            >
                <svg
                    class="w-4 h-4 transition-transform"
                    :class="showCompleted ? 'rotate-90' : ''"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                Completados ({{ completedPlans.length }})
            </button>

            <div v-show="showCompleted" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                <div
                    v-for="plan in completedPlans"
                    :key="plan.id"
                    class="bg-gray-50 border border-gray-200 rounded-xl p-4 opacity-70"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-200 text-gray-600">
                            {{ typeConfig[plan.type].emoji }} {{ typeConfig[plan.type].label }}
                        </span>
                        <span class="text-xs bg-emerald-100 text-emerald-700 font-semibold px-2 py-0.5 rounded-full">Completado</span>
                    </div>
                    <p class="text-sm font-semibold text-gray-700 mb-2">{{ plan.name }}</p>
                    <div class="h-1.5 bg-emerald-200 rounded-full">
                        <div class="h-full bg-emerald-500 rounded-full w-full" />
                    </div>
                    <p class="text-xs text-gray-400 mt-2">{{ plan.total_installments }} cuotas · {{ fmt(plan.total_amount) }}</p>
                    <button
                        @click="deletePlan(plan)"
                        class="mt-2 text-xs text-gray-400 hover:text-red-500 transition-colors"
                    >Eliminar</button>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════
             Modal: Nuevo / Editar plan
        ════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-start justify-center p-4 overflow-y-auto">
                <div class="absolute inset-0 bg-black/40" @click="showModal = false" />

                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg my-8 p-6">
                    <h2 class="text-base font-semibold text-gray-900 mb-5">
                        {{ editingPlan ? 'Editar plan' : 'Nuevo plan en cuotas' }}
                    </h2>

                    <form @submit.prevent="savePlan" class="space-y-4">

                        <!-- Nombre -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                            <input
                                v-model="form.name"
                                type="text"
                                placeholder="Ej: TV Samsung 65&quot;, Préstamo auto…"
                                required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                            <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                        </div>

                        <!-- Tipo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                            <div class="flex rounded-lg overflow-hidden border border-gray-200">
                                <button
                                    v-for="(cfg, key) in typeConfig"
                                    :key="key"
                                    type="button"
                                    @click="form.type = key"
                                    class="flex-1 py-2 text-xs font-medium transition-colors"
                                    :class="form.type === key
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-white text-gray-600 hover:bg-gray-50'"
                                >
                                    {{ cfg.emoji }} {{ cfg.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Categoría + Responsable (fila) -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                                <select
                                    v-model="form.category_id"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option :value="null">Sin categoría</option>
                                    <option v-for="c in expenseCategories" :key="c.id" :value="c.id">
                                        {{ c.icon ? c.icon + ' ' : '' }}{{ c.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Responsable *</label>
                                <select
                                    v-model="form.member_id"
                                    required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option v-for="m in members" :key="m.id" :value="m.id">{{ m.name }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Monto total + Nro cuotas (fila) -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Monto total *</label>
                                <input
                                    v-model="form.total_amount"
                                    @input="autoComputeInstallment"
                                    type="number"
                                    min="1"
                                    step="1"
                                    placeholder="0"
                                    required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nro de cuotas *</label>
                                <input
                                    v-model="form.total_installments"
                                    @input="autoComputeInstallment"
                                    type="number"
                                    min="1"
                                    step="1"
                                    placeholder="12"
                                    required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                        </div>

                        <!-- Monto por cuota + Cuotas ya pagadas (fila) -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Valor por cuota *</label>
                                <input
                                    v-model="form.installment_amount"
                                    type="number"
                                    min="1"
                                    step="1"
                                    placeholder="Auto-calculado"
                                    required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                />
                                <p class="text-xs text-gray-400 mt-0.5">Auto-calcula o edita manualmente</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Cuotas ya pagadas</label>
                                <input
                                    v-model="form.paid_installments"
                                    type="number"
                                    min="0"
                                    step="1"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                />
                                <p class="text-xs text-gray-400 mt-0.5">Para planes ya en curso</p>
                            </div>
                        </div>

                        <!-- Fecha primer pago -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha del primer pago *</label>
                            <input
                                v-model="form.start_date"
                                type="date"
                                required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>

                        <!-- Notas -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notas <span class="text-gray-400">(opcional)</span></label>
                            <textarea
                                v-model="form.notes"
                                rows="2"
                                placeholder="Detalles adicionales…"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"
                            />
                        </div>

                        <!-- Botones -->
                        <div class="flex justify-end gap-3 pt-2">
                            <button
                                type="button"
                                @click="showModal = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors disabled:opacity-50"
                            >
                                {{ editingPlan ? 'Guardar cambios' : 'Crear plan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ═══════════════════════════════════════════════════
             Modal: Pagar cuota
        ════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="showPayModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40" @click="showPayModal = false" />

                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">

                    <!-- Info del plan -->
                    <div class="bg-gray-50 rounded-xl px-4 py-3 mb-5">
                        <p class="text-xs text-gray-400 mb-0.5">Registrando pago para</p>
                        <p class="text-sm font-semibold text-gray-800">{{ payingPlan?.name }}</p>
                        <div class="flex items-center justify-between mt-2">
                            <span class="text-xs text-gray-500">
                                Cuota
                                <span class="font-bold text-gray-700">{{ (payingPlan?.paid_installments ?? 0) + 1 }}</span>
                                de {{ payingPlan?.total_installments }}
                            </span>
                            <span class="text-sm font-bold text-emerald-600">{{ payingPlan ? fmt(payingPlan.installment_amount) : '' }}</span>
                        </div>
                    </div>

                    <form @submit.prevent="submitPay" class="space-y-4">

                        <!-- Miembro -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Pagado por *</label>
                            <select
                                v-model="payForm.member_id"
                                required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                                <option v-for="m in members" :key="m.id" :value="m.id">{{ m.name }}</option>
                            </select>
                        </div>

                        <!-- Fecha -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                            <input
                                v-model="payForm.date"
                                type="date"
                                required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>

                        <!-- Descripción -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descripción <span class="text-gray-400">(opcional)</span></label>
                            <input
                                v-model="payForm.description"
                                type="text"
                                :placeholder="`Cuota ${(payingPlan?.paid_installments ?? 0) + 1} de ${payingPlan?.total_installments}: ${payingPlan?.name ?? ''}`"
                                maxlength="255"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>

                        <div class="flex justify-end gap-3 pt-1">
                            <button
                                type="button"
                                @click="showPayModal = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="payForm.processing"
                                class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors disabled:opacity-50"
                            >
                                <span v-if="payForm.processing">Registrando…</span>
                                <span v-else>Registrar pago</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

    </AuthenticatedLayout>
</template>
