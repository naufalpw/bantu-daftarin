<span class="bd-chat-notifier" wire:poll.12s="poll">
    @if($unreadThreadCount > 0)
        <span class="bd-chat-unread-badge" aria-label="{{ $unreadThreadCount }} percakapan belum dibaca">{{ $unreadThreadCount }}</span>
    @endif

    @if($toasts !== [])
        @teleport('body')
            <aside @class(['bd-chat-toast-stack', 'bd-chat-toast-stack--admin' => $isAdmin, 'bd-chat-toast-stack--client' => ! $isAdmin, 'is-chat-active' => $hasActiveChat]) aria-label="Pesan chat baru" aria-live="polite">
                @foreach($toasts as $toast)
                    <article class="bd-chat-toast" wire:key="chat-toast-{{ $toast['thread_id'] }}" role="status" x-data="{ timer: null, startTimer() { this.stopTimer(); this.timer = window.setTimeout(() => $wire.dismissToast('{{ $toast['thread_id'] }}'), 8000) }, stopTimer() { if (this.timer) window.clearTimeout(this.timer) } }" x-init="startTimer()" @mouseenter="stopTimer()" @mouseleave="startTimer()" @focusin="stopTimer()" @focusout="startTimer()">
                        <div class="bd-chat-toast__header">
                            <strong>{{ $toast['title'] }}</strong>
                            <button type="button" wire:click="dismissToast('{{ $toast['thread_id'] }}')" aria-label="Tutup notifikasi pesan baru">&times;</button>
                        </div>
                        <p class="bd-chat-toast__context">{{ $toast['context'] }}</p>
                        <p class="bd-chat-toast__summary">{{ $toast['count'] > 1 ? $toast['count'].' pesan baru' : 'Anda menerima pesan baru.' }}</p>
                        <p class="bd-chat-toast__preview">{{ $toast['preview'] }}</p>
                        <a href="{{ $toast['href'] }}">{{ $toast['action_label'] }}</a>
                    </article>
                @endforeach
            </aside>
        @endteleport
    @endif
</span>
