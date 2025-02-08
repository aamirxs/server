<?php
if (!defined('AUTHORIZED')) {
    define('AUTHORIZED', true);
}

class FileManager {
    private $current_dir;
    private $base_dir;
    private $allowed_extensions;

    public function __construct() {
        $this->base_dir = '/var/www';
        $this->current_dir = isset($_GET['dir']) ? $_GET['dir'] : $this->base_dir;
        $this->allowed_extensions = ['txt', 'php', 'html', 'css', 'js', 'json', 'yml', 'yaml', 'md', 'log'];
        
        // Ensure we don't go above base directory
        if (!$this->isPathSafe($this->current_dir)) {
            $this->current_dir = $this->base_dir;
        }
    }

    private function isPathSafe($path) {
        $realPath = realpath($path);
        return $realPath && strpos($realPath, $this->base_dir) === 0;
    }

    public function listFiles() {
        $files = scandir($this->current_dir);
        $items = [];
        
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $path = $this->current_dir . '/' . $file;
                $items[] = [
                    'name' => $file,
                    'path' => $path,
                    'type' => is_dir($path) ? 'dir' : 'file',
                    'size' => is_dir($path) ? '-' : $this->formatSize(filesize($path)),
                    'permissions' => substr(sprintf('%o', fileperms($path)), -4),
                    'modified' => date('Y-m-d H:i:s', filemtime($path))
                ];
            }
        }
        
        return $items;
    }

    private function formatSize($size) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 2) . ' ' . $units[$i];
    }

    public function createDirectory($name) {
        $path = $this->current_dir . '/' . $name;
        if (!$this->isPathSafe($path)) {
            return false;
        }
        return mkdir($path, 0755);
    }

    public function uploadFile($file) {
        if (!isset($file['tmp_name'])) {
            return false;
        }
        
        $path = $this->current_dir . '/' . $file['name'];
        if (!$this->isPathSafe($path)) {
            return false;
        }
        
        return move_uploaded_file($file['tmp_name'], $path);
    }

    public function deleteItem($path) {
        if (!$this->isPathSafe($path)) {
            return false;
        }
        
        if (is_dir($path)) {
            return $this->deleteDirectory($path);
        } else {
            return unlink($path);
        }
    }

    private function deleteDirectory($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        
        return rmdir($dir);
    }

    public function getFileContent($path) {
        if (!$this->isPathSafe($path)) {
            return false;
        }
        
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if (!in_array($ext, $this->allowed_extensions)) {
            return false;
        }
        
        return file_get_contents($path);
    }

    public function saveFileContent($path, $content) {
        if (!$this->isPathSafe($path)) {
            return false;
        }
        
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if (!in_array($ext, $this->allowed_extensions)) {
            return false;
        }
        
        return file_put_contents($path, $content);
    }
}

$fileManager = new FileManager();
$files = $fileManager->listFiles();
?>

<div class="p-6">
    <h2 class="text-2xl font-bold mb-6">File Manager</h2>

    <!-- Breadcrumb -->
    <div class="bg-gray-100 p-3 rounded mb-6 flex items-center">
        <span class="text-gray-600">Current Directory:</span>
        <div class="ml-2 flex items-center">
            <?php
            $path_parts = explode('/', trim($fileManager->current_dir, '/'));
            $current_path = '';
            foreach ($path_parts as $part) {
                $current_path .= '/' . $part;
                echo "<a href='?dir=" . urlencode($current_path) . "' class='text-blue-600 hover:underline mx-1'>$part</a>/";
            }
            ?>
        </div>
    </div>

    <!-- Actions -->
    <div class="mb-6 flex space-x-4">
        <button onclick="showNewFolderModal()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
            New Folder
        </button>
        <button onclick="showUploadModal()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
            Upload File
        </button>
    </div>

    <!-- File List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Permissions</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Modified</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <tr>
                    <td colspan="5">
                        <a href="?dir=<?php echo urlencode(dirname($fileManager->current_dir)); ?>" 
                           class="flex items-center px-6 py-4 text-blue-600 hover:text-blue-800">
                            <i class="fas fa-level-up-alt mr-2"></i> ..
                        </a>
                    </td>
                </tr>
                <?php foreach ($files as $file): ?>
                <tr>
                    <td class="px-6 py-4">
                        <?php if ($file['type'] === 'dir'): ?>
                        <a href="?dir=<?php echo urlencode($file['path']); ?>" 
                           class="flex items-center text-blue-600 hover:text-blue-800">
                            <i class="fas fa-folder mr-2"></i>
                            <?php echo htmlspecialchars($file['name']); ?>
                        </a>
                        <?php else: ?>
                        <a href="#" onclick="viewFile('<?php echo $file['path']; ?>')" 
                           class="flex items-center text-gray-900 hover:text-blue-600">
                            <i class="fas fa-file mr-2"></i>
                            <?php echo htmlspecialchars($file['name']); ?>
                        </a>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo $file['size']; ?></td>
                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo $file['permissions']; ?></td>
                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo $file['modified']; ?></td>
                    <td class="px-6 py-4 text-sm">
                        <button onclick="deleteItem('<?php echo $file['path']; ?>')" 
                                class="text-red-600 hover:text-red-800">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modals -->
<div id="newFolderModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-medium mb-4">Create New Folder</h3>
        <input type="text" id="folderName" class="w-full border rounded p-2 mb-4">
        <div class="flex justify-end">
            <button onclick="hideNewFolderModal()" class="mr-2 px-4 py-2 text-gray-500">Cancel</button>
            <button onclick="createFolder()" class="px-4 py-2 bg-blue-500 text-white rounded">Create</button>
        </div>
    </div>
</div>

<div id="uploadModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-medium mb-4">Upload File</h3>
        <form id="uploadForm" enctype="multipart/form-data">
            <input type="file" name="file" class="w-full border rounded p-2 mb-4">
            <div class="flex justify-end">
                <button type="button" onclick="hideUploadModal()" class="mr-2 px-4 py-2 text-gray-500">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded">Upload</button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal functions
function showNewFolderModal() {
    document.getElementById('newFolderModal').classList.remove('hidden');
}

function hideNewFolderModal() {
    document.getElementById('newFolderModal').classList.add('hidden');
}

function showUploadModal() {
    document.getElementById('uploadModal').classList.remove('hidden');
}

function hideUploadModal() {
    document.getElementById('uploadModal').classList.add('hidden');
}

// File operations
async function createFolder() {
    const name = document.getElementById('folderName').value;
    try {
        const response = await fetch('modules/file-manager/create-folder.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ name })
        });
        
        if (response.ok) {
            location.reload();
        } else {
            alert('Failed to create folder');
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function deleteItem(path) {
    if (!confirm('Are you sure you want to delete this item?')) return;
    
    try {
        const response = await fetch('modules/file-manager/delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ path })
        });
        
        if (response.ok) {
            location.reload();
        } else {
            alert('Failed to delete item');
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

document.getElementById('uploadForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('modules/file-manager/upload.php', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            location.reload();
        } else {
            alert('Failed to upload file');
        }
    } catch (error) {
        console.error('Error:', error);
    }
});

async function viewFile(path) {
    try {
        const response = await fetch('modules/file-manager/view.php?path=' + encodeURIComponent(path));
        const content = await response.text();
        // Show file content in a modal or new window
        // Implementation depends on your UI requirements
    } catch (error) {
        console.error('Error:', error);
    }
}
</script>