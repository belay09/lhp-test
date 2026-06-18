<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import EventImageGallery, { type EventImageItem } from '@/components/events/EventImageGallery.vue';
import { formatEventSchedule, type EventScheduleSource } from '@/lib/formatEventDate';

export interface EventCardData extends EventScheduleSource {
    id: string;
    type: string;
    status: string;
    location_label: string | null;
    payload?: Record<string, unknown> | null;
    images?: EventImageItem[];
}

const props = defineProps<{
    event: EventCardData;
    index?: number;
}>();

const title = computed(() => {
    const name = props.event.payload?.name;
    return typeof name === 'string' ? name : props.event.type;
});

const description = computed(() => {
    const text = props.event.payload?.description;
    return typeof text === 'string' ? text : '';
});

const scheduleDisplay = computed(() => formatEventSchedule(props.event, { relative: true }));

const statusVariant = computed(() => {
    switch (props.event.status) {
        case 'published':
            return 'default';
        case 'cancelled':
            return 'destructive';
        case 'sold_out':
            return 'secondary';
        default:
            return 'outline';
    }
});

const animationDelay = computed(() => `${Math.min(props.index ?? 0, 12) * 40}ms`);
</script>

<template>
    <article
        class="animate-in fade-in-0 slide-in-from-bottom-2 fill-mode-both flex flex-col overflow-hidden rounded-xl border bg-card shadow-sm duration-500"
        :style="{ animationDelay }"
    >
        <Link :href="`/events/${event.id}`" class="block">
            <EventImageGallery :images="event.images ?? []" :alt="title" />
        </Link>

        <div class="flex flex-1 flex-col gap-2 p-4">
            <div class="flex items-start justify-between gap-2">
                <Link :href="`/events/${event.id}`" class="line-clamp-2 font-semibold leading-snug hover:underline">
                    {{ title }}
                </Link>
                <Badge :variant="statusVariant" class="shrink-0 capitalize">{{ event.status }}</Badge>
            </div>

            <p v-if="description" class="line-clamp-3 text-sm text-muted-foreground">
                {{ description }}
            </p>

            <div class="mt-auto space-y-1 pt-1 text-sm">
                <p class="text-muted-foreground">{{ scheduleDisplay }}</p>
                <p v-if="event.location_label" class="font-medium">{{ event.location_label }}</p>
            </div>

            <Link
                :href="`/events/${event.id}`"
                class="text-sm font-medium text-primary hover:underline"
            >
                View details →
            </Link>
        </div>
    </article>
</template>
