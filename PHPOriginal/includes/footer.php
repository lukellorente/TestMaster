    </main>

    <!-- Footer -->
    <footer>
        <p> <img src="/TestMaster/assets/images/logo2.png" alt="TestMaster Logo" style="height: 80px; vertical-align: middle;"> © <?= date('Y') ?></p>
        <div class="footer-links">
            <a href="#">ayuda</a>
            <a href="#">privacidad</a>
            <a href="#">términos</a>
            <a href="#">contacto</a>
        </div>
    </footer>

    <!-- Theme Toggle Script -->
    <script>
        function toggleTheme() {
            const body = document.body;
            body.classList.toggle('dark-mode');
            
            if (body.classList.contains('dark-mode')) {
                localStorage.setItem('theme', 'dark');
            } else {
                localStorage.setItem('theme', 'light');
            }
        }

        // Aplicar tema guardado al cargar
        (function() {
            const theme = localStorage.getItem('theme');
            if (theme === 'dark') {
                document.body.classList.add('dark-mode');
            }
        })();
    </script>
</body>
</html>