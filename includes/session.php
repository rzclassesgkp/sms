<?php
if (session_status() === PHP_SESSION_NONE) {
    // Session settings
    ini_set('session.gc_maxlifetime', 1800); // 30 minutes
    session_set_cookie_params(1800);
    session_start();
}
