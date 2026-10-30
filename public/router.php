<?php
declare(strict_types=1);
$path=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)?:'/';
$file=__DIR__.$path;
if($path!=='/' && is_file($file)) return false;
// Read the demo-mode switch from the local .env without loading the database bootstrap.
$envFile=dirname(__DIR__).'/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line) {
        $line=trim($line);
        if ($line==='' || str_starts_with($line,'#') || !str_contains($line,'=')) continue;
        [$key,$value]=explode('=',$line,2);
        if (trim($key)==='APP_DEMO_MODE') putenv('APP_DEMO_MODE='.trim($value," \t\n\r\0\x0B\"'"));
    }
}
if (filter_var(getenv('APP_DEMO_MODE') ?: '0', FILTER_VALIDATE_BOOLEAN)) {
    require __DIR__.'/demo.php';
    return true;
}
require __DIR__.'/index.php';
