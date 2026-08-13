<section id="test-sender" class="mt-6 rounded-2xl border border-fuchsia-700/40 bg-slate-900 p-5">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-fuchsia-400">Opt-in test tool</p>
            <h2 class="mt-1 text-xl font-bold">Send test notification</h2>
        </div>
        <span class="w-fit rounded-full bg-fuchsia-500/15 px-3 py-1 text-xs text-fuchsia-300">Max 1 user per portal</span>
    </div>

    <div class="mt-5 grid gap-4 lg:grid-cols-3">
        @foreach (['admin', 'company', 'worker'] as $targetPortal)
            <label class="flex items-center gap-3 rounded-xl border border-slate-700 bg-slate-950 p-3 text-sm">
                <input class="target-enabled h-4 w-4 accent-fuchsia-500" type="checkbox" data-portal="{{ $targetPortal }}">
                <span class="w-20 font-semibold capitalize">{{ $targetPortal }}</span>
                <input class="target-user-id min-w-0 flex-1 rounded border border-slate-700 bg-slate-900 px-2 py-1 text-white" data-portal="{{ $targetPortal }}" type="number" min="1" placeholder="User ID" disabled>
            </label>
        @endforeach
    </div>

    <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <label class="text-sm text-slate-300 md:col-span-2 lg:col-span-4">Event type
            <select id="test-preset" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white">
                @foreach (config('notification_lab.events') as $event => $definition)
                    <option value="{{ $event }}">{{ $definition['label'] }}</option>
                @endforeach
            </select>
        </label>
        <p class="md:col-span-2 lg:col-span-4 rounded-lg border border-slate-700 bg-slate-950 p-3 text-sm text-slate-400">
            The title, description, priority, action, and task fixture data are fixed by the selected event, so the test payload matches a real notification type.
        </p>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-3">
        <button id="send-test" class="rounded-lg bg-fuchsia-500 px-4 py-2 font-semibold text-white hover:bg-fuchsia-400">Send test notification</button>
        <span id="send-test-status" class="text-sm text-slate-400">Select at least one portal and enter its user ID.</span>
    </div>
    <pre id="send-test-output" class="mt-4 hidden max-h-64 overflow-auto rounded-lg bg-slate-950 p-4 text-xs leading-6 text-fuchsia-200"></pre>
</section>
