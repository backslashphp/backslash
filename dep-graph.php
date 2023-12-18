<?php

declare(strict_types=1);

function rglob(string $pattern, int $flags = 0): array
{
    $files = glob($pattern, $flags);
    foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
        $files = array_merge($files, rglob($dir . '/' . basename($pattern), $flags));
    }
    return $files;
}

$deps = [];
foreach (rglob('./src/*.php') as $file) {
    $code = file_get_contents($file);
    $matches = [];
    preg_match('/namespace Backslash\\\\(.*);/', $code, $matches);
    $component = $matches[1];
    $component = explode('\\', $component)[0];
    if (!isset($deps[$component])) {
        $deps[$component] = [];
    }

    preg_match_all('/use Backslash\\\\(.*);/', $code, $matches);
    if (count($matches) < 2) {
        continue;
    }
    foreach ($matches[1] as $match) {
        $dep = substr($match, 0, strpos($match, '\\'));
        if ($component != $dep) {
            $deps[$component][$dep] = $dep;
        }
    }
}

$dot = [];
foreach ($deps as $component => $componentDeps) {
    $dot[] = '    ' . $component;
    foreach ($componentDeps as $dep) {
        $dot[] = sprintf('    %s -> %s', $component, $dep);
    }
}
sort($dot);

echo 'digraph {' . PHP_EOL;
echo implode(PHP_EOL, $dot) . PHP_EOL;
echo '}' . PHP_EOL;
