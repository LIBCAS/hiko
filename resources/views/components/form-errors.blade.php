@if ($errors->any())
    <x-error-alert>
        <div class="prose-sm prose text-white">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </x-error-alert>
@endif
