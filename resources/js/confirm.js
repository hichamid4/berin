import { Modal } from 'bootstrap';

/**
 * Shared confirmation and dependency modals.
 *
 * - Forms with data-confirm="message" ask for confirmation before submitting.
 * - Links with data-dependency-url load the server-side dependency report into a modal.
 *   Without JavaScript the link still opens the full confirmation page.
 */
const template = `
<div class="modal fade" id="breinModal" tabindex="-1" aria-hidden="true" aria-labelledby="breinModalTitle">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg" style="border-radius:1rem">
      <div class="modal-header border-0 pb-0">
        <h2 class="modal-title h5" id="breinModalTitle">Please confirm</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body"></div>
      <div class="modal-footer border-0 pt-0"></div>
    </div>
  </div>
</div>`;

function modalElement() {
    let element = document.getElementById('breinModal');

    if (!element) {
        document.body.insertAdjacentHTML('beforeend', template);
        element = document.getElementById('breinModal');
    }

    return element;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || !form.dataset.confirm || form.dataset.confirmed === 'true') {
        return;
    }

    event.preventDefault();

    const element = modalElement();
    const variant = form.dataset.confirmVariant || 'primary';
    element.querySelector('.modal-title').textContent = form.dataset.confirmTitle || 'Please confirm';
    element.querySelector('.modal-body').innerHTML = `<p class="mb-0">${escapeHtml(form.dataset.confirm)}</p>`;
    element.querySelector('.modal-footer').innerHTML = `
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-${variant}" data-modal-confirm>${escapeHtml(form.dataset.confirmButton || 'Confirm')}</button>`;

    const modal = Modal.getOrCreateInstance(element);
    element.querySelector('[data-modal-confirm]').addEventListener('click', () => {
        form.dataset.confirmed = 'true';
        modal.hide();
        form.requestSubmit();
    }, { once: true });
    modal.show();
});

/**
 * Prevent duplicate submissions (for example a double-click on "Send offer"): once a form is
 * actually submitting, its submit buttons are disabled and show a sending state.
 */
document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || event.defaultPrevented || form.method.toLowerCase() === 'get') {
        return;
    }

    window.setTimeout(() => {
        form.querySelectorAll('button[type="submit"]').forEach((button) => {
            button.disabled = true;
            button.dataset.originalLabel = button.innerHTML;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Sending…';
        });
    }, 0);
});

window.addEventListener('pageshow', () => {
    document.querySelectorAll('button[type="submit"][data-original-label]').forEach((button) => {
        button.disabled = false;
        button.innerHTML = button.dataset.originalLabel;
    });
});

document.addEventListener('click', async (event) => {
    const trigger = event.target.closest('[data-dependency-url]');

    if (!trigger) {
        return;
    }

    event.preventDefault();

    const element = modalElement();
    element.querySelector('.modal-title').textContent = 'Checking dependencies…';
    element.querySelector('.modal-body').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="small text-secondary mt-2 mb-0">Inspecting related services, skills, handymen and marketplace history on the server.</p></div>';
    element.querySelector('.modal-footer').innerHTML = '';
    const modal = Modal.getOrCreateInstance(element);
    modal.show();

    try {
        const response = await fetch(trigger.dataset.dependencyUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        element.querySelector('.modal-title').textContent = 'Delete — dependency check';
        element.querySelector('.modal-body').innerHTML = await response.text();
    } catch (error) {
        element.querySelector('.modal-body').innerHTML = '<div class="alert alert-danger mb-0">The dependency check could not be loaded. Refresh the page and try again.</div>';
    }
});
