<?php
// Database Migration Script - Add all missing columns
require_once 'connection_sqlite.php';

echo "Starting database migration to add comprehensive event management fields...\n\n";

try {
    // Add all missing columns to event_details table
    $alterStatements = [
        "ALTER TABLE event_details ADD COLUMN reg_deadline DATE",
        "ALTER TABLE event_details ADD COLUMN event_organizer TEXT",
        "ALTER TABLE event_details ADD COLUMN domain TEXT",
        "ALTER TABLE event_details ADD COLUMN event_type TEXT",
        "ALTER TABLE event_details ADD COLUMN event_category TEXT",
        "ALTER TABLE event_details ADD COLUMN competition_name TEXT",
        "ALTER TABLE event_details ADD COLUMN country TEXT DEFAULT 'India'",
        "ALTER TABLE event_details ADD COLUMN state TEXT",
        "ALTER TABLE event_details ADD COLUMN city TEXT",
        "ALTER TABLE event_details ADD COLUMN venue_details TEXT",
        "ALTER TABLE event_details ADD COLUMN brochure TEXT",
        "ALTER TABLE event_details ADD COLUMN applied_by INTEGER",
        "ALTER TABLE event_details ADD COLUMN applicant_name TEXT",
        "ALTER TABLE event_details ADD COLUMN applicant_id TEXT",
        "ALTER TABLE event_details ADD COLUMN department TEXT",
        "ALTER TABLE event_details ADD COLUMN year_role TEXT",
        "ALTER TABLE event_details ADD COLUMN email TEXT",
        "ALTER TABLE event_details ADD COLUMN phone TEXT",
        "ALTER TABLE event_details ADD COLUMN special_lab_name TEXT",
        "ALTER TABLE event_details ADD COLUMN special_lab_id TEXT",
        "ALTER TABLE event_details ADD COLUMN special_lab_incharge TEXT",
        "ALTER TABLE event_details ADD COLUMN ira TEXT DEFAULT 'NO'",
        "ALTER TABLE event_details ADD COLUMN remarks TEXT",
        "ALTER TABLE event_details ADD COLUMN admin_notes TEXT",
        "ALTER TABLE event_details ADD COLUMN priority TEXT DEFAULT 'Medium'",
        "ALTER TABLE event_details ADD COLUMN estimated_participants INTEGER DEFAULT 0",
        "ALTER TABLE event_details ADD COLUMN budget_required REAL DEFAULT 0.00",
        "ALTER TABLE event_details ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP"
    ];

    $successCount = 0;
    $errorCount = 0;

    foreach ($alterStatements as $sql) {
        try {
            $conn->exec($sql);
            $successCount++;
            echo "✅ Added column: " . preg_replace('/.*ADD COLUMN (\w+).*/', '$1', $sql) . "\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'duplicate column name') !== false) {
                echo "⚠️  Column already exists: " . preg_replace('/.*ADD COLUMN (\w+).*/', '$1', $sql) . "\n";
            } else {
                echo "❌ Error adding column: " . $e->getMessage() . "\n";
                $errorCount++;
            }
        }
    }

    // Update existing records with default values
    echo "\nUpdating existing records with default values...\n";
    
    $updateStatements = [
        "UPDATE event_details SET applied_by = organizer_id WHERE applied_by IS NULL",
        "UPDATE event_details SET event_organizer = 'Unknown Organizer' WHERE event_organizer IS NULL",
        "UPDATE event_details SET domain = 'General' WHERE domain IS NULL",
        "UPDATE event_details SET event_type = 'Event' WHERE event_type IS NULL",
        "UPDATE event_details SET event_category = 'General' WHERE event_category IS NULL",
        "UPDATE event_details SET country = 'India' WHERE country IS NULL",
        "UPDATE event_details SET state = 'Unknown' WHERE state IS NULL", 
        "UPDATE event_details SET city = 'Unknown' WHERE city IS NULL",
        "UPDATE event_details SET venue_details = venue WHERE venue_details IS NULL",
        "UPDATE event_details SET applicant_name = 'Unknown' WHERE applicant_name IS NULL",
        "UPDATE event_details SET applicant_id = 'UNK001' WHERE applicant_id IS NULL",
        "UPDATE event_details SET department = 'Unknown' WHERE department IS NULL",
        "UPDATE event_details SET year_role = 'Unknown' WHERE year_role IS NULL",
        "UPDATE event_details SET email = 'unknown@college.edu' WHERE email IS NULL",
        "UPDATE event_details SET phone = '0000000000' WHERE phone IS NULL",
        "UPDATE event_details SET ira = 'NO' WHERE ira IS NULL",
        "UPDATE event_details SET priority = 'Medium' WHERE priority IS NULL",
        "UPDATE event_details SET estimated_participants = 50 WHERE estimated_participants IS NULL OR estimated_participants = 0",
        "UPDATE event_details SET budget_required = 0.00 WHERE budget_required IS NULL",
        "UPDATE event_details SET updated_at = created_at WHERE updated_at IS NULL",
        "UPDATE event_details SET reg_deadline = date(event_date, '-7 days') WHERE reg_deadline IS NULL"
    ];

    foreach ($updateStatements as $sql) {
        try {
            $conn->exec($sql);
            echo "✅ Updated default values\n";
        } catch (PDOException $e) {
            echo "⚠️  Update warning: " . $e->getMessage() . "\n";
        }
    }

    echo "\n📊 Migration Summary:\n";
    echo "✅ Successful column additions: $successCount\n";
    echo "❌ Errors: $errorCount\n";

    // Verify the new structure
    echo "\n📋 Updated table structure:\n";
    $columns = $conn->query("PRAGMA table_info(event_details)")->fetchAll();
    foreach($columns as $column) {
        echo "- " . $column['name'] . " (" . $column['type'] . ")\n";
    }

    echo "\n🎉 Database migration completed successfully!\n";
    echo "Your Event Management Portal now has all comprehensive fields.\n";

} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}
?>
