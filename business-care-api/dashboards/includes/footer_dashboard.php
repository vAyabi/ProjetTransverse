</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        const currentPage = window.location.pathname;
        $('.nav-link').each(function() {
            if (currentPage.includes($(this).attr('href'))) {
                $(this).addClass('active');
            }
        });
    });
    </script>
</body>
</html>