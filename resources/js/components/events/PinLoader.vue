<script setup lang="ts">
withDefaults(defineProps<{ label?: string; center?: boolean }>(), {
    label: '',
    center: false,
});
</script>

<template>
    <div
        class="flex items-center gap-3"
        :class="center ? 'flex-col justify-center py-20 text-center' : ''"
        role="status"
        aria-live="polite"
    >
        <span
            class="flex items-end gap-1.5"
            :class="center ? 'h-6' : 'h-4'"
            aria-hidden="true"
        >
            <span class="h-2.5 w-2.5 animate-bounce rounded-full bg-pin-red" />
            <span
                class="h-2.5 w-2.5 animate-bounce rounded-full bg-pin-red [animation-delay:0.15s]"
            />
            <span
                class="h-2.5 w-2.5 animate-bounce rounded-full bg-pin-red [animation-delay:0.3s]"
            />
        </span>
        <span v-if="label" class="text-sm font-semibold text-pin-mute">{{
            label
        }}</span>
    </div>
</template>
