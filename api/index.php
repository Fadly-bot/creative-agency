<?php
declare(strict_types=1);
// Vercel preview deliberately uses session-backed demo data; it never opens MySQL.
require dirname(__DIR__) . '/public/demo.php';
