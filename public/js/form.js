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
})();
