<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const path = computed(() => page.url.split('?')[0]);

const links = [
    { label: 'Discover', href: '/events-visual-2' },
    { label: 'Map', href: '/events-visual-1' },
];

function isActive(href: string) {
    return path.value === href;
}
</script>

<template>
    <div class="min-h-screen bg-pin-soft font-pin text-pin-body">
        <!-- Primary nav -->
        <header
            class="sticky top-0 z-50 border-b border-pin-hairline bg-pin-canvas"
        >
            <nav
                class="mx-auto flex h-16 max-w-[1280px] items-center gap-2 px-4 sm:px-6"
            >
                <Link
                    href="/events-visual-2"
                    class="flex items-center gap-2 pr-2"
                >
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-pin-red text-pin-canvas"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            class="h-4 w-4"
                            fill="currentColor"
                            aria-hidden="true"
                        >
                            <path
                                d="M12 2C7.6 2 4 5.6 4 10c0 5.2 7 11.4 7.3 11.7.4.4 1 .4 1.4 0C13 21.4 20 15.2 20 10c0-4.4-3.6-8-8-8zm0 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"
                            />
                        </svg>
                    </span>
                    <span
                        class="text-xl font-bold tracking-[-0.6px] text-pin-red"
                        >Eventful</span
                    >
                </Link>

                <div class="ml-1 flex items-center gap-1">
                    <Link
                        v-for="l in links"
                        :key="l.href"
                        :href="l.href"
                        class="rounded-full px-4 py-2 text-base font-semibold transition-colors"
                        :class="
                            isActive(l.href)
                                ? 'bg-pin-ink text-pin-canvas'
                                : 'text-pin-ink hover:bg-pin-card'
                        "
                    >
                        {{ l.label }}
                    </Link>
                </div>

                <div class="ml-auto flex items-center gap-2">
                    <Link
                        href="/events/random"
                        class="group flex items-center gap-1.5 rounded-full bg-pin-red px-4 py-2 text-sm font-bold text-pin-canvas transition-all hover:bg-pin-red-pressed active:scale-95"
                    >
                        <span
                            class="transition-transform duration-300 group-hover:rotate-12"
                            >✦</span
                        >
                        Surprise me
                    </Link>
                </div>
            </nav>
        </header>

        <main>
            <slot />
        </main>
    </div>
</template>
