<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LineChart  from '@/Components/LineChart.vue';
import DonutChart from '@/Components/DonutChart.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    annualCurve:          Array,
    transactionsByDate:   Object,
    totalIncome:          Number,
    totalExpense:         Number,
    balance:              Number,
    memberStats:          Array,
    categoryStats:        Array,
    topExpenseCategories: Array,
    topTransactions:      Array,
    filters:              Object,
    members:              Array,
});

// ── Vista ────────────────────────────────────────────────────
const view = ref('monthly');

// ── Navegación ───────────────────────────────────────────────
const year     = ref(props.filters.year);
const month    = ref(props.filters.month);
const memberId = ref(props.filters.member_id ?? 0);

const monthNames = [
    'Enero','Febrero','Marzo','Abril','Mayo','Junio',
    'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre',
];
const months = monthNames.map((label, i) => ({ value: i + 1, label }));

const currentYear = new Date().getFullYear();
const years = Array.from({ length: 4 }, (_, i) => currentYear - i);

function navigate(params = {}) {
    const base = { year: year.value, month: month.value };
    if (memberId.value) base.member_id = memberId.value;
    router.get(route('dashboard'), { ...base, ...params }, { preserveState: false });
}

function selectMember(id) {
    memberId.value = id;
    navigate({ member_id: id || undefined });
}

function goToMonth(m) {
    month.value = m;
    view.value  = 'monthly';
    navigate();
}

// Nombre del miembro activo
const activeMemberName = computed(() =>
    memberId.value ? (props.members.find(m => m.id === memberId.value)?.name ?? 'Total') : 'Total'
);

// ── Resumen mensual por año (para vista anual) ───────────────
const monthlyBreakdown = computed(() => {
    const data = Array.from({ length: 12 }, (_, i) => ({
        value: i + 1,
        label: monthNames[i],
        income: 0,
        expense: 0,
    }));

    Object.entries(props.transactionsByDate).forEach(([date, txs]) => {
        const m = parseInt(date.split('-')[1]) - 1;
        txs.forEach(tx => {
            if (tx.type === 'income') data[m].income += parseFloat(tx.amount);
            else data[m].expense += parseFloat(tx.amount);
        });
    });

    return data.map(m => ({ ...m, balance: m.income - m.expense }));
});

const annualIncome  = computed(() => monthlyBreakdown.value.reduce((s, m) => s + m.income,  0));
const annualExpense = computed(() => monthlyBreakdown.value.reduce((s, m) => s + m.expense, 0));
const annualBalance = computed(() => annualIncome.value - annualExpense.value);

// ── Vista categorías ─────────────────────────────────────────
const catView = ref('expense');
const categoryDonut = computed(() => props.categoryStats.filter(c => c.type === catView.value));
const categoryBars  = computed(() => {
    const items = categoryDonut.value;
    const total = items.reduce((s, c) => s + c.total, 0);
    return items.map(c => ({ ...c, pct: total > 0 ? Math.round((c.total / total) * 100) : 0 }));
});

// ── Helpers ──────────────────────────────────────────────────
function fmt(v) {
    return new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(v ?? 0);
}
function pct(part, total) {
    return total > 0 ? Math.round((part / total) * 100) : 0;
}
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-lg font-semibold text-gray-800">Dashboard</h1>
        </template>

        <div class="space-y-6">

            <!-- ── Tabs + filtros ────────────────────────────────────── -->
            <div class="flex flex-wrap items-center justify-between gap-3">

                <!-- Tabs -->
                <div class="flex gap-1 bg-gray-100 rounded-xl p-1">
                    <button
                        @click="view = 'annual'"
                        class="flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium transition-all"
                        :class="view === 'annual'
                            ? 'bg-white shadow-sm text-indigo-600'
                            : 'text-gray-500 hover:text-gray-700'"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                        Vista anual
                    </button>
                    <button
                        @click="view = 'monthly'"
                        class="flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium transition-all"
                        :class="view === 'monthly'
                            ? 'bg-white shadow-sm text-indigo-600'
                            : 'text-gray-500 hover:text-gray-700'"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 9v7.5m-9-6h.008v.008H12V12zm0 3h.008v.008H12v-.008zm0 3h.008v.008H12v-.008zm-3-6h.008v.008H9V12zm0 3h.008v.008H9v-.008zm0 3h.008v.008H9v-.008zm-3-6h.008v.008H6V12zm0 3h.008v.008H6v-.008zm0 3h.008v.008H6v-.008zm12-6h.008v.008H18V12zm0 3h.008v.008H18v-.008z" />
                        </svg>
                        Vista mensual
                    </button>
                </div>

                <!-- Selector de miembro -->
                <div v-if="members.length > 1" class="flex items-center gap-2 flex-wrap">
                    <button
                        @click="selectMember(0)"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium transition-colors"
                        :class="memberId === 0
                            ? 'bg-indigo-600 text-white shadow-sm'
                            : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Total hogar
                    </button>
                    <button
                        v-for="m in members" :key="m.id"
                        @click="selectMember(m.id)"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium transition-colors"
                        :class="memberId === m.id
                            ? 'bg-indigo-600 text-white shadow-sm'
                            : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'"
                    >
                        {{ m.name }}
                    </button>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════ -->
            <!-- VISTA ANUAL                                             -->
            <!-- ═══════════════════════════════════════════════════════ -->
            <template v-if="view === 'annual'">

                <!-- Selector de año -->
                <div class="flex items-center justify-between">
                    <select
                        v-model.number="year"
                        @change="navigate"
                        class="text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg px-3 py-2 pr-8 appearance-none cursor-pointer hover:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-300 transition-colors"
                    >
                        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                    </select>
                </div>

                <!-- Curva anual -->
                <section class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-sm font-semibold text-gray-800">
                                Saldo corriente {{ year }}
                                <span v-if="memberId" class="ml-1.5 text-xs font-normal text-indigo-500 bg-indigo-50 px-2 py-0.5 rounded-full">
                                    {{ activeMemberName }}
                                </span>
                            </h2>
                            <p class="text-xs text-gray-400">Acumulado día a día — hover para ver movimientos</p>
                        </div>
                        <span class="text-sm font-bold" :class="annualCurve.length && annualCurve[annualCurve.length-1]?.balance >= 0 ? 'text-indigo-600' : 'text-red-500'">
                            {{ annualCurve.length ? fmt(annualCurve[annualCurve.length-1]?.balance) : '$0' }}
                        </span>
                    </div>
                    <LineChart
                        v-if="annualCurve.length"
                        :points="annualCurve"
                        :transactionsByDate="transactionsByDate"
                    />
                    <p v-else class="text-center text-sm text-gray-400 py-10 italic">Sin transacciones registradas este año.</p>
                </section>

                <!-- Resumen anual total -->
                <div class="grid grid-cols-3 gap-3">
                    <div class="bg-emerald-50 rounded-xl p-4">
                        <p class="text-xs text-emerald-600 font-medium mb-1">Ingresos {{ year }}</p>
                        <p class="text-xl font-bold text-emerald-700">{{ fmt(annualIncome) }}</p>
                    </div>
                    <div class="bg-red-50 rounded-xl p-4">
                        <p class="text-xs text-red-500 font-medium mb-1">Egresos {{ year }}</p>
                        <p class="text-xl font-bold text-red-600">{{ fmt(annualExpense) }}</p>
                    </div>
                    <div :class="annualBalance >= 0 ? 'bg-indigo-50' : 'bg-orange-50'" class="rounded-xl p-4">
                        <p class="text-xs font-medium mb-1" :class="annualBalance >= 0 ? 'text-indigo-600' : 'text-orange-500'">Balance {{ year }}</p>
                        <p class="text-xl font-bold" :class="annualBalance >= 0 ? 'text-indigo-700' : 'text-orange-600'">{{ fmt(annualBalance) }}</p>
                    </div>
                </div>

                <!-- Tabla mes a mes -->
                <section class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h2 class="text-sm font-semibold text-gray-800">Desglose mensual {{ year }}</h2>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Mes</th>
                                <th class="text-right px-5 py-3 text-xs font-semibold text-emerald-600 uppercase tracking-wide">Ingresos</th>
                                <th class="text-right px-5 py-3 text-xs font-semibold text-red-500 uppercase tracking-wide">Egresos</th>
                                <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Balance</th>
                                <th class="px-5 py-3 w-32"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr
                                v-for="m in monthlyBreakdown" :key="m.value"
                                class="hover:bg-indigo-50/40 cursor-pointer transition-colors group"
                                :class="{ 'opacity-40': m.income === 0 && m.expense === 0 }"
                                @click="goToMonth(m.value)"
                            >
                                <td class="px-5 py-3 font-medium text-gray-700 flex items-center gap-2">
                                    {{ m.label }}
                                    <svg class="w-3.5 h-3.5 text-indigo-400 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </td>
                                <td class="px-5 py-3 text-right text-emerald-600">
                                    {{ m.income > 0 ? fmt(m.income) : '—' }}
                                </td>
                                <td class="px-5 py-3 text-right text-red-500">
                                    {{ m.expense > 0 ? fmt(m.expense) : '—' }}
                                </td>
                                <td class="px-5 py-3 text-right font-semibold"
                                    :class="m.balance >= 0 ? 'text-gray-700' : 'text-red-600'">
                                    {{ (m.income > 0 || m.expense > 0) ? fmt(m.balance) : '—' }}
                                </td>
                                <td class="px-5 py-3">
                                    <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden" v-if="m.income > 0 || m.expense > 0">
                                        <div
                                            class="h-full rounded-full transition-all"
                                            :class="m.balance >= 0 ? 'bg-emerald-400' : 'bg-red-400'"
                                            :style="{ width: Math.min(100, Math.abs(m.balance) / Math.max(m.income, m.expense) * 100) + '%' }"
                                        />
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>

            </template>

            <!-- ═══════════════════════════════════════════════════════ -->
            <!-- VISTA MENSUAL                                           -->
            <!-- ═══════════════════════════════════════════════════════ -->
            <template v-else>

                <!-- Selectores de mes y año -->
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

                <!-- Tarjetas resumen -->
                <div class="grid grid-cols-3 gap-3">
                    <div class="bg-emerald-50 rounded-xl p-4">
                        <p class="text-xs text-emerald-600 font-medium mb-1">Ingresos</p>
                        <p class="text-xl font-bold text-emerald-700">{{ fmt(totalIncome) }}</p>
                    </div>
                    <div class="bg-red-50 rounded-xl p-4">
                        <p class="text-xs text-red-500 font-medium mb-1">Egresos</p>
                        <p class="text-xl font-bold text-red-600">{{ fmt(totalExpense) }}</p>
                    </div>
                    <div :class="balance >= 0 ? 'bg-indigo-50' : 'bg-orange-50'" class="rounded-xl p-4">
                        <p class="text-xs font-medium mb-1" :class="balance >= 0 ? 'text-indigo-600' : 'text-orange-500'">Balance</p>
                        <p class="text-xl font-bold" :class="balance >= 0 ? 'text-indigo-700' : 'text-orange-600'">{{ fmt(balance) }}</p>
                    </div>
                </div>

                <!-- Tabla comparativa por miembro -->
                <section v-if="memberStats.length > 1" class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <h2 class="text-sm font-semibold text-gray-800 mb-4">Por miembro</h2>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="text-left py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide">Miembro</th>
                                <th class="text-right py-2 text-xs font-semibold text-emerald-600 uppercase tracking-wide">Ingresos</th>
                                <th class="text-right py-2 text-xs font-semibold text-red-500 uppercase tracking-wide">Egresos</th>
                                <th class="text-right py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr
                                v-for="m in memberStats" :key="m.id"
                                class="cursor-pointer transition-colors"
                                :class="memberId === m.id ? 'bg-indigo-50/60' : 'hover:bg-gray-50'"
                                @click="selectMember(memberId === m.id ? 0 : m.id)"
                            >
                                <td class="py-2.5 font-medium text-gray-700 flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full shrink-0"
                                        :class="memberId === m.id ? 'bg-indigo-500' : 'bg-gray-300'" />
                                    {{ m.name }}
                                </td>
                                <td class="py-2.5 text-right text-emerald-600">{{ fmt(m.income) }}</td>
                                <td class="py-2.5 text-right text-red-500">{{ fmt(m.expense) }}</td>
                                <td class="py-2.5 text-right font-semibold"
                                    :class="(m.income - m.expense) >= 0 ? 'text-gray-700' : 'text-red-600'">
                                    {{ fmt(m.income - m.expense) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <!-- Ingresos vs Egresos -->
                <section class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <h2 class="text-sm font-semibold text-gray-800 mb-4">Ingresos vs Egresos</h2>
                    <div class="space-y-3">
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-emerald-600 font-medium">Ingresos</span>
                                <span class="font-bold text-emerald-700">{{ fmt(totalIncome) }}</span>
                            </div>
                            <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded-full transition-all duration-700"
                                    :style="{ width: (totalIncome + totalExpense > 0 ? pct(totalIncome, totalIncome + totalExpense) : 0) + '%' }" />
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-red-500 font-medium">Egresos</span>
                                <span class="font-bold text-red-600">{{ fmt(totalExpense) }}</span>
                            </div>
                            <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-red-500 rounded-full transition-all duration-700"
                                    :style="{ width: (totalIncome + totalExpense > 0 ? pct(totalExpense, totalIncome + totalExpense) : 0) + '%' }" />
                            </div>
                        </div>
                    </div>
                    <p class="text-center text-xs mt-3 text-gray-500">
                        Balance: <span class="font-bold" :class="balance >= 0 ? 'text-indigo-600' : 'text-red-600'">{{ fmt(balance) }}</span>
                    </p>
                </section>

                <!-- Por categoría -->
                <section class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-semibold text-gray-800">Por categoría</h2>
                        <div class="flex rounded-lg overflow-hidden border border-gray-200 text-xs">
                            <button @click="catView = 'income'"
                                :class="catView === 'income' ? 'bg-emerald-500 text-white' : 'bg-white text-gray-500 hover:bg-gray-50'"
                                class="px-3 py-1.5 font-medium transition-colors">Ingresos</button>
                            <button @click="catView = 'expense'"
                                :class="catView === 'expense' ? 'bg-red-500 text-white' : 'bg-white text-gray-500 hover:bg-gray-50'"
                                class="px-3 py-1.5 font-medium transition-colors">Egresos</button>
                        </div>
                    </div>

                    <div v-if="categoryDonut.length" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <DonutChart :items="categoryDonut" />
                            <div class="mt-3 space-y-1.5">
                                <div v-for="c in categoryDonut" :key="c.id" class="flex items-center gap-2 text-xs">
                                    <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ backgroundColor: c.color }"></span>
                                    <span class="text-gray-600 flex-1 truncate">{{ c.name }}</span>
                                    <span class="font-semibold text-gray-700">{{ fmt(c.total) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div v-for="c in categoryBars" :key="c.id">
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-5 h-5 rounded-md flex items-center justify-center text-white text-xs font-bold shrink-0"
                                            :style="{ backgroundColor: c.color }">{{ c.icon || c.name.charAt(0) }}</span>
                                        <span class="text-gray-700 font-medium truncate max-w-[110px]">{{ c.name }}</span>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="font-semibold text-gray-800">{{ fmt(c.total) }}</span>
                                        <span class="text-gray-400 ml-1">{{ c.pct }}%</span>
                                    </div>
                                </div>
                                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500"
                                        :style="{ width: c.pct + '%', backgroundColor: c.color }" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-center text-sm text-gray-400 italic py-6">Sin datos de categorías este mes.</p>
                </section>

                <!-- Top 5 -->
                <section class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <h2 class="text-sm font-semibold text-gray-800 mb-4">Top 5 categorías de egreso</h2>
                        <div v-if="topExpenseCategories.length" class="space-y-3">
                            <div v-for="(c, i) in topExpenseCategories" :key="c.id" class="flex items-center gap-3">
                                <span class="text-xs font-bold text-gray-300 w-4 shrink-0 text-right">{{ i + 1 }}</span>
                                <span class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-xs font-bold shrink-0"
                                    :style="{ backgroundColor: c.color }">{{ c.icon || c.name.charAt(0) }}</span>
                                <span class="flex-1 text-sm text-gray-700 truncate">{{ c.name }}</span>
                                <div class="text-right shrink-0">
                                    <p class="text-sm font-bold text-gray-800">{{ fmt(c.total) }}</p>
                                    <p v-if="c.change !== null" class="text-xs font-medium"
                                        :class="c.change > 0 ? 'text-red-500' : 'text-emerald-600'">
                                        {{ c.change > 0 ? '▲' : '▼' }} {{ Math.abs(c.change) }}%
                                    </p>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-400 italic text-center py-4">Sin egresos este mes.</p>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <h2 class="text-sm font-semibold text-gray-800 mb-4">Top 5 egresos individuales</h2>
                        <div v-if="topTransactions.length" class="space-y-3">
                            <div v-for="(t, i) in topTransactions" :key="t.id" class="flex items-center gap-3">
                                <span class="text-xs font-bold text-gray-300 w-4 shrink-0 text-right">{{ i + 1 }}</span>
                                <span class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-xs font-bold shrink-0"
                                    :style="{ backgroundColor: t.category.color }">{{ t.category.icon || t.category.name.charAt(0) }}</span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-700 truncate">{{ t.description || t.category.name }}</p>
                                    <p class="text-xs text-gray-400">{{ t.member.name }} · {{ t.date }}</p>
                                </div>
                                <span class="text-sm font-bold text-red-500 shrink-0">{{ fmt(t.amount) }}</span>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-400 italic text-center py-4">Sin egresos este mes.</p>
                    </div>
                </section>

            </template>

        </div>
    </AuthenticatedLayout>
</template>
