<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import type { EventCardData } from '@/components/events/EventCard.vue';
import EventFilters from '@/components/events/EventFilters.vue';
import EventTimelineItem from '@/components/events/EventTimelineItem.vue';
import { getEventSchedule } from '@/lib/formatEventDate';

const props = defineProps<{
    filters: { status: string | null; from: string; to?: string | null; location?: string | null };
    statuses: string[];
}>();

const form = reactive({
    status: props.filters.status ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
    location: props.filters.location ?? '',
});

const events = ref<EventCardData[]>([]);
const page = ref(0);
const lastPage = ref<number | null>(null);
const total = ref<number | null>(null);
const loading = ref(false);
const hasLoadedOnce = ref(false);

const sentinel = ref<HTMLElement | null>(null);
let observer: IntersectionObserver | null = null;

const hasMore = computed(() => lastPage.value === null || page.value < lastPage.value);

interface TimelineGroup {
    key: string;
    label: string;
    sortKey: number;
    events: EventCardData[];
}

function monthKey(unixSeconds: number): string {
    const date = new Date(unixSeconds * 1000);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');

    return `${year}-${month}`;
}

function monthLabel(unixSeconds: number): string {
    return new Intl.DateTimeFormat(undefined, { month: 'long', year: 'numeric' }).format(
        new Date(unixSeconds * 1000),
    );
}

const groupedEvents = computed((): TimelineGroup[] => {
    const groups = new Map<string, TimelineGroup>();

    for (const event of events.value) {
        const { startsAt } = getEventSchedule(event);
        const ts = startsAt ?? 0;
        const key = ts > 0 ? monthKey(ts) : 'unknown';
        const label = ts > 0 ? monthLabel(ts) : 'Unknown date';

        if (!groups.has(key)) {
            groups.set(key, { key, label, sortKey: ts, events: [] });
        }

        groups.get(key)!.events.push(event);
    }

    return [...groups.values()].sort((a, b) => b.sortKey - a.sortKey);
});

async function loadMore() {
    if (loading.value || !hasMore.value) {
        return;
    }

    loading.value = true;

    const params = new URLSearchParams({ page: String(page.value + 1) });

    if (form.status) {
params.set('status', form.status);
}

    if (form.from) {
params.set('from', form.from);
}

    if (form.to) {
params.set('to', form.to);
}

    if (form.location) {
params.set('location', form.location);
}

    try {
        const response = await fetch(`/events/data?${params.toString()}`, {
            headers: { Accept: 'application/json' },
        });
        const payload = await response.json();

        events.value.push(...payload.data);
        page.value = payload.current_page;
        lastPage.value = payload.last_page;
        total.value = payload.total;
        hasLoadedOnce.value = true;
    } finally {
        loading.value = false;
    }
}

function applyFilters() {
    events.value = [];
    page.value = 0;
    lastPage.value = null;
    total.value = null;
    hasLoadedOnce.value = false;
    loadMore();
}

onMounted(() => {
    observer = new IntersectionObserver(
        (entries) => {
            if (entries[0]?.isIntersecting) {
                loadMore();
            }
        },
        { rootMargin: '400px' },
    );

    if (sentinel.value) {
        observer.observe(sentinel.value);
    }

    loadMore();
});

onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <Head title="Events Visual 2" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-semibold">Events Visual 2</h1>
            <p class="text-sm text-muted-foreground">
                Browse events on a chronological timeline
                <span v-if="total !== null"> · {{ total.toLocaleString() }} matching</span>
            </p>
        </div>

        <EventFilters v-model="form" :statuses="statuses" @apply="applyFilters" />

        <div
            v-if="hasLoadedOnce && events.length > 0"
            class="relative ml-3 border-l-2 border-primary/20 pl-8 sm:ml-4 sm:pl-10"
        >
            <section
                v-for="group in groupedEvents"
                :key="group.key"
                class="mb-10 last:mb-0"
            >
                <h2
                    class="sticky top-0 z-20 -ml-8 mb-5 border-b border-border/60 bg-background/95 py-2 pl-8 text-lg font-semibold tracking-tight backdrop-blur-sm sm:-ml-10 sm:pl-10"
                >
                    {{ group.label }}
                </h2>

                <div class="space-y-4">
                    <EventTimelineItem
                        v-for="(event, index) in group.events"
                        :key="event.id"
                        :event="event"
                        :index="index"
                    />
                </div>
            </section>
        </div>

        <div
            v-else-if="!loading && hasLoadedOnce && events.length === 0"
            class="rounded-lg border border-dashed py-16 text-center text-muted-foreground"
        >
            No events found. Try adjusting your filters.
        </div>

        <div ref="sentinel" class="h-1" />

        <p v-if="loading" class="py-2 text-center text-sm text-muted-foreground">Loading events…</p>
    </div>
</template>
