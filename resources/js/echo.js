import Echo from 'laravel-echo';

import Pusher from 'pusher-js';

window.Pusher = Pusher;

export function createEcho({ token, authEndpoint, reverb = {} }) {
    return new Echo({
        broadcaster: 'reverb',
        key: reverb.key ?? import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: reverb.host ?? import.meta.env.VITE_REVERB_HOST,
        wsPort: reverb.port ?? import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: reverb.port ?? import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (reverb.scheme ?? import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint,
        auth: {
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
        },
    });
}
