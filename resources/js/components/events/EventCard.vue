<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { EventCard } from '@/lib/events';

const props = defineProps<{ event: EventCard }>();
const emit = defineEmits<{ register: [event: EventCard] }>();

const price = computed(() => {
    if (props.event.min_price === null) {
        return null;
    }

    return props.event.min_price === 0
        ? 'Free'
        : `$${props.event.min_price.toFixed(0)}`;
});

const canRegister = computed(() => props.event.status !== 'cancelled');
</script>

<template>
    <article class="group mb-5 break-inside-avoid">
        <!-- Pin image: the photograph is the card -->
        <Link
            :href="`/events/${event.id}`"
            class="relative block overflow-hidden rounded-pin-md bg-pin-card"
        >
            <img
                v-if="event.images[0]"
                :src="event.images[0]"
                :alt="event.title"
                loading="lazy"
                class="aspect-[4/3] w-full object-cover transition-transform duration-500 ease-out group-hover:scale-[1.04]"
            />
            <div v-else class="aspect-[4/3] w-full bg-pin-card" />

            <!-- Overlay pills -->
            <span
                class="pointer-events-none absolute top-2 left-2 rounded-full bg-pin-canvas px-3 py-1 text-xs font-bold text-pin-ink capitalize"
            >
                {{ event.type }}
            </span>
            <span
                v-if="event.featured"
                class="pointer-events-none absolute top-2 right-2 rounded-full bg-pin-red px-3 py-1 text-xs font-bold text-pin-canvas"
            >
                ★ Featured
            </span>

            <div
                v-if="
                    event.status === 'sold_out' || event.status === 'cancelled'
                "
                class="absolute inset-x-0 bottom-0 bg-pin-ink/75 py-1.5 text-center text-xs font-bold tracking-wide text-pin-canvas uppercase"
            >
                {{ event.status.replace('_', ' ') }}
            </div>
        </Link>

        <!-- Meta sits under the pin, no card chrome -->
        <div class="px-1 pt-2.5 pb-1">
            <Link
                :href="`/events/${event.id}`"
                class="line-clamp-1 text-base font-semibold text-pin-ink transition-colors hover:text-pin-mute"
            >
                {{ event.title }}
            </Link>

            <p
                v-if="event.starts_at_local"
                class="mt-1 line-clamp-1 text-xs font-medium text-pin-mute"
            >
                {{ event.starts_at_local }}
            </p>
            <p
                v-if="event.location_label"
                class="line-clamp-1 text-xs text-pin-ash"
            >
                {{ event.venue ? `${event.venue} · ` : ''
                }}{{ event.location_label }}
            </p>

            <div class="mt-2 flex items-center justify-between">
                <div class="flex items-baseline gap-1.5">
                    <span v-if="price" class="text-sm font-bold text-pin-ink">{{
                        price
                    }}</span>
                    <span
                        v-if="event.attendees_count"
                        class="text-xs text-pin-ash"
                        >· {{ event.attendees_count }} going</span
                    >
                </div>
                <button
                    type="button"
                    :disabled="!canRegister"
                    class="rounded-full bg-pin-red px-3.5 py-1.5 text-xs font-bold text-pin-canvas transition-all hover:bg-pin-red-pressed active:scale-95 disabled:cursor-not-allowed disabled:bg-pin-secondary disabled:text-pin-ash"
                    @click="emit('register', event)"
                >
                    Register
                </button>
            </div>
        </div>
    </article>
</template>
