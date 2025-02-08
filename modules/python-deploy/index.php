<?php
if (!defined('AUTHORIZED')) {
    define('AUTHORIZED', true);
}

class PythonDeployment {
    private $db;
    private $projects_dir;
    private $venv_dir;
    private $logs_dir;

    public function __construct() {
        $this->db = connectDB();
        $this->projects_dir = '/var/www/python-projects';
        $this->venv_dir = '/var/www/python-venvs';
        $this->logs_dir = '/var/www/server-panel/storage/logs/python';
        
        // Create necessary directories
        foreach ([$this->projects_dir, $this->venv_dir, $this->logs_dir] as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    public function listProjects() {
        $stmt = $this->db->query("SELECT * FROM python_projects ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deployProject($name, $git_url, $python_version, $requirements = null) {
        try {
            // Create project directory
            $project_dir = "{$this->projects_dir}/$name";
            $venv_path = "{$this->venv_dir}/$name";
            
            // Clone repository
            $clone_output = shell_exec("git clone $git_url $project_dir 2>&1");
            
            // Create virtual environment
            $venv_output = shell_exec("python$python_version -m venv $venv_path 2>&1");
            
            // Install requirements if provided
            if ($requirements) {
                file_put_contents("$project_dir/requirements.txt", $requirements);
            }
            
            if (file_exists("$project_dir/requirements.txt")) {
                $install_output = shell_exec("source $venv_path/bin/activate && pip install -r $project_dir/requirements.txt 2>&1");
            }
            
            // Save to database
            $stmt = $this->db->prepare("
                INSERT INTO python_projects (name, git_url, python_version, status, created_by)
                VALUES (?, ?, ?, 'stopped', ?)
            ");
            $stmt->execute([$name, $git_url, $python_version, $_SESSION['user_id']]);
            
            return true;
        } catch (Exception $e) {
            error_log("Python deployment error: " . $e->getMessage());
            return false;
        }
    }

    public function startProject($project_id) {
        $stmt = $this->db->prepare("SELECT * FROM python_projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$project) return false;
        
        $project_dir = "{$this->projects_dir}/{$project['name']}";
        $venv_path = "{$this->venv_dir}/{$project['name']}";
        $log_file = "{$this->logs_dir}/{$project['name']}.log";
        
        // Find main.py or app.py
        $main_file = file_exists("$project_dir/main.py") ? "main.py" : "app.py";
        
        // Start with gunicorn
        $cmd = "source $venv_path/bin/activate && cd $project_dir && " .
               "gunicorn --bind 0.0.0.0:{$project['port']} $main_file:app " .
               "> $log_file 2>&1 & echo $!";
        
        $pid = shell_exec($cmd);
        
        // Update database
        $stmt = $this->db->prepare("
            UPDATE python_projects 
            SET status = 'running', pid = ? 
            WHERE id = ?
        ");
        return $stmt->execute([trim($pid), $project_id]);
    }

    public function stopProject($project_id) {
        $stmt = $this->db->prepare("SELECT * FROM python_projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$project || !$project['pid']) return false;
        
        // Kill process
        shell_exec("kill {$project['pid']}");
        
        // Update database
        $stmt = $this->db->prepare("
            UPDATE python_projects 
            SET status = 'stopped', pid = NULL 
            WHERE id = ?
        ");
        return $stmt->execute([$project_id]);
    }

    public function getLogs($project_id) {
        $stmt = $this->db->prepare("SELECT name FROM python_projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$project) return '';
        
        $log_file = "{$this->logs_dir}/{$project['name']}.log";
        return file_exists($log_file) ? file_get_contents($log_file) : '';
    }
}

$deployment = new PythonDeployment();
$projects = $deployment->listProjects();
?>

<div class="p-6">
    <h2 class="text-2xl font-bold mb-6">Python Deployment</h2>

    <!-- Deploy New Project -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4">Deploy New Project</h3>
        <form id="deployForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-2">Project Name</label>
                <input type="text" name="name" class="w-full border rounded p-2" required>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Git Repository URL</label>
                <input type="text" name="git_url" class="w-full border rounded p-2" required>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Python Version</label>
                <select name="python_version" class="w-full border rounded p-2">
                    <option value="3.8">Python 3.8</option>
                    <option value="3.9">Python 3.9</option>
                    <option value="3.10">Python 3.10</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Requirements (optional)</label>
                <textarea name="requirements" class="w-full border rounded p-2" rows="4"></textarea>
            </div>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Deploy Project
            </button>
        </form>
    </div>

    <!-- Project List -->
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h3 class="text-lg font-semibold mb-4">Deployed Projects</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($projects as $project): ?>
            <div class="border rounded-lg p-4">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h4 class="font-semibold"><?php echo htmlspecialchars($project['name']); ?></h4>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($project['python_version']); ?></p>
                    </div>
                    <span class="px-2 py-1 rounded text-sm <?php echo $project['status'] === 'running' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo ucfirst($project['status']); ?>
                    </span>
                </div>
                <div class="space-x-2">
                    <?php if ($project['status'] === 'stopped'): ?>
                    <button onclick="startProject(<?php echo $project['id']; ?>)" 
                            class="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600">
                        Start
                    </button>
                    <?php else: ?>
                    <button onclick="stopProject(<?php echo $project['id']; ?>)" 
                            class="bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600">
                        Stop
                    </button>
                    <?php endif; ?>
                    <button onclick="viewLogs(<?php echo $project['id']; ?>)" 
                            class="bg-gray-500 text-white px-3 py-1 rounded text-sm hover:bg-gray-600">
                        Logs
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Logs Modal -->
<div id="logsModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-3/4 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium">Project Logs</h3>
            <button onclick="closeLogsModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <pre id="logContent" class="bg-gray-900 text-green-400 p-4 rounded h-96 overflow-y-auto font-mono text-sm"></pre>
    </div>
</div>

<script>
document.getElementById('deployForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('modules/python-deploy/deploy.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (result.success) {
            location.reload();
        } else {
            alert(result.error || 'Failed to deploy project');
        }
    } catch (error) {
        console.error('Error:', error);
    }
});

async function startProject(id) {
    try {
        const response = await fetch('modules/python-deploy/start.php', {
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
            alert(result.error || 'Failed to start project');
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function stopProject(id) {
    try {
        const response = await fetch('modules/python-deploy/stop.php', {
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
            alert(result.error || 'Failed to stop project');
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function viewLogs(id) {
    try {
        const response = await fetch(`modules/python-deploy/logs.php?id=${id}`);
        const logs = await response.text();
        
        document.getElementById('logContent').textContent = logs;
        document.getElementById('logsModal').classList.remove('hidden');
    } catch (error) {
        console.error('Error:', error);
    }
}

function closeLogsModal() {
    document.getElementById('logsModal').classList.add('hidden');
}
</script> 