<?php
require_once '../../includes/auth.php';
if (!isLoggedIn()) {
    die('Unauthorized');
}

class CommandExecutor {
    private $allowed_commands = [
        'ls', 'cd', 'pwd', 'cat', 'head', 'tail', 'grep', 'find',
        'df', 'du', 'free', 'top', 'ps', 'netstat', 'ifconfig',
        'ping', 'wget', 'curl', 'tar', 'zip', 'unzip', 'git',
        'composer', 'npm', 'node', 'python', 'pip'
    ];

    public function execute($command) {
        // Validate command
        $command_base = strtok($command, ' ');
        if (!in_array($command_base, $this->allowed_commands)) {
            return json_encode(['error' => 'Command not allowed']);
        }

        // Execute command
        $output = [];
        $return_var = 0;
        exec($command . " 2>&1", $output, $return_var);
        
        return json_encode([
            'output' => implode("\n", $output),
            'status' => $return_var
        ]);
    }
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$command = $data['command'] ?? '';

// Execute command
$executor = new CommandExecutor();
echo $executor->execute($command); 