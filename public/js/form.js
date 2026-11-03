// Progressive enhancement for the public form (P1, P5, S9). Everything works without it.
(() => {
    // After a refused submission, take screen readers and keyboards straight to what needs fixing.
    document.querySelector('[data-error-summary]')?.focus();

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

    // A second tap while a slow upload is still on its way would send the grievance twice (P1, P7).
    for (const each of document.querySelectorAll('form[method="post"]')) {
        each.addEventListener('submit', (event) => {
            if (each.dataset.sent) {
                event.preventDefault();
                return;
            }
            each.dataset.sent = 'true';
            const button = event.submitter;
            if (button?.dataset.sending) {
                button.dataset.label = button.textContent;
                button.textContent = button.dataset.sending;
            }
            button?.setAttribute('aria-disabled', 'true');
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
        for (const button of document.querySelectorAll('[aria-disabled="true"]')) {
            button.removeAttribute('aria-disabled');
            button.textContent = button.dataset.label ?? button.textContent;
        }
    });

    // Switching language loads the same form in the other language (P2). Carry what was typed across,
    // in this tab only, and forget it the moment it is put back. A chosen file cannot be carried.
    const form = document.querySelector('form[data-grievance]');
    if (form) {
        const key = 'grievance-draft';
        const skip = new Set(['_token', 'form_started', 'website']);
        const fields = () => [...form.elements].filter((el) => el.name && !skip.has(el.name) && !['file', 'hidden', 'submit'].includes(el.type));
        const changed = (el) => {
            if (el.type === 'radio') {
                return el.checked !== el.defaultChecked;
            }
            if (el.tagName === 'SELECT') {
                return [...el.options].some((option) => option.selected !== option.defaultSelected);
            }
            return el.value !== el.defaultValue;
        };

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
            link.addEventListener('click', () => {
                if (!fields().some(changed)) {
                    return;
                }
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
