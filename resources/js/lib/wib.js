export const WIB_TIMEZONE = 'Asia/Jakarta';

const toDate = (value) => {
    const date = value instanceof Date ? value : new Date(value);
    return Number.isNaN(date.getTime()) ? null : date;
};

/**
 * Calendar parts of a moment as seen in WIB, keyed by Intl part type.
 */
export const wibDateTimeParts = (value = new Date()) => {
    const date = toDate(value);
    if (!date) return null;

    const parts = new Intl.DateTimeFormat('en-CA', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
        timeZone: WIB_TIMEZONE,
    }).formatToParts(date);

    return Object.fromEntries(parts.map(({ type, value: part }) => [type, part]));
};

/**
 * WIB moment formatted for a `datetime-local` style input: `YYYY-MM-DDTHH:mm`.
 */
export const toWibDateTimeInput = (value = new Date()) => {
    const parts = wibDateTimeParts(value);
    if (!parts) return '';

    return `${parts.year}-${parts.month}-${parts.day}T${parts.hour}:${parts.minute}`;
};

/**
 * Long Indonesian date/time label in WIB, or null when the value is unusable.
 */
export const formatWibDateTime = (value) => {
    const date = toDate(value);
    if (!date) return null;

    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        timeZone: WIB_TIMEZONE,
    }).format(date).replace('.', ':');
};

/**
 * Elapsed time split into days/hours/minutes, or null when the range is invalid.
 */
export const durationBetween = (start, end = new Date()) => {
    const startDate = toDate(start);
    const endDate = toDate(end);
    if (!startDate || !endDate) return null;

    const totalMinutes = Math.floor((endDate.getTime() - startDate.getTime()) / 60_000);
    if (totalMinutes < 0) return null;

    return {
        totalMinutes,
        days: Math.floor(totalMinutes / (24 * 60)),
        hours: Math.floor((totalMinutes % (24 * 60)) / 60),
        minutes: totalMinutes % 60,
    };
};
