<?php
declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'AutoLoc\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $parts = explode('\\', $relativeClass);
    $parts[0] = strtolower($parts[0]);
    $file = __DIR__ . '/' . implode('/', $parts) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});
