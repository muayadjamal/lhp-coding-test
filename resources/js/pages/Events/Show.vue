<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import RegisterDialog from '@/components/events/RegisterDialog.vue';
import EventsLayout from '@/layouts/EventsLayout.vue';
import type { EventCard } from '@/lib/events';

defineOptions({ layout: EventsLayout });

const props = defineProps<{ event: EventCard }>();

/** Return to the page we came from (map or list, with its filters intact). */
function goBack() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        router.visit('/events-visual-2');
    }
}

const activeImage = ref(props.event.images[0] ?? null);
const dialogOpen = ref(false);
const attendees = ref(props.event.attendees_count ?? 0);

const price = computed(() => {
    if (props.event.min_price === null) {
        return null;
    }

    return props.event.min_price === 0
        ? 'Free'
        : `$${props.event.min_price.toFixed(0)}`;
});

function onRegistered({ count }: { count: number }) {
    attendees.value = count;
}
</script>

<template>
    <Head :title="event.title" />

    <div
        class="mx-auto flex w-full max-w-[1080px] animate-in flex-col gap-8 px-4 py-8 duration-500 ease-out fade-in slide-in-from-bottom-4 sm:px-6"
    >
        <button
            type="button"
            class="self-start text-sm font-semibold text-pin-mute transition-colors hover:text-pin-ink"
            @click="goBack"
        >
            ← Back to events
        </button>

        <div class="grid gap-8 lg:grid-cols-[1.7fr_1fr]">
            <!-- Left: gallery -->
            <div class="flex flex-col gap-3">
                <div class="overflow-hidden rounded-pin-lg bg-pin-card">
                    <Transition
                        mode="out-in"
                        enter-active-class="transition duration-300 ease-out"
                        enter-from-class="scale-[1.02] opacity-0"
                        leave-active-class="transition duration-150 ease-in"
                        leave-to-class="opacity-0"
                    >
                        <img
                            :key="activeImage ?? ''"
                            :src="activeImage ?? ''"
                            :alt="event.title"
                            class="aspect-[16/10] w-full object-cover"
                        />
                    </Transition>
                </div>

                <div v-if="event.images.length > 1" class="flex gap-3">
                    <button
                        v-for="img in event.images"
                        :key="img"
                        type="button"
                        class="w-24 shrink-0 overflow-hidden rounded-pin-md transition hover:opacity-100"
                        :class="
                            img === activeImage
                                ? 'ring-2 ring-pin-ink'
                                : 'opacity-70'
                        "
                        @click="activeImage = img"
                    >
                        <img
                            :src="img"
                            :alt="event.title"
                            class="aspect-[4/3] w-full object-cover"
                        />
                    </button>
                </div>
            </div>

            <!-- Right: info card -->
            <aside
                class="flex h-fit flex-col gap-5 rounded-pin-lg border border-pin-hairline bg-pin-canvas p-6 lg:sticky lg:top-24"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="rounded-full bg-pin-card px-3 py-1 text-xs font-bold text-pin-ink capitalize"
                    >
                        {{ event.type }}
                    </span>
                    <span
                        v-if="event.featured"
                        class="rounded-full bg-pin-red px-3 py-1 text-xs font-bold text-pin-canvas"
                    >
                        ★ Featured
                    </span>
                    <span
                        v-if="event.status !== 'published'"
                        class="rounded-full bg-pin-card px-3 py-1 text-xs font-bold text-pin-mute capitalize"
                    >
                        {{ event.status.replace('_', ' ') }}
                    </span>
                </div>

                <h1
                    class="text-[26px] leading-[1.2] font-semibold tracking-[-0.6px] text-pin-ink"
                >
                    {{ event.title }}
                </h1>

                <div class="flex flex-col gap-1">
                    <span
                        class="text-[11px] font-bold tracking-wide text-pin-mute uppercase"
                        >When</span
                    >
                    <span class="font-semibold text-pin-ink">{{
                        event.starts_at_local ?? 'TBA'
                    }}</span>
                    <span class="text-xs text-pin-ash"
                        >Local time · {{ event.timezone }}</span
                    >
                </div>
                <div class="flex flex-col gap-1">
                    <span
                        class="text-[11px] font-bold tracking-wide text-pin-mute uppercase"
                        >Where</span
                    >
                    <span class="font-semibold text-pin-ink">{{
                        event.venue ?? 'Venue TBA'
                    }}</span>
                    <span class="text-sm text-pin-mute">{{
                        event.location_label
                    }}</span>
                </div>

                <div
                    class="flex items-center justify-between border-t border-pin-hairline pt-5"
                >
                    <div>
                        <p v-if="price" class="text-xl font-bold text-pin-ink">
                            {{ price }}
                        </p>
                        <p class="text-xs text-pin-ash">
                            {{ attendees }} going
                        </p>
                    </div>
                    <button
                        type="button"
                        :disabled="event.status === 'cancelled'"
                        class="rounded-pin-md bg-pin-red px-6 py-3 text-sm font-bold text-pin-canvas transition-all hover:bg-pin-red-pressed active:scale-95 disabled:bg-pin-secondary disabled:text-pin-ash"
                        @click="dialogOpen = true"
                    >
                        Register
                    </button>
                </div>
            </aside>
        </div>

        <!-- About -->
        <div class="max-w-[680px]">
            <h2 class="text-[18px] font-semibold text-pin-ink">
                About this event
            </h2>
            <p
                class="mt-3 text-base leading-relaxed whitespace-pre-line text-pin-body"
            >
                {{ event.description }}
            </p>
        </div>
    </div>

    <RegisterDialog
        v-model:open="dialogOpen"
        :event="event"
        @registered="onRegistered"
    />
</template>
