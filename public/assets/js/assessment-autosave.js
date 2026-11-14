(function () {
    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = () => (tokenMeta ? tokenMeta.getAttribute('content') : '');

    document.querySelectorAll('form[data-assessment-autosave]').forEach((form) => {
        const url = form.getAttribute('data-autosave-url');
        if (!url) {
            return;
        }

        const timers = {};
        let dirty = false;
        const status = document.createElement('span');
        status.className = 'sw-autosave-status';
        const toolbar = form.querySelector('.sw-assess-toolbar');
        if (toolbar) {
            toolbar.appendChild(status);
        }

        const collectValue = (questionId) => {
            const fieldset = form.querySelector('[data-question-id="' + questionId + '"]');
            if (!fieldset) {
                return '';
            }

            const hidden = fieldset.querySelector('.js-blank-value, .js-order-value');
            if (hidden && hidden.value) {
                return hidden.value;
            }

            const checks = fieldset.querySelectorAll('input[type="checkbox"][name^="answers["]:checked');
            if (checks.length) {
                return Array.from(checks).map((input) => input.value).join(',');
            }

            const radio = fieldset.querySelector('input[type="radio"][name^="answers["]:checked');
            if (radio) {
                return radio.value;
            }

            const field = fieldset.querySelector('textarea[name^="answers["], input[name^="answers["]');
            return field ? String(field.value || '').trim() : '';
        };

        const save = (questionId) => {
            const value = collectValue(questionId);
            if (!questionId || value === '') {
                return;
            }

            const body = new FormData();
            body.append('_token', csrfToken());
            body.append('question_id', String(questionId));
            body.append('answers[' + questionId + ']', value);

            status.textContent = 'Đang lưu…';

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: body,
            })
                .then((response) => response.json().then((data) => ({ ok: response.ok, data: data })))
                .then((result) => {
                    if (result.ok && result.data && result.data.ok) {
                        status.textContent = 'Đã lưu';
                        window.setTimeout(() => {
                            if (status.textContent === 'Đã lưu') {
                                status.textContent = '';
                            }
                        }, 1600);
                        return;
                    }

                    status.textContent = (result.data && result.data.error) ? result.data.error : 'Không lưu được';
                })
                .catch(() => {
                    status.textContent = 'Không lưu được';
                });
        };

        const queue = (questionId) => {
            dirty = true;
            window.clearTimeout(timers[questionId]);
            timers[questionId] = window.setTimeout(() => save(questionId), 600);
        };

        form.addEventListener('change', (event) => {
            const fieldset = event.target.closest('[data-question-id]');
            if (fieldset) {
                queue(fieldset.getAttribute('data-question-id'));
            }
        });

        form.addEventListener('input', (event) => {
            const fieldset = event.target.closest('[data-question-id]');
            if (fieldset) {
                queue(fieldset.getAttribute('data-question-id'));
            }
        });

        form.addEventListener('submit', () => {
            dirty = false;
        });

        if (form.hasAttribute('data-assessment-leave-guard')) {
            window.addEventListener('beforeunload', (event) => {
                if (!dirty) {
                    return;
                }

                event.preventDefault();
                event.returnValue = '';
            });
        }
    });
})();
