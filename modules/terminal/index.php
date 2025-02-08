<?php
if (!defined('AUTHORIZED')) {
    define('AUTHORIZED', true);
}

class Terminal {
    private $allowed_commands = [
        'ls', 'cd', 'pwd', 'cat', 'head', 'tail', 'grep', 'find',
        'df', 'du', 'free', 'top', 'ps', 'netstat', 'ifconfig',
        'ping', 'wget', 'curl', 'tar', 'zip', 'unzip', 'git',
        'composer', 'npm', 'node', 'python', 'pip'
    ];

    public function executeCommand($command) {
        // Basic command validation
        $command_base = strtok($command, ' ');
        if (!in_array($command_base, $this->allowed_commands)) {
            return "Error: Command not allowed";
        }

        // Execute command and capture output
        $output = [];
        $return_var = 0;
        exec($command . " 2>&1", $output, $return_var);
        return implode("\n", $output);
    }

    public function getWorkingDirectory() {
        return getcwd();
    }
}

$terminal = new Terminal();
?>

<div class="p-6">
    <h2 class="text-2xl font-bold mb-6">Terminal</h2>

    <!-- Terminal Window -->
    <div class="bg-gray-900 rounded-lg shadow-lg p-4">
        <!-- Terminal Output -->
        <div id="terminal-output" class="font-mono text-sm text-green-400 h-96 overflow-y-auto mb-4 whitespace-pre-wrap"></div>
        
        <!-- Command Input -->
        <div class="flex items-center bg-gray-800 rounded p-2">
            <span class="text-green-400 mr-2">$</span>
            <input type="text" id="command-input" 
                   class="flex-1 bg-transparent text-green-400 focus:outline-none font-mono"
                   placeholder="Enter command...">
        </div>
    </div>

    <!-- Command History -->
    <div class="mt-6">
        <h3 class="text-lg font-semibold mb-2">Command History</h3>
        <div id="command-history" class="bg-white rounded-lg shadow p-4">
            <!-- History will be populated by JavaScript -->
        </div>
    </div>

    <!-- Quick Commands -->
    <div class="mt-6">
        <h3 class="text-lg font-semibold mb-2">Quick Commands</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <button onclick="executeQuickCommand('ls -la')" 
                    class="p-2 bg-blue-50 rounded hover:bg-blue-100 text-sm">
                List Files (ls -la)
            </button>
            <button onclick="executeQuickCommand('df -h')" 
                    class="p-2 bg-blue-50 rounded hover:bg-blue-100 text-sm">
                Disk Usage (df -h)
            </button>
            <button onclick="executeQuickCommand('free -m')" 
                    class="p-2 bg-blue-50 rounded hover:bg-blue-100 text-sm">
                Memory Usage (free -m)
            </button>
            <button onclick="executeQuickCommand('ps aux')" 
                    class="p-2 bg-blue-50 rounded hover:bg-blue-100 text-sm">
                Process List (ps aux)
            </button>
        </div>
    </div>
</div>

<!-- WebSocket Status -->
<div id="ws-status" class="fixed bottom-4 right-4 px-4 py-2 rounded-full text-sm"></div>

<script>
class TerminalManager {
    constructor() {
        this.output = document.getElementById('terminal-output');
        this.input = document.getElementById('command-input');
        this.history = [];
        this.historyIndex = -1;
        this.wsStatus = document.getElementById('ws-status');
        
        this.setupWebSocket();
        this.setupEventListeners();
    }

    setupWebSocket() {
        this.ws = new WebSocket(`ws://${window.location.hostname}:8080`);
        
        this.ws.onopen = () => {
            this.updateStatus('Connected', 'bg-green-500');
            this.appendOutput('Terminal Connected\n');
        };
        
        this.ws.onclose = () => {
            this.updateStatus('Disconnected', 'bg-red-500');
            setTimeout(() => this.setupWebSocket(), 5000);
        };
        
        this.ws.onmessage = (event) => {
            this.appendOutput(event.data);
        };
    }

    setupEventListeners() {
        this.input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                this.executeCommand();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.navigateHistory(-1);
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.navigateHistory(1);
            }
        });
    }

    async executeCommand() {
        const command = this.input.value.trim();
        if (!command) return;

        this.appendOutput(`$ ${command}\n`);
        this.history.push(command);
        this.historyIndex = this.history.length;
        this.input.value = '';

        try {
            const response = await fetch('modules/terminal/execute.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ command })
            });

            const result = await response.text();
            this.appendOutput(result + '\n');
            
            // Update command history display
            this.updateHistoryDisplay();
        } catch (error) {
            this.appendOutput(`Error: ${error.message}\n`);
        }
    }

    appendOutput(text) {
        this.output.textContent += text;
        this.output.scrollTop = this.output.scrollHeight;
    }

    navigateHistory(direction) {
        this.historyIndex += direction;
        
        if (this.historyIndex < 0) {
            this.historyIndex = 0;
        } else if (this.historyIndex >= this.history.length) {
            this.historyIndex = this.history.length;
            this.input.value = '';
            return;
        }

        this.input.value = this.history[this.historyIndex] || '';
    }

    updateHistoryDisplay() {
        const historyDiv = document.getElementById('command-history');
        historyDiv.innerHTML = this.history
            .slice(-5)
            .map(cmd => `<div class="text-sm text-gray-600 py-1">${cmd}</div>`)
            .join('');
    }

    updateStatus(text, className) {
        this.wsStatus.textContent = text;
        this.wsStatus.className = `fixed bottom-4 right-4 px-4 py-2 rounded-full text-sm text-white ${className}`;
    }
}

// Initialize terminal
const terminal = new TerminalManager();

// Quick command execution
function executeQuickCommand(command) {
    terminal.input.value = command;
    terminal.executeCommand();
}
</script>

<?php
// WebSocket server implementation (save as websocket/server.js)
$websocket_server = <<<'EOD'
const WebSocket = require('ws');
const server = new WebSocket.Server({ port: 8080 });

server.on('connection', (ws) => {
    console.log('New client connected');
    
    ws.on('message', (message) => {
        // Handle incoming messages
        console.log('Received:', message);
        
        // Echo back to client
        ws.send(`${message}\n`);
    });
    
    ws.on('close', () => {
        console.log('Client disconnected');
    });
});

console.log('WebSocket server running on port 8080');
EOD;

// Save WebSocket server code
file_put_contents(__DIR__ . '/websocket/server.js', $websocket_server);
?> 