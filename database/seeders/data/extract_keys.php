<?php

$root = dirname(__DIR__, 3);
$dirs = [$root . '/resources/views', $root . '/app'];
$keys = [];

foreach ($dirs as $dir) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.php')) {
            continue;
        }
        $content = file_get_contents($file->getPathname());
        if (preg_match_all("/__\(['\"]([^'\"]+)['\"]/", $content, $m)) {
            foreach ($m[1] as $key) {
                if (! str_contains($key, ' ') && ! str_contains($key, 'Delete') && ! str_contains($key, 'Email') && ! str_contains($key, 'Password') && ! str_contains($key, 'Cancel') && ! str_contains($key, 'Forgot') && ! str_contains($key, 'Reset') && ! str_contains($key, 'Are you sure')) {
                    $keys[$key] = true;
                }
            }
        }
    }
}

ksort($keys);
foreach (array_keys($keys) as $k) {
    echo $k . PHP_EOL;
}
