<?php
define('WS_SERVER', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "wss" : "ws") . "://" . $_SERVER['HTTP_HOST'] . "/ws");