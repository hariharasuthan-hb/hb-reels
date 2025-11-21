class ConfirmDialog {
    constructor() {
        this.modal = document.getElementById('admin-confirm-modal');
        if (!this.modal) {
            this.available = false;
            return;
        }

        this.available = true;
        this.titleEl = document.getElementById('admin-confirm-title');
        this.messageEl = document.getElementById('admin-confirm-message');
        this.confirmBtn = document.getElementById('admin-confirm-approve');
        this.cancelBtn = document.getElementById('admin-confirm-cancel');
        this.iconWrapper = document.getElementById('admin-confirm-icon');
        this.iconSvg = document.getElementById('admin-confirm-icon-svg');

        this.activeResolve = null;
        this.activeReject = null;

        this.confirmBtn.addEventListener('click', () => this.handleConfirm());
        this.cancelBtn.addEventListener('click', () => this.handleCancel());
        this.modal.querySelector('[data-confirm-close]')?.addEventListener('click', () => this.handleCancel());
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !this.modal.classList.contains('hidden')) {
                this.handleCancel();
            }
        });
    }

    setTone(tone = 'warning') {
        const toneMap = {
            danger: {
                classes: 'bg-gradient-to-br from-rose-500 via-red-500 to-orange-400 shadow-rose-200/70',
                icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />`
            },
            success: {
                classes: 'bg-gradient-to-br from-emerald-500 via-green-500 to-lime-400 shadow-emerald-200/70',
                icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />`
            },
            info: {
                classes: 'bg-gradient-to-br from-sky-500 via-blue-500 to-indigo-500 shadow-sky-200/70',
                icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 6a9 9 0 100 18 9 9 0 000-18z" />`
            },
            warning: {
                classes: 'bg-gradient-to-br from-amber-500 via-orange-500 to-yellow-400 shadow-amber-200/70',
                icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />`
            }
        };

        const preset = toneMap[tone] ?? toneMap.warning;
        this.iconWrapper.className = `w-14 h-14 rounded-2xl text-white flex items-center justify-center shadow-lg ${preset.classes}`;
        this.iconSvg.innerHTML = preset.icon;

        // Button color
        const btnClassMap = {
            danger: 'btn btn-danger',
            success: 'btn btn-primary',
            warning: 'btn btn-warning',
            info: 'btn btn-secondary'
        };
        this.confirmBtn.className = btnClassMap[tone] ?? 'btn btn-warning';
    }

    open({ title, message, confirmText, cancelText, tone } = {}) {
        if (!this.available) {
            return Promise.resolve(true);
        }

        this.titleEl.textContent = title || 'Are you sure?';
        this.messageEl.textContent = message || 'This action cannot be undone.';
        this.confirmBtn.textContent = confirmText || 'Continue';
        this.cancelBtn.textContent = cancelText || 'Cancel';
        this.setTone(tone);

        this.modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        return new Promise((resolve, reject) => {
            this.activeResolve = resolve;
            this.activeReject = reject;
        });
    }

    close() {
        this.modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    handleConfirm() {
        this.close();
        this.activeResolve?.(true);
        this.activeResolve = null;
        this.activeReject = null;
    }

    handleCancel() {
        this.close();
        this.activeReject?.(false);
        this.activeResolve = null;
        this.activeReject = null;
    }
}

export function initConfirmDialogs() {
    const dialog = new ConfirmDialog();
    if (!dialog.available) {
        return;
    }

    const toOptions = (node) => ({
        title: node.dataset.confirmTitle,
        message: node.dataset.confirmMessage,
        confirmText: node.dataset.confirmButton,
        cancelText: node.dataset.cancelButton,
        tone: node.dataset.confirmTone,
    });

    document.querySelectorAll('form[data-confirm="true"]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed === 'true') {
                form.dataset.confirmed = 'false';
                return;
            }

            event.preventDefault();

            dialog.open(toOptions(form))
                .then(() => {
                    form.dataset.confirmed = 'true';
                    form.submit();
                })
                .catch(() => {
                    // User canceled; nothing to do.
                });
        });
    });

    document.querySelectorAll('[data-confirm-click="true"]').forEach((element) => {
        element.addEventListener('click', (event) => {
            event.preventDefault();
            const href = element.getAttribute('href');
            const targetSelector = element.dataset.confirmTarget;
            const targetForm = targetSelector ? document.querySelector(targetSelector) : null;

            dialog.open(toOptions(element))
                .then(() => {
                    if (href) {
                        window.location.href = href;
                    } else if (targetForm) {
                        targetForm.submit();
                    }
                })
                .catch(() => {});
        });
    });
}


