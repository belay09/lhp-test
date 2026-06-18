<x-mail::message>
# See you soon, {{ $attendeeName }}!

This is a friendly reminder that **{{ $eventName }}** starts in **{{ $reminderLead }}**.

**When:** {{ $startsAt?->timezone(config('app.timezone'))->format('l, F j, Y \a\t g:i A T') ?? 'TBA' }}

@if ($locationLabel)
**Where:** {{ $locationLabel }}
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
