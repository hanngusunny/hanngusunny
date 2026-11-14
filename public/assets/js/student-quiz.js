(function () {
    const forms = document.querySelectorAll('form.js-student-quiz');

    forms.forEach((form) => {
        form.querySelectorAll('.sw-blanks').forEach((wrap) => {
            const hidden = wrap.parentElement.querySelector('.js-blank-value');
            const sync = () => {
                const values = Array.from(wrap.querySelectorAll('.js-blank')).map((input) => input.value.trim());
                if (hidden) {
                    hidden.value = JSON.stringify({ blanks: values });
                }
            };
            wrap.addEventListener('input', sync);
            sync();
        });

        form.querySelectorAll('.js-word-order').forEach((wrap) => {
            const hidden = wrap.parentElement.querySelector('.js-order-value');
            const preview = wrap.parentElement.querySelector('.js-order-preview');
            const chosen = [];
            wrap.querySelectorAll('button').forEach((button) => {
                button.addEventListener('click', () => {
                    button.classList.toggle('is-on');
                    const id = button.dataset.id;
                    const index = chosen.indexOf(id);
                    if (index >= 0) {
                        chosen.splice(index, 1);
                    } else {
                        chosen.push(id);
                    }
                    const texts = chosen.map((tokenId) => {
                        const el = wrap.querySelector('button[data-id="' + tokenId + '"]');
                        return el ? el.dataset.text : '';
                    }).filter(Boolean);
                    if (hidden) {
                        hidden.value = JSON.stringify({
                            status: chosen.length === wrap.querySelectorAll('button').length ? 'completed' : 'draft',
                            order: chosen,
                            text: texts,
                        });
                    }
                    if (preview) {
                        preview.textContent = texts.join(' ') || 'Chọn từ theo đúng thứ tự';
                    }
                });
            });
        });
    });
})();
