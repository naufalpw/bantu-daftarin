@php($isAdmin = auth()->user()->isAdmin())
@php($isGeneralSupport = $thread->isGeneralSupport())
@php($isCurrentUser = fn ($message): bool => $message->sender_user_id === auth()->id())

<section wire:poll.5s="markRead" class="bd-chat-card {{ $isAdmin ? 'bd-chat-card--admin' : '' }}" aria-label="Percakapan">
    <header class="bd-chat-card__header">
        <div class="bd-chat-card__identity">
            <span class="pb-chat-initial" aria-hidden="true">{{ $isAdmin ? mb_strtoupper(mb_substr($thread->client?->name ?? 'K', 0, 1)) : 'BD' }}</span>
            <div>
                <strong>{{ $isAdmin ? ($thread->client?->name ?? 'Klien') : 'Tim Bantu Daftarin' }}</strong>
                <span>{{ $isGeneralSupport ? 'Bantuan umum' : ($thread->application?->service?->name ?? 'Layanan pengajuan') }}</span>
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
            <div class="bd-chat-empty"><strong>Belum ada pesan.</strong><span>{{ $isGeneralSupport ? 'Tulis pertanyaan umum Anda untuk memulai percakapan.' : 'Tulis pertanyaan tentang pengajuan ini untuk memulai percakapan.' }}</span></div>
        @endforelse

        @if($isOtherParticipantTyping)
            <p class="bd-chat-typing" role="status" aria-live="polite">Sedang mengetik...</p>
        @endif
    </div>

    <form wire:submit="send" class="bd-chat-composer">
        <textarea wire:model.live.debounce.400ms="body" rows="1" placeholder="Tulis pesan..." aria-label="Tulis pesan"></textarea>
        <button class="bd-chat-composer__send" type="submit" aria-label="Kirim pesan">
            <img wire:loading.remove wire:target="send" src="{{ asset('images/figma/phase3/chat/send.svg') }}" alt="">
            <span wire:loading wire:target="send" aria-live="polite">Mengirim</span>
        </button>
    </form>
    @error('body')
        <p class="bd-chat-error">{{ $message }}</p>
    @enderror
</section>
