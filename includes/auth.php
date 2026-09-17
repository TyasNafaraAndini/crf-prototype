<?php

require_once __DIR__ . '/session.php';

/**
 * Memastikan user sudah login.
 */
function requireLogin(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit;
    }
}