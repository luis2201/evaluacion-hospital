const body = document.body;
const sidebar = document.querySelector('[data-sidebar]');
const sidebarOverlay = document.querySelector('[data-sidebar-overlay]');

const setSidebar = (open) => {
    sidebar?.classList.toggle('-translate-x-full', !open);
    sidebarOverlay?.toggleAttribute('hidden', !open);
    body.classList.toggle('overflow-hidden', open && window.innerWidth < 1024);
};

document.querySelectorAll('[data-sidebar-open]').forEach((button) => {
    button.addEventListener('click', () => setSidebar(true));
});

document.querySelectorAll('[data-sidebar-close]').forEach((button) => {
    button.addEventListener('click', () => setSidebar(false));
});

document.querySelectorAll('[data-modal-open]').forEach((button) => {
    button.addEventListener('click', () => {
        document.getElementById(button.dataset.modalOpen)?.showModal();
    });
});

document.querySelectorAll('[data-modal-close]').forEach((button) => {
    button.addEventListener('click', () => button.closest('dialog')?.close());
});

document.querySelectorAll('dialog[data-modal]').forEach((dialog) => {
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });
});

document.querySelectorAll('[data-alert-dismiss]').forEach((button) => {
    button.addEventListener('click', () => button.closest('[role="alert"]')?.remove());
});

document.querySelectorAll('[data-word-limit]').forEach((field) => {
    const counter = document.getElementById(field.dataset.wordCounter);
    const limit = Number(field.dataset.wordLimit);
    const updateCounter = () => {
        const words = field.value.trim().match(/[\p{L}\p{N}]+(?:[’'-][\p{L}\p{N}]+)*/gu) ?? [];
        counter.textContent = words.length;
        counter.classList.toggle('font-bold', words.length > limit);
        counter.classList.toggle('text-red-600', words.length > limit);
        field.setAttribute('aria-invalid', words.length > limit ? 'true' : 'false');
    };

    field.addEventListener('input', updateCounter);
    updateCounter();
});

document.querySelectorAll('[data-file-selection]').forEach((field) => {
    const counter = document.getElementById(field.dataset.fileCounter);
    const updateFileCount = () => {
        const count = field.files?.length ?? 0;
        counter.textContent = count === 0
            ? 'Ningún archivo seleccionado'
            : `${count} ${count === 1 ? 'archivo seleccionado' : 'archivos seleccionados'}`;
        counter.classList.toggle('text-brand-700', count > 0);
        counter.classList.toggle('text-slate-500', count === 0);
    };

    field.addEventListener('change', updateFileCount);
    updateFileCount();
});

window.addEventListener('resize', () => {
    if (window.innerWidth >= 1024) setSidebar(false);
});
