import '../bootstrap';
import { initYajraDataTable } from './datatables';
import { initAllRichTextEditors } from './rich-text-editor';
import { initConfirmDialogs } from './confirm-dialog';

// Import jQuery and DataTables locally
import $ from 'jquery';
import 'datatables.net';
import 'datatables.net-buttons';
import 'datatables.net-buttons/js/buttons.html5';

// Import DataTables CSS
import 'datatables.net-dt/css/dataTables.dataTables.min.css';
import 'datatables.net-buttons-dt/css/buttons.dataTables.min.css';
import '../../css/admin/datatables.css';

// Make jQuery globally available
window.$ = window.jQuery = $;

// Initialize DataTables enhancement automatically
initYajraDataTable();

// Admin specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Admin dashboard functionality
    console.log('Admin dashboard loaded');
    
    // Initialize all rich text editors on the page
    initAllRichTextEditors();
    initConfirmDialogs();
});

