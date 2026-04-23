<?php
$_SESSION = [];
session_destroy();
header('Location: ?page=landing');
exit;
