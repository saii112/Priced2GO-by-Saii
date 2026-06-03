<?php
session_start();
session_destroy();
header('Location: /priced2go/index.php');
exit;