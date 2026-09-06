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

        const closeDrawer = ({ restoreFocus = true } = {}) => {
            drawer.classList.remove('is-open');
            document.body.classList.remove('is-admin-drawer-open');
            openButton.setAttribute('aria-expanded', 'false');
            backdrop.hidden = true;

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
            testimonials = JSON.parse(data.textContent || '[]');
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
