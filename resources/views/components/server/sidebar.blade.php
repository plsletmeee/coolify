<div>
    @if ($server->isFunctional())
        <div class="flex h-full pr-4">
            <div class="flex flex-col gap-4 min-w-fit">
                <a class="{{ request()->routeIs('server.proxy') ? 'text-white' : '' }}"
                    href="{{ route('server.proxy', $parameters) }}">
                    <button>Configuration</button>
                </a>
                @if (data_get($server, 'proxy.type') !== 'NONE')
                    <a class="{{ request()->routeIs('server.proxy.logs') ? 'text-white' : '' }}"
                        href="{{ route('server.proxy.logs', $parameters) }}">
                        <button>Logs</button>
                    </a>
                @endif
            </div>
        </div>
    @else
        <div>Server is not validated. Validate first.</div>
    @endif
</div>