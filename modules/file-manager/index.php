<?php
if (!defined('AUTHORIZED')) {
    die('Direct access not permitted');
}

class FileManager {
    private $current_dir;

    public function __construct() {
        $this->current_dir = isset($_GET['dir']) ? $_GET['dir'] : '/';
    }

    public function listFiles() {
        $files = scandir($this->current_dir);
        $output = '<div class="grid grid-cols-4 gap-4">';
        
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $path = $this->current_dir . '/' . $file;
                $is_dir = is_dir($path);
                
                $output .= $this->generateFileCard($file, $path, $is_dir);
            }
        }
        
        return $output . '</div>';
    }

    private function generateFileCard($file, $path, $is_dir) {
        return '
        <div class="bg-white p-4 rounded-lg shadow">
            <div class="flex items-center">
                <img src="assets/img/' . ($is_dir ? 'folder' : 'file') . '.svg" class="w-8 h-8 mr-2">
                <span class="truncate">' . htmlspecialchars($file) . '</span>
            </div>
            <div class="mt-2 flex space-x-2">
                ' . $this->generateActions($file, $path, $is_dir) . '
            </div>
        </div>';
    }

    private function generateActions($file, $path, $is_dir) {
        $actions = '';
        
        if ($is_dir) {
            $actions .= '<a href="?dir=' . urlencode($path) . '" class="text-blue-500 hover:underline">Open</a>';
        } else {
            $actions .= '<a href="?action=download&file=' . urlencode($path) . '" class="text-green-500 hover:underline">Download</a>';
        }
        
        $actions .= '<button onclick="deleteFile(\'' . addslashes($path) . '\')" class="text-red-500 hover:underline">Delete</button>';
        
        return $actions;
    }
}

$fileManager = new FileManager();
?>

<div class="bg-white rounded-lg shadow-lg p-6">
    <h2 class="text-2xl font-bold mb-4">File Manager</h2>
    
    <!-- Upload Form -->
    <form action="upload.php" method="post" enctype="multipart/form-data" class="mb-6">
        <div class="flex items-center space-x-4">
            <input type="file" name="file" class="border p-2 rounded">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Upload
            </button>
        </div>
    </form>

    <!-- File Listing -->
    <?php echo $fileManager->listFiles(); ?>
</div> 