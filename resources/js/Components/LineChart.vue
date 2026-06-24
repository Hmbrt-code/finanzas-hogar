<script setup>
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const props = defineProps({
    points:             { type: Array, required: true },
    transactionsByDate: { type: Object, default: () => ({}) },
});

const canvas      = ref(null);
const tooltipData = ref({ visible: false, x: 0, y: 0, date: '', balance: 0, transactions: [] });
let chartInstance = null;

// Plugin that creates the gradient after Chart.js sizes the canvas
const gradientPlugin = {
    id: 'gradientFill',
    beforeDatasetsUpdate(chart) {
        const ctx    = chart.ctx;
        const height = chart.chartArea?.height ?? chart.height;
        const top    = chart.chartArea?.top ?? 0;
        const grad   = ctx.createLinearGradient(0, top, 0, top + height);
        grad.addColorStop(0, 'rgba(99,102,241,0.3)');
        grad.addColorStop(1, 'rgba(99,102,241,0.0)');
        chart.data.datasets[0].backgroundColor = grad;
    },
};

async function buildChart() {
    await nextTick();

    if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
    }

    if (!canvas.value || !props.points.length) return;

    const labels = props.points.map(p => p.date);
    const data   = props.points.map(p => p.balance);

    chartInstance = new Chart(canvas.value, {
        type: 'line',
        plugins: [gradientPlugin],
        data: {
            labels,
            datasets: [{
                data,
                borderColor:               '#6366f1',
                borderWidth:               2,
                fill:                      true,
                backgroundColor:           'rgba(99,102,241,0.15)',
                pointRadius:               0,
                pointHoverRadius:          6,
                pointHoverBackgroundColor: '#6366f1',
                pointHoverBorderColor:     '#ffffff',
                pointHoverBorderWidth:     2,
                tension:                   0.4,
            }],
        },
        options: {
            responsive:          true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    enabled: false,
                    external({ chart, tooltip: tt }) {
                        if (!tt.dataPoints?.length || tt.opacity === 0) {
                            tooltipData.value = { ...tooltipData.value, visible: false };
                            return;
                        }
                        const idx  = tt.dataPoints[0].dataIndex;
                        const date = labels[idx];
                        const rect = chart.canvas.getBoundingClientRect();

                        tooltipData.value = {
                            visible:      true,
                            x:            rect.left + window.scrollX + tt.caretX,
                            y:            rect.top  + window.scrollY + tt.caretY,
                            date,
                            balance:      data[idx],
                            transactions: props.transactionsByDate[date] ?? [],
                        };
                    },
                },
            },
            scales: {
                x: {
                    ticks: {
                        maxTicksLimit: 12,
                        callback(_, i) {
                            if (!labels[i]) return '';
                            return new Date(labels[i] + 'T00:00:00').toLocaleDateString('es', { month: 'short' });
                        },
                        color: '#94a3b8',
                        font:  { size: 11 },
                    },
                    grid: { display: false },
                    border: { display: false },
                },
                y: {
                    ticks: {
                        color: '#94a3b8',
                        font:  { size: 11 },
                        callback: v => Intl.NumberFormat('es-CL', { notation: 'compact' }).format(v),
                    },
                    grid:   { color: '#f1f5f9' },
                    border: { display: false },
                },
            },
            interaction: { mode: 'index', intersect: false },
        },
    });
}

onMounted(buildChart);
watch(() => props.points, buildChart, { deep: true });
onUnmounted(() => { chartInstance?.destroy(); chartInstance = null; });

const fmt = v => Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(v);
const fmtDate = d => new Date(d + 'T00:00:00').toLocaleDateString('es', { day: 'numeric', month: 'long' });
</script>

<template>
    <div style="position: relative; height: 220px;">
        <canvas ref="canvas" />
    </div>

    <Teleport to="body">
        <div
            v-if="tooltipData.visible"
            class="fixed z-50 bg-white border border-gray-200 shadow-xl rounded-xl p-3 w-56 pointer-events-none text-xs"
            :style="{
                left:      tooltipData.x + 'px',
                top:       (tooltipData.y - 12) + 'px',
                transform: 'translate(-50%,-100%)',
            }"
        >
            <p class="font-semibold text-gray-700 mb-0.5">{{ fmtDate(tooltipData.date) }}</p>
            <p class="font-bold mb-2" :class="tooltipData.balance >= 0 ? 'text-indigo-600' : 'text-red-500'">
                {{ fmt(tooltipData.balance) }}
            </p>
            <div v-if="tooltipData.transactions.length" class="space-y-1 border-t border-gray-100 pt-2">
                <div v-for="t in tooltipData.transactions" :key="t.id" class="flex justify-between gap-2">
                    <span class="text-gray-500 truncate">{{ t.category }} · {{ t.member }}</span>
                    <span class="font-medium shrink-0" :class="t.type === 'income' ? 'text-emerald-600' : 'text-red-500'">
                        {{ t.type === 'income' ? '+' : '-' }}{{ fmt(t.amount) }}
                    </span>
                </div>
            </div>
            <p v-else class="text-gray-400 italic">Sin movimientos</p>
        </div>
    </Teleport>
</template>
