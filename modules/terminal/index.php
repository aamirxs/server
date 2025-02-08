<?php
if (!defined('AUTHORIZED')) {
    die('Direct access not permitted');
}
?>

<div class="bg-white rounded-lg shadow-lg p-6">
    <h2 class="text-2xl font-bold mb-4">Terminal</h2>
    
    <div id="terminal" class="bg-black text-green-400 p-4 rounded-lg h-96 overflow-y-auto font-mono">
        <div id="output"></div>
        <div class="flex items-center">
            <span class="mr-2">$</span>
            <input type="text" id="command" class="bg-transparent border-none outline-none flex-1 text-green-400" autofocus>
        </div>
    </div>
</div>

<script>
const terminal = {
    init: function() {
        this.commandInput = document.getElementById('command');
        this.output = document.getElementById('output');
        
        this.commandInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                this.executeCommand();
            }
        });
    },
    
    executeCommand: async function() {
        const command = this.commandInput.value;
        this.commandInput.value = '';
        
        this.appendOutput(`$ ${command}`);
        
        try {
            const response = await fetch('modules/terminal/execute.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ command })
            });
            
            const result = await response.text();
            this.appendOutput(result);
        } catch (error) {
            this.appendOutput('Error executing command');
        }
    },
    
    appendOutput: function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        this.output.appendChild(div);
        this.output.scrollTop = this.output.scrollHeight;
    }
};

terminal.init();
</script> 