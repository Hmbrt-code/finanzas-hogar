<script setup>
import { useConfirm } from '@/composables/useConfirm';

const { state, onConfirm, onCancel } = useConfirm();
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="state.show"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                role="dialog"
                aria-modal="true"
            >
                <!-- Overlay -->
                <div
                    class="absolute inset-0 bg-black/40 backdrop-blur-sm"
                    @click="onCancel"
                />

                <!-- Card -->
                <Transition
                    enter-active-class="duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                    appear
                >
                    <div class="relative bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 flex flex-col gap-4">

                        <!-- Ícono -->
                        <div
                            class="w-12 h-12 rounded-full flex items-center justify-center mx-auto"
                            :class="state.danger ? 'bg-red-100' : 'bg-indigo-100'"
                        >
                            <!-- Danger icon -->
                            <svg v-if="state.danger"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor"
                                class="w-6 h-6 text-red-600"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                            <!-- Info icon -->
                            <svg v-else
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor"
                                class="w-6 h-6 text-indigo-600"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
                            </svg>
                        </div>

                        <!-- Texto -->
                        <div class="text-center">
                            <h3 v-if="state.title" class="text-base font-semibold text-gray-900 mb-1">
                                {{ state.title }}
                            </h3>
                            <p class="text-sm text-gray-500 leading-relaxed">
                                {{ state.message }}
                            </p>
                        </div>

                        <!-- Botones -->
                        <div class="flex gap-3 mt-1">
                            <button
                                @click="onCancel"
                                class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-300"
                            >
                                {{ state.cancelLabel }}
                            </button>
                            <button
                                @click="onConfirm"
                                class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium text-white transition-colors focus:outline-none focus:ring-2"
                                :class="state.danger
                                    ? 'bg-red-600 hover:bg-red-700 focus:ring-red-400'
                                    : 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-400'"
                            >
                                {{ state.confirmLabel }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
