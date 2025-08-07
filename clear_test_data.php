<?php
echo "🧹 Clearing all test data from database...\n\n";

try {
    $pdo = new PDO('sqlite:event_management.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "📊 Current data counts BEFORE cleanup:\n";
    
    // Show current counts
    $tables = [
        'event_details' => 'Events',
        'ira_registered_students' => 'IRA Registrations', 
        'slots' => 'Time Slots',
        'users' => 'Users (will keep admin/faculty/student accounts)'
    ];
    
    foreach ($tables as $table => $label) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch()['count'];
        echo "  - $label: $count\n";
    }
    
    echo "\n🗑️ Clearing test data...\n";
    
    // Clear IRA registrations first (foreign key dependencies)
    echo "  ✅ Clearing IRA registrations...\n";
    $pdo->exec('DELETE FROM ira_registered_students');
    $pdo->exec('UPDATE sqlite_sequence SET seq = 0 WHERE name = "ira_registered_students"');
    
    // Clear slots
    echo "  ✅ Clearing time slots...\n";
    $pdo->exec('DELETE FROM slots');
    $pdo->exec('UPDATE sqlite_sequence SET seq = 0 WHERE name = "slots"');
    
    // Clear events
    echo "  ✅ Clearing events...\n";
    $pdo->exec('DELETE FROM event_details');
    $pdo->exec('UPDATE sqlite_sequence SET seq = 0 WHERE name = "event_details"');
    
    // Keep user accounts but reset any test-specific data
    echo "  ✅ Keeping user accounts (admin, faculty, students)...\n";
    
    echo "\n📊 Data counts AFTER cleanup:\n";
    
    foreach ($tables as $table => $label) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch()['count'];
        echo "  - $label: $count\n";
    }
    
    echo "\n✅ Database cleanup completed successfully!\n";
    echo "🎯 You can now start fresh with new test data.\n\n";
    
    echo "📋 What's been cleared:\n";
    echo "  ❌ All events (including IRA approved ones)\n";
    echo "  ❌ All IRA student registrations\n";  
    echo "  ❌ All time slots\n";
    echo "  ✅ User accounts preserved (admin, faculty, students)\n\n";
    
    echo "🔄 Next steps:\n";
    echo "  1. Create new events via Dashboard\n";
    echo "  2. Admin can approve events and set IRA requirement\n";
    echo "  3. Admin can create time slots with faculty assignment\n";
    echo "  4. Students can register for IRA slots\n";
    echo "  5. Faculty can evaluate registered students\n\n";
    
    // Show remaining user accounts
    echo "👥 Available user accounts for testing:\n";
    $users = $pdo->query("SELECT full_name, email, role FROM users ORDER BY role, full_name")->fetchAll();
    foreach ($users as $user) {
        echo "  - {$user['full_name']} ({$user['email']}) - Role: {$user['role']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
