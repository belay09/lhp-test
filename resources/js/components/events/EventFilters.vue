<script setup lang="ts">
import { Button } from '@/components/ui/button';

export interface EventFilterValues {
    status: string;
    from: string;
    to: string;
    location: string;
}

const filters = defineModel<EventFilterValues>({ required: true });

defineProps<{
    statuses: string[];
}>();

const emit = defineEmits<{
    apply: [];
}>();
</script>

<template>
    <form class="flex flex-wrap items-end gap-3" @submit.prevent="emit('apply')">
        <div class="flex flex-col gap-1">
            <label class="text-xs text-muted-foreground" for="event-filter-status">Status</label>
            <select
                id="event-filter-status"
                v-model="filters.status"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
            >
                <option value="">All</option>
                <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
            </select>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs text-muted-foreground" for="event-filter-from">From</label>
            <input
                id="event-filter-from"
                v-model="filters.from"
                type="date"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
            />
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs text-muted-foreground" for="event-filter-to">To</label>
            <input
                id="event-filter-to"
                v-model="filters.to"
                type="date"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
            />
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs text-muted-foreground" for="event-filter-location">Location</label>
            <input
                id="event-filter-location"
                v-model="filters.location"
                type="search"
                placeholder="City or region"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
            />
        </div>
        <Button type="submit">Filter</Button>
    </form>
</template>
