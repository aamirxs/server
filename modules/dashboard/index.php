<?php
if (!defined('AUTHORIZED')) {
    define('AUTHORIZED', true);
}

class Dashboard {
    private $db;

    public function __construct() {
        $this->db = connectDB();
    }

    public function getSystemStats() {
        $stats = [
            'cpu' => $this->getCPUUsage(),
            'memory' => $this->getMemoryUsage(),
            'disk' => $this->getDiskUsage(),
            'uptime' => $this->getUptime(),
            'load_average' => sys_getloadavg()
        ];
        return $stats;
    }

    private function getCPUUsage() {
        $load = sys_getloadavg();
        return $load[0];
    }

    private function getMemoryUsage() {
        $free = shell_exec('free');
        $free = (string)trim($free);
        $free_arr = explode("\n", $free);
        $mem = explode(" ", $free_arr[1]);
        $mem = array_filter($mem);
        $mem = array_merge($mem);
        return [
            'total' => round($mem[1] / 1024, 2),
            'used' => round($mem[2] / 1024, 2),
            'free' => round($mem[3] / 1024, 2),
            'percentage' => round($mem[2]/$mem[1]*100, 2)
        ];
    }

    private function getDiskUsage() {
        $disktotal = disk_total_space('/');
        $diskfree = disk_free_space('/');
        $diskused = $disktotal - $diskfree;
        return [
            'total' => round($disktotal / 1024 / 1024 / 1024, 2),
            'used' => round($diskused / 1024 / 1024 / 1024, 2),
            'free' => round($diskfree / 1024 / 1024 / 1024, 2),
            'percentage' => round(($diskused / $disktotal) * 100, 2)
        ];
    }

    private function getUptime() {
        $uptime = shell_exec('uptime -p');
        return trim($uptime);
    }
}

$page_title = "Dashboard";
require_once '../../includes/header.php';

$dashboard = new Dashboard();
$stats = $dashboard->getSystemStats();
?>

<div class="space-y-6">
    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- CPU Usage -->
        <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-microchip text-blue-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">CPU Usage</h3>
                        <div class="flex items-center">
                            <span class="text-2xl font-bold text-gray-900" id="cpu-usage"><?php echo $stats['cpu']; ?>%</span>
                            <span class="ml-2 text-sm text-green-500" id="cpu-trend">
                                <i class="fas fa-arrow-up"></i> 2.5%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="relative pt-1">
                <div class="overflow-hidden h-2 text-xs flex rounded bg-blue-100">
                    <div id="cpu-bar" 
                         style="width:<?php echo $stats['cpu']; ?>%" 
                         class="animate-pulse shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-blue-500">
                    </div>
                </div>
            </div>
        </div>

        <!-- Memory Usage -->
        <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                        <i class="fas fa-memory text-green-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">Memory Usage</h3>
                        <div class="flex items-center">
                            <span class="text-2xl font-bold text-gray-900" id="memory-usage">
                                <?php echo $stats['memory']['percentage']; ?>%
                            </span>
                            <span class="ml-2 text-xs text-gray-500">
                                <?php echo round($stats['memory']['used']); ?>GB / <?php echo round($stats['memory']['total']); ?>GB
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="relative pt-1">
                <div class="overflow-hidden h-2 text-xs flex rounded bg-green-100">
                    <div id="memory-bar" 
                         style="width:<?php echo $stats['memory']['percentage']; ?>%" 
                         class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-green-500">
                    </div>
                </div>
            </div>
        </div>

        <!-- Disk Usage -->
        <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center">
                        <i class="fas fa-hdd text-purple-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">Disk Usage</h3>
                        <div class="flex items-center">
                            <span class="text-2xl font-bold text-gray-900" id="disk-usage">
                                <?php echo $stats['disk']['percentage']; ?>%
                            </span>
                            <span class="ml-2 text-xs text-gray-500">
                                <?php echo round($stats['disk']['used']); ?>GB / <?php echo round($stats['disk']['total']); ?>GB
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="relative pt-1">
                <div class="overflow-hidden h-2 text-xs flex rounded bg-purple-100">
                    <div id="disk-bar" 
                         style="width:<?php echo $stats['disk']['percentage']; ?>%" 
                         class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-purple-500">
                    </div>
                </div>
            </div>
        </div>

        <!-- Network Traffic -->
        <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                        <i class="fas fa-network-wired text-red-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">Network Traffic</h3>
                        <div class="flex flex-col">
                            <div class="flex items-center">
                                <i class="fas fa-arrow-down text-green-500 mr-1"></i>
                                <span class="text-sm" id="network-in">0 Mbps</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-arrow-up text-blue-500 mr-1"></i>
                                <span class="text-sm" id="network-out">0 Mbps</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="network-chart" class="h-10"></div>
        </div>
    </div>

    <!-- Quick Actions & System Info -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Quick Actions -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <button onclick="location.href='../file-manager'" 
                            class="p-4 rounded-lg bg-blue-50 hover:bg-blue-100 transition-colors group">
                        <i class="fas fa-folder text-2xl text-blue-500 group-hover:scale-110 transition-transform"></i>
                        <span class="block mt-2 text-sm text-gray-600">File Manager</span>
                    </button>
                    
                    <button onclick="location.href='../terminal'" 
                            class="p-4 rounded-lg bg-green-50 hover:bg-green-100 transition-colors group">
                        <i class="fas fa-terminal text-2xl text-green-500 group-hover:scale-110 transition-transform"></i>
                        <span class="block mt-2 text-sm text-gray-600">Terminal</span>
                    </button>
                    
                    <button onclick="location.href='../backup'" 
                            class="p-4 rounded-lg bg-purple-50 hover:bg-purple-100 transition-colors group">
                        <i class="fas fa-database text-2xl text-purple-500 group-hover:scale-110 transition-transform"></i>
                        <span class="block mt-2 text-sm text-gray-600">Backup</span>
                    </button>
                    
                    <button onclick="location.href='../monitoring'" 
                            class="p-4 rounded-lg bg-red-50 hover:bg-red-100 transition-colors group">
                        <i class="fas fa-chart-line text-2xl text-red-500 group-hover:scale-110 transition-transform"></i>
                        <span class="block mt-2 text-sm text-gray-600">Monitoring</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">System Information</h3>
            <div class="space-y-4">
                <div>
                    <label class="text-sm text-gray-500">Operating System</label>
                    <p class="text-gray-900"><?php echo php_uname('s') . ' ' . php_uname('r'); ?></p>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Server Software</label>
                    <p class="text-gray-900"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
                </div>
                <div>
                    <label class="text-sm text-gray-500">PHP Version</label>
                    <p class="text-gray-900"><?php echo PHP_VERSION; ?></p>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Server Time</label>
                    <p class="text-gray-900" id="server-time"></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Real-time updates
function updateStats() {
    fetch('update-stats.php')
        .then(response => response.json())
        .then(data => {
            // Update CPU
            document.getElementById('cpu-usage').textContent = data.cpu + '%';
            document.getElementById('cpu-bar').style.width = data.cpu + '%';

            // Update Memory
            document.getElementById('memory-usage').textContent = data.memory.percentage + '%';
            document.getElementById('memory-bar').style.width = data.memory.percentage + '%';

            // Update Disk
            document.getElementById('disk-usage').textContent = data.disk.percentage + '%';
            document.getElementById('disk-bar').style.width = data.disk.percentage + '%';

            // Update Network
            document.getElementById('network-in').textContent = data.network.in + ' Mbps';
            document.getElementById('network-out').textContent = data.network.out + ' Mbps';
        });
}

// Update server time
function updateTime() {
    const now = new Date();
    document.getElementById('server-time').textContent = now.toLocaleString();
}

// Initialize updates
setInterval(updateStats, 5000);
setInterval(updateTime, 1000);
updateTime();

// Initialize network chart
const ctx = document.getElementById('network-chart').getContext('2d');
const networkChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: Array(20).fill(''),
        datasets: [{
            label: 'Network In',
            data: Array(20).fill(0),
            borderColor: 'rgb(34, 197, 94)',
            tension: 0.4,
            fill: false
        }, {
            label: 'Network Out',
            data: Array(20).fill(0),
            borderColor: 'rgb(59, 130, 246)',
            tension: 0.4,
            fill: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                display: false
            },
            x: {
                display: false
            }
        },
        elements: {
            point: {
                radius: 0
            }
        }
    }
});

// Update network chart
function updateNetworkChart(inSpeed, outSpeed) {
    networkChart.data.datasets[0].data.shift();
    networkChart.data.datasets[0].data.push(inSpeed);
    networkChart.data.datasets[1].data.shift();
    networkChart.data.datasets[1].data.push(outSpeed);
    networkChart.update();
}
</script>

<?php require_once '../../includes/footer.php'; ?> 