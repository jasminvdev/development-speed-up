#!/usr/bin/env php
<?php
/**
 * Magento 2 Module Lister
 * 
 * Lists all Magento 2 modules with their details
 * 
 * Usage:
 *   php list-modules.php                    # List all modules
 *   php list-modules.php --app-only         # Only app/code modules
 *   php list-modules.php --vendor-only      # Only vendor modules
 *   php list-modules.php --enabled          # Only enabled modules
 *   php list-modules.php --disabled         # Only disabled modules
 *   php list-modules.php --json             # Output as JSON
 *   php list-modules.php --html             # Output as HTML table
 */

// Get Magento root directory
$magentoRoot = __DIR__;
$appCodePath = $magentoRoot . '/app/code';
$vendorPath = $magentoRoot . '/vendor';
$configPath = $magentoRoot . '/app/etc/config.php';

// Parse command line arguments
$options = [
    'app-only' => in_array('--app-only', $argv),
    'vendor-only' => in_array('--vendor-only', $argv),
    'enabled' => in_array('--enabled', $argv),
    'disabled' => in_array('--disabled', $argv),
    'json' => in_array('--json', $argv),
    'html' => in_array('--html', $argv),
    'help' => in_array('--help', $argv) || in_array('-h', $argv)
];

if ($options['help']) {
    echo "Magento 2 Module Lister\n\n";
    echo "Usage: php list-modules.php [options]\n\n";
    echo "Options:\n";
    echo "  --app-only      List only modules from app/code\n";
    echo "  --vendor-only   List only modules from vendor\n";
    echo "  --enabled       List only enabled modules\n";
    echo "  --disabled      List only disabled modules\n";
    echo "  --json          Output as JSON\n";
    echo "  --html          Output as HTML table\n";
    echo "  --help, -h      Show this help message\n\n";
    exit(0);
}

/**
 * Get module configuration from config.php
 */
function getModuleConfig($configPath) {
    if (!file_exists($configPath)) {
        return [];
    }
    
    $config = include $configPath;
    return isset($config['modules']) ? $config['modules'] : [];
}

/**
 * Get module details from module.xml
 */
function getModuleDetails($modulePath) {
    $moduleXmlPath = $modulePath . '/etc/module.xml';
    $details = [
        'name' => '',
        'setup_version' => '',
        'sequence' => []
    ];
    
    if (!file_exists($moduleXmlPath)) {
        return $details;
    }
    
    $xml = @simplexml_load_file($moduleXmlPath);
    if ($xml === false) {
        return $details;
    }
    
    if (isset($xml->module)) {
        $module = $xml->module;
        $details['name'] = (string)$module['name'];
        $details['setup_version'] = isset($module['setup_version']) ? (string)$module['setup_version'] : '';
        
        if (isset($xml->module->sequence->module)) {
            foreach ($xml->module->sequence->module as $dep) {
                $details['sequence'][] = (string)$dep['name'];
            }
        }
    }
    
    return $details;
}

/**
 * Get composer.json details if available
 */
function getComposerDetails($modulePath) {
    $composerPath = $modulePath . '/composer.json';
    $details = [
        'version' => '',
        'description' => '',
        'authors' => []
    ];
    
    if (!file_exists($composerPath)) {
        return $details;
    }
    
    $composer = @json_decode(file_get_contents($composerPath), true);
    if ($composer === null) {
        return $details;
    }
    
    $details['version'] = isset($composer['version']) ? $composer['version'] : '';
    $details['description'] = isset($composer['description']) ? $composer['description'] : '';
    $details['authors'] = isset($composer['authors']) ? $composer['authors'] : [];
    
    return $details;
}

/**
 * Scan directory for modules
 */
function scanModules($basePath, $moduleConfig = []) {
    $modules = [];
    
    if (!is_dir($basePath)) {
        return $modules;
    }
    
    $vendors = glob($basePath . '/*', GLOB_ONLYDIR);
    
    foreach ($vendors as $vendorPath) {
        $vendorName = basename($vendorPath);
        $moduleDirs = glob($vendorPath . '/*', GLOB_ONLYDIR);
        
        foreach ($moduleDirs as $modulePath) {
            $moduleName = basename($modulePath);
            $fullModuleName = $vendorName . '_' . $moduleName;
            
            // Check if it's a module (has etc/module.xml)
            $moduleXmlPath = $modulePath . '/etc/module.xml';
            if (!file_exists($moduleXmlPath)) {
                continue;
            }
            
            $moduleDetails = getModuleDetails($modulePath);
            $composerDetails = getComposerDetails($modulePath);
            
            $module = [
                'name' => $fullModuleName,
                'vendor' => $vendorName,
                'module' => $moduleName,
                'path' => $modulePath,
                'enabled' => isset($moduleConfig[$fullModuleName]) ? (bool)$moduleConfig[$fullModuleName] : null,
                'setup_version' => $moduleDetails['setup_version'],
                'composer_version' => $composerDetails['version'],
                'description' => $composerDetails['description'],
                'dependencies' => $moduleDetails['sequence'],
                'authors' => $composerDetails['authors']
            ];
            
            $modules[$fullModuleName] = $module;
        }
    }
    
    return $modules;
}

// Get module configuration
$moduleConfig = getModuleConfig($configPath);

// Scan modules
$allModules = [];

if (!$options['vendor-only']) {
    $appModules = scanModules($appCodePath, $moduleConfig);
    $allModules = array_merge($allModules, $appModules);
}

if (!$options['app-only']) {
    $vendorModules = scanModules($vendorPath, $moduleConfig);
    $allModules = array_merge($allModules, $vendorModules);
}

// Filter by enabled/disabled
if ($options['enabled']) {
    $allModules = array_filter($allModules, function($module) {
        return $module['enabled'] === true;
    });
}

if ($options['disabled']) {
    $allModules = array_filter($allModules, function($module) {
        return $module['enabled'] === false;
    });
}

// Sort by module name
ksort($allModules);

// Output results
if ($options['json']) {
    header('Content-Type: application/json');
    echo json_encode(array_values($allModules), JSON_PRETTY_PRINT);
    exit(0);
}

if ($options['html']) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html>\n<html><head><title>Magento Modules</title>\n";
    echo "<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .enabled { color: green; font-weight: bold; }
        .disabled { color: red; font-weight: bold; }
        .unknown { color: gray; }
    </style></head><body>\n";
    echo "<h1>Magento 2 Modules (" . count($allModules) . ")</h1>\n";
    echo "<table>\n";
    echo "<tr><th>Module Name</th><th>Vendor</th><th>Status</th><th>Setup Version</th><th>Composer Version</th><th>Description</th></tr>\n";
    
    foreach ($allModules as $module) {
        $statusClass = $module['enabled'] === true ? 'enabled' : ($module['enabled'] === false ? 'disabled' : 'unknown');
        $statusText = $module['enabled'] === true ? 'Enabled' : ($module['enabled'] === false ? 'Disabled' : 'Unknown');
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($module['name']) . "</td>";
        echo "<td>" . htmlspecialchars($module['vendor']) . "</td>";
        echo "<td class='$statusClass'>" . htmlspecialchars($statusText) . "</td>";
        echo "<td>" . htmlspecialchars($module['setup_version'] ?: '-') . "</td>";
        echo "<td>" . htmlspecialchars($module['composer_version'] ?: '-') . "</td>";
        echo "<td>" . htmlspecialchars($module['description'] ?: '-') . "</td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n</body></html>\n";
    exit(0);
}

// CLI output
echo "Magento 2 Modules List\n";
echo str_repeat("=", 80) . "\n\n";
echo sprintf("%-50s %-15s %-10s %-15s\n", "Module Name", "Vendor", "Status", "Version");
echo str_repeat("-", 80) . "\n";

foreach ($allModules as $module) {
    $status = $module['enabled'] === true ? 'Enabled' : ($module['enabled'] === false ? 'Disabled' : 'Unknown');
    $version = $module['setup_version'] ?: $module['composer_version'] ?: '-';
    
    printf("%-50s %-15s %-10s %-15s\n", 
        $module['name'],
        $module['vendor'],
        $status,
        $version
    );
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "Total modules: " . count($allModules) . "\n";

// Statistics
$enabledCount = count(array_filter($allModules, function($m) { return $m['enabled'] === true; }));
$disabledCount = count(array_filter($allModules, function($m) { return $m['enabled'] === false; }));
$appCount = count(array_filter($allModules, function($m) { return strpos($m['path'], '/app/code/') !== false; }));
$vendorCount = count(array_filter($allModules, function($m) { return strpos($m['path'], '/vendor/') !== false; }));

echo "Enabled: $enabledCount | Disabled: $disabledCount\n";
echo "App modules: $appCount | Vendor modules: $vendorCount\n";
