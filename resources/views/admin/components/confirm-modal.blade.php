<div id="admin-confirm-modal" class="fixed inset-0 z-[2000] hidden">
    <div class="absolute inset-0 bg-slate-900/70 backdrop-blur-sm" data-confirm-close></div>
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md border border-slate-100 animate-fade-in">
            <div class="flex items-start gap-4 p-6">
                <div id="admin-confirm-icon"
                     class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 text-white flex items-center justify-center shadow-lg shadow-purple-200/70">
                    <svg id="admin-confirm-icon-svg" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold uppercase tracking-wide text-slate-400 mb-1">
                        Action Required
                    </p>
                    <h3 id="admin-confirm-title" class="text-xl font-semibold text-slate-900">
                        Are you sure?
                    </h3>
                    <p id="admin-confirm-message" class="mt-2 text-slate-600 leading-relaxed">
                        This action cannot be undone.
                    </p>
                </div>
            </div>
            <div class="px-6 pb-6">
                <div class="flex flex-col sm:flex-row sm:justify-end gap-3">
                    <button type="button"
                            id="admin-confirm-cancel"
                            class="btn btn-secondary w-full sm:w-auto">
                        Cancel
                    </button>
                    <button type="button"
                            id="admin-confirm-approve"
                            class="btn btn-danger w-full sm:w-auto">
                        Continue
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

