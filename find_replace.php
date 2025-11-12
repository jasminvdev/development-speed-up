<?php
$targetDir = __DIR__ . '/InvigorateSystems/Popup'; 
$find = '2020-2022';          
$replace = '2015-2025';      

function replaceInFiles($dir, $find, $replace) {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

    foreach ($rii as $file) {
        if ($file->isDir()) continue;

        $path = $file->getPathname();
        $contents = file_get_contents($path);

        if (strpos($contents, $find) !== false) {
            $newContents = str_replace($find, $replace, $contents);
            file_put_contents($path, $newContents);
            echo "Replaced in: $path\n";
        }
    }
}

// Run
replaceInFiles($targetDir, $find, $replace);

echo "Find and replace complete.\n";
