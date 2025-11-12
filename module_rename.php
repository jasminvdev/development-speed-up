<?php

$oldVendor = 'VendorName'; // Old Vendor
$oldModule = 'ModuleName'; // Old Module
$newVendor = 'InvigorateSystems'; // New Vendor
$newModule = 'Popup'; // New Module

// $moduleBasePath = __DIR__ ."/InvigorateSystems/" . $oldModule;
$moduleBasePath = __DIR__ . "/" . $oldVendor . "/" . $oldModule;
// Lowercase formats for layout and references
$oldVendorLower = strtolower($oldVendor);
$oldModuleLower = strtolower($oldModule);
$newVendorLower = strtolower($newVendor);
$newModuleLower = strtolower($newModule);
$OldShortName  = "some_";
$NewShortName  = "invi_";

// Function to recursively replace content inside files
function recursiveReplace($dir, $oldVendor, $oldModule, $newVendor, $newModule, $OldShortName, $NewShortName){
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

    foreach ($rii as $file) {
        if ($file->isDir()) continue;

        $path = $file->getPathname();
        $contents = file_get_contents($path);

        // Replace various namespace and string references
        $replacements = [
            "$oldVendor\\$oldModule" => "$newVendor\\$newModule",
            "{$oldVendor}_{$oldModule}" => "{$newVendor}_{$newModule}",
            "{$OldShortName}" => "{$NewShortName}",
            strtolower($oldVendor . '_' . $oldModule) => strtolower($newVendor . '_' . $newModule),
            "$oldVendor/$oldModule" => "$newVendor/$newModule",
        ];

        $newContents = str_replace(array_keys($replacements), array_values($replacements), $contents);

        if ($contents !== $newContents) {
            file_put_contents($path, $newContents);
        }
    }
}

// Function to rename layout XML files (e.g., bss_popup_popup_edit.xml)
function renameLayoutFiles($dir, $oldVendorLower, $oldModuleLower, $newVendorLower, $newModuleLower) {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

    foreach ($rii as $file) {
        if ($file->isDir()) continue;

        $filename = $file->getFilename();
        $filepath = $file->getPathname();

        // Match pattern: bss_popup_*.xml
        $prefix = $oldVendorLower . '_' . $oldModuleLower . '_';
        if (str_starts_with($filename, $prefix) && str_ends_with($filename, '.xml')) {
            $newFilename = str_replace(
                $prefix,
                $newVendorLower . '_' . $newModuleLower . '_',
                $filename
            );

            $newPath = $file->getPath() . DIRECTORY_SEPARATOR . $newFilename;

            rename($filepath, $newPath);
        }
    }
}

// Step 1: Replace text content in all files
recursiveReplace($moduleBasePath, $oldVendor, $oldModule, $newVendor, $newModule, $OldShortName, $NewShortName);

// Step 2: Rename layout files
renameLayoutFiles($moduleBasePath, $oldVendorLower, $oldModuleLower, $newVendorLower, $newModuleLower);

// Step 3: Rename folder structure
$oldPath = "$oldVendor/$oldModule";
$newPath = "$newVendor/$newModule";

if (!is_dir("$newVendor")) {
    mkdir("$newVendor", 0777, true);
}
rename($oldPath, $newPath);

$oldVendorDir = "$oldVendor";
if (is_dir($oldVendorDir) && count(scandir($oldVendorDir)) == 2) {
    rmdir($oldVendorDir);
}

echo "Module renamed from $oldVendor/$oldModule to $newVendor/$newModule\n";
echo "Layout files updated.\n";
