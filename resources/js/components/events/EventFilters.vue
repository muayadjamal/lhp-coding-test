<script setup lang="ts">
import { VueDatePicker } from '@vuepic/vue-datepicker';
import { computed, onMounted, ref } from 'vue';
import { fetchFilterOptions } from '@/lib/events';
import type { EventFilters, FilterOptions } from '@/lib/events';

const props = defineProps<{
    modelValue: EventFilters;
    /** Hide status/type controls when a page wants a leaner bar. */
    compact?: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: EventFilters];
}>();

const options = ref<FilterOptions | null>(null);

onMounted(async () => {
    options.value = await fetchFilterOptions();
});

function update<K extends keyof EventFilters>(key: K, value: string) {
    const next: EventFilters = {
        ...props.modelValue,
        [key]: value || undefined,
    };

    // Clearing the country also clears a city that no longer belongs to it.
    if (key === 'country' && next.city) {
        const stillValid = options.value?.cities.some(
            (c) => c.city === next.city && (!value || c.country === value),
        );

        if (!stillValid) {
            next.city = undefined;
        }
    }

    emit('update:modelValue', next);
}

const cities = computed(() => {
    if (!options.value) {
        return [];
    }

    const country = props.modelValue.country;

    return options.value.cities.filter(
        (c) => !country || c.country === country,
    );
});

const activeCount = computed(
    () =>
        Object.values(props.modelValue).filter(
            (v) => v !== undefined && v !== '',
        ).length,
);

function clear() {
    emit('update:modelValue', {});
}

/** Bridge the string filter value (YYYY-MM-DD) and the picker's Date. */
function toDate(value?: string): Date | null {
    return value ? new Date(`${value}T00:00:00`) : null;
}

function toIso(date: Date | null): string {
    if (!date) {
        return '';
    }

    const local = new Date(date.getTime() - date.getTimezoneOffset() * 60000);

    return local.toISOString().slice(0, 10);
}

function dateLabel(value?: string): string {
    if (!value) {
        return 'Any date';
    }

    return new Date(`${value}T00:00:00`).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

// --- "Near me" (geolocation) -------------------------------------------------
const locating = ref(false);
const geoError = ref<string | null>(null);
const isNear = computed(() => !!props.modelValue.near);

function toggleNear() {
    geoError.value = null;

    if (props.modelValue.near) {
        update('near', '');

        return;
    }

    if (!navigator.geolocation) {
        geoError.value = 'Location is not available in this browser.';

        return;
    }

    locating.value = true;
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            locating.value = false;
            update(
                'near',
                `${pos.coords.latitude.toFixed(4)},${pos.coords.longitude.toFixed(4)}`,
            );
        },
        () => {
            locating.value = false;
            geoError.value = 'Could not get your location.';
        },
        { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 },
    );
}
</script>

<template>
    <div class="flex flex-wrap items-end gap-2.5">
        <button
            type="button"
            :disabled="locating"
            class="flex h-11 items-center gap-2 self-end rounded-full px-4 text-sm font-bold transition-all active:scale-95 disabled:opacity-70"
            :class="
                isNear
                    ? 'bg-pin-red text-pin-canvas hover:bg-pin-red-pressed'
                    : 'border border-pin-hairline bg-pin-card text-pin-ink hover:bg-pin-secondary'
            "
            :title="geoError ?? ''"
            @click="toggleNear"
        >
            <span>📍</span>
            <span>{{
                locating ? 'Locating…' : isNear ? 'Near me ✓' : 'Near me'
            }}</span>
        </button>

        <div class="flex flex-col gap-1">
            <span
                class="px-1 text-[11px] font-bold tracking-wide text-pin-mute uppercase"
                >From</span
            >
            <VueDatePicker
                :model-value="toDate(modelValue.from)"
                :enable-time-picker="false"
                :max-date="toDate(modelValue.to) ?? undefined"
                :clearable="true"
                auto-apply
                format="MMM d, yyyy"
                placeholder="Any date"
                teleport
                @update:model-value="
                    (d: Date | null) => update('from', toIso(d))
                "
            >
                <template #dp-input>
                    <button
                        type="button"
                        class="flex h-11 min-w-[9.5rem] items-center rounded-full border border-pin-hairline bg-pin-card px-4 text-sm font-semibold transition hover:bg-pin-secondary"
                    >
                        <span
                            :class="
                                modelValue.from
                                    ? 'text-pin-ink'
                                    : 'text-pin-ash'
                            "
                            >{{ dateLabel(modelValue.from) }}</span
                        >
                    </button>
                </template>
            </VueDatePicker>
        </div>

        <div class="flex flex-col gap-1">
            <span
                class="px-1 text-[11px] font-bold tracking-wide text-pin-mute uppercase"
                >To</span
            >
            <VueDatePicker
                :model-value="toDate(modelValue.to)"
                :enable-time-picker="false"
                :min-date="toDate(modelValue.from) ?? undefined"
                :clearable="true"
                auto-apply
                format="MMM d, yyyy"
                placeholder="Any date"
                teleport
                @update:model-value="(d: Date | null) => update('to', toIso(d))"
            >
                <template #dp-input>
                    <button
                        type="button"
                        class="flex h-11 min-w-[9.5rem] items-center rounded-full border border-pin-hairline bg-pin-card px-4 text-sm font-semibold transition hover:bg-pin-secondary"
                    >
                        <span
                            :class="
                                modelValue.to ? 'text-pin-ink' : 'text-pin-ash'
                            "
                            >{{ dateLabel(modelValue.to) }}</span
                        >
                    </button>
                </template>
            </VueDatePicker>
        </div>

        <label class="flex flex-col gap-1">
            <span
                class="px-1 text-[11px] font-bold tracking-wide text-pin-mute uppercase"
                >Country</span
            >
            <select
                :value="modelValue.country ?? ''"
                class="h-11 min-w-[9.5rem] rounded-full border border-pin-hairline bg-pin-card px-4 text-sm font-semibold text-pin-ink transition focus:border-pin-ink focus:bg-pin-canvas focus:ring-2 focus:ring-pin-focus/40 focus:outline-none"
                @change="
                    update(
                        'country',
                        ($event.target as HTMLSelectElement).value,
                    )
                "
            >
                <option value="">All countries</option>
                <option
                    v-for="(name, code) in options?.countries ?? {}"
                    :key="code"
                    :value="code"
                >
                    {{ name }}
                </option>
            </select>
        </label>

        <label class="flex flex-col gap-1">
            <span
                class="px-1 text-[11px] font-bold tracking-wide text-pin-mute uppercase"
                >City</span
            >
            <select
                :value="modelValue.city ?? ''"
                class="h-11 min-w-[9.5rem] rounded-full border border-pin-hairline bg-pin-card px-4 text-sm font-semibold text-pin-ink transition focus:border-pin-ink focus:bg-pin-canvas focus:ring-2 focus:ring-pin-focus/40 focus:outline-none"
                @change="
                    update('city', ($event.target as HTMLSelectElement).value)
                "
            >
                <option value="">All cities</option>
                <option v-for="c in cities" :key="c.city" :value="c.city">
                    {{ c.city }}
                </option>
            </select>
        </label>

        <label v-if="!compact" class="flex flex-col gap-1">
            <span
                class="px-1 text-[11px] font-bold tracking-wide text-pin-mute uppercase"
                >Type</span
            >
            <select
                :value="modelValue.type ?? ''"
                class="h-11 rounded-full border border-pin-hairline bg-pin-card px-4 text-sm font-semibold text-pin-ink capitalize transition focus:border-pin-ink focus:bg-pin-canvas focus:ring-2 focus:ring-pin-focus/40 focus:outline-none"
                @change="
                    update('type', ($event.target as HTMLSelectElement).value)
                "
            >
                <option value="">All types</option>
                <option
                    v-for="t in options?.types ?? []"
                    :key="t"
                    :value="t"
                    class="capitalize"
                >
                    {{ t }}
                </option>
            </select>
        </label>

        <label v-if="!compact" class="flex flex-col gap-1">
            <span
                class="px-1 text-[11px] font-bold tracking-wide text-pin-mute uppercase"
                >Status</span
            >
            <select
                :value="modelValue.status ?? ''"
                class="h-11 rounded-full border border-pin-hairline bg-pin-card px-4 text-sm font-semibold text-pin-ink capitalize transition focus:border-pin-ink focus:bg-pin-canvas focus:ring-2 focus:ring-pin-focus/40 focus:outline-none"
                @change="
                    update('status', ($event.target as HTMLSelectElement).value)
                "
            >
                <option value="">Any status</option>
                <option
                    v-for="s in options?.statuses ?? []"
                    :key="s"
                    :value="s"
                    class="capitalize"
                >
                    {{ s.replace('_', ' ') }}
                </option>
            </select>
        </label>

        <button
            v-if="activeCount > 0"
            type="button"
            class="h-11 rounded-full px-4 text-sm font-bold text-pin-mute transition hover:bg-pin-card hover:text-pin-ink"
            @click="clear"
        >
            Clear ({{ activeCount }})
        </button>
    </div>
</template>
