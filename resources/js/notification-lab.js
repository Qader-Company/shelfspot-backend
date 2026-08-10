import { createEcho } from './echo';

const byId = (id) => document.getElementById(id);
const apiOutput = byId('api-output');
const realtimeOutput = byId('realtime-output');
const connectionStatus = byId('connection-status');
let echo;

const reverbDefaults = {
    key: import.meta.env.VITE_REVERB_APP_KEY ?? '',
    host: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
    port: import.meta.env.VITE_REVERB_PORT ?? '8080',
    scheme: import.meta.env.VITE_REVERB_SCHEME ?? 'http',
};

Object.entries(reverbDefaults).forEach(([key, value]) => {
    byId(`reverb-${key}`).value = value;
});

const credentials = () => ({
    apiBase: byId('api-base').value.replace(/\/$/, ''),
    portal: byId('portal').value,
    token: byId('token').value.trim(),
    apiKey: byId('api-key').value.trim(),
    userId: byId('user-id').value.trim(),
});

const setStatus = (text, color = 'slate') => {
    connectionStatus.textContent = text;
    connectionStatus.className = `w-fit rounded-full bg-${color}-500/20 px-3 py-1 text-sm text-${color}-300`;
};

const appendRealtime = (label, payload) => {
    const previous = realtimeOutput.textContent.startsWith('Waiting') ? '' : `${realtimeOutput.textContent}\n\n`;
    realtimeOutput.textContent = `${previous}${label}\n${JSON.stringify(payload, null, 2)}`;
    realtimeOutput.scrollTop = realtimeOutput.scrollHeight;
};

const apiRequest = async (path, options = {}) => {
    const { apiBase, token, apiKey } = credentials();

    if (!token || !apiKey) {
        throw new Error('Enter both the Sanctum Bearer token and Platform API key first.');
    }

    const response = await fetch(`${apiBase}${path}`, {
        ...options,
        headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
            'X-Authorization': apiKey,
            ...options.headers,
        },
    });
    const body = await response.json();

    if (!response.ok) {
        throw new Error(body.message ?? `API request failed with ${response.status}.`);
    }

    return body;
};

const loadApiData = async () => {
    try {
        const { portal } = credentials();
        const [notifications, unread] = await Promise.all([
            apiRequest(`/${portal}/notifications?per_page=20`),
            apiRequest(`/${portal}/notifications/unread-count`),
        ]);

        byId('unread-count').textContent = `Unread: ${unread.data.unread_count}`;
        apiOutput.textContent = JSON.stringify(notifications, null, 2);
    } catch (error) {
        apiOutput.textContent = error.message;
    }
};

const connect = () => {
    const { apiBase, token, userId } = credentials();

    if (!token || !userId) {
        appendRealtime('Configuration error', { message: 'Bearer token and authenticated user ID are required.' });

        return;
    }

    echo?.disconnect();
    const apiOrigin = apiBase.replace(/\/api\/v1$/, '');
    echo = createEcho({
        token,
        authEndpoint: `${apiOrigin}/broadcasting/auth`,
        reverb: {
            key: byId('reverb-key').value.trim(),
            host: byId('reverb-host').value.trim(),
            port: Number(byId('reverb-port').value),
            scheme: byId('reverb-scheme').value,
        },
    });

    setStatus('Connecting…', 'amber');
    echo.connector.pusher.connection.bind('connected', () => setStatus('Socket connected', 'emerald'));
    echo.connector.pusher.connection.bind('error', (error) => {
        setStatus('Socket error', 'rose');
        appendRealtime('Socket error', error);
    });
    const channelName = `App.Models.User.${userId}`;

    echo.private(channelName)
        .subscribed(() => {
            setStatus('Channel subscribed', 'emerald');
            appendRealtime('Channel subscribed', { channel: `private-${channelName}` });
        })
        .notification((notification) => appendRealtime('Notification received', notification))
        .error((error) => {
            setStatus('Channel authorization failed', 'rose');
            appendRealtime('Channel error', error);
        });
};

byId('load').addEventListener('click', loadApiData);
byId('connect').addEventListener('click', connect);
byId('disconnect').addEventListener('click', () => {
    echo?.disconnect();
    echo = undefined;
    setStatus('Disconnected');
});
byId('clear-events').addEventListener('click', () => {
    realtimeOutput.textContent = 'Waiting for a channel subscription.';
});
byId('read-all').addEventListener('click', async () => {
    try {
        const { portal } = credentials();
        await apiRequest(`/${portal}/notifications/read-all`, { method: 'PATCH' });
        await loadApiData();
    } catch (error) {
        apiOutput.textContent = error.message;
    }
});

const sender = byId('test-sender');

if (sender) {
    const sendButton = byId('send-test');
    const sendStatus = byId('send-test-status');
    const sendOutput = byId('send-test-output');

    sender.querySelectorAll('.target-enabled').forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            const input = sender.querySelector(`.target-user-id[data-portal="${checkbox.dataset.portal}"]`);
            input.disabled = !checkbox.checked;
            if (checkbox.checked) input.focus();
        });
    });

    sendButton.addEventListener('click', async () => {
        try {
            const targets = [...sender.querySelectorAll('.target-enabled:checked')].map((checkbox) => ({
                portal: checkbox.dataset.portal,
                user_id: Number(sender.querySelector(`.target-user-id[data-portal="${checkbox.dataset.portal}"]`).value),
            }));

            if (!targets.length || targets.some((target) => !target.user_id)) {
                throw new Error('Select at least one portal and enter a valid user ID for every selection.');
            }

            let meta;
            try {
                meta = JSON.parse(byId('test-meta').value || '{}');
            } catch {
                throw new Error('Extra meta must be a valid JSON object.');
            }
            if (!meta || Array.isArray(meta) || typeof meta !== 'object') {
                throw new Error('Extra meta must be a JSON object.');
            }

            sendButton.disabled = true;
            sendStatus.textContent = 'Queueing test notification…';
            const response = await fetch('/notification-lab/send', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    targets,
                    event: byId('test-preset').value,
                    category: byId('test-preset').value.startsWith('task.') ? 'task' : 'test',
                    priority: byId('test-priority').value,
                    title: byId('test-title').value,
                    message: byId('test-message').value,
                    meta,
                }),
            });
            const body = await response.json();

            if (!response.ok) {
                const validationMessage = Object.values(body.errors ?? {}).flat().join(' ');
                throw new Error(body.message || validationMessage || `Request failed with ${response.status}.`);
            }

            sendStatus.textContent = `Queued for ${body.data.queued} recipient(s).`;
            sendOutput.textContent = JSON.stringify(body, null, 2);
            sendOutput.classList.remove('hidden');
        } catch (error) {
            sendStatus.textContent = error.message;
            sendOutput.textContent = error.message;
            sendOutput.classList.remove('hidden');
        } finally {
            sendButton.disabled = false;
        }
    });
}
