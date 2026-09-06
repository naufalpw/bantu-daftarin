@php($isAdmin = auth()->user()->isAdmin())
@php($isGeneralSupport = $thread->isGeneralSupport())
@php($isCurrentUser = fn ($message): bool => $message->sender_user_id === auth()->id())
@php($clientName = $thread->client?->name ?? 'Klien')

<section wire:poll.5s="markRead" class="bd-chat-card {{ $isAdmin ? 'bd-chat-card--admin' : '' }}" aria-label="Percakapan">
    <header class="bd-chat-card__header">
        <div class="bd-chat-card__identity">
            <span class="pb-chat-initial" aria-hidden="true">{{ $isAdmin ? mb_strtoupper(mb_substr($clientName, 0, 1)) : 'BD' }}</span>
            <div>
                <strong>{{ $isAdmin ? $clientName : 'Tim Bantu Daftarin' }}</strong>
                <span>{{ $isAdmin ? ($isGeneralSupport ? 'Bantuan Umum' : 'Percakapan dengan klien') : ($isGeneralSupport ? 'Bantuan umum' : ($thread->application?->service?->name ?? 'Layanan pengajuan')) }}</span>
            </div>
        </div>
    </header>

    <div @class(['bd-chat-messages', 'bd-chat-messages--empty' => $messageGroups->isEmpty()]) wire:key="messages-{{ $thread->public_id }}">
        @forelse($messageGroups as $group)
            <div class="bd-chat-date-separator" role="separator" aria-label="{{ $group['label'] }}"><span>{{ $group['label'] }}</span></div>
            @foreach($group['messages'] as $message)
                @php($outgoing = $isCurrentUser($message))
                <div class="bd-chat-message {{ $outgoing ? 'bd-chat-message--outgoing' : 'bd-chat-message--incoming' }}">
                    <div class="bd-chat-bubble">
                        <p>{{ $message->body }}</p>
                        <span class="bd-chat-message__meta">
                            <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('H:i') }}</time>
                            @if($outgoing && $message->read_at)
                                @if($isAdmin)
                                    <span class="bd-chat-message__read-state">Dibaca</span>
                                @else
                                    <img src="{{ asset('images/figma/phase3/chat/check-read.svg') }}" alt="Dibaca">
                                @endif
                            @endif
                        </span>
                    </div>
                </div>
            @endforeach
        @empty
            <div class="bd-chat-empty">
                <strong>Belum ada percakapan</strong>
                <span>
                    @if($isAdmin)
                        {{ $isGeneralSupport ? 'Mulai percakapan dengan klien jika diperlukan.' : 'Percakapan ini terkait dengan pengajuan berikut.' }}
                    @elseif($isGeneralSupport)
                        Mulai percakapan jika Anda membutuhkan bantuan yang tidak terkait dengan satu pengajuan.
                    @else
                        Gunakan chat ini untuk pertanyaan terkait pengajuan NPWP Anda.
                    @endif
                </span>
            </div>
        @endforelse
    </div>

    @if($isOtherParticipantTyping)
        <p class="bd-chat-typing" role="status">{{ $isAdmin ? $clientName.' sedang mengetik...' : 'Admin sedang mengetik...' }}</p>
    @endif

    @if($isAdmin && $quickReplies !== [])
        <div class="bd-chat-quick-reply">
            <label for="chat-quick-reply-{{ $thread->public_id }}">Balasan cepat</label>
            <select id="chat-quick-reply-{{ $thread->public_id }}" wire:model.live="quickReply">
                <option value="">Pilih balasan untuk mengisi pesan</option>
                @foreach($quickReplies as $key => $template)
                    <option value="{{ $key }}">{{ $quickReplyLabels[$key] }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <form wire:submit="send" class="bd-chat-composer">
        <label class="sr-only" for="chat-message-{{ $thread->public_id }}">Tulis pesan</label>
        <textarea id="chat-message-{{ $thread->public_id }}" wire:model.live.debounce.400ms="body" rows="1" placeholder="Tulis pesan..." aria-label="Tulis pesan"></textarea>
        <button class="bd-chat-composer__send" type="submit" aria-label="Kirim pesan">
            @if($isAdmin)
                <span wire:loading.remove wire:target="send">Kirim</span>
            @else
                <img wire:loading.remove wire:target="send" src="{{ asset('images/figma/phase3/chat/send.svg') }}" alt="">
            @endif
            <span wire:loading wire:target="send" aria-live="polite">Mengirim</span>
        </button>
    </form>
    @error('body')
        <p class="bd-chat-error">{{ $message }}</p>
    @enderror
</section>
