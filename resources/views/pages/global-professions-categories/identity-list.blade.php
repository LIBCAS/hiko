<div class="max-w-sm bg-white p-6 shadow rounded-md">
    <h2 class="text-l font-semibold">
        {{ __('hiko.attached_persons_count') }}: {{ $identityGroups->sum(fn ($group) => $group->identities->count()) }}
    </h2>

    @forelse ($identityGroups as $group)
        <details class="profession-identity-group mt-3 overflow-hidden border border-gray-200 rounded-md" @if ($group->is_current_tenant) open @endif>
            <summary class="cursor-pointer bg-gray-50 p-3 text-sm font-semibold hover:bg-gray-100">
                {{ $group->tenant_name }}: {{ $group->identities->count() }}
                <span class="block mt-1 text-xs font-normal text-gray-500">{{ $group->tenant_domain ?: $group->tenant_prefix }}</span>
            </summary>
            <ul class="list-none m-0 p-3 space-y-2 border-t border-gray-200">
                @foreach ($group->identities as $identity)
                    <li class="m-0 text-sm leading-5 break-words">
                        @if ($group->tenant_domain)
                            <a href="{{ 'https://' . $group->tenant_domain . '/identities/' . $identity->id . '/edit' }}"
                                class="text-sm border-b text-primary-dark border-primary-light hover:border-primary-dark">{{ $identity->name }}</a>
                        @else
                            <span class="text-sm">{{ $identity->name }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </details>
    @empty
        <p class="text-sm text-gray-500 mt-2">{{ __('hiko.no_attached_persons') }}</p>
    @endforelse
</div>
