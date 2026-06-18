<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import EventCard from '@/components/events/EventCard.vue';
import type {EventCardData} from '@/components/events/EventCard.vue';
import EventFilters from '@/components/events/EventFilters.vue';

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
    <Head title="Events Visual 1" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-semibold">Events Visual 1</h1>
            <p class="text-sm text-muted-foreground">
                Browse events in a card grid
                <span v-if="total !== null"> · {{ total.toLocaleString() }} matching</span>
            </p>
        </div>

        <EventFilters v-model="form" :statuses="statuses" @apply="applyFilters" />

        <div
            v-if="hasLoadedOnce && events.length > 0"
            class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3"
        >
            <EventCard
                v-for="(event, index) in events"
                :key="event.id"
                :event="event"
                :index="index"
            />
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
