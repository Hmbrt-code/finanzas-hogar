<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BarChart from '@/Components/BarChart.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    months:        Array,
    totalIncome:   Number,
    totalExpense:  Number,
    balance:       Number,
    bestMonth:     Object,
    worstMonth:    Object,
    topCategories: Array,
    year:          Number,
    years:         Array,
});

const selectedYear = ref(props.year);

function changeYear(y) {
    selectedYear.value = y;
    router.get(route('annual.index'), { year: y });
}

const monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                    'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

// Mes activo (hover en la tabla)
const activeMonth = ref(null);

const fmt = v => Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(v ?? 0);

const maxExpense = computed(() => Math.max(...props.months.map(m => m.expense), 1));
</script>

<template>
    <Head :title="`Anual ${year}`" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-lg font-semibold text-gray-800">Vista anual</h1>
        </template>

        <!-- Selector de año -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-2">
                <button
                    @click="changeYear(selectedYear - 1)"
                    class="p-1.5 rounded-lg hover:bg-gray-200 transition-colors"
                >
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <span class="text-xl font-bold text-gray-800 min-w-[60px] text-center">{{ year }}</span>
                <button
                    @click="changeYear(selectedYear + 1)"
                    class="p-1.5 rounded-lg hover:bg-gray-200 transition-colors"
                >
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
            <!-- Pills de años con datos -->
            <div class="flex gap-1.5 flex-wrap">
                <button
                    v-for="y in years" :key="y"
                    @click="changeYear(y)"
                    :class="y === year
                        ? 'bg-indigo-600 text-white'
                        : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'"
                    class="text-xs font-medium px-3 py-1.5 rounded-full transition-colors"
                >
                    {{ y }}
                </button>
            </div>
        </div>

        <!-- Tarjetas resumen anual -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <p class="text-xs text-gray-500 mb-1">Ingresos {{ year }}</p>
                <p class="text-lg font-bold text-emerald-600">{{ fmt(totalIncome) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <p class="text-xs text-gray-500 mb-1">Egresos {{ year }}</p>
                <p class="text-lg font-bold text-red-500">{{ fmt(totalExpense) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <p class="text-xs text-gray-500 mb-1">Balance {{ year }}</p>
                <p class="text-lg font-bold" :class="balance >= 0 ? 'text-indigo-600' : 'text-red-600'">{{ fmt(balance) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <p class="text-xs text-gray-500 mb-1">Promedio mensual</p>
                <p class="text-lg font-bold text-gray-700">
                    {{ fmt(months.filter(m => m.count > 0).length > 0 ? balance / months.filter(m => m.count > 0).length : 0) }}
                </p>
            </div>
        </div>

        <!-- Gráfico de barras -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 mb-6">
            <h2 class="text-sm font-semibold text-gray-800 mb-4">Ingresos vs Egresos por mes</h2>
            <BarChart :months="months" />
        </div>

        <!-- Tabla mensual detallada -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden mb-6">
            <div class="px-5 py-3 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-800">Detalle mensual</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Mes</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-emerald-600 uppercase tracking-wide">Ingresos</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-red-500 uppercase tracking-wide">Egresos</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Balance</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide w-32">Barra</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Mov.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr
                            v-for="m in months" :key="m.month"
                            class="hover:bg-indigo-50/40 transition-colors cursor-default"
                            @mouseenter="activeMonth = m.month"
                            @mouseleave="activeMonth = null"
                        >
                            <td class="px-5 py-3 font-semibold text-gray-700">
                                {{ monthNames[m.month - 1] }}
                            </td>
                            <td class="px-4 py-3 text-right text-emerald-600 font-medium">
                                {{ m.income > 0 ? fmt(m.income) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right text-red-500 font-medium">
                                {{ m.expense > 0 ? fmt(m.expense) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold"
                                :class="m.balance > 0 ? 'text-gray-800' : m.balance < 0 ? 'text-red-600' : 'text-gray-400'">
                                {{ m.count > 0 ? fmt(m.balance) : '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                    <div
                                        class="h-full rounded-full transition-all duration-500"
                                        :class="m.balance >= 0 ? 'bg-emerald-400' : 'bg-red-400'"
                                        :style="{ width: ((m.expense / maxExpense) * 100) + '%' }"
                                    />
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-400 text-xs">
                                {{ m.count > 0 ? m.count : '—' }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-50 border-t border-gray-200">
                        <tr>
                            <td class="px-5 py-3 font-bold text-gray-700 text-sm">Total</td>
                            <td class="px-4 py-3 text-right font-bold text-emerald-600">{{ fmt(totalIncome) }}</td>
                            <td class="px-4 py-3 text-right font-bold text-red-500">{{ fmt(totalExpense) }}</td>
                            <td class="px-4 py-3 text-right font-bold" :class="balance >= 0 ? 'text-indigo-600' : 'text-red-600'">
                                {{ fmt(balance) }}
                            </td>
                            <td class="px-4 py-3" colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Mejor / Peor mes + Top categorías -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            <!-- Mejor y peor mes -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-800 mb-4">Destacados del año</h2>
                <div class="space-y-3">
                    <div class="flex items-center gap-3 p-3 bg-emerald-50 rounded-lg">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs text-emerald-600 font-medium">Mejor mes</p>
                            <p class="text-sm font-bold text-gray-800">{{ bestMonth ? monthNames[bestMonth.month - 1] : '—' }}</p>
                        </div>
                        <span class="text-sm font-bold text-emerald-600">{{ bestMonth ? fmt(bestMonth.balance) : '—' }}</span>
                    </div>
                    <div class="flex items-center gap-3 p-3 bg-red-50 rounded-lg">
                        <div class="w-8 h-8 rounded-lg bg-red-500 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs text-red-500 font-medium">Peor mes</p>
                            <p class="text-sm font-bold text-gray-800">{{ worstMonth ? monthNames[worstMonth.month - 1] : '—' }}</p>
                        </div>
                        <span class="text-sm font-bold text-red-500">{{ worstMonth ? fmt(worstMonth.balance) : '—' }}</span>
                    </div>
                </div>
            </div>

            <!-- Top categorías del año -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-800 mb-4">Top categorías de egreso</h2>
                <div v-if="topCategories.length" class="space-y-2.5">
                    <div v-for="(c, i) in topCategories" :key="i" class="flex items-center gap-2.5">
                        <span class="text-xs font-bold text-gray-300 w-4 text-right shrink-0">{{ i + 1 }}</span>
                        <span
                            class="w-6 h-6 rounded-md flex items-center justify-center text-white text-xs font-bold shrink-0"
                            :style="{ backgroundColor: c.color }"
                        >{{ c.icon || c.name.charAt(0) }}</span>
                        <span class="flex-1 text-sm text-gray-700 truncate">{{ c.name }}</span>
                        <span class="text-sm font-bold text-gray-800 shrink-0">{{ fmt(c.total) }}</span>
                    </div>
                </div>
                <p v-else class="text-sm text-gray-400 italic text-center py-4">Sin egresos este año.</p>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
