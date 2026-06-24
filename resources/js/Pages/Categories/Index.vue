<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CategoryTreeNode from '@/Components/CategoryTreeNode.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useConfirm } from '@/composables/useConfirm';

const { confirm } = useConfirm();

const props = defineProps({
    categories: Array,
});

// ── Árbol ─────────────────────────────────────────────────────
function buildTree(items, parentId = null) {
    return items
        .filter(c => c.parent_id === parentId)
        .map(c => ({ ...c, children: buildTree(items, c.id) }));
}

const incomeTree  = computed(() => buildTree(props.categories.filter(c => c.type === 'income' || hasAncestorType(c, 'income')), null).filter(c => c.type === 'income'));
const expenseTree = computed(() => buildTree(props.categories.filter(c => c.type === 'expense' || hasAncestorType(c, 'expense')), null).filter(c => c.type === 'expense'));

// Árbol completo para todos los nodos raíz
const fullTree = computed(() => buildTree(props.categories, null));
const incomeRoots  = computed(() => fullTree.value.filter(n => n.type === 'income'));
const expenseRoots = computed(() => fullTree.value.filter(n => n.type === 'expense'));

function hasAncestorType(cat, type) {
    if (!cat.parent_id) return false;
    const parent = props.categories.find(c => c.id === cat.parent_id);
    return parent ? (parent.type === type || hasAncestorType(parent, type)) : false;
}

// ── Modal ─────────────────────────────────────────────────────
const showModal  = ref(false);
const editing    = ref(null);
const lockedType = ref(null); // tipo forzado cuando hay padre

const form = useForm({
    name:      '',
    type:      'expense',
    icon:      '',
    color:     '#6366f1',
    parent_id: null,
});

const colors = [
    '#6366f1', '#8b5cf6', '#ec4899', '#ef4444',
    '#f97316', '#eab308', '#22c55e', '#14b8a6',
    '#3b82f6', '#06b6d4', '#64748b', '#1e293b',
];

// Categorías disponibles como padre (excluye la editada y sus descendientes)
const parentOptions = computed(() => {
    if (!editing.value) return props.categories;
    const descendants = getAllDescendantIds(editing.value.id);
    return props.categories.filter(c => c.id !== editing.value.id && !descendants.includes(c.id));
});

function getAllDescendantIds(id) {
    const children = props.categories.filter(c => c.parent_id === id);
    return children.flatMap(c => [c.id, ...getAllDescendantIds(c.id)]);
}

function openCreate(type = 'expense', parentNode = null) {
    editing.value   = null;
    lockedType.value = parentNode ? parentNode.type : null;
    form.reset();
    form.type      = parentNode ? parentNode.type : type;
    form.parent_id = parentNode ? parentNode.id : null;
    form.color     = parentNode ? (parentNode.color ?? '#6366f1') : '#6366f1';
    showModal.value = true;
}

function openEdit(cat) {
    editing.value = cat;
    const parent  = cat.parent_id ? props.categories.find(c => c.id === cat.parent_id) : null;
    lockedType.value = parent ? parent.type : null;
    form.name      = cat.name;
    form.type      = cat.type;
    form.icon      = cat.icon ?? '';
    form.color     = cat.color ?? '#6366f1';
    form.parent_id = cat.parent_id ?? null;
    showModal.value = true;
}

function onParentChange() {
    if (form.parent_id) {
        const parent = props.categories.find(c => c.id === parseInt(form.parent_id));
        if (parent) {
            form.type    = parent.type;
            lockedType.value = parent.type;
        }
    } else {
        lockedType.value = null;
    }
}

function save() {
    const data = { ...form.data(), parent_id: form.parent_id || null };
    if (editing.value) {
        form.put(route('categories.update', editing.value.id), {
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post(route('categories.store'), {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    }
}

async function onDelete(cat) {
    const childCount = props.categories.filter(c => c.parent_id === cat.id).length;
    const message = childCount > 0
        ? `"${cat.name}" tiene ${childCount} subcategoría(s). Al eliminarla quedarán sin padre. ¿Continuar?`
        : `¿Eliminar la categoría "${cat.name}"?`;
    const ok = await confirm({ title: 'Eliminar categoría', message, confirmLabel: 'Eliminar', danger: true });
    if (ok) {
        useForm({}).delete(route('categories.destroy', cat.id));
    }
}

function onQuickTransaction(node) {
    router.get(route('transactions.index'), { quick_category_id: node.id, quick_type: node.type });
}

// Indentación del nombre en el selector padre
function indent(cat) {
    let depth = 0;
    let current = cat;
    while (current.parent_id) {
        current = props.categories.find(c => c.id === current.parent_id);
        if (!current) break;
        depth++;
    }
    return '　'.repeat(depth) + (depth > 0 ? '└ ' : '') + cat.name;
}
</script>

<template>
    <Head title="Categorías" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-lg font-semibold text-gray-800">Categorías</h1>
        </template>

        <!-- Botón nueva categoría raíz -->
        <div class="flex justify-end mb-4">
            <button
                @click="openCreate('expense')"
                class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nueva categoría
            </button>
        </div>

        <div class="space-y-5">
            <!-- Árbol Ingresos -->
            <section class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="flex items-center gap-2 px-4 py-3 border-b border-gray-100 bg-emerald-50">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <h2 class="text-sm font-semibold text-emerald-700 uppercase tracking-wide">Ingresos</h2>
                    <span class="ml-auto text-xs text-emerald-600 font-medium">
                        {{ categories.filter(c => c.type === 'income').length }} categorías
                    </span>
                    <button
                        @click="openCreate('income')"
                        class="ml-2 text-xs font-medium text-emerald-600 hover:text-emerald-800 flex items-center gap-1"
                    >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Agregar
                    </button>
                </div>
                <div class="p-2">
                    <div v-if="incomeRoots.length === 0" class="py-6 text-center text-sm text-gray-400 italic">
                        Sin categorías de ingreso aún.
                    </div>
                    <CategoryTreeNode
                        v-for="node in incomeRoots"
                        :key="node.id"
                        :node="node"
                        :depth="0"
                        @edit="openEdit"
                        @delete="onDelete"
                        @add-child="n => openCreate(n.type, n)"
                        @quick-transaction="onQuickTransaction"
                    />
                </div>
            </section>

            <!-- Árbol Egresos -->
            <section class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="flex items-center gap-2 px-4 py-3 border-b border-gray-100 bg-red-50">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                    <h2 class="text-sm font-semibold text-red-600 uppercase tracking-wide">Egresos</h2>
                    <span class="ml-auto text-xs text-red-500 font-medium">
                        {{ categories.filter(c => c.type === 'expense').length }} categorías
                    </span>
                    <button
                        @click="openCreate('expense')"
                        class="ml-2 text-xs font-medium text-red-500 hover:text-red-700 flex items-center gap-1"
                    >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Agregar
                    </button>
                </div>
                <div class="p-2">
                    <div v-if="expenseRoots.length === 0" class="py-6 text-center text-sm text-gray-400 italic">
                        Sin categorías de egreso aún.
                    </div>
                    <CategoryTreeNode
                        v-for="node in expenseRoots"
                        :key="node.id"
                        :node="node"
                        :depth="0"
                        @edit="openEdit"
                        @delete="onDelete"
                        @add-child="n => openCreate(n.type, n)"
                        @quick-transaction="onQuickTransaction"
                    />
                </div>
            </section>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40" @click="showModal = false" />

                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                    <h2 class="text-base font-semibold text-gray-900 mb-5">
                        {{ editing ? 'Editar categoría' : 'Nueva categoría' }}
                    </h2>

                    <form @submit.prevent="save" class="space-y-4">
                        <!-- Tipo (bloqueado si tiene padre) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                            <div class="flex rounded-lg overflow-hidden border border-gray-200" :class="lockedType ? 'opacity-60' : ''">
                                <button
                                    type="button"
                                    @click="!lockedType && (form.type = 'income')"
                                    :class="form.type === 'income' ? 'bg-emerald-500 text-white' : 'bg-white text-gray-600'"
                                    class="flex-1 py-2 text-sm font-medium transition-colors"
                                    :disabled="!!lockedType"
                                >
                                    Ingreso
                                </button>
                                <button
                                    type="button"
                                    @click="!lockedType && (form.type = 'expense')"
                                    :class="form.type === 'expense' ? 'bg-red-500 text-white' : 'bg-white text-gray-600'"
                                    class="flex-1 py-2 text-sm font-medium transition-colors"
                                    :disabled="!!lockedType"
                                >
                                    Egreso
                                </button>
                            </div>
                            <p v-if="lockedType" class="text-xs text-gray-400 mt-1">El tipo se hereda del padre.</p>
                        </div>

                        <!-- Categoría padre -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Categoría padre <span class="text-gray-400">(opcional)</span>
                            </label>
                            <select
                                v-model="form.parent_id"
                                @change="onParentChange"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                                <option :value="null">Sin padre (categoría raíz)</option>
                                <option v-for="c in parentOptions" :key="c.id" :value="c.id">
                                    {{ indent(c) }}
                                </option>
                            </select>
                        </div>

                        <!-- Nombre -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                            <input
                                v-model="form.name"
                                type="text"
                                placeholder="Ej: Supermercado, Sueldo…"
                                required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                            <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                        </div>

                        <!-- Ícono -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Ícono <span class="text-gray-400">(emoji opcional)</span>
                            </label>
                            <input
                                v-model="form.icon"
                                type="text"
                                placeholder="🛒"
                                maxlength="4"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>

                        <!-- Color -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Color</label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="c in colors"
                                    :key="c"
                                    type="button"
                                    @click="form.color = c"
                                    class="w-7 h-7 rounded-full border-2 transition-transform hover:scale-110"
                                    :style="{ backgroundColor: c }"
                                    :class="form.color === c ? 'border-gray-800 scale-110' : 'border-transparent'"
                                />
                            </div>
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
                                {{ editing ? 'Guardar cambios' : 'Crear categoría' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
