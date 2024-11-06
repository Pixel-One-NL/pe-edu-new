<x-filament::section>
    <x-slot name="heading">
        Mislukte exports:
    </x-slot>

    @if(empty($users))
        Geen
    @else
        <ul>
            @foreach($users as $user)
                @if($user->middle_name)
                    <li>{{ $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name }}</li>
                @else
                    <li>{{ $user->first_name . ' ' . $user->last_name }}</li>
                @endif
            @endforeach
        </ul>
    @endif
</x-filament::section>