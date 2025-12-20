<?php
require_once 'session_helper.php';
start_secure_session();
destroy_session();
header('Location: index.php');
exit;
?>