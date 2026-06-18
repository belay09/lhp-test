<script setup lang="ts">
import {
    Building2,
    CalendarDays,
    ChevronLeft,
    Layers,
    MapPin,
    Ticket,
    UserRound,
} from '@lucide/vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import EventImageGallery, { type EventImageItem } from '@/components/events/EventImageGallery.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { formatEventSchedule, type EventScheduleSource } from '@/lib/formatEventDate';
import { cn } from '@/lib/utils';

interface EventDetail extends EventScheduleSource {
    id: string;
    type: string;
    status: string;
    latitude: number | null;
    longitude: number | null;
    location_label: string | null;
    payload: Record<string, unknown>;
    images?: EventImageItem[];
}

const props = defineProps<{ event: EventDetail }>();

const prettyPayload = computed(() => JSON.stringify(props.event.payload, null, 2));

const eventName = computed(() => {
    const name = props.event.payload.name;
    return typeof name === 'string' ? name : props.event.type;
});

const description = computed(() => {
    const text = props.event.payload.description;
    return typeof text === 'string' ? text : '';
});

const category = computed(() => {
    const cat = props.event.payload.category;
    return typeof cat === 'string' ? cat : props.event.type;
});

const scheduleDisplay = computed(() => formatEventSchedule(props.event, { relative: true }));

const statusLabel = computed(() => props.event.status.replace('_', ' '));

const statusBadgeClass = computed(() => {
    switch (props.event.status) {
        case 'published':
            return 'border-emerald-500/30 bg-emerald-500/15 text-emerald-700 dark:text-emerald-300';
        case 'sold_out':
            return 'border-amber-500/30 bg-amber-500/15 text-amber-700 dark:text-amber-300';
        case 'cancelled':
            return 'border-destructive/30 bg-destructive/15 text-destructive';
        default:
            return 'border-border bg-muted/50 text-muted-foreground';
    }
});

const organizerName = computed(() => {
    const organizer = props.event.payload.organizer;
    if (organizer && typeof organizer === 'object') {
        const name = (organizer as { name?: unknown }).name;
        if (typeof name === 'string') {
            return name;
        }
    }
    return null;
});

const venueDisplay = computed(() => {
    const venue = props.event.payload.venue;
    if (!venue || typeof venue !== 'object') {
        return null;
    }

    const { name, capacity } = venue as { name?: unknown; capacity?: unknown };
    const parts: string[] = [];

    if (typeof name === 'string') {
        parts.push(name);
    }

    const cap = Number(capacity);
    if (!Number.isNaN(cap) && capacity !== '' && capacity != null) {
        parts.push(`Capacity ${cap.toLocaleString()}`);
    }

    return parts.length > 0 ? parts.join(' · ') : null;
});

const pricingDisplay = computed(() => {
    const pricing = props.event.payload.pricing;
    if (!pricing || typeof pricing !== 'object') {
        return null;
    }

    const { currency, min_price: minPrice } = pricing as {
        currency?: unknown;
        min_price?: unknown;
    };

    const price = Number(minPrice);
    if (Number.isNaN(price)) {
        return null;
    }

    const curr = typeof currency === 'string' ? currency : 'USD';

    if (price === 0) {
        return 'Free';
    }

    return `From ${new Intl.NumberFormat(undefined, { style: 'currency', currency: curr }).format(price)}`;
});

const tags = computed(() => {
    const raw = props.event.payload.tags;
    if (!Array.isArray(raw)) {
        return [];
    }

    return raw.filter((tag): tag is string => typeof tag === 'string');
});

interface SidebarItem {
    icon: typeof CalendarDays;
    label: string;
    value: string;
}

const sidebarItems = computed((): SidebarItem[] => {
    const items: SidebarItem[] = [
        { icon: CalendarDays, label: 'When', value: scheduleDisplay.value },
    ];

    if (props.event.location_label) {
        items.push({ icon: MapPin, label: 'Location', value: props.event.location_label });
    }

    if (venueDisplay.value) {
        items.push({ icon: Building2, label: 'Venue', value: venueDisplay.value });
    }

    if (organizerName.value) {
        items.push({ icon: UserRound, label: 'Organizer', value: organizerName.value });
    }

    if (pricingDisplay.value) {
        items.push({ icon: Ticket, label: 'Pricing', value: pricingDisplay.value });
    }

    items.push({
        icon: Layers,
        label: 'Type',
        value: props.event.type.replace('_', ' '),
    });

    return items;
});

const canRegister = computed(
    () => props.event.status === 'published' && pricingDisplay.value !== null,
);
</script>

<template>
    <Head :title="eventName" />

    <div
        class="animate-in fade-in-0 slide-in-from-bottom-2 fill-mode-both pb-10 duration-700"
    >
        <div class="flex w-full justify-start px-4 pt-4 md:px-6 md:pt-6">
            <Link
                href="/events"
                class="group -ml-2 inline-flex items-center gap-1 rounded-md px-2.5 py-1.5 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted/60 hover:text-foreground dark:hover:bg-white/5"
            >
                <ChevronLeft
                    class="size-4 shrink-0 transition-transform group-hover:-translate-x-0.5"
                    aria-hidden="true"
                />
                Back to events
            </Link>
        </div>

        <section class="relative mt-4 overflow-hidden">
            <EventImageGallery
                :images="event.images ?? []"
                :alt="eventName"
                variant="hero"
                class="w-full"
            />

            <div
                class="pointer-events-none absolute inset-0 z-10 bg-gradient-to-t from-background via-background/70 to-background/10"
                aria-hidden="true"
            />

            <div class="pointer-events-none absolute inset-x-0 bottom-0 px-4 pb-6 md:px-6 md:pb-10">
                <div class="mx-auto max-w-6xl space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge
                            variant="outline"
                            class="pointer-events-auto border-white/20 bg-black/30 capitalize text-white backdrop-blur-sm"
                        >
                            {{ category }}
                        </Badge>
                        <Badge
                            :class="cn('pointer-events-auto capitalize backdrop-blur-sm', statusBadgeClass)"
                        >
                            {{ statusLabel }}
                        </Badge>
                    </div>
                    <h1
                        class="max-w-4xl text-3xl font-semibold tracking-tight text-foreground drop-shadow-sm md:text-4xl lg:text-5xl"
                    >
                        {{ eventName }}
                    </h1>
                </div>
            </div>
        </section>

        <div class="mx-auto mt-8 grid max-w-6xl gap-8 px-4 md:px-6 lg:grid-cols-3 lg:gap-10">
            <div class="space-y-8 lg:col-span-2">
                <section v-if="description" class="space-y-4">
                    <h2 class="text-lg font-semibold tracking-tight">About this event</h2>
                    <p class="text-base leading-relaxed text-muted-foreground whitespace-pre-line">
                        {{ description }}
                    </p>
                </section>

                <section v-if="tags.length > 0" class="space-y-4">
                    <h2 class="text-lg font-semibold tracking-tight">Tags</h2>
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="tag in tags"
                            :key="tag"
                            class="inline-flex items-center rounded-full border border-border/60 bg-muted/40 px-3 py-1 text-xs font-medium capitalize text-foreground/90 transition-colors hover:bg-muted/70"
                        >
                            {{ tag }}
                        </span>
                    </div>
                </section>

                <section
                    v-if="!description && tags.length === 0"
                    class="rounded-xl border border-dashed border-border/60 py-12 text-center text-sm text-muted-foreground"
                >
                    No additional details for this event.
                </section>
            </div>

            <aside class="lg:col-span-1">
                <Card class="sticky top-6 overflow-hidden border-border/60 shadow-md">
                    <CardHeader class="pb-4">
                        <CardTitle class="text-base font-semibold">Event details</CardTitle>
                    </CardHeader>

                    <CardContent class="space-y-4">
                        <div
                            v-for="item in sidebarItems"
                            :key="item.label"
                            class="flex gap-3"
                        >
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                            >
                                <component :is="item.icon" class="size-4" aria-hidden="true" />
                            </div>
                            <div class="min-w-0 flex-1 pt-0.5">
                                <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                    {{ item.label }}
                                </p>
                                <p class="mt-0.5 text-sm font-medium capitalize leading-snug">
                                    {{ item.value }}
                                </p>
                            </div>
                        </div>

                        <Separator class="my-2" />

                        <div class="space-y-3 rounded-lg border border-dashed border-border/70 bg-muted/20 p-4">
                            <p class="text-sm font-medium">Attendee registration</p>
                            <p class="text-xs leading-relaxed text-muted-foreground">
                                Registration will be available here in a future update.
                            </p>
                            <button
                                type="button"
                                disabled
                                class="w-full rounded-md bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground opacity-50"
                                :class="{ 'cursor-not-allowed': !canRegister }"
                            >
                                {{
                                    event.status === 'sold_out'
                                        ? 'Sold out'
                                        : event.status === 'cancelled'
                                          ? 'Event cancelled'
                                          : 'Register — coming soon'
                                }}
                            </button>
                        </div>
                    </CardContent>
                </Card>
            </aside>
        </div>

        <div class="mx-auto mt-10 max-w-6xl px-4 md:px-6">
            <details class="rounded-lg border border-border/60 bg-card/50">
                <summary
                    class="cursor-pointer px-4 py-3 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                >
                    Raw payload (dev)
                </summary>
                <pre
                    class="overflow-x-auto border-t border-border/60 p-4 text-xs leading-relaxed text-muted-foreground"
                >{{ prettyPayload }}</pre>
            </details>
        </div>
    </div>
</template>
