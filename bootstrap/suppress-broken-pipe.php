<?php

/**
 * Suppress "Broken pipe" notices from Laravel's built-in server.
 * This file should be included early to catch errors from server.php
 */

// Suppress broken pipe errors from file_put_contents to stdout
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    // Suppress broken pipe errors from Laravel's server.php
    if (($errno === E_NOTICE || $errno === E_WARNING) && 
        strpos($errfile, 'server.php') !== false && 
        strpos($errstr, 'Broken pipe') !== false) {
        return true; // Suppress this error
    }
    return false; // Let other errors be handled normally
}, E_NOTICE | E_WARNING);

