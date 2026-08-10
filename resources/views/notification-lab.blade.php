<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Notification Lab</title>
    @vite(['resources/css/app.css', 'resources/js/notification-lab.js'])
</head>
<body class="min-h-screen bg-slate-950 font-sans text-slate-100">
<main class="mx-auto max-w-7xl p-6 lg:p-10">
    <div class="mb-8 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan-400">ShelfSpots · developer tool</p>
            <h1 class="mt-2 text-3xl font-bold">Notification Lab</h1>
            <p class="mt-2 max-w-2xl text-slate-400">Load persisted notifications, subscribe to the private channel, and inspect the exact realtime payload.</p>
        </div>
        <span id="connection-status" class="w-fit rounded-full bg-slate-800 px-3 py-1 text-sm text-slate-300">Disconnected</span>
    </div>

    <section class="rounded-2xl border border-slate-800 bg-slate-900 p-5 shadow-2xl shadow-black/20">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <label class="text-sm text-slate-300">API base URL
                <input id="api-base" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white" value="{{ url('/api/v1') }}">
            </label>
            <label class="text-sm text-slate-300">Portal
                <select id="portal" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white">
                    <option value="worker">worker</option>
                    <option value="company">company</option>
                    <option value="admin">admin</option>
                </select>
            </label>
            <label class="text-sm text-slate-300">Authenticated user ID
                <input id="user-id" type="number" min="1" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white" placeholder="e.g. 42">
            </label>
            <label class="text-sm text-slate-300">Bearer token
                <input id="token" type="password" autocomplete="off" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white" placeholder="Sanctum token">
            </label>
            <label class="text-sm text-slate-300">Platform API key
                <input id="api-key" type="password" autocomplete="off" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white" placeholder="X-Authorization">
            </label>
        </div>
        <details class="mt-4 rounded-lg bg-slate-950 p-3">
            <summary class="cursor-pointer text-sm text-slate-300">Reverb runtime settings</summary>
            <div class="mt-3 grid gap-3 md:grid-cols-4">
                <label class="text-sm text-slate-400">App key<input id="reverb-key" class="mt-1 w-full rounded border border-slate-700 bg-slate-900 px-2 py-1 text-white"></label>
                <label class="text-sm text-slate-400">Host<input id="reverb-host" class="mt-1 w-full rounded border border-slate-700 bg-slate-900 px-2 py-1 text-white"></label>
                <label class="text-sm text-slate-400">Port<input id="reverb-port" type="number" class="mt-1 w-full rounded border border-slate-700 bg-slate-900 px-2 py-1 text-white"></label>
                <label class="text-sm text-slate-400">Scheme<select id="reverb-scheme" class="mt-1 w-full rounded border border-slate-700 bg-slate-900 px-2 py-1 text-white"><option value="http">http</option><option value="https">https</option></select></label>
            </div>
        </details>
        <div class="mt-5 flex flex-wrap gap-3">
            <button id="load" class="rounded-lg bg-cyan-500 px-4 py-2 font-semibold text-slate-950 hover:bg-cyan-400">Load API data</button>
            <button id="connect" class="rounded-lg bg-emerald-500 px-4 py-2 font-semibold text-slate-950 hover:bg-emerald-400">Connect realtime</button>
            <button id="disconnect" class="rounded-lg border border-slate-600 px-4 py-2 font-semibold hover:bg-slate-800">Disconnect</button>
            <button id="read-all" class="rounded-lg border border-slate-600 px-4 py-2 font-semibold hover:bg-slate-800">Mark all read</button>
        </div>
        <p class="mt-3 text-xs text-slate-500">The Bearer token and platform API key are used only in this browser tab and are never stored by this page.</p>
    </section>

    @if (config('notification_lab.sending_enabled'))
        @include('notification-lab.sender')
    @else
        <section class="mt-6 rounded-2xl border border-amber-700/40 bg-amber-950/30 p-5 text-sm text-amber-200">
            Test sending is disabled. Set <code class="rounded bg-slate-950 px-2 py-1">NOTIFICATION_LAB_SEND_ENABLED=true</code> only in an isolated development or testing environment.
        </section>
    @endif

    <section class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
            <div class="flex items-center justify-between"><h2 class="font-bold">Persisted notifications</h2><span id="unread-count" class="rounded-full bg-cyan-500/20 px-3 py-1 text-sm text-cyan-300">Unread: —</span></div>
            <pre id="api-output" class="mt-4 max-h-[32rem] overflow-auto rounded-lg bg-slate-950 p-4 text-xs leading-6 text-slate-300">Click “Load API data”.</pre>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
            <div class="flex items-center justify-between"><h2 class="font-bold">Realtime payloads</h2><button id="clear-events" class="text-sm text-slate-400 hover:text-white">Clear</button></div>
            <pre id="realtime-output" class="mt-4 max-h-[32rem] overflow-auto rounded-lg bg-slate-950 p-4 text-xs leading-6 text-emerald-300">Waiting for a channel subscription.</pre>
        </div>
    </section>
</main>
</body>
</html>
