<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { EventCardData } from '@/components/events/EventCard.vue';
import { formatEventSchedule } from '@/lib/formatEventDate';

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

const thumbnailUrl = computed(() => props.event.images?.[0]?.url ?? null);
const imageCount = computed(() => props.event.images?.length ?? 0);

const animationDelay = computed(() => `${Math.min(props.index ?? 0, 16) * 35}ms`);
</script>

<template>
    <article
        class="animate-in fade-in-0 slide-in-from-left-4 fill-mode-both relative duration-500"
        :style="{ animationDelay }"
    >
        <span
            class="absolute top-6 -left-[1.625rem] z-10 size-3 rounded-full border-2 border-background bg-primary shadow-sm"
            aria-hidden="true"
        />

        <Link
            :href="`/events/${event.id}`"
            class="group flex gap-4 rounded-lg border bg-card p-3 shadow-sm transition-colors hover:border-primary/30 hover:bg-accent/30 sm:gap-5 sm:p-4"
        >
            <div class="relative size-20 shrink-0 overflow-hidden rounded-md bg-muted sm:size-24">
                <img
                    v-if="thumbnailUrl"
                    :src="thumbnailUrl"
                    :alt="title"
                    class="size-full object-cover transition-transform duration-300 group-hover:scale-105"
                />
                <div
                    v-else
                    class="flex size-full items-center justify-center text-xs text-muted-foreground"
                >
                    No image
                </div>
                <span
                    v-if="imageCount > 1"
                    class="absolute right-1 bottom-1 rounded bg-black/60 px-1.5 py-0.5 text-[10px] font-medium text-white"
                >
                    +{{ imageCount - 1 }}
                </span>
            </div>

            <div class="min-w-0 flex-1 space-y-1.5">
                <h3 class="line-clamp-1 font-semibold leading-snug group-hover:underline">
                    {{ title }}
                </h3>

                <p v-if="description" class="line-clamp-2 text-sm text-muted-foreground">
                    {{ description }}
                </p>

                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                    <time class="text-muted-foreground">{{ scheduleDisplay }}</time>
                    <span v-if="event.location_label" class="font-medium">{{ event.location_label }}</span>
                </div>
            </div>
        </Link>
    </article>
</template>
