<script setup lang="ts">
import { ChevronDown, SlidersHorizontal } from '@lucide/vue';
import { computed, ref } from 'vue';
import EventFilters from '@/components/events/EventFilters.vue';
import type { EventFilters as Filters } from '@/lib/events';

const props = defineProps<{ modelValue: Filters; compact?: boolean }>();
const emit = defineEmits<{ 'update:modelValue': [value: Filters] }>();

const open = ref(false);
const activeCount = computed(
    () =>
        Object.values(props.modelValue).filter(
            (v) => v !== undefined && v !== '',
        ).length,
);
</script>

<template>
    <div>
        <button
            type="button"
            class="flex items-center gap-2 rounded-full border border-pin-hairline bg-pin-card px-4 py-2.5 text-sm font-bold text-pin-ink transition-all hover:bg-pin-secondary active:scale-95"
            :aria-expanded="open"
            @click="open = !open"
        >
            <SlidersHorizontal class="h-4 w-4" />
            <span>Filters</span>
            <span
                v-if="activeCount"
                class="flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-pin-red px-1.5 text-[11px] font-bold text-pin-canvas"
            >
                {{ activeCount }}
            </span>
            <ChevronDown
                class="h-4 w-4 transition-transform duration-300"
                :class="open ? 'rotate-180' : ''"
            />
        </button>

        <!-- Smooth expand/collapse via grid-template-rows 0fr → 1fr -->
        <div
            class="grid transition-[grid-template-rows] duration-300 ease-out"
            :class="open ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'"
        >
            <div class="overflow-hidden">
                <div class="pt-4">
                    <EventFilters
                        :model-value="modelValue"
                        :compact="compact"
                        @update:model-value="
                            (v) => emit('update:modelValue', v)
                        "
                    />
                </div>
            </div>
        </div>
    </div>
</template>
