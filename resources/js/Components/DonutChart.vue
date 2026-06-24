<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { Chart, ArcElement, Tooltip, Legend } from 'chart.js';

Chart.register(ArcElement, Tooltip, Legend);

const props = defineProps({
    items: { type: Array, required: true }, // [{name, total, color}]
});

const canvas = ref(null);
let chartInstance = null;

function buildChart() {
    if (chartInstance) chartInstance.destroy();
    if (!props.items.length) return;

    chartInstance = new Chart(canvas.value.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: props.items.map(i => i.name),
            datasets: [{
                data:            props.items.map(i => i.total),
                backgroundColor: props.items.map(i => i.color ?? '#6366f1'),
                borderWidth:     2,
                borderColor:     '#fff',
                hoverOffset:     6,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(ctx.raw)}`,
                    },
                },
            },
        },
    });
}

onMounted(buildChart);
watch(() => props.items, buildChart, { deep: true });
onUnmounted(() => chartInstance?.destroy());
</script>

<template>
    <div style="height: 180px; position: relative;">
        <canvas v-if="items.length" ref="canvas" />
        <div v-else class="flex items-center justify-center h-full text-sm text-gray-400 italic">Sin datos</div>
    </div>
</template>
