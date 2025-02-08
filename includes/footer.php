            </main>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div id="toast-container" class="fixed bottom-4 right-4 space-y-2"></div>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-4 rounded-lg shadow-lg flex items-center">
            <div class="loading-spinner mr-3 w-6 h-6 border-2 border-gray-300 border-t-blue-500 rounded-full"></div>
            <span>Loading...</span>
        </div>
    </div>

    <!-- Global Scripts -->
    <script>
    // Toast notification system
    const toast = {
        show(message, type = 'info') {
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                info: 'bg-blue-500',
                warning: 'bg-yellow-500'
            };

            const toast = document.createElement('div');
            toast.className = `${colors[type]} text-white p-4 rounded-lg shadow-lg flex items-center justify-between`;
            toast.innerHTML = `
                <span>${message}</span>
                <button onclick="this.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            `;

            document.getElementById('toast-container').appendChild(toast);
            setTimeout(() => toast.remove(), 5000);
        }
    };

    // Loading overlay
    const loading = {
        show() {
            document.getElementById('loading-overlay').classList.remove('hidden');
        },
        hide() {
            document.getElementById('loading-overlay').classList.add('hidden');
        }
    };

    // Responsive sidebar
    document.getElementById('menu-toggle').addEventListener('click', () => {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('-translate-x-full');
    });

    // Theme switcher
    const theme = {
        toggle() {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', 
                document.documentElement.classList.contains('dark') ? 'dark' : 'light'
            );
        },
        init() {
            if (localStorage.theme === 'dark' || 
                (!('theme' in localStorage) && 
                window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        }
    };
    theme.init();
    </script>
</body>
</html> 