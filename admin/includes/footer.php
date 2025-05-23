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

        // Custom scripts untuk admin
        document.addEventListener('DOMContentLoaded', function() {
            // Animasi untuk kartu statistik
            const statsCards = document.querySelectorAll('.stats-card');
            if (statsCards.length > 0) {
                statsCards.forEach((card, index) => {
                    setTimeout(() => {
                        card.style.opacity = '0';
                        card.style.transform = 'translateY(10px)';
                        card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                        
                        setTimeout(() => {
                            card.style.opacity = '1';
                            card.style.transform = 'translateY(0)';
                        }, 100 + (index * 100));
                    }, 0);
                });
            }
        });
    </script>
</body>
</html> 