<?php
if (!defined('AUTHORIZED')) {
    die('Direct access not permitted');
}
?>

<div class="bg-white rounded-lg shadow-lg p-6">
    <h2 class="text-2xl font-bold mb-4">Python Deployment</h2>
    
    <!-- Project List -->
    <div class="mb-8">
        <h3 class="text-xl font-semibold mb-4">Active Projects</h3>
        <div class="grid grid-cols-3 gap-4" id="projectList">
            <!-- Projects will be loaded here -->
        </div>
    </div>
    
    <!-- New Project Form -->
    <div class="border-t pt-6">
        <h3 class="text-xl font-semibold mb-4">Deploy New Project</h3>
        <form id="deployForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-2">Project Name</label>
                <input type="text" name="name" class="w-full border rounded p-2">
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-2">Git Repository URL</label>
                <input type="text" name="git_url" class="w-full border rounded p-2">
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
                <label class="block text-sm font-medium mb-2">Requirements File</label>
                <input type="file" name="requirements" class="w-full border rounded p-2">
            </div>
            
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Deploy Project
            </button>
        </form>
    </div>
</div>

<script>
const pythonDeploy = {
    init: function() {
        this.loadProjects();
        this.setupFormHandler();
    },
    
    loadProjects: async function() {
        try {
            const response = await fetch('modules/python-deploy/list.php');
            const projects = await response.json();
            this.renderProjects(projects);
        } catch (error) {
            console.error('Error loading projects:', error);
        }
    },
    
    renderProjects: function(projects) {
        const container = document.getElementById('projectList');
        container.innerHTML = projects.map(project => `
            <div class="bg-gray-50 p-4 rounded-lg">
                <h4 class="font-semibold">${project.name}</h4>
                <p class="text-sm text-gray-600">Status: ${project.status}</p>
                <div class="mt-2 space-x-2">
                    <button onclick="pythonDeploy.startProject('${project.id}')" class="text-green-500 hover:underline">Start</button>
                    <button onclick="pythonDeploy.stopProject('${project.id}')" class="text-red-500 hover:underline">Stop</button>
                    <button onclick="pythonDeploy.logs('${project.id}')" class="text-blue-500 hover:underline">Logs</button>
                </div>
            </div>
        `).join('');
    },
    
    setupFormHandler: function() {
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
                    this.loadProjects();
                    e.target.reset();
                }
            } catch (error) {
                console.error('Error deploying project:', error);
            }
        });
    }
};

pythonDeploy.init();
</script> 