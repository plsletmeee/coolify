<div>
    @if ($limit_reached)
        <x-limit-reached name="servers" />
    @else
        <h1>Create a new Server</h1>
        <div class="subtitle">Servers are the main blocks of your infrastructure.</div>
        <form class="flex flex-col gap-2" wire:submit='submit'>
            <div class="flex gap-2">
                <x-forms.input id="name" label="Name" required />
                <x-forms.input id="description" label="Description" />
            </div>
            <div class="flex gap-2">
                <x-forms.input id="ip" label="IP Address/Domain" required
                    helper="An IP Address (127.0.0.1) or domain (example.com)." />
                <x-forms.input id="user" label  ="User" required />
                <x-forms.input type="number" id="port" label="Port" required />
            </div>
            <x-forms.select label="Private Key" id="private_key_id">
                <option disabled>Select a private key</option>
                @foreach ($private_keys as $key)
                    @if ($loop->first)
                        <option selected value="{{ $key->id }}">{{ $key->name }}</option>
                    @else
                        <option value="{{ $key->id }}">{{ $key->name }}</option>
                    @endif
                @endforeach
            </x-forms.select>
            <div class="w-96">
                <x-forms.checkbox instantSave type="checkbox" id="is_build_server" label="Use it as a build server?" />
            </div>
            <div class="w-96">
                <h3 class="pt-6">Swarm Support</h3>
                <div> Swarm support is experimental. Read the docs <a class='text-white'
                        href='https://coolify.io/docs/docker/swarm#deploy-with-persistent-storage'
                        target='_blank'>here</a>.</div>
                @if ($is_swarm_worker || $is_build_server)
                    <x-forms.checkbox disabled instantSave type="checkbox" id="is_swarm_manager"
                        helper="For more information, please read the documentation <a class='text-white' href='https://coolify.io/docs/docker/swarm' target='_blank'>here</a>."
                        label="Is it a Swarm Manager?" />
                @else
                    <x-forms.checkbox type="checkbox" instantSave id="is_swarm_manager"
                        helper="For more information, please read the documentation <a class='text-white' href='https://coolify.io/docs/docker/swarm' target='_blank'>here</a>."
                        label="Is it a Swarm Manager?" />
                @endif
                @if ($is_swarm_manager|| $is_build_server)
                    <x-forms.checkbox disabled instantSave type="checkbox" id="is_swarm_worker"
                        helper="For more information, please read the documentation <a class='text-white' href='https://coolify.io/docs/docker/swarm' target='_blank'>here</a>."
                        label="Is it a Swarm Worker?" />
                @else
                    <x-forms.checkbox type="checkbox" instantSave id="is_swarm_worker"
                        helper="For more information, please read the documentation <a class='text-white' href='https://coolify.io/docs/docker/swarm' target='_blank'>here</a>."
                        label="Is it a Swarm Worker?" />
                @endif
                @if ($is_swarm_worker && count($swarm_managers) > 0)
                    <div class="py-4">
                        <x-forms.select label="Select a Swarm Cluster" id="selected_swarm_cluster" required>
                            @foreach ($swarm_managers as $server)
                                @if ($loop->first)
                                    <option selected value="{{ $server->id }}">{{ $server->name }}</option>
                                @else
                                    <option value="{{ $server->id }}">{{ $server->name }}</option>
                                @endif
                            @endforeach
                        </x-forms.select>
                    </div>
                @endif
            </div>
            <x-forms.button type="submit">
                Continue
            </x-forms.button>
        </form>
    @endif
</div>
