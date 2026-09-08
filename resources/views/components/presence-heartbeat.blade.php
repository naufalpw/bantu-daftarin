@auth
    <span
        data-presence-heartbeat
        data-presence-heartbeat-interval="{{ \App\Support\ChatPresence::HEARTBEAT_INTERVAL_SECONDS * 1000 }}"
        data-presence-heartbeat-token="{{ csrf_token() }}"
        data-presence-heartbeat-url="{{ route('presence.heartbeat') }}"
        hidden
    ></span>
@endauth
