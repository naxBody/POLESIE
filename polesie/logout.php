<?php
/**
 * Выход из системы
 */

require_once '../config/config.php';
session_start();

logout();
redirect('login.php');
