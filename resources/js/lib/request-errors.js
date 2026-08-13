import { toast } from 'vue-sonner';

const FALLBACK_MESSAGE = 'Terjadi kesalahan. Silakan coba lagi.';

export const firstErrorMessage = (errors, fallback = FALLBACK_MESSAGE) => {
    if (typeof errors === 'string') return errors || fallback;
    const messages = Object.values(errors ?? {}).flat().filter((message) => typeof message === 'string' && message);

    return messages[0] ?? fallback;
};

export const notifyRequestError = (errors, fallback = FALLBACK_MESSAGE) => {
    const message = firstErrorMessage(errors, fallback);
    console.error('Request gagal:', errors);
    toast.error(message);
};

export const notifyAxiosError = (error, fallback = FALLBACK_MESSAGE) => {
    const data = error?.response?.data;
    const message = data?.message || firstErrorMessage(data?.errors, fallback);
    console.error('Request gagal:', error);
    toast.error(message);

    return message;
};
