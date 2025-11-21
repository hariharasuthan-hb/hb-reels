/**
 * Rich Text Editor Initialization
 * 
 * Handles TinyMCE initialization for rich text editor components.
 * Ensures proper HTML encoding/decoding and handles form submissions.
 */

// Import TinyMCE core
import tinymce from 'tinymce';

// Import theme
import 'tinymce/themes/silver';

// Import plugins
import 'tinymce/plugins/lists';
import 'tinymce/plugins/link';
import 'tinymce/plugins/image';
import 'tinymce/plugins/code';
import 'tinymce/plugins/table';
import 'tinymce/plugins/media';
import 'tinymce/plugins/wordcount';
import 'tinymce/plugins/autoresize';

// Import content CSS
import 'tinymce/skins/ui/oxide/content.min.css';

/**
 * Initialize a rich text editor instance
 * 
 * @param {string} editorId - The ID of the textarea element
 * @param {object} options - Configuration options
 */
export function initRichTextEditor(editorId, options = {}) {
    const defaultOptions = {
        selector: `#${editorId}`,
        height: options.height || 400,
        menubar: false,
        plugins: options.plugins || ['lists', 'link', 'image', 'code', 'wordcount', 'autoresize'],
        toolbar: options.toolbar || 'undo redo | formatselect | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image code | removeformat',
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; }',
        branding: false,
        promotion: false,
        resize: true,
        convert_urls: false,
        relative_urls: false,
        remove_script_host: false,
        // Ensure proper encoding
        entity_encoding: 'raw',
        // Auto-resize to content
        autoresize_bottom_margin: 16,
        autoresize_min_height: options.height || 400,
        autoresize_max_height: 800,
        // Setup callback to handle content properly
        setup: function(editor) {
            // Ensure content is properly handled on save
            editor.on('SaveContent', function(e) {
                // Content is already in correct format, no encoding needed
                // TinyMCE handles HTML properly
            });
            
            // Handle form submission
            editor.on('submit', function() {
                // Get content and ensure it's properly formatted
                const content = editor.getContent();
                // Update the textarea value
                editor.save();
            });
        },
        // Image upload handler (can be extended)
        images_upload_handler: function(blobInfo, progress) {
            return new Promise(function(resolve, reject) {
                // For now, convert to base64
                // Can be extended to upload to server
                const reader = new FileReader();
                reader.onload = function() {
                    resolve(reader.result);
                };
                reader.onerror = function() {
                    reject('Image upload failed');
                };
                reader.readAsDataURL(blobInfo.blob());
            });
        }
    };

    // Merge with custom options
    const config = { ...defaultOptions, ...options };

    // Initialize TinyMCE
    tinymce.init(config);
}

/**
 * Initialize all rich text editors on the page
 */
export function initAllRichTextEditors() {
    document.querySelectorAll('.rich-text-editor').forEach(function(textarea) {
        const editorId = textarea.id;
        if (!editorId) {
            console.warn('Rich text editor textarea missing ID');
            return;
        }

        const toolbar = textarea.dataset.toolbar || 'full';
        const height = parseInt(textarea.dataset.height) || 400;
        const plugins = textarea.dataset.plugins 
            ? JSON.parse(textarea.dataset.plugins) 
            : ['lists', 'link', 'image', 'code'];

        // Toolbar presets
        const toolbarConfigs = {
            'full': 'undo redo | formatselect | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image code | removeformat',
            'basic': 'bold italic underline | bullist numlist | link | removeformat',
            'minimal': 'bold italic | removeformat',
        };

        const toolbarConfig = toolbarConfigs[toolbar] || toolbarConfigs['full'];

        initRichTextEditor(editorId, {
            toolbar: toolbarConfig,
            height: height,
            plugins: plugins
        });
    });
}

/**
 * Destroy all TinyMCE instances
 * Useful for cleanup or when navigating away
 */
export function destroyAllRichTextEditors() {
    if (typeof tinymce !== 'undefined') {
        tinymce.remove();
    }
}

// Make functions globally available
window.initRichTextEditor = initRichTextEditor;
window.initAllRichTextEditors = initAllRichTextEditors;
window.destroyAllRichTextEditors = destroyAllRichTextEditors;

