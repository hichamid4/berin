import 'bootstrap';
import './confirm.js';

/**
 * Public site behaviour: compact sticky header on scroll, and live preview of selected photos.
 */
const header = document.querySelector('.site-header');

if (header) {
    const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 8);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
}

document.querySelectorAll('input[type="file"][data-preview]').forEach((input) => {
    const target = document.getElementById(input.dataset.preview);

    input.addEventListener('change', () => {
        target.innerHTML = '';
        Array.from(input.files).forEach((file) => {
            const image = document.createElement('img');
            image.src = URL.createObjectURL(file);
            image.alt = file.name;
            image.className = 'photo-thumb';
            target.appendChild(image);
        });
    });
});
