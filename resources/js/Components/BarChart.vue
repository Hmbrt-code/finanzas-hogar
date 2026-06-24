<script setup>
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const props = defineProps({
    months: { type: Array, required: true }, // [{label, income, expense, balance}]
});

const canvas = ref(null);
let chartInstance = null;

async function buildChart() {
    await nextTick();
    if (chartInstance) { chartInstance.destroy(); chartInstance = null; }
    if (!canvas.value || !props.months.length) return;

    chartInstance = new Chart(canvas.value.getContext('2d'), {
        type: 'bar',
        data: {
            labels: props.months.map(m => m.label),
            datasets: [
                {
                    label:           'Ingresos',
                    data:            props.months.map(m => m.income),
                    backgroundColor: 'rgba(34,197,94,0.75)',
                    borderColor:     'rgba(34,197,94,1)',
                    borderWidth:     1,
                    borderRadius:    4,
                    order:           2,
                },
                {
                    label:           'Egresos',
                    data:            props.months.map(m => m.expense),
                    backgroundColor: 'rgba(239,68,68,0.75)',
                    borderColor:     'rgba(239,68,68,1)',
                    borderWidth:     1,
                    borderRadius:    4,
                    order:           2,
                },
                {
                    label:           'Balance',
                    data:            props.months.map(m => m.balance),
                    type:            'line',
                    borderColor:     '#6366f1',
                    borderWidth:     2,
                    backgroundColor: 'transparent',
                    pointRadius:     4,
                    pointBackgroundColor: '#6366f1',
                    tension:         0.3,
                    order:           1,
                    yAxisID:         'y',
                },
            ],
        },
        options: {
            responsive:          true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font:      { size: 12 },
                        color:     '#6b7280',
                        boxWidth:  12,
                        boxHeight: 12,
                        padding:   16,
                    },
                },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.dataset.label}: ${Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(ctx.raw)}`,
                    },
                },
            },
            scales: {
                x: {
                    ticks:  { color: '#6b7280', font: { size: 11 } },
                    grid:   { display: false },
                    border: { display: false },
                },
                y: {
                    ticks: {
                        color: '#6b7280',
                        font:  { size: 11 },
                        callback: v => Intl.NumberFormat('es-CL', { notation: 'compact' }).format(v),
                    },
                    grid:   { color: '#f3f4f6' },
                    border: { display: false },
                },
            },
        },
    });
}

onMounted(buildChart);
watch(() => props.months, buildChart, { deep: true });
onUnmounted(() => { chartInstance?.destroy(); chartInstance = null; });
</script>

<template>
    <div style="position: relative; height: 300px;">
        <canvas ref="canvas" />
    </div>
</template>
