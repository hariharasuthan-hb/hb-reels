<?php
/**
 * PHP Configuration Checker
 * Run this file to check your current PHP upload settings
 * Access via: http://127.0.0.1:8000/check-php-config.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>PHP Upload Configuration Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .setting { margin: 15px 0; padding: 10px; background: #f9f9f9; border-left: 4px solid #007bff; }
        .setting.ok { border-left-color: #28a745; }
        .setting.warning { border-left-color: #ffc107; }
        .setting.error { border-left-color: #dc3545; }
        .label { font-weight: bold; color: #555; }
        .value { color: #333; margin-left: 10px; }
        .instructions { background: #e7f3ff; padding: 20px; border-radius: 5px; margin-top: 20px; }
        .instructions h2 { margin-top: 0; color: #0056b3; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 PHP Upload Configuration Check</h1>
        
        <?php
        $settings = [
            'upload_max_filesize' => ['current' => ini_get('upload_max_filesize'), 'recommended' => '200M'],
            'post_max_size' => ['current' => ini_get('post_max_size'), 'recommended' => '200M'],
            'max_execution_time' => ['current' => ini_get('max_execution_time'), 'recommended' => '300'],
            'max_input_time' => ['current' => ini_get('max_input_time'), 'recommended' => '300'],
            'memory_limit' => ['current' => ini_get('memory_limit'), 'recommended' => '256M'],
        ];
        
        function convertToBytes($value) {
            $value = trim($value);
            $last = strtolower($value[strlen($value)-1]);
            $value = (int)$value;
            switch($last) {
                case 'g': $value *= 1024;
                case 'm': $value *= 1024;
                case 'k': $value *= 1024;
            }
            return $value;
        }
        
        function formatBytes($bytes) {
            if ($bytes >= 1073741824) {
                return number_format($bytes / 1073741824, 2) . ' GB';
            } elseif ($bytes >= 1048576) {
                return number_format($bytes / 1048576, 2) . ' MB';
            } elseif ($bytes >= 1024) {
                return number_format($bytes / 1024, 2) . ' KB';
            } else {
                return $bytes . ' bytes';
            }
        }
        
        $allOk = true;
        foreach ($settings as $key => $setting) {
            $currentBytes = convertToBytes($setting['current']);
            $recommendedBytes = convertToBytes($setting['recommended']);
            
            $status = 'ok';
            if ($currentBytes < $recommendedBytes) {
                $status = 'error';
                $allOk = false;
            } elseif ($currentBytes < $recommendedBytes * 1.2) {
                $status = 'warning';
            }
            
            echo "<div class='setting $status'>";
            echo "<span class='label'>$key:</span>";
            echo "<span class='value'>{$setting['current']}</span>";
            echo "<span style='color: #666; margin-left: 10px;'>(Recommended: {$setting['recommended']})</span>";
            if ($status === 'error') {
                echo "<span style='color: #dc3545; margin-left: 10px;'>⚠️ Too low!</span>";
            }
            echo "</div>";
        }
        ?>
        
        <div class="instructions">
            <h2>📝 How to Fix Configuration Issues</h2>
            
            <h3>For XAMPP on Windows:</h3>
            <ol>
                <li>Open <code>C:\xampp\php\php.ini</code> in a text editor (as Administrator)</li>
                <li>Find and update these lines:
                    <ul>
                        <li><code>upload_max_filesize = 200M</code></li>
                        <li><code>post_max_size = 200M</code></li>
                        <li><code>max_execution_time = 300</code></li>
                        <li><code>max_input_time = 300</code></li>
                        <li><code>memory_limit = 256M</code></li>
                    </ul>
                </li>
                <li>Save the file</li>
                <li>Restart Apache in XAMPP Control Panel</li>
                <li>Refresh this page to verify changes</li>
            </ol>
            
            <h3>Alternative: Using .htaccess (if mod_php is enabled)</h3>
            <p>The <code>public/.htaccess</code> file has been updated with these settings. Make sure:</p>
            <ul>
                <li>Apache has <code>mod_php</code> enabled</li>
                <li>The <code>.htaccess</code> file is in the <code>public</code> directory</li>
                <li>Apache allows <code>php_value</code> directives in .htaccess</li>
            </ul>
            
            <h3>Verify Changes:</h3>
            <p>After making changes, refresh this page to see if the settings are updated.</p>
            
            <?php if ($allOk): ?>
                <p style="color: #28a745; font-weight: bold; margin-top: 15px;">
                    ✅ All settings are configured correctly!
                </p>
            <?php else: ?>
                <p style="color: #dc3545; font-weight: bold; margin-top: 15px;">
                    ⚠️ Some settings need to be updated. Please follow the instructions above.
                </p>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 30px; padding: 15px; background: #fff3cd; border-radius: 5px;">
            <strong>Note:</strong> If you're using Laravel's built-in server (<code>php artisan serve</code>), 
            the .htaccess file won't work. You must update php.ini directly.
        </div>
    </div>
</body>
</html>

