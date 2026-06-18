<x-mail::message>
# You're registered, {{ $attendeeName }}!

Thanks for registering for **{{ $eventName }}**.

**When:** {{ $startsAt?->timezone(config('app.timezone'))->format('l, F j, Y \a\t g:i A T') ?? 'TBA' }}

@if ($locationLabel)
**Where:** {{ $locationLabel }}
@endif

We look forward to seeing you there.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
