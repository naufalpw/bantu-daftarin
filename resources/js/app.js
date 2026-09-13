import './bootstrap';

document.addEventListener('submit', (event) => {
    const form = event.target.closest('[data-payment-form]');
    if (!form || form.dataset.submitting === 'true') {
        return;
    }

    form.dataset.submitting = 'true';
    const button = form.querySelector('[data-payment-submit]');
    if (button) {
        button.disabled = true;
        button.textContent = 'Memproses...';
    }
});

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-copy-value]');
    if (!button) {
        return;
    }

    const value = button.dataset.copyValue || '';
    if (navigator.clipboard) {
        await navigator.clipboard.writeText(value);
    } else {
        const input = document.createElement('textarea');
        input.value = value;
        input.setAttribute('readonly', '');
        input.style.position = 'fixed';
        input.style.opacity = '0';
        document.body.append(input);
        input.select();
        document.execCommand('copy');
        input.remove();
    }
    const originalLabel = button.textContent;
    button.textContent = 'Tersalin';
    window.setTimeout(() => {
        button.textContent = originalLabel;
    }, 1600);
});

document.addEventListener('DOMContentLoaded', () => {
    const phoneViewport = window.matchMedia('(max-width: 430px)');
    // Move the existing presentation node, never clone forms or Livewire controls.
    document.querySelectorAll('[data-phone-move-to]').forEach((element) => {
        const destination = document.querySelector(element.dataset.phoneMoveTo);
        if (!destination) return;
        const home = document.createComment('presentation-home');
        element.before(home);
        const syncPosition = () => {
            if (phoneViewport.matches) destination.append(element);
            else home.after(element);
        };
        syncPosition();
        phoneViewport.addEventListener('change', syncPosition);
    });
    document.querySelectorAll('[data-phone-disclosure]').forEach((details) => {
        const syncDisclosure = () => {
            details.open = !phoneViewport.matches || details.hasAttribute('data-keep-open') || details.querySelector('[aria-invalid="true"]') !== null;
        };
        syncDisclosure();
        phoneViewport.addEventListener('change', syncDisclosure);
    });

    const revealAnchor = (hash = location.hash) => {
        let target;
        try { target = document.getElementById(decodeURIComponent(hash.slice(1))); } catch { return; }
        if (!target) return;
        for (let node = target; node; node = node.parentElement) {
            if (node instanceof HTMLDetailsElement) node.open = true;
        }
    };
    revealAnchor();
    window.addEventListener('hashchange', () => revealAnchor());
    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        if (!link) return;
        const destination = new URL(link.href, location.href);
        if (destination.origin === location.origin && destination.pathname === location.pathname && destination.search === location.search && destination.hash) revealAnchor(destination.hash);
    });
    // Keep only the existing inbox URL, scoped to this tab; never accept an external return URL.
    document.addEventListener('click', (event) => {
        if (!phoneViewport.matches || !event.target.closest('[data-support-thread]')) return;
        try { sessionStorage.setItem('bd-support-return', location.pathname + location.search); } catch { /* Storage may be unavailable. */ }
    });
    const supportReturn = document.querySelector('[data-support-return]');
    if (supportReturn) {
        const original = new URL(supportReturn.href);
        const syncSupportReturn = () => {
            supportReturn.href = original.href;
            if (!phoneViewport.matches) return;
            try {
                const stored = sessionStorage.getItem('bd-support-return');
                const destination = stored ? new URL(stored, location.origin) : null;
                if (destination?.origin === location.origin && destination.pathname === original.pathname) supportReturn.href = destination.href;
            } catch { /* The original safe link remains available. */ }
        };
        syncSupportReturn();
        phoneViewport.addEventListener('change', syncSupportReturn);
    }
    if (phoneViewport.matches) {
        const error = document.querySelector('[data-phone-form-error]');
        if (error) {
            for (let node = error.parentElement; node; node = node.parentElement) {
                if (node instanceof HTMLDetailsElement) node.open = true;
            }
            error.tabIndex = -1;
            error.focus({ preventScroll: true });
            error.scrollIntoView({ block: 'center' });
        }
    }

    document.querySelectorAll('[data-otp-resend]').forEach((container) => {
        const button = container.querySelector('[data-otp-resend-button]');
        const countdown = container.querySelector('[data-otp-countdown]');
        const waiting = container.querySelector('[data-otp-resend-waiting]');
        const ready = container.querySelector('[data-otp-resend-ready]');
        if (!button || !countdown || !waiting || !ready) return;

        let remaining = Math.max(0, Number.parseInt(container.dataset.otpResendRemaining || '0', 10));
        const render = () => {
            const isWaiting = remaining > 0;
            countdown.textContent = String(remaining);
            waiting.hidden = !isWaiting;
            ready.hidden = isWaiting;
            button.disabled = isWaiting;
            button.setAttribute('aria-disabled', isWaiting ? 'true' : 'false');
        };

        render();
        if (remaining === 0) return;

        const timer = window.setInterval(() => {
            remaining -= 1;
            render();

            if (remaining === 0) window.clearInterval(timer);
        }, 1000);
    });

    const businessType = document.getElementById('business-type');
    const businessTypeOtherField = document.getElementById('business-type-other-field');
    const businessTypeOtherInput = document.getElementById('business-type-other');
    if (businessType && businessTypeOtherField && businessTypeOtherInput) {
        const updateBusinessType = () => {
            const isOther = businessType.value === 'OTHER';
            businessTypeOtherField.hidden = !isOther;
            businessTypeOtherInput.required = isOther;
            if (!isOther) businessTypeOtherInput.value = '';
        };
        businessType.addEventListener('change', updateBusinessType);
        updateBusinessType();
    }

    document.querySelectorAll('[data-registration-face-upload]').forEach((form) => {
        const input = form.querySelector('#face-file');
        const preview = document.getElementById('face-camera-preview');
        const guide = document.getElementById('face-guide-image');
        const startButton = document.getElementById('face-camera-button');
        const captureButton = document.getElementById('face-capture-button');
        const fileName = document.getElementById('face-file-name');
        if (!input || !preview || !guide || !startButton || !captureButton || !fileName) return;

        let stream = null;
        const stopCamera = () => {
            stream?.getTracks().forEach((track) => track.stop());
            stream = null;
        };
        const submitFile = () => {
            if (!input.files?.length) return;
            fileName.textContent = input.files[0].name;
            form.submit();
        };

        input.addEventListener('change', submitFile);
        startButton.addEventListener('click', async () => {
            if (!navigator.mediaDevices?.getUserMedia) {
                input.click();
                return;
            }

            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
                preview.srcObject = stream;
                preview.hidden = false;
                guide.hidden = true;
                captureButton.hidden = false;
                await preview.play();
            } catch {
                input.click();
            }
        });
        captureButton.addEventListener('click', () => {
            const canvas = document.createElement('canvas');
            canvas.width = preview.videoWidth || 640;
            canvas.height = preview.videoHeight || 480;
            canvas.getContext('2d').drawImage(preview, 0, 0, canvas.width, canvas.height);
            canvas.toBlob((blob) => {
                if (!blob) return;
                const transfer = new DataTransfer();
                transfer.items.add(new File([blob], 'foto-wajah.jpg', { type: 'image/jpeg' }));
                input.files = transfer.files;
                stopCamera();
                submitFile();
            }, 'image/jpeg', 0.9);
        });
        window.addEventListener('pagehide', stopCamera);
    });

    const cancellationDialog = document.querySelector('[data-cancel-dialog]');
    if (cancellationDialog) {
        const cancellationTrigger = document.querySelector('[data-cancel-dialog-open]');
        const cancellationClose = cancellationDialog.querySelector('[data-cancel-dialog-close]');
        const cancellationReason = cancellationDialog.querySelector('[data-cancellation-reason]');
        const cancellationOther = cancellationDialog.querySelector('[data-cancellation-other]');
        const updateCancellationOther = () => {
            if (!cancellationOther || !cancellationReason) return;
            const isOther = cancellationReason.value === 'OTHER';
            cancellationOther.hidden = !isOther;
            cancellationOther.querySelector('textarea')?.toggleAttribute('required', isOther);
        };

        cancellationTrigger?.addEventListener('click', () => cancellationDialog.showModal());
        cancellationClose?.addEventListener('click', () => cancellationDialog.close());
        cancellationReason?.addEventListener('change', updateCancellationOther);
        cancellationDialog.addEventListener('click', (event) => {
            if (event.target === cancellationDialog) cancellationDialog.close();
        });
        updateCancellationOther();
        if (cancellationDialog.dataset.openOnLoad === 'true') cancellationDialog.showModal();
    }

    const showUploadPending = (form) => {
        if (!form || !phoneViewport.matches) return;
        form.setAttribute('aria-busy', 'true');
        const status = form.querySelector('[data-upload-status]');
        if (status) status.hidden = false;
    };
    window.addEventListener('pageshow', () => {
        document.querySelectorAll('[data-upload-status]').forEach((status) => {
            status.hidden = true;
            status.closest('form')?.removeAttribute('aria-busy');
        });
    });
    document.querySelectorAll('[data-file-input]').forEach((input) => {
        input.addEventListener('change', () => {
            const target = input.dataset.fileNameTarget
                ? document.getElementById(input.dataset.fileNameTarget)
                : null;
            if (target) target.textContent = input.files?.[0]?.name || 'Belum ada file dipilih.';
            if (input.files?.length && input.dataset.autoSubmit !== undefined) {
                showUploadPending(input.form);
                input.form?.submit();
            }
        });
    });

    document.querySelectorAll('[data-face-upload]').forEach((surface) => {
        const form = surface.querySelector('[data-face-form]');
        const cameraInput = surface.querySelector('[data-face-camera-input]');
        const fileInput = surface.querySelector('[data-face-file-input]');
        const preview = surface.querySelector('[data-face-preview]');
        const guide = surface.querySelector('[data-face-guide]');
        const start = surface.querySelector('[data-face-start]');
        const capture = surface.querySelector('[data-face-capture]');
        const cancel = surface.querySelector('[data-face-cancel]');
        const message = surface.querySelector('[data-face-message]');
        if (!form || !cameraInput || !fileInput || !preview || !guide || !start || !capture || !message) return;

        let stream = null;
        const stopCamera = () => {
            stream?.getTracks().forEach((track) => track.stop());
            stream = null;
        };
        cancel?.addEventListener('click', () => {
            stopCamera();
            preview.srcObject = null;
            preview.hidden = true;
            guide.hidden = false;
            capture.hidden = true;
            cancel.hidden = true;
            start.focus();
        });

        fileInput.addEventListener('change', () => {
            const target = fileInput.dataset.fileNameTarget
                ? document.getElementById(fileInput.dataset.fileNameTarget)
                : null;
            if (target) target.textContent = fileInput.files?.[0]?.name || 'Belum ada file dipilih.';
            if (fileInput.files?.length) {
                showUploadPending(form);
                form.submit();
            }
        });

        start.addEventListener('click', async () => {
            if (!navigator.mediaDevices?.getUserMedia) {
                message.textContent = 'Kamera tidak tersedia di browser ini. Pilih file foto sebagai alternatif.';
                fileInput.click();
                return;
            }

            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
                preview.srcObject = stream;
                preview.hidden = false;
                guide.hidden = true;
                capture.hidden = false;
                if (cancel) cancel.hidden = false;
                message.textContent = '';
                await preview.play();
            } catch {
                message.textContent = 'Izin kamera tidak tersedia. Anda tetap dapat mengunggah file foto.';
                fileInput.click();
            }
        });

        capture.addEventListener('click', () => {
            const canvas = document.createElement('canvas');
            canvas.width = preview.videoWidth || 640;
            canvas.height = preview.videoHeight || 480;
            canvas.getContext('2d').drawImage(preview, 0, 0, canvas.width, canvas.height);
            canvas.toBlob((blob) => {
                if (!blob) return;
                const transfer = new DataTransfer();
                transfer.items.add(new File([blob], 'foto-wajah.jpg', { type: 'image/jpeg' }));
                cameraInput.files = transfer.files;
                cameraInput.name = 'file';
                fileInput.removeAttribute('name');
                stopCamera();
                showUploadPending(form);
                form.submit();
            }, 'image/jpeg', 0.9);
        });

        window.addEventListener('pagehide', stopCamera);
    });

    // ── Chat auto-scroll ──────────────────────────────────────────────────────
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const scrollBehavior = () => reducedMotion.matches ? 'instant' : 'smooth';

    const chatScrollBottom = (messagesEl) => {
        const sentinel = messagesEl.querySelector('[data-chat-bottom]');
        if (sentinel) {
            sentinel.scrollIntoView({ behavior: scrollBehavior(), block: 'end' });
        } else {
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }
    };

    const isNearBottom = (messagesEl, threshold = 140) =>
        messagesEl.scrollHeight - messagesEl.scrollTop - messagesEl.clientHeight <= threshold;

    // Scroll to bottom on initial page load for all visible chat message containers.
    document.querySelectorAll('[data-chat-messages]').forEach((el) => chatScrollBottom(el));

    // After the authenticated user successfully sends a message, Livewire
    // dispatches 'chat-message-sent' as a browser event. Scroll to bottom.
    document.addEventListener('chat-message-sent', () => {
        document.querySelectorAll('[data-chat-messages]').forEach((el) => chatScrollBottom(el));
    });

    // When an incoming message arrives via wire:poll morphing, auto-follow only
    // if the user was already near the bottom. A MutationObserver on the
    // messages container detects newly added child nodes (new message rows or
    // date separators). We store the "near-bottom" state just before the DOM
    // mutation so we don't read a stale scrollTop after morphing.
    document.querySelectorAll('[data-chat-messages]').forEach((el) => {
        let wasNearBottom = isNearBottom(el);

        // Re-evaluate near-bottom whenever the user scrolls.
        el.addEventListener('scroll', () => { wasNearBottom = isNearBottom(el); }, { passive: true });

        const observer = new MutationObserver(() => {
            if (wasNearBottom) {
                chatScrollBottom(el);
            }
            // Always refresh near-bottom state after morph.
            wasNearBottom = isNearBottom(el);
        });

        observer.observe(el, { childList: true, subtree: true });
    });
    // ─────────────────────────────────────────────────────────────────────────

    document.querySelectorAll('[data-presence-heartbeat]').forEach((heartbeatElement) => {
        const url = heartbeatElement.dataset.presenceHeartbeatUrl;
        const token = heartbeatElement.dataset.presenceHeartbeatToken;
        const interval = Number(heartbeatElement.dataset.presenceHeartbeatInterval || 40000);
        if (!url || !token || Number.isNaN(interval)) {
            return;
        }

        let timer = null;
        let inFlight = false;

        const heartbeat = () => {
            if (document.visibilityState !== 'visible' || inFlight) {
                return;
            }

            inFlight = true;
            fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            }).catch(() => {
                // Presence is optional; a failed heartbeat must not interrupt the workspace.
            }).finally(() => {
                inFlight = false;
            });
        };

        const stopHeartbeat = () => {
            if (timer !== null) {
                window.clearInterval(timer);
                timer = null;
            }
        };

        const startHeartbeat = () => {
            if (document.visibilityState !== 'visible') {
                return;
            }

            stopHeartbeat();
            heartbeat();
            timer = window.setInterval(heartbeat, interval);
        };

        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                startHeartbeat();
            } else {
                stopHeartbeat();
            }
        });

        startHeartbeat();
    });

    document.querySelectorAll('[data-admin-shell]').forEach((shell) => {
        const drawer = shell.querySelector('[data-admin-drawer]');
        const openButton = shell.querySelector('[data-admin-drawer-open]');
        const closeButtons = [...shell.querySelectorAll('[data-admin-drawer-close]')];
        const backdrop = shell.querySelector('.bd-admin-backdrop');

        if (!drawer || !openButton || !backdrop) {
            return;
        }

        const focusableSelector = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
        let returnFocus = null;
        const drawerViewport = window.matchMedia('(max-width: 1023px)');
        const syncDrawerAccess = () => {
            drawer.inert = drawerViewport.matches && !drawer.classList.contains('is-open');
        };
        syncDrawerAccess();
        drawerViewport.addEventListener('change', syncDrawerAccess);

        const closeDrawer = ({ restoreFocus = true } = {}) => {
            drawer.classList.remove('is-open');
            document.body.classList.remove('is-admin-drawer-open');
            openButton.setAttribute('aria-expanded', 'false');
            backdrop.hidden = true;
            syncDrawerAccess();

            if (restoreFocus && returnFocus instanceof HTMLElement) {
                returnFocus.focus();
            }
        };

        const openDrawer = () => {
            returnFocus = document.activeElement;
            drawer.classList.add('is-open');
            document.body.classList.add('is-admin-drawer-open');
            openButton.setAttribute('aria-expanded', 'true');
            backdrop.hidden = false;
            syncDrawerAccess();

            const firstFocusable = drawer.querySelector(focusableSelector);
            if (firstFocusable instanceof HTMLElement) {
                firstFocusable.focus();
            }
        };

        openButton.addEventListener('click', openDrawer);
        closeButtons.forEach((button) => button.addEventListener('click', () => closeDrawer()));
        drawer.querySelectorAll('a[href]').forEach((link) => link.addEventListener('click', () => closeDrawer({ restoreFocus: false })));

        document.addEventListener('keydown', (event) => {
            if (!drawer.classList.contains('is-open')) {
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                closeDrawer();
                return;
            }

            if (event.key !== 'Tab') {
                return;
            }

            const focusable = [...drawer.querySelectorAll(focusableSelector)].filter((element) => element instanceof HTMLElement && element.offsetParent !== null);
            if (focusable.length === 0) {
                return;
            }

            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        });

        window.matchMedia('(min-width: 1024px)').addEventListener('change', (event) => {
            if (event.matches) {
                closeDrawer({ restoreFocus: false });
            }
        });
    });

    const publicMenu = document.querySelector('[data-public-menu]');
    const publicMenuButton = document.querySelector('[data-public-menu-button]');
    if (publicMenu && publicMenuButton) {
        const closePublicMenu = ({ focusButton = false } = {}) => {
            publicMenu.hidden = true;
            publicMenuButton.setAttribute('aria-expanded', 'false');
            if (focusButton) publicMenuButton.focus();
        };

        publicMenuButton.addEventListener('click', () => {
            const open = publicMenu.hidden;
            publicMenu.hidden = !open;
            publicMenuButton.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        publicMenu.addEventListener('click', (event) => {
            if (event.target.closest('a')) closePublicMenu();
        });

        document.addEventListener('click', (event) => {
            if (!publicMenu.hidden && !publicMenu.contains(event.target) && !publicMenuButton.contains(event.target)) {
                closePublicMenu();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !publicMenu.hidden) {
                closePublicMenu({ focusButton: true });
            }
        });
    }

    document.querySelectorAll('[data-account-menu]').forEach((menu) => {
        const summary = menu.querySelector('summary');
        const sync = () => summary?.setAttribute('aria-expanded', menu.open ? 'true' : 'false');
        menu.addEventListener('toggle', sync);
        sync();
    });

    document.querySelectorAll('[data-help-center]').forEach((helpCenter) => {
        const search = helpCenter.querySelector('[data-help-search]');
        const items = [...helpCenter.querySelectorAll('[data-help-item]')];
        const categoryButtons = [...helpCenter.querySelectorAll('[data-help-category]')];
        const resultStatus = helpCenter.querySelector('[data-help-result-status]');
        const emptyState = helpCenter.querySelector('[data-help-empty]');
        const emptyQuery = helpCenter.querySelector('[data-help-empty-query]');
        const clearButton = helpCenter.querySelector('[data-help-clear]');

        if (!search || items.length === 0 || categoryButtons.length === 0 || !resultStatus || !emptyState) {
            return;
        }

        const normalize = (value) => value.toLocaleLowerCase('id-ID').trim().replace(/\s+/g, ' ');
        let activeCategory = 'all';

        const filter = () => {
            const query = normalize(search.value);
            const terms = query ? query.split(' ') : [];
            let matches = 0;

            items.forEach((item) => {
                const searchable = normalize(item.dataset.search || '');
                const categoryMatches = activeCategory === 'all' || item.dataset.category === activeCategory;
                const queryMatches = terms.every((term) => searchable.includes(term));
                const visible = categoryMatches && queryMatches;

                item.hidden = !visible;
                if (!visible) item.open = false;
                if (visible) matches += 1;
            });

            const activeLabel = categoryButtons.find((button) => button.dataset.helpCategory === activeCategory)?.textContent.trim() || 'Semua';
            resultStatus.textContent = query
                ? `Hasil untuk “${search.value.trim()}” · ${matches} jawaban ditemukan`
                : activeCategory === 'all'
                    ? `${matches} jawaban tersedia`
                    : `${matches} jawaban dalam ${activeLabel}`;

            emptyState.hidden = matches > 0;
            if (emptyQuery) emptyQuery.textContent = search.value.trim() || activeLabel;
        };

        search.addEventListener('input', filter);
        categoryButtons.forEach((button) => {
            button.addEventListener('click', () => {
                activeCategory = button.dataset.helpCategory || 'all';
                categoryButtons.forEach((candidate) => candidate.setAttribute('aria-pressed', candidate === button ? 'true' : 'false'));
                filter();
            });
        });

        items.forEach((item) => {
            item.addEventListener('toggle', () => {
                item.querySelector('summary')?.setAttribute('aria-expanded', item.open ? 'true' : 'false');
                if (!item.open) return;
                items.forEach((candidate) => {
                    if (candidate !== item) {
                        candidate.open = false;
                        candidate.querySelector('summary')?.setAttribute('aria-expanded', 'false');
                    }
                });
            });
        });

        clearButton?.addEventListener('click', () => {
            search.value = '';
            filter();
            search.focus();
        });

        filter();
    });

    document.addEventListener('click', (event) => {
        document.querySelectorAll('[data-account-menu][open]').forEach((menu) => {
            if (!menu.contains(event.target)) menu.removeAttribute('open');
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        document.querySelectorAll('[data-account-menu][open]').forEach((menu) => {
            menu.removeAttribute('open');
            menu.querySelector('summary')?.focus();
        });
    });

    document.querySelectorAll('[data-section-select]').forEach((select) => {
        const syncFromHash = () => {
            const value = window.location.hash.slice(1);
            if ([...select.options].some((option) => option.value === value)) select.value = value;
        };
        select.addEventListener('change', () => {
            const section = document.getElementById(select.value);
            if (!section) return;
            history.replaceState(null, '', `#${select.value}`);
            section.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
            section.focus({ preventScroll: true });
        });
        window.addEventListener('hashchange', syncFromHash);
        syncFromHash();
    });

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm || 'Lanjutkan tindakan ini?')) event.preventDefault();
        });
    });

    document.querySelectorAll('[data-qna]').forEach((qna) => {
        const buttons = [...qna.querySelectorAll('[data-qna-question]')];
        const title = qna.querySelector('[data-qna-answer-title]');
        const body = qna.querySelector('[data-qna-answer-body]');

        const selectQuestion = (selectedButton) => {
            buttons.forEach((button) => {
                const active = button === selectedButton;
                button.dataset.active = active ? 'true' : 'false';
                button.setAttribute('aria-expanded', active ? 'true' : 'false');
            });

            title.textContent = selectedButton.dataset.answerTitle || selectedButton.textContent.trim();
            body.textContent = selectedButton.dataset.answerBody || '';
            body.hidden = !selectedButton.dataset.answerBody;
        };

        buttons.forEach((button) => {
            button.addEventListener('click', () => selectQuestion(button));
            button.addEventListener('keydown', (event) => {
                if (!['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
                    return;
                }

                event.preventDefault();
                const currentIndex = buttons.indexOf(button);
                const nextIndex = event.key === 'ArrowDown'
                    ? (currentIndex + 1) % buttons.length
                    : event.key === 'ArrowUp'
                        ? (currentIndex - 1 + buttons.length) % buttons.length
                        : event.key === 'Home'
                            ? 0
                            : buttons.length - 1;
                buttons[nextIndex].focus();
                selectQuestion(buttons[nextIndex]);
            });
        });

        const initial = buttons.find((button) => button.dataset.active === 'true') || buttons[0];
        if (initial) {
            selectQuestion(initial);
        }
    });

    document.querySelectorAll('[data-home-qna]').forEach((qna) => {
        const buttons = [...qna.querySelectorAll('[data-home-qna-question]')];
        const title = qna.querySelector('[data-home-qna-answer-title]');
        const body = qna.querySelector('[data-home-qna-answer-body]');

        const selectQuestion = (selectedButton) => {
            buttons.forEach((button) => {
                const active = button === selectedButton;
                const mobileAnswer = button.nextElementSibling;

                button.dataset.active = active ? 'true' : 'false';
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
                button.setAttribute('aria-expanded', active ? 'true' : 'false');

                if (mobileAnswer?.matches('[data-home-qna-mobile-answer]')) {
                    mobileAnswer.hidden = !active;
                }
            });

            if (title) title.textContent = selectedButton.dataset.answerTitle || selectedButton.textContent.trim();
            if (body) body.textContent = selectedButton.dataset.answerBody || '';
        };

        buttons.forEach((button) => {
            button.addEventListener('click', () => selectQuestion(button));
        });

        const initial = buttons.find((button) => button.dataset.active === 'true') || buttons[0];
        if (initial) selectQuestion(initial);
    });

    document.querySelectorAll('[data-testimonial-carousel]').forEach((carousel) => {
        const data = carousel.querySelector('[data-testimonial-data]');
        const quote = carousel.querySelector('[data-testimonial-quote]');
        const author = carousel.querySelector('[data-testimonial-author]');
        const index = carousel.querySelector('[data-testimonial-index]');
        const previous = carousel.querySelector('[data-testimonial-previous]');
        const next = carousel.querySelector('[data-testimonial-next]');

        if (!data || !quote || !author || !index || !previous || !next) {
            return;
        }

        let testimonials;

        try {
            testimonials = JSON.parse(data.content?.textContent || data.textContent || '[]');
        } catch {
            return;
        }

        if (!Array.isArray(testimonials) || testimonials.length < 2) {
            return;
        }

        let activeIndex = 0;
        const show = (nextIndex) => {
            activeIndex = (nextIndex + testimonials.length) % testimonials.length;
            const testimonial = testimonials[activeIndex];

            quote.textContent = testimonial.quote;
            author.textContent = testimonial.name;
            index.textContent = `${activeIndex + 1} / ${testimonials.length}`;
        };

        previous.addEventListener('click', () => show(activeIndex - 1));
        next.addEventListener('click', () => show(activeIndex + 1));
    });
});
