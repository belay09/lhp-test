<script setup lang="ts">
import { ChevronLeft, ChevronRight, ImageIcon } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { cn } from '@/lib/utils';

export interface EventImageItem {
    url: string;
}

const props = withDefaults(
    defineProps<{
        images: EventImageItem[];
        alt: string;
        variant?: 'card' | 'hero';
    }>(),
    {
        variant: 'card',
    },
);

const currentIndex = ref(0);
const failedUrls = ref(new Set<string>());

const hasMultiple = computed(() => props.images.length > 1);

const currentImage = computed(() => {
    const url = props.images[currentIndex.value]?.url ?? null;
    if (url && failedUrls.value.has(url)) {
        return null;
    }
    return url;
});

const aspectClass = computed(() =>
    props.variant === 'hero'
        ? 'aspect-[21/9] min-h-[220px] max-h-[520px] sm:min-h-[280px]'
        : 'aspect-[16/10]',
);

const navButtonClass = computed(() => {
    if (props.variant === 'hero') {
        return 'absolute top-1/2 z-40 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white text-black shadow-xl ring-2 ring-white/50 transition-colors hover:bg-white/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-black/30 sm:h-12 sm:w-12';
    }

    return cn(
        'absolute top-1/2 z-10 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full border border-white/25 bg-black/70 text-white shadow-lg shadow-black/40 backdrop-blur-sm transition-all hover:bg-black/85 focus-visible:ring-2 focus-visible:ring-white/60 focus-visible:outline-none opacity-90 group-hover/gallery:opacity-100',
    );
});

const navPositionClass = computed(() =>
    props.variant === 'hero' ? 'left-4' : 'left-3 sm:left-4',
);

const navNextPositionClass = computed(() =>
    props.variant === 'hero' ? 'right-4' : 'right-3 sm:right-4',
);

const navIconClass = computed(() => (props.variant === 'hero' ? 'size-6' : 'size-4'));

const counterClass = computed(() => {
    if (props.variant === 'hero') {
        return 'absolute top-3 right-3 z-40 rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-black shadow-xl ring-2 ring-white/50';
    }

    return cn(
        'absolute top-3 right-3 z-10 rounded-full border border-white/25 bg-black/70 px-2.5 py-1 text-xs font-medium text-white shadow-md shadow-black/30 backdrop-blur-sm opacity-90 group-hover/gallery:opacity-100',
    );
});

const dotsContainerClass = computed(() =>
    cn(
        'absolute inset-x-0 bottom-3 flex justify-center gap-2',
        props.variant === 'hero' ? 'z-40' : 'z-10',
    ),
);

function dotClass(index: number) {
    const isActive = index === currentIndex.value;
    if (props.variant === 'hero') {
        return isActive
            ? 'h-2.5 w-6 rounded-full bg-white shadow-md'
            : 'size-2.5 rounded-full bg-white/40 hover:bg-white/60';
    }
    return isActive
        ? 'size-2 rounded-full bg-white shadow-sm ring-1 ring-white/30'
        : 'size-2 rounded-full bg-white/55 ring-1 ring-black/15 hover:bg-white/80';
}

watch(
    () => props.images,
    () => {
        currentIndex.value = 0;
        failedUrls.value = new Set();
    },
);

function showPrevious() {
    if (props.images.length === 0) {
        return;
    }
    currentIndex.value = (currentIndex.value - 1 + props.images.length) % props.images.length;
}

function showNext() {
    if (props.images.length === 0) {
        return;
    }
    currentIndex.value = (currentIndex.value + 1) % props.images.length;
}

function goTo(index: number) {
    currentIndex.value = index;
}

function onImageError(url: string) {
    failedUrls.value = new Set([...failedUrls.value, url]);
}
</script>

<template>
    <div
        class="group/gallery relative overflow-hidden"
        :class="cn(aspectClass, !currentImage && 'bg-muted/40')"
    >
        <img
            v-if="currentImage"
            :key="currentImage"
            :src="currentImage"
            :alt="alt"
            class="size-full object-cover transition-transform duration-500 group-hover/gallery:scale-[1.02]"
            @error="onImageError(currentImage)"
        />
        <div
            v-else
            class="relative flex size-full flex-col items-center justify-center gap-2"
            :style="{
                backgroundImage:
                    'radial-gradient(circle at 1px 1px, color-mix(in oklab, var(--muted-foreground) 12%, transparent) 1px, transparent 0)',
                backgroundSize: '20px 20px',
            }"
        >
            <ImageIcon class="size-8 text-muted-foreground/40" aria-hidden="true" />
            <span class="text-xs font-medium text-muted-foreground/70">
                {{ images.length > 0 ? 'Image unavailable' : 'No images' }}
            </span>
        </div>

        <template v-if="hasMultiple">
            <button
                type="button"
                :class="cn(navButtonClass, navPositionClass)"
                aria-label="Previous image"
                @click.prevent="showPrevious"
            >
                <ChevronLeft :class="navIconClass" />
            </button>
            <button
                type="button"
                :class="cn(navButtonClass, navNextPositionClass)"
                aria-label="Next image"
                @click.prevent="showNext"
            >
                <ChevronRight :class="navIconClass" />
            </button>

            <span :class="counterClass">
                {{ currentIndex + 1 }} / {{ images.length }}
            </span>

            <div :class="dotsContainerClass">
                <button
                    v-for="(_, index) in images"
                    :key="index"
                    type="button"
                    class="rounded-full transition-all"
                    :class="dotClass(index)"
                    :aria-label="`Show image ${index + 1}`"
                    @click.prevent="goTo(index)"
                />
            </div>
        </template>
    </div>
</template>
