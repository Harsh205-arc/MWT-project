<?php
// includes/footer.php
require_once __DIR__ . '/helpers.php';
startSession();
$currentUser = getCurrentUser();
?>
<?php if ($currentUser): ?>
    </main><!-- .main-content -->
</div><!-- .layout -->
<?php else: ?>
</main><!-- .auth-page -->
<?php endif; ?>

<script src="/roomatehub/assets/js/main.js"></script>
</body>
</html>
