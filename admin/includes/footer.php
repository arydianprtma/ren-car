            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-4 mt-auto">
        <div class="container mx-auto px-6 text-center">
            <p>&copy; <?= date('Y') ?> Rental Mobil - Sistem Manajemen Armada. All rights reserved.</p>
        </div>
    </footer>

    <!-- JS Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        // Sidebar toggle
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            
            sidebar.classList.toggle('-translate-x-full');
            
            if (sidebar.classList.contains('-translate-x-full')) {
                overlay.classList.add('hidden');
            } else {
                overlay.classList.remove('hidden');
            }
        });
        
        // Overlay click untuk menutup sidebar
        document.getElementById('sidebar-overlay').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        });
        
        // Auto-hide flash messages after 3 seconds
        setTimeout(function() {
            const alert = document.querySelector('[role="alert"]');
            if (alert) {
                alert.style.transition = 'opacity 1s';
                alert.style.opacity = 0;
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 1000);
            }
        }, 3000);
        
        // Close alert on click
        const closeButtons = document.querySelectorAll('.alert-close');
        closeButtons.forEach(button => {
            button.addEventListener('click', () => {
                const alert = button.closest('[role="alert"]');
                alert.style.display = 'none';
            });
        });
    </script>
</body>
</html> 