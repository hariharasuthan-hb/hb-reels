/**
 * Reusable SweetAlert2 Component
 * 
 * This component provides a consistent interface for showing SweetAlert dialogs
 * throughout the application.
 * 
 * Usage:
 *   SwalHelper.success('Title', 'Message');
 *   SwalHelper.error('Title', 'Message');
 *   SwalHelper.warning('Title', 'Message');
 *   SwalHelper.info('Title', 'Message');
 *   SwalHelper.confirm('Title', 'Message').then((result) => { ... });
 */

const SwalHelper = {
    /**
     * Default configuration
     */
    defaults: {
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#ef4444',
        confirmButtonText: 'OK',
        cancelButtonText: 'Cancel',
        allowOutsideClick: false,
        allowEscapeKey: true,
    },

    /**
     * Show success alert
     * @param {string} title - Alert title
     * @param {string} text - Alert message
     * @param {object} options - Additional options
     * @returns {Promise}
     */
    success: function(title, text = '', options = {}) {
        return Swal.fire({
            icon: 'success',
            title: title,
            text: text,
            confirmButtonColor: this.defaults.confirmButtonColor,
            confirmButtonText: this.defaults.confirmButtonText,
            ...this.defaults,
            ...options
        });
    },

    /**
     * Show error alert
     * @param {string} title - Alert title
     * @param {string} text - Alert message
     * @param {object} options - Additional options
     * @returns {Promise}
     */
    error: function(title, text = '', options = {}) {
        return Swal.fire({
            icon: 'error',
            title: title,
            text: text,
            confirmButtonColor: this.defaults.cancelButtonColor,
            confirmButtonText: this.defaults.confirmButtonText,
            ...this.defaults,
            ...options
        });
    },

    /**
     * Show warning alert
     * @param {string} title - Alert title
     * @param {string} text - Alert message
     * @param {object} options - Additional options
     * @returns {Promise}
     */
    warning: function(title, text = '', options = {}) {
        return Swal.fire({
            icon: 'warning',
            title: title,
            text: text,
            confirmButtonColor: '#f59e0b',
            confirmButtonText: this.defaults.confirmButtonText,
            ...this.defaults,
            ...options
        });
    },

    /**
     * Show info alert
     * @param {string} title - Alert title
     * @param {string} text - Alert message
     * @param {object} options - Additional options
     * @returns {Promise}
     */
    info: function(title, text = '', options = {}) {
        return Swal.fire({
            icon: 'info',
            title: title,
            text: text,
            confirmButtonColor: '#3b82f6',
            confirmButtonText: this.defaults.confirmButtonText,
            ...this.defaults,
            ...options
        });
    },

    /**
     * Show confirmation dialog
     * @param {string} title - Dialog title
     * @param {string} text - Dialog message
     * @param {object} options - Additional options
     * @returns {Promise}
     */
    confirm: function(title, text = '', options = {}) {
        return Swal.fire({
            icon: 'question',
            title: title,
            text: text,
            showCancelButton: true,
            confirmButtonColor: this.defaults.confirmButtonColor,
            cancelButtonColor: this.defaults.cancelButtonColor,
            confirmButtonText: options.confirmButtonText || this.defaults.confirmButtonText,
            cancelButtonText: options.cancelButtonText || this.defaults.cancelButtonText,
            ...this.defaults,
            ...options
        });
    },

    /**
     * Show loading alert
     * @param {string} title - Loading title
     * @param {string} text - Loading message
     * @returns {Promise}
     */
    loading: function(title = 'Loading...', text = 'Please wait') {
        return Swal.fire({
            title: title,
            text: text,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    },

    /**
     * Close current alert
     */
    close: function() {
        Swal.close();
    }
};

// Make SwalHelper globally available
window.SwalHelper = SwalHelper;

