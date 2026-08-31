@php($isAdmin = auth()->user()->isAdmin())
@php($isCurrentUser = fn ($message): bool => $message->sender_user_id === auth()->id())

<section wire:poll.5s="markRead" class="bd-chat-card {{ $isAdmin ? 'bd-chat-card--admin' : '' }}" aria-label="Percakapan">
    <header class="bd-chat-card__header">
        <div class="bd-chat-card__identity">
            <img src="{{ asset('images/figma/phase3/chat/avatar.svg') }}" alt="">
            <div>
                <strong>{{ $isAdmin ? ($thread->client?->name ?? 'Customer') : 'Customer Service' }}</strong>
                @if($isAdmin)
                    <span>{{ $thread->application?->service?->name ?? 'Layanan aplikasi' }}</span>
                @endif
            </div>
        </div>
        @if($isAdmin)
            <span class="bd-chat-card__online">Online</span>
        @endif
    </header>

    <div class="bd-chat-messages" wire:key="messages-{{ $thread->public_id }}" aria-live="polite">
        @forelse($thread->messages as $message)
            @php($outgoing = $isCurrentUser($message))
            <div class="bd-chat-message {{ $outgoing ? 'bd-chat-message--outgoing' : 'bd-chat-message--incoming' }}">
                <div class="bd-chat-bubble">
                    <p>{{ $message->body }}</p>
                    <span class="bd-chat-message__meta">
                        {{ $message->created_at->format('H:i') }}
                        @if($outgoing && $message->read_at)
                            <img src="{{ asset('images/figma/phase3/chat/check-read.svg') }}" alt="Dibaca">
                        @endif
                    </span>
                </div>
            </div>
        @empty
            <p class="bd-chat-empty">Belum ada pesan.</p>
        @endforelse

        @if($isOtherParticipantTyping)
            <p class="bd-chat-typing" role="status" aria-live="polite">Sedang mengetik...</p>
        @endif
    </div>

    <form wire:submit="send" class="bd-chat-composer">
        <button class="bd-chat-composer__plus" type="button" disabled aria-label="Lampiran belum tersedia">
            <img src="{{ asset('images/figma/phase3/chat/plus.svg') }}" alt="">
        </button>
        <textarea wire:model.live.debounce.400ms="body" rows="1" placeholder="Tulis pesan..." aria-label="Tulis pesan"></textarea>
        <button class="bd-chat-composer__send" type="submit" aria-label="Kirim pesan">
            <img src="{{ asset('images/figma/phase3/chat/send.svg') }}" alt="">
        </button>
    </form>
    @error('body')
        <p class="bd-chat-error">{{ $message }}</p>
    @enderror
</section>
