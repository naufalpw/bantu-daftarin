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
    if (!button || !navigator.clipboard) {
        return;
    }

    await navigator.clipboard.writeText(button.dataset.copyValue || '');
    const originalLabel = button.textContent;
    button.textContent = 'Tersalin';
    window.setTimeout(() => {
        button.textContent = originalLabel;
    }, 1600);
});

document.addEventListener('DOMContentLoaded', () => {
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
});
