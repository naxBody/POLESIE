<?php
/**
 * Выход из системы
 */

require_once __DIR__ . '/config/config.php';
session_start();

logout();
redirect('login.php');
