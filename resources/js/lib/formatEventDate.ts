/**
 * Event timestamps (`created_time`, `payload.schedule.starts_at` / `ends_at`) are
 * stored as Unix seconds in UTC. They are displayed in the user's browser
 * timezone via `Intl.DateTimeFormat` (no explicit `timeZone` = local).
 */

export interface FormatEventDateOptions {
    dateStyle?: Intl.DateTimeFormatOptions['dateStyle'];
    timeStyle?: Intl.DateTimeFormatOptions['timeStyle'];
    /** Show a relative hint (e.g. "in 3 days") for events within 7 days. */
    relative?: boolean;
}

const DEFAULT_FORMAT: Intl.DateTimeFormatOptions = {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
};

function unixToDate(unixSeconds: number): Date {
    return new Date(unixSeconds * 1000);
}

function resolveFormatOptions(options?: FormatEventDateOptions): Intl.DateTimeFormatOptions {
    if (options?.dateStyle || options?.timeStyle) {
        return {
            dateStyle: options.dateStyle ?? 'medium',
            timeStyle: options.timeStyle ?? 'short',
        };
    }

    return DEFAULT_FORMAT;
}

export function formatEventDate(unixSeconds: number, options?: FormatEventDateOptions): string {
    const formatted = new Intl.DateTimeFormat(undefined, resolveFormatOptions(options)).format(
        unixToDate(unixSeconds),
    );

    if (!options?.relative) {
        return formatted;
    }

    const hint = formatRelativeHint(unixSeconds);
    return hint ? `${formatted} (${hint})` : formatted;
}

export function formatEventDateRange(
    startUnix: number,
    endUnix?: number | null,
    options?: FormatEventDateOptions,
): string {
    if (endUnix == null || endUnix <= startUnix) {
        return formatEventDate(startUnix, options);
    }

    const startDate = unixToDate(startUnix);
    const endDate = unixToDate(endUnix);

    const dayKey = (date: Date) =>
        new Intl.DateTimeFormat(undefined, {
            year: 'numeric',
            month: 'numeric',
            day: 'numeric',
        }).format(date);

    if (dayKey(startDate) === dayKey(endDate)) {
        const datePart = new Intl.DateTimeFormat(undefined, {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        }).format(startDate);
        const timeFormat: Intl.DateTimeFormatOptions = { hour: 'numeric', minute: '2-digit' };
        const range = `${datePart}, ${new Intl.DateTimeFormat(undefined, timeFormat).format(startDate)} – ${new Intl.DateTimeFormat(undefined, timeFormat).format(endDate)}`;

        if (!options?.relative) {
            return range;
        }

        const hint = formatRelativeHint(startUnix);
        return hint ? `${range} (${hint})` : range;
    }

    return `${formatEventDate(startUnix, options)} – ${formatEventDate(endUnix, options)}`;
}

function formatRelativeHint(unixSeconds: number): string | null {
    const diffMs = unixSeconds * 1000 - Date.now();
    const diffDays = Math.round(diffMs / (1000 * 60 * 60 * 24));

    if (Math.abs(diffDays) > 7) {
        return null;
    }

    const rtf = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' });

    if (Math.abs(diffDays) >= 1) {
        return rtf.format(diffDays, 'day');
    }

    const diffHours = Math.round(diffMs / (1000 * 60 * 60));
    if (Math.abs(diffHours) >= 1) {
        return rtf.format(diffHours, 'hour');
    }

    const diffMinutes = Math.round(diffMs / (1000 * 60));
    return rtf.format(diffMinutes, 'minute');
}

export interface EventScheduleSource {
    created_time: number | null;
    payload?: Record<string, unknown> | null;
}

export function getEventSchedule(event: EventScheduleSource): {
    startsAt: number | null;
    endsAt: number | null;
} {
    const schedule = event.payload?.schedule;

    if (schedule && typeof schedule === 'object') {
        const { starts_at: startsAt, ends_at: endsAt } = schedule as {
            starts_at?: unknown;
            ends_at?: unknown;
        };

        if (typeof startsAt === 'number') {
            return {
                startsAt,
                endsAt: typeof endsAt === 'number' ? endsAt : null,
            };
        }
    }

    return { startsAt: event.created_time, endsAt: null };
}

export function formatEventSchedule(
    event: EventScheduleSource,
    options?: FormatEventDateOptions,
): string {
    const { startsAt, endsAt } = getEventSchedule(event);

    if (startsAt === null) {
        return '—';
    }

    return formatEventDateRange(startsAt, endsAt, options);
}
