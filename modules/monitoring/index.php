<?php
if (!defined('AUTHORIZED')) {
    die('Direct access not permitted');
}

class SystemMonitoring {
    public function getCPUUsage() {
        $load = sys_getloadavg();
        return $load[0];
    }

    public function getMemoryUsage() {
        $free = shell_exec('free');
        $free = (string)trim($free);
        $free_arr = explode("\n", $free);
        $mem = explode(" ", $free_arr[1]);
        $mem = array_filter($mem);
        $mem = array_merge($mem);
        $memory_usage = $mem[2]/$mem[1]*100;
        return round($memory_usage, 2);
    }

    public function getDiskUsage() {
        $disktotal = disk_total_space('/');
        $diskfree = disk_free_space('/');
        $diskuse = round(($disktotal - $diskfree) / $disktotal * 100, 2);
        return $diskuse;
    }

    public function getRunningProcesses() {
        $processes = [];
        exec('ps aux', $output);
        foreach ($output as $line) {
            $processes[] = preg_split('/\s+/', trim($line));
        }
        return $processes;
    }
}

$monitoring = new SystemMonitoring();
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <!-- CPU Usage -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-2">CPU Usage</h3>
        <div class="relative pt-1">
            <?php $cpu = $monitoring->getCPUUsage(); ?>
            <div class="flex mb-2 items-center justify-between">
                <div>
                    <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-blue-600 bg-blue-200">
                        Load: <?php echo $cpu; ?>
                    </span>
                </div>
            </div>
            <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-blue-200">
                <div style="width:<?php echo min($cpu * 100 / 4, 100); ?>%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-blue-500"></div>
            </div>
        </div>
    </div>

    <!-- Memory Usage -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-2">Memory Usage</h3>
        <div class="relative pt-1">
            <?php $memory = $monitoring->getMemoryUsage(); ?>
            <div class="flex mb-2 items-center justify-between">
                <div>
                    <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-green-600 bg-green-200">
                        <?php echo $memory; ?>%
                    </span>
                </div>
            </div>
            <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-green-200">
                <div style="width:<?php echo $memory; ?>%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-green-500"></div>
            </div>
        </div>
    </div>

    <!-- Disk Usage -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-2">Disk Usage</h3>
        <div class="relative pt-1">
            <?php $disk = $monitoring->getDiskUsage(); ?>
            <div class="flex mb-2 items-center justify-between">
                <div>
                    <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-yellow-600 bg-yellow-200">
                        <?php echo $disk; ?>%
                    </span>
                </div>
            </div>
            <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-yellow-200">
                <div style="width:<?php echo $disk; ?>%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-yellow-500"></div>
            </div>
        </div>
    </div>
</div>

<!-- Process List -->
<div class="bg-white rounded-lg shadow-lg p-6">
    <h2 class="text-2xl font-bold mb-4">Running Processes</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">User</th>
                    <th class="px-6 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">PID</th>
                    <th class="px-6 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">CPU %</th>
                    <th class="px-6 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Memory %</th>
                    <th class="px-6 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Command</th>
                </tr>
            </thead>
            <tbody class="bg-white">
                <?php
                $processes = $monitoring->getRunningProcesses();
                array_shift($processes); // Remove header
                foreach (array_slice($processes, 0, 10) as $process) {
                    echo "<tr>";
                    echo "<td class='px-6 py-4 whitespace-nowrap border-b border-gray-200'>" . htmlspecialchars($process[0]) . "</td>";
                    echo "<td class='px-6 py-4 whitespace-nowrap border-b border-gray-200'>" . htmlspecialchars($process[1]) . "</td>";
                    echo "<td class='px-6 py-4 whitespace-nowrap border-b border-gray-200'>" . htmlspecialchars($process[2]) . "</td>";
                    echo "<td class='px-6 py-4 whitespace-nowrap border-b border-gray-200'>" . htmlspecialchars($process[3]) . "</td>";
                    echo "<td class='px-6 py-4 whitespace-nowrap border-b border-gray-200'>" . htmlspecialchars($process[10]) . "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Update stats every 30 seconds
setInterval(() => {
    fetch('modules/monitoring/update-stats.php')
        .then(response => response.json())
        .then(data => {
            // Update the UI with new values
            updateStats(data);
        });
}, 30000);

function updateStats(data) {
    // Update CPU, Memory, and Disk usage displays
    // Implementation details here
}
</script> 