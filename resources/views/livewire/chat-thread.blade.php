@php($isAdmin = auth()->user()->isAdmin())
@php($isGeneralSupport = $thread->isGeneralSupport())
@php($isCurrentUser = fn ($message): bool => $message->sender_user_id === auth()->id())
@php($clientName = $thread->client?->name ?? 'Klien')
@php($presenceLabel = $counterpartPresence['label'] ?? 'Sedang tidak aktif')
@php($isCounterpartOnline = (bool) ($counterpartPresence['is_online'] ?? false))

<section wire:poll.5s="markRead" class="bd-chat-card {{ $isAdmin ? 'bd-chat-card--admin' : '' }}" aria-label="Percakapan">
    <header class="bd-chat-card__header">
        <div class="bd-chat-card__identity">
            <span class="pb-chat-initial" aria-hidden="true">{{ $isAdmin ? mb_strtoupper(mb_substr($clientName, 0, 1)) : 'BD' }}</span>
            <div>
                <strong>{{ $isAdmin ? $clientName : 'Tim Bantu Daftarin' }}</strong>
                <div class="bd-chat-card__status-line">
                    <span @class(['bd-chat-presence', 'is-online' => $isCounterpartOnline]) aria-label="Status kehadiran: {{ $presenceLabel }}">
                        @if($isCounterpartOnline)<i class="bd-chat-presence__dot" aria-hidden="true"></i>@endif
                        {{ $presenceLabel }}
                    </span>
                    <span class="bd-chat-card__context">
                        {{ $isAdmin ? ($isGeneralSupport ? 'Bantuan Umum' : 'Percakapan dengan klien') : ($isGeneralSupport ? 'Bantuan umum' : ($thread->application?->service?->name ?? 'Layanan pengajuan')) }}
                        @if(! $isAdmin && ! $isGeneralSupport && $thread->application)
                            <span class="bd-chat-card__phone-reference"> · ID …{{ strtoupper(substr($thread->application->public_id, -4)) }}</span>
                        @endif
                    </span>
                </div>
            </div>
        </div>
        <div class="bd-chat-card__thread-actions">
            @if($isArchived)
                <button class="bd-chat-thread-action" type="button" wire:click="unarchiveConversation" aria-label="Keluarkan percakapan dari arsip">
                    @unless($isAdmin)<span class="bd-chat-thread-action__icon" aria-hidden="true"><x-ui-icon name="archive-restore" :size="18" /></span>@endunless
                    <span class="bd-chat-thread-action__label">Keluarkan dari arsip</span>
                </button>
            @else
                <button class="bd-chat-thread-action" type="button" wire:click="archiveConversation" aria-label="Arsipkan percakapan">
                    @unless($isAdmin)<span class="bd-chat-thread-action__icon" aria-hidden="true"><x-ui-icon name="archive" :size="18" /></span>@endunless
                    <span class="bd-chat-thread-action__label">Arsipkan</span>
                </button>
            @endif
        </div>
    </header>
    @error('archive')<p class="bd-chat-error bd-chat-error--thread">{{ $message }}</p>@enderror

    <div @class(['bd-chat-messages', 'bd-chat-messages--empty' => $messageGroups->isEmpty()]) wire:key="messages-{{ $thread->public_id }}" data-chat-messages>
        @forelse($messageGroups as $group)
            <div class="bd-chat-date-separator" role="separator" aria-label="{{ $group['label'] }}"><span>{{ $group['label'] }}</span></div>
            @foreach($group['messages'] as $message)
                @php($outgoing = $isCurrentUser($message))
                <div class="bd-chat-message {{ $outgoing ? 'bd-chat-message--outgoing' : 'bd-chat-message--incoming' }}">
                    <div @class(['bd-chat-bubble', 'bd-chat-bubble--editing' => $editingMessageId === $message->id])>
                        @if($message->deleted_at)
                            <p class="bd-chat-message__tombstone">Pesan ini telah dihapus</p>
                        @elseif($editingMessageId === $message->id)
                            <div class="bd-chat-message__editor">
                                <span class="bd-chat-message__editor-label">Edit pesan</span>
                                <label class="sr-only" for="chat-edit-{{ $message->id }}">Edit pesan</label>
                                <textarea id="chat-edit-{{ $message->id }}" wire:model="editingBody" rows="3" maxlength="2000"></textarea>
                                @error('editingBody')<p class="bd-chat-error">{{ $message }}</p>@enderror
                                <div>
                                    <button type="button" wire:click="cancelEditing">Batal</button>
                                    <button type="button" wire:click="saveEdit">Simpan</button>
                                </div>
                            </div>
                        @else
                            <p>{{ $message->body }}</p>
                        @endif
                        @if($editingMessageId !== $message->id)
                        <span class="bd-chat-message__meta">
                            <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('H:i') }}</time>
                            @if(! $message->deleted_at && $message->edited_at)
                                <span class="bd-chat-message__edited">Diedit</span>
                            @endif
                            @if(! $message->deleted_at && $outgoing)
                                @if($message->read_at)
                                    <span class="bd-chat-message__read-status" role="img" aria-label="Dibaca"><img src="{{ asset('images/figma/phase3/chat/check-read.svg') }}" alt="" aria-hidden="true" class="bd-chat-message__read-icon"></span>
                                @else
                                    <span class="bd-chat-message__read-status" role="img" aria-label="Terkirim"><img src="{{ asset('images/figma/phase3/chat/check-sent.svg') }}" alt="" aria-hidden="true" class="bd-chat-message__read-icon"></span>
                                @endif
                            @endif
                        </span>
                        @endif
                    </div>
                    @if($outgoing && $message->canBeManagedBy(auth()->user()) && $editingMessageId !== $message->id)
                        <details class="bd-chat-message__actions" x-data @keydown.escape.window="$el.open = false">
                            <summary aria-label="Tindakan untuk pesan Anda"><span aria-hidden="true">&hellip;</span></summary>
                            <div>
                                <button type="button" wire:click="startEditing({{ $message->id }})">Edit pesan</button>
                                <button type="button" wire:click="confirmDelete({{ $message->id }})">Hapus pesan</button>
                            </div>
                        </details>
                    @endif
                </div>
            @endforeach
        @empty
            <div class="bd-chat-empty">
                @unless($isAdmin)<span class="bd-chat-empty__icon" aria-hidden="true"><x-ui-icon name="support" :size="21" /></span>@endunless
                <strong>Belum ada percakapan</strong>
                <span>
                    @if($isAdmin)
                        {{ $isGeneralSupport ? 'Mulai percakapan dengan klien jika diperlukan.' : 'Gunakan percakapan ini untuk membahas pengajuan ini.' }}
                    @elseif($isGeneralSupport)
                        Mulai percakapan jika Anda membutuhkan bantuan umum.
                    @else
                        Gunakan percakapan ini untuk pertanyaan tentang pengajuan NPWP Anda.
                    @endif
                </span>
            </div>
        @endforelse
        <div data-chat-bottom aria-hidden="true"></div>
    </div>


    @if($isOtherParticipantTyping)
        <p class="bd-chat-typing" role="status">{{ $isAdmin ? $clientName.' sedang mengetik...' : 'Admin sedang mengetik...' }}</p>
    @endif

    @if($deletingMessageId !== null)
        <div class="bd-chat-delete-confirmation" role="dialog" aria-modal="true" aria-labelledby="chat-delete-title"
            x-data="{
                previous: null, outside: [],
                init() {
                    this.previous = document.activeElement;
                    if (window.matchMedia('(max-width: 430px)').matches) {
                        for (let node = this.$el; node.parentElement; node = node.parentElement) {
                            for (const sibling of node.parentElement.children) {
                                if (sibling !== node) { this.outside.push([sibling, sibling.inert]); sibling.inert = true; }
                            }
                        }
                    }
                    this.$nextTick(() => this.$refs.cancelDelete.focus());
                },
                destroy() {
                    this.outside.forEach(([node, inert]) => node.inert = inert);
                    if (window.matchMedia('(max-width: 430px)').matches) {
                        const target = this.previous?.isConnected ? this.previous : document.querySelector('.bd-chat-composer textarea');
                        target?.focus({ preventScroll: true });
                    }
                },
                trap(event) {
                    if (event.key !== 'Tab' || !window.matchMedia('(max-width: 430px)').matches) return;
                    const buttons = [...this.$el.querySelectorAll('button:not([disabled])')];
                    const first = buttons[0], last = buttons[buttons.length - 1];
                    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
                    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
                }
            }"
            @keydown="trap($event)" @keydown.escape.window="$wire.cancelDelete()">
            <div>
                <strong id="chat-delete-title">Hapus pesan?</strong>
                <p>Pesan ini akan ditarik dari percakapan dan tidak lagi ditampilkan kepada Anda maupun penerima.</p>
                <span>
                    <button type="button" x-ref="cancelDelete" wire:click="cancelDelete">Batal</button>
                    <button type="button" wire:click="deleteMessage">Hapus pesan</button>
                </span>
            </div>
        </div>
    @endif
    @error('messageAction')<p class="bd-chat-error">{{ $message }}</p>@enderror

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
        <textarea id="chat-message-{{ $thread->public_id }}" wire:model.live="body" rows="1" placeholder="Tulis pesan..." aria-label="Tulis pesan" aria-invalid="{{ $errors->has('body') ? 'true' : 'false' }}" @error('body') aria-describedby="chat-body-error" @enderror x-on:keydown="
            if ($event.key !== 'Enter' || $event.shiftKey || $event.isComposing) {
                return;
            }

            $event.preventDefault();

            if ($event.currentTarget.value.trim() !== '') {
                $wire.send();
            }
        "></textarea>
        <button class="bd-chat-composer__send" type="submit" aria-label="Kirim pesan">
            <img wire:loading.remove wire:target="send" src="{{ asset('images/figma/phase3/chat/send.svg') }}" alt="">
            <span wire:loading wire:target="send" aria-live="polite" class="sr-only">Mengirim</span>
        </button>
    </form>
    @error('body')
        <p id="chat-body-error" class="bd-chat-error">{{ $message }}</p>
    @enderror
</section>
