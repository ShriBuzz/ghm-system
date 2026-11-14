// Progressive enhancement for the public form. Everything works without it.
(() => {
    // After a refused submission, take screen readers and keyboards straight to what needs fixing; after an
    // action, to what it did. A message already on the page when it opens is rarely read out otherwise.
    (document.querySelector('[data-error-summary]') ?? document.querySelector('[data-notice]'))?.focus();

    // An error link brings its field's question into view (the group's legend, or the field's own label) and then
    // focuses the field, so the question, hint and error are never left above the screen, whatever the font.
    // Without JavaScript, the scroll room in the stylesheet does the same for most fonts.
    for (const link of document.querySelectorAll('[data-error-summary] a[href^="#"]')) {
        link.addEventListener('click', (event) => {
            const field = document.getElementById(link.hash.slice(1));
            const question = field?.closest('.choices, .date-parts-group')?.querySelector('legend')
                ?? (field ? document.querySelector(`label[for="${field.id}"]`) : null);
            if (!field || !question) {
                return;
            }
            event.preventDefault();
            question.scrollIntoView();
            field.focus({ preventScroll: true });
        });
    }

    // A file over the limit is refused before a slow upload on a phone connection, not after it.
    for (const input of document.querySelectorAll('input[type="file"][data-max-bytes]')) {
        const note = document.createElement('p');
        note.className = 'error';
        note.setAttribute('role', 'alert');
        note.hidden = true;
        input.after(note);

        input.addEventListener('change', () => {
            const file = input.files && input.files[0];
            const tooLarge = Boolean(file) && file.size > Number(input.dataset.maxBytes);

            note.textContent = tooLarge ? input.dataset.tooLarge : '';
            note.hidden = !tooLarge;
            if (tooLarge) {
                input.value = '';
            }
        });
    }

    // A second tap while a slow upload is still on its way would send the grievance twice. Only for
    // 10 seconds: the browser's Stop button cancels a send without an event, and must not leave Send dead.
    for (const each of document.querySelectorAll('form[method="post"]')) {
        each.addEventListener('submit', (event) => {
            if (Date.now() - Number(each.dataset.sent ?? 0) < 10_000) {
                event.preventDefault();
                return;
            }
            each.dataset.sent = String(Date.now());
            const button = event.submitter;
            if (button?.dataset.sending) {
                button.dataset.label ??= button.textContent;
                button.textContent = button.dataset.sending;
            }
        });
    }
    // Back on this page from the browser's memory (the Back button): it can be sent again.
    window.addEventListener('pageshow', (event) => {
        if (!event.persisted) {
            return;
        }
        for (const each of document.querySelectorAll('form[data-sent]')) {
            delete each.dataset.sent;
        }
        for (const button of document.querySelectorAll('[data-label]')) {
            button.textContent = button.dataset.label;
        }
    });

    // Switching language loads the same form in the other language. Carry what was typed across,
    // in this tab only, and forget it the moment it is put back. A chosen file cannot be carried.
    const form = document.querySelector('form[data-grievance]');
    if (form) {
        const key = 'grievance-draft';
        const skip = new Set(['_token', 'form_started', 'website']);
        const fields = () => [...form.elements].filter((el) => el.name && !skip.has(el.name) && !['file', 'hidden', 'submit'].includes(el.type));

        let draft = null;
        try {
            draft = JSON.parse(sessionStorage.getItem(key) || 'null');
            sessionStorage.removeItem(key);
        } catch {
            draft = null;
        }
        if (draft) {
            for (const el of fields()) {
                if (el.name in draft) {
                    if (el.type === 'radio') {
                        el.checked = el.value === draft[el.name];
                    } else {
                        el.value = draft[el.name];
                    }
                }
            }
        }

        for (const link of document.querySelectorAll('.language-switch a')) {
            // Always, even when nothing was edited here: a form sent back with an error was filled in by the
            // server, and its answers would be lost too.
            link.addEventListener('click', () => {
                const values = {};
                for (const el of fields()) {
                    if (el.type !== 'radio' || el.checked) {
                        values[el.name] = el.value;
                    }
                }
                try {
                    sessionStorage.setItem(key, JSON.stringify(values));
                } catch {
                    // Storage refused (private mode, full): the switch still works, the draft is not kept.
                }
            });
        }

        form.addEventListener('submit', () => sessionStorage.removeItem(key));
    }
})();
