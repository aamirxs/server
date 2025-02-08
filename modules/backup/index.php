<?php
if (!defined('AUTHORIZED')) {
    die('Direct access not permitted');
}

class BackupManager {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function createBackup($name, $path, $frequency, $retention_days, $user_id) {
        $sql = "INSERT INTO backups (name, path, frequency, retention_days, created_by) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$name, $path, $frequency, $retention_days, $user_id]);
    }

    public function listBackups() {
        $sql = "SELECT * FROM backups ORDER BY created_at DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function executeBackup($backup_id) {
        // Get backup configuration
        $sql = "SELECT * FROM backups WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$backup_id]);
        $backup = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$backup) {
            return false;
        }

        // Create backup directory if it doesn't exist
        $backup_dir = dirname($backup['path']);
        if (!file_exists($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }

        // Create backup filename with timestamp
        $timestamp = date('Y-m-d_H-i-s');
        $backup_file = $backup['path'] . '_' . $timestamp . '.tar.gz';

        // Execute backup command
        $command = "tar -czf {$backup_file} {$backup['path']} 2>&1";
        exec($command, $output, $return_var);

        // Update backup status
        $status = $return_var === 0 ? 'success' : 'failed';
        $sql = "UPDATE backups SET last_backup = NOW(), status = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status, $backup_id]);

        return $return_var === 0;
    }
}

$backupManager = new BackupManager($db);
?>

<div class="bg-white rounded-lg shadow-lg p-6">
    <h2 class="text-2xl font-bold mb-4">Backup Management</h2>

    <!-- Create New Backup -->
    <div class="mb-8">
        <h3 class="text-xl font-semibold mb-4">Create New Backup</h3>
        <form id="backupForm" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Backup Name</label>
                    <input type="text" name="name" class="w-full border rounded p-2" required>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Path to Backup</label>
                    <input type="text" name="path" class="w-full border rounded p-2" required>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Frequency</label>
                    <select name="frequency" class="w-full border rounded p-2">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Retention (days)</label>
                    <input type="number" name="retention_days" class="w-full border rounded p-2" value="30" required>
                </div>
            </div>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Create Backup Configuration
            </button>
        </form>
    </div>

    <!-- Backup List -->
    <div>
        <h3 class="text-xl font-semibold mb-4">Existing Backups</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-6 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Path</th>
                        <th class="px-6 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Frequency</th>
                        <th class="px-6 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Last Backup</th>
                        <th class="px-6 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    <?php
                    $backups = $backupManager->listBackups();
                    foreach ($backups as $backup) {
                        echo "<tr>";
                        echo "<td class='px-6 py-4 whitespace-nowrap border-b border-gray-200'>" . htmlspecialchars($backup['name']) . "</td>";
                        echo "<td class='px-6 py-4 whitespace-nowrap border-b border-gray-200'>" . htmlspecialchars($backup['path']) . "</td>";
                        echo "<td class='px-6 py-4 whitespace-nowrap border-b border-gray-200'>" . htmlspecialchars($backup['frequency']) . "</td>";
                        echo "<td class='px-6 py-4 whitespace-nowrap border-b border-gray-200'>" . ($backup['last_backup'] ?? 'Never') . "</td>";
                        echo "<td class='px-6 py-4 whitespace-nowrap border-b border-gray-200'>" . htmlspecialchars($backup['status']) . "</td>";
                        echo "<td class='px-6 py-4 whitespace-nowrap border-b border-gray-200'>
                                <button onclick='executeBackup(" . $backup['id'] . ")' class='text-blue-500 hover:underline'>Run Now</button>
                                <button onclick='deleteBackup(" . $backup['id'] . ")' class='text-red-500 hover:underline ml-2'>Delete</button>
                              </td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.getElementById('backupForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('modules/backup/create.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (result.success) {
            location.reload();
        } else {
            alert('Failed to create backup configuration');
        }
    } catch (error) {
        console.error('Error:', error);
    }
});

async function executeBackup(id) {
    if (!confirm('Are you sure you want to run this backup now?')) return;
    
    try {
        const response = await fetch('modules/backup/execute.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id })
        });
        
        const result = await response.json();
        if (result.success) {
            location.reload();
        } else {
            alert('Failed to execute backup');
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function deleteBackup(id) {
    if (!confirm('Are you sure you want to delete this backup configuration?')) return;
    
    try {
        const response = await fetch('modules/backup/delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id })
        });
        
        const result = await response.json();
        if (result.success) {
            location.reload();
        } else {
            alert('Failed to delete backup configuration');
        }
    } catch (error) {
        console.error('Error:', error);
    }
}
</script> 