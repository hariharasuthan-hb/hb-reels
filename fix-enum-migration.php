<?php

// Direct script to fix the enum issue
// Run this with: php fix-enum-migration.php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "Checking database connection...\n";
    $connection = DB::getDriverName();
    echo "Connected to: {$connection}\n";

    echo "Updating check_in_method enum...\n";

    if ($connection === 'sqlite') {
        echo "Using SQLite syntax...\n";
        // Drop and recreate for SQLite
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('ALTER TABLE activity_logs RENAME TO activity_logs_backup');
        DB::statement('CREATE TABLE activity_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            activity_type VARCHAR(255) DEFAULT "gym_checkin",
            date DATE NOT NULL,
            check_in_time DATETIME NULL,
            check_out_time DATETIME NULL,
            workout_summary TEXT NULL,
            duration_minutes INTEGER DEFAULT 0,
            calories_burned DECIMAL(8,2) NULL,
            exercises_done TEXT NULL,
            performance_metrics TEXT NULL,
            check_in_method VARCHAR(255) NULL CHECK (check_in_method IN ("qr_code", "rfid", "manual", "web")),
            checked_in_by INTEGER NULL,
            video_filename VARCHAR(255) NULL,
            video_caption TEXT NULL,
            video_path VARCHAR(255) NULL,
            video_size_bytes INTEGER NULL,
            created_at DATETIME,
            updated_at DATETIME,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (checked_in_by) REFERENCES users(id)
        )');
        DB::statement('INSERT INTO activity_logs SELECT * FROM activity_logs_backup');
        DB::statement('DROP TABLE activity_logs_backup');
        DB::statement('PRAGMA foreign_keys = ON');
    } else {
        echo "Using MySQL syntax...\n";
        // Use ALTER TABLE for MySQL
        DB::statement("ALTER TABLE activity_logs MODIFY COLUMN check_in_method ENUM('qr_code', 'rfid', 'manual', 'web') NULL");
    }

    echo "✅ Enum updated successfully!\n";
    echo "The check_in_method column now accepts 'web' values.\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

