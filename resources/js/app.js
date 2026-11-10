import 'bootstrap';

/**
 * Public site behaviour: compact sticky header on scroll.
 */
const header = document.querySelector('.site-header');

if (header) {
    const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 8);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
}
