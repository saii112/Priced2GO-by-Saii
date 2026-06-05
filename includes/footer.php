</main>
<footer class="bg-white border-top mt-5 py-4">
    <div class="container text-center text-muted small">
        &copy; 2026 Priced2GO. Your trusted C2C marketplace.
        <!-- support link -->
        <div class="mt-1">
            <a href="<?= isset($isAdmin) ? '../' : '' ?>support.php" class="text-decoration-none">Customer Support</a>
        </div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= isset($isAdmin) ? '../' : '' ?>js/main.js"></script>
</body>
</html>
