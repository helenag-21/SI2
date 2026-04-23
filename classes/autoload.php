<?php
// autoload.php — automatické načítavanie tried

spl_autoload_register(function (string $className): void {
    $dirs = [
        __DIR__ . '/entities/',
        __DIR__ . '/repositories/',
        __DIR__ . '/services/',
        __DIR__ . '/controllers/',
        __DIR__ . '/dto/',
    ];

    foreach ($dirs as $dir) {
        // Skús priamu triedu
        $file = $dir . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
        // Skús rozhranie (I prefix)
        $iFile = $dir . 'I' . $className . '.php';
        if (file_exists($iFile)) {
            require_once $iFile;
        }
    }
});
