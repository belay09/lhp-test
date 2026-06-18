<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    eventId: string;
    disabled?: boolean;
    disabledMessage?: string;
}>();

const form = useForm({
    name: '',
    email: '',
});

function submit(): void {
    if (props.disabled) {
        return;
    }

    form.post(`/events/${props.eventId}/attendees`, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <div class="space-y-3">
        <p class="text-sm font-medium">Attendee registration</p>

        <p v-if="disabled" class="text-xs leading-relaxed text-muted-foreground">
            {{ disabledMessage }}
        </p>

        <form v-else class="space-y-3" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="attendee-name">Name</Label>
                <Input
                    id="attendee-name"
                    v-model="form.name"
                    name="name"
                    autocomplete="name"
                    placeholder="Your name"
                    required
                    :disabled="form.processing"
                    :aria-invalid="!!form.errors.name"
                />
                <InputError :message="form.errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="attendee-email">Email</Label>
                <Input
                    id="attendee-email"
                    v-model="form.email"
                    type="email"
                    name="email"
                    autocomplete="email"
                    placeholder="you@example.com"
                    required
                    :disabled="form.processing"
                    :aria-invalid="!!form.errors.email"
                />
                <InputError :message="form.errors.email" />
            </div>

            <Button type="submit" class="w-full" :disabled="form.processing">
                {{ form.processing ? 'Registering…' : 'Register for this event' }}
            </Button>
        </form>
    </div>
</template>
