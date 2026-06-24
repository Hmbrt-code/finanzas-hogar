<script setup>
import { ref } from 'vue';

const props = defineProps({
    node:  { type: Object, required: true },
    depth: { type: Number, default: 0 },
});

const emit = defineEmits(['edit', 'delete', 'add-child', 'quick-transaction']);

const open = ref(true);
const hasChildren = () => props.node.children && props.node.children.length > 0;
</script>

<template>
    <div>
        <!-- Fila del nodo -->
        <div
            class="flex items-center group rounded-lg hover:bg-gray-50 py-1.5 px-2 transition-colors"
            :style="{ paddingLeft: `${depth * 20 + 8}px` }"
        >
            <!-- Línea + toggle expand -->
            <div class="flex items-center shrink-0 mr-2" style="width: 20px;">
                <button
                    v-if="hasChildren()"
                    @click="open = !open"
                    class="w-5 h-5 flex items-center justify-center text-gray-400 hover:text-gray-600 rounded transition-colors"
                >
                    <svg class="w-3.5 h-3.5 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
                <!-- Punto hoja -->
                <span v-else class="w-1.5 h-1.5 rounded-full bg-gray-300 mx-auto block"></span>
            </div>

            <!-- Icono de color -->
            <span
                class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-xs font-bold shrink-0 mr-2.5"
                :style="{ backgroundColor: node.color ?? '#6366f1' }"
            >
                {{ node.icon || node.name.charAt(0).toUpperCase() }}
            </span>

            <!-- Nombre -->
            <span class="text-sm text-gray-700 flex-1 font-medium">{{ node.name }}</span>

            <!-- Badge tipo (solo raíz) -->
            <span
                v-if="depth === 0"
                class="text-xs font-medium px-2 py-0.5 rounded-full mr-2 shrink-0"
                :class="node.type === 'income'
                    ? 'bg-emerald-100 text-emerald-700'
                    : 'bg-red-100 text-red-600'"
            >
                {{ node.type === 'income' ? 'Ingreso' : 'Egreso' }}
            </span>

            <!-- Acciones (hover) -->
            <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                <button
                    @click.stop="emit('quick-transaction', node)"
                    class="p-1 text-gray-400 hover:text-emerald-600 rounded"
                    title="Nueva transacción"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </button>
                <button
                    @click="emit('add-child', node)"
                    class="p-1 text-gray-400 hover:text-indigo-600 rounded"
                    title="Agregar subcategoría"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </button>
                <button
                    @click="emit('edit', node)"
                    class="p-1 text-gray-400 hover:text-indigo-600 rounded"
                    title="Editar"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.5-6.5a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H9v-2a2 2 0 01.586-1.414z" />
                    </svg>
                </button>
                <button
                    @click="emit('delete', node)"
                    class="p-1 text-gray-400 hover:text-red-500 rounded"
                    title="Eliminar"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m-7 0a1 1 0 01-1-1V5a1 1 0 011-1h8a1 1 0 011 1v1a1 1 0 01-1 1H9z" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Hijos (recursivo) con línea vertical -->
        <div v-if="hasChildren() && open" class="relative">
            <div
                class="absolute top-0 bottom-2 border-l border-gray-200"
                :style="{ left: `${depth * 20 + 19}px` }"
            />
            <CategoryTreeNode
                v-for="child in node.children"
                :key="child.id"
                :node="child"
                :depth="depth + 1"
                @edit="emit('edit', $event)"
                @delete="emit('delete', $event)"
                @add-child="emit('add-child', $event)"
                @quick-transaction="emit('quick-transaction', $event)"
            />
        </div>
    </div>
</template>
