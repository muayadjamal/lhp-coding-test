// Shared types and API helpers for the two event visual pages.

export interface EventFilters {
    from?: string;
    to?: string;
    status?: string;
    type?: string;
    country?: string;
    city?: string;
    /** "lat,lng" point for the "near me" filter. */
    near?: string;
}

export interface CityOption {
    city: string;
    country: string;
    lat: number;
    lng: number;
}

export interface FilterOptions {
    statuses: string[];
    types: string[];
    countries: Record<string, string>;
    cities: CityOption[];
}

export interface EventCard {
    id: string;
    title: string;
    description: string;
    type: string;
    status: string;
    featured: boolean;
    venue: string | null;
    latitude: number | null;
    longitude: number | null;
    location_label: string | null;
    city: string | null;
    country: string | null;
    timezone: string;
    starts_at_utc: string | null;
    starts_at_local: string | null;
    min_price: number | null;
    currency: string;
    images: string[];
    attendees_count: number | null;
}

export interface MapPoint {
    id: string;
    lat: number;
    lng: number;
    type: string;
    title: string;
    location_label: string | null;
    starts_at_local: string | null;
    featured: boolean;
}

export interface Cluster {
    lat: number;
    lng: number;
    count: number;
}

export interface ClusterResponse {
    mode: 'clusters' | 'points';
    total: number;
    clusters?: Cluster[];
    points?: MapPoint[];
}

export interface Bounds {
    north: number;
    south: number;
    east: number;
    west: number;
}

/** Serialise active (non-empty) filters into a URLSearchParams. */
function toParams(
    filters: EventFilters,
    extra: Record<string, string | number> = {},
): URLSearchParams {
    const params = new URLSearchParams();

    for (const [key, value] of Object.entries(filters)) {
        if (value !== undefined && value !== null && value !== '') {
            params.set(key, String(value));
        }
    }

    for (const [key, value] of Object.entries(extra)) {
        params.set(key, String(value));
    }

    return params;
}

export async function fetchFilterOptions(): Promise<FilterOptions> {
    const res = await fetch('/events/filters', {
        headers: { Accept: 'application/json' },
    });

    return res.json();
}

export async function fetchEventPage(
    filters: EventFilters,
    page: number,
): Promise<{
    data: EventCard[];
    current_page: number;
    last_page: number;
    total: number;
    stats: { ms: number };
}> {
    const res = await fetch(
        `/events/cards?${toParams(filters, { page }).toString()}`,
        {
            headers: { Accept: 'application/json' },
        },
    );

    return res.json();
}

export async function fetchClusters(
    filters: EventFilters,
    bounds: Bounds,
    zoom: number,
): Promise<ClusterResponse> {
    const res = await fetch(
        `/events/clusters?${toParams(filters, { ...bounds, zoom }).toString()}`,
        { headers: { Accept: 'application/json' } },
    );

    return res.json();
}

/** Read a cookie value (used for the CSRF token). */
function cookie(name: string): string | null {
    const match = document.cookie.match(
        new RegExp('(^|; )' + name + '=([^;]*)'),
    );

    return match ? decodeURIComponent(match[2]) : null;
}

export interface RegisterResult {
    ok: boolean;
    already_registered: boolean;
    attendees_count: number;
    attendee: { name: string; status: string };
}

export async function registerAttendee(
    eventId: string,
    payload: { name: string; email: string; status: string },
): Promise<RegisterResult> {
    const res = await fetch(`/events/${eventId}/attendees`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-XSRF-TOKEN': cookie('XSRF-TOKEN') ?? '',
        },
        credentials: 'same-origin',
        body: JSON.stringify(payload),
    });

    if (!res.ok) {
        const body = await res.json().catch(() => ({}));

        throw new Error(body.message ?? 'Could not register for this event.');
    }

    return res.json();
}
