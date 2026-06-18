import type { EventFilters } from '@/lib/events';

const KEYS: (keyof EventFilters)[] = [
    'from',
    'to',
    'status',
    'type',
    'country',
    'city',
];

/** Read the active filters from the current URL's query string. */
export function readFiltersFromUrl(): EventFilters {
    const params = new URLSearchParams(window.location.search);
    const filters: EventFilters = {};

    for (const key of KEYS) {
        const value = params.get(key);

        if (value) {
            filters[key] = value;
        }
    }

    return filters;
}

/**
 * Reflect the active filters into the URL (without a navigation), preserving
 * Inertia's history state so the browser back button still restores the page.
 */
export function syncFiltersToUrl(filters: EventFilters): void {
    const params = new URLSearchParams();

    for (const key of KEYS) {
        const value = filters[key];

        if (value) {
            params.set(key, value);
        }
    }

    const query = params.toString();
    const url = window.location.pathname + (query ? `?${query}` : '');

    window.history.replaceState(window.history.state, '', url);
}
