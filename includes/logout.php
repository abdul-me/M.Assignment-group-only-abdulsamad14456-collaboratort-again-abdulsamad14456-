<?php
/**
 * Logout Handler
 */

require_once __DIR__ . '/auth.php';

// Destroy session and redirect to project root index
destroySession();
?>
