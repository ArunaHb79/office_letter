<?php
/**
 * Reusable Footer Component
 * Use this component across all pages for consistent footer styling
 */

function render_system_footer($fixed = false) {
    $fixed_class = $fixed ? 'fixed-bottom' : '';
    ?>
    <div class="links-section">
        <a href="register.php"><i class="fas fa-user-plus"></i> Create Account</a>
        <span class="text-muted mx-2">|</span>
        <a href="reset_password.php"><i class="fas fa-question-circle"></i> Forgot Password?</a>
        <div class="mt-3">
            <p class="mb-0 text-muted">
                <i class="fas fa-copyright"></i> <?php echo date('Y'); ?> <?php echo APP_NAME; ?> | Created and Developed By Group No-05
            </p>
        </div>
    </div>
    <?php
}

function render_dashboard_footer() {
    ?>
    <footer class="text-center py-4 mt-5" style="background: rgba(255,255,255,0.95); border-top: 1px solid rgba(0,0,0,0.1); backdrop-filter: blur(10px);">
        <div class="container">
            <p class="mb-0 text-muted">
                <i class="fas fa-copyright"></i> <?php echo date('Y'); ?> <?php echo APP_NAME; ?> | Created and Developed By Group No-05
            </p>
        </div>
    </footer>
    <?php
}
?>
