<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Bantu Daftarin' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="@yield('body_class', 'bd-client-body')">
    @php
        $clientChatUrl = null;
        if (isset($thread) && $thread?->public_id) {
            $clientChatUrl = route('client.chat.show', $thread->public_id);
        } elseif (isset($application) && $application?->chatThread?->public_id) {
            $clientChatUrl = route('client.chat.show', $application->chatThread->public_id);
        }
    @endphp
    <x-client-header :chat-url="$clientChatUrl" />

    <div class="bd-client-feedback" aria-live="polite">
        @if(session('status'))
            <div class="bd-client-feedback__status" role="status">{{ session('status') }}</div>
        @endif

        @if(isset($errors) && $errors->any())
            <div class="bd-client-feedback__errors" role="alert">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <main class="bd-client-main">
        @yield('content')
    </main>

    @livewireScripts
</body>
</html>
