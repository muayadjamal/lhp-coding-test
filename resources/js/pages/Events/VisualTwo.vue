<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import EventCardComponent from '@/components/events/EventCard.vue';
import EventFilters from '@/components/events/EventFilters.vue';
import PinLoader from '@/components/events/PinLoader.vue';
import RegisterDialog from '@/components/events/RegisterDialog.vue';
import {
    readFiltersFromUrl,
    syncFiltersToUrl,
} from '@/composables/useEventFilters';
import EventsLayout from '@/layouts/EventsLayout.vue';
import { fetchEventPage } from '@/lib/events';
import type { EventCard, EventFilters as Filters } from '@/lib/events';

defineOptions({ layout: EventsLayout });

const filters = ref<Filters>(readFiltersFromUrl());
const events = ref<EventCard[]>([]);
const page = ref(0);
const lastPage = ref<number | null>(null);
const total = ref<number | null>(null);
const loading = ref(false);

const sentinel = ref<HTMLElement | null>(null);
let observer: IntersectionObserver | null = null;

const dialogOpen = ref(false);
const selected = ref<EventCard | null>(null);

const hasMore = computed(
    () => lastPage.value === null || page.value < lastPage.value,
);
const initialLoading = computed(
    () => loading.value && events.value.length === 0,
);

async function loadMore() {
    if (loading.value || !hasMore.value) {
        return;
    }

    loading.value = true;

    try {
        const res = await fetchEventPage(filters.value, page.value + 1);
        events.value.push(...res.data);
        page.value = res.current_page;
        lastPage.value = res.last_page;
        total.value = res.total;
    } finally {
        loading.value = false;
    }
}

function reset() {
    events.value = [];
    page.value = 0;
    lastPage.value = null;
    total.value = null;
    loadMore();
}

watch(
    filters,
    () => {
        syncFiltersToUrl(filters.value);
        reset();
    },
    { deep: true },
);

function openRegister(event: EventCard) {
    selected.value = event;
    dialogOpen.value = true;
}

function onRegistered({ eventId, count }: { eventId: string; count: number }) {
    const target = events.value.find((e) => e.id === eventId);

    if (target) {
        target.attendees_count = count;
    }
}

onMounted(() => {
    observer = new IntersectionObserver(
        (entries) => {
            if (entries[0]?.isIntersecting) {
                loadMore();
            }
        },
        { rootMargin: '600px' },
    );

    if (sentinel.value) {
        observer.observe(sentinel.value);
    }

    loadMore();
});

onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <Head title="Discover events" />

    <!-- Hero -->
    <section class="border-b border-pin-hairline bg-pin-canvas">
        <div
            class="mx-auto max-w-[1280px] animate-in px-4 py-12 duration-500 ease-out fade-in slide-in-from-bottom-4 sm:px-6 sm:py-16"
        >
            <p
                class="text-sm font-bold tracking-widest text-pin-mute uppercase"
            >
                Discover what's on
            </p>
            <h1
                class="mt-3 max-w-3xl text-[44px] leading-[1.1] font-semibold tracking-[-1.2px] text-pin-ink sm:text-[64px]"
            >
                Find your next concert, conference or festival
            </h1>
            <p class="mt-4 max-w-xl text-base text-pin-body">
                Browse
                <span class="font-bold text-pin-ink">{{
                    total !== null ? total.toLocaleString() : '—'
                }}</span>
                events around the world. Filter by date and location, then
                register in a click.
            </p>
        </div>
    </section>

    <!-- Sticky filter strip -->
    <div
        class="sticky top-16 z-30 border-b border-pin-hairline bg-pin-soft/90 backdrop-blur"
    >
        <div class="mx-auto max-w-[1280px] px-4 py-3 sm:px-6">
            <EventFilters v-model="filters" />
        </div>
    </div>

    <!-- Pin grid -->
    <div class="mx-auto max-w-[1280px] px-4 py-6 sm:px-6">
        <!-- Initial load -->
        <PinLoader v-if="initialLoading" center label="Finding events…" />

        <!-- Empty -->
        <div
            v-else-if="!loading && events.length === 0"
            class="flex flex-col items-center gap-2 rounded-pin-lg border border-dashed border-pin-hairline py-24 text-center"
        >
            <p class="text-4xl">🗺️</p>
            <p class="text-[22px] font-semibold text-pin-ink">
                No events match your filters
            </p>
            <p class="text-sm text-pin-mute">
                Try widening the date range or clearing the location.
            </p>
        </div>

        <!-- Masonry -->
        <TransitionGroup
            v-else
            tag="div"
            class="columns-1 gap-5 sm:columns-2 lg:columns-3"
            enter-active-class="transition duration-500 ease-out"
            enter-from-class="translate-y-3 opacity-0"
        >
            <EventCardComponent
                v-for="event in events"
                :key="event.id"
                :event="event"
                @register="openRegister"
            />
        </TransitionGroup>

        <div ref="sentinel" class="h-px" />
        <div class="flex justify-center py-6 text-center text-sm text-pin-mute">
            <PinLoader
                v-if="loading && events.length > 0"
                label="Loading more…"
            />
            <span v-else-if="!hasMore && events.length > 0">
                You've reached the end · {{ total?.toLocaleString() }} events
            </span>
        </div>
    </div>

    <RegisterDialog
        v-model:open="dialogOpen"
        :event="selected"
        @registered="onRegistered"
    />
</template>
