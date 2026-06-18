<script setup lang="ts">
import { reactive, ref, watch } from 'vue';
import { registerAttendee } from '@/lib/events';
import type { EventCard } from '@/lib/events';

const props = defineProps<{
    open: boolean;
    event: EventCard | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    registered: [payload: { eventId: string; count: number }];
}>();

const form = reactive({ name: '', email: '', status: 'going' });
const submitting = ref(false);
const error = ref<string | null>(null);
const done = ref<{ already: boolean } | null>(null);

watch(
    () => props.open,
    (open) => {
        if (open) {
            form.name = '';
            form.email = '';
            form.status = 'going';
            error.value = null;
            done.value = null;
        }
    },
);

function close() {
    emit('update:open', false);
}

async function submit() {
    if (!props.event || submitting.value) {
        return;
    }

    submitting.value = true;
    error.value = null;

    try {
        const result = await registerAttendee(props.event.id, { ...form });
        done.value = { already: result.already_registered };
        emit('registered', {
            eventId: props.event.id,
            count: result.attendees_count,
        });
    } catch (e) {
        error.value = e instanceof Error ? e.message : 'Something went wrong.';
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-150 ease-in"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-[1000] flex items-center justify-center bg-black/50 p-4 font-pin"
                @click.self="close"
            >
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="translate-y-3 scale-95 opacity-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-to-class="translate-y-3 scale-95 opacity-0"
                    appear
                >
                    <div
                        v-if="open && event"
                        class="w-full max-w-md overflow-hidden rounded-pin-lg bg-pin-canvas shadow-[0_16px_48px_rgba(0,0,0,0.18)]"
                    >
                        <div
                            class="h-28 bg-cover bg-center"
                            :style="
                                event.images[0]
                                    ? {
                                          backgroundImage: `url(${event.images[0]})`,
                                      }
                                    : {}
                            "
                        >
                            <div
                                class="flex h-full items-end bg-gradient-to-t from-black/70 to-transparent p-4"
                            >
                                <p
                                    class="line-clamp-1 text-sm font-bold text-white"
                                >
                                    {{ event.title }}
                                </p>
                            </div>
                        </div>

                        <div class="p-8">
                            <template v-if="!done">
                                <h2
                                    class="text-[22px] font-semibold tracking-[-0.4px] text-pin-ink"
                                >
                                    Register for this event
                                </h2>
                                <p class="mt-1 text-base text-pin-body">
                                    We'll email a confirmation and remind you
                                    before it starts.
                                </p>

                                <form
                                    class="mt-5 flex flex-col gap-3"
                                    @submit.prevent="submit"
                                >
                                    <div class="flex flex-col gap-1.5">
                                        <label
                                            class="text-sm font-semibold text-pin-ink"
                                            >Name</label
                                        >
                                        <input
                                            v-model="form.name"
                                            required
                                            type="text"
                                            class="h-11 rounded-pin-md border border-pin-ash bg-pin-canvas px-4 text-base text-pin-ink transition focus:border-pin-ink focus:ring-2 focus:ring-pin-focus focus:outline-none"
                                        />
                                    </div>
                                    <div class="flex flex-col gap-1.5">
                                        <label
                                            class="text-sm font-semibold text-pin-ink"
                                            >Email</label
                                        >
                                        <input
                                            v-model="form.email"
                                            required
                                            type="email"
                                            class="h-11 rounded-pin-md border border-pin-ash bg-pin-canvas px-4 text-base text-pin-ink transition focus:border-pin-ink focus:ring-2 focus:ring-pin-focus focus:outline-none"
                                        />
                                    </div>
                                    <div class="flex gap-2">
                                        <label
                                            v-for="opt in [
                                                'going',
                                                'interested',
                                            ]"
                                            :key="opt"
                                            class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-pin-md border px-3 py-2.5 text-sm font-bold capitalize transition"
                                            :class="
                                                form.status === opt
                                                    ? 'border-pin-ink bg-pin-ink text-pin-canvas'
                                                    : 'border-pin-hairline text-pin-ink hover:bg-pin-card'
                                            "
                                        >
                                            <input
                                                v-model="form.status"
                                                type="radio"
                                                :value="opt"
                                                class="sr-only"
                                            />
                                            {{ opt }}
                                        </label>
                                    </div>

                                    <p
                                        v-if="error"
                                        class="text-sm font-semibold text-pin-red"
                                    >
                                        {{ error }}
                                    </p>

                                    <div class="mt-3 flex justify-end gap-2">
                                        <button
                                            type="button"
                                            class="rounded-pin-md bg-pin-secondary px-5 py-2.5 text-sm font-bold text-pin-ink transition-all hover:bg-pin-secondary-pressed active:scale-95"
                                            @click="close"
                                        >
                                            Cancel
                                        </button>
                                        <button
                                            type="submit"
                                            :disabled="submitting"
                                            class="rounded-pin-md bg-pin-red px-5 py-2.5 text-sm font-bold text-pin-canvas transition-all hover:bg-pin-red-pressed active:scale-95 disabled:opacity-50"
                                        >
                                            {{
                                                submitting
                                                    ? 'Registering…'
                                                    : 'Confirm'
                                            }}
                                        </button>
                                    </div>
                                </form>
                            </template>

                            <div
                                v-else
                                class="flex flex-col items-center gap-3 py-4 text-center"
                            >
                                <div
                                    class="flex h-14 w-14 items-center justify-center rounded-full bg-pin-success-pale text-2xl text-pin-success"
                                >
                                    ✓
                                </div>
                                <h2
                                    class="text-[22px] font-semibold tracking-[-0.4px] text-pin-ink"
                                >
                                    {{
                                        done.already
                                            ? "You're already on the list"
                                            : "You're on the list!"
                                    }}
                                </h2>
                                <p class="text-base text-pin-body">
                                    {{
                                        done.already
                                            ? 'No need to register again.'
                                            : 'Check your inbox for a confirmation email.'
                                    }}
                                </p>
                                <button
                                    class="mt-2 rounded-pin-md bg-pin-red px-5 py-2.5 text-sm font-bold text-pin-canvas transition-all hover:bg-pin-red-pressed active:scale-95"
                                    @click="close"
                                >
                                    Done
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
