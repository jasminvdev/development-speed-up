## Magento 2 Dev Utilities

This repository contains a small collection of **PHP helper scripts and notes** to speed up common Magento 2 development and maintenance tasks.

### Contents

- **`magento2_utility_commands.txt`**: Curated list of useful Magento 2, MySQL, Nginx and server commands and snippets (deployment, backups, permissions, cron, SSH, etc.).
- **`find_replace.php`**: Recursive **search and replace** script for updating strings inside a Magento module (or any PHP project directory).
- **`module_rename.php`**: Utility to **rename a Magento 2 module** (vendor/module, namespaces, layout XML filenames, and folder structure).
- **`cms_translated_line.php`**: Script to **extract all `{{trans ""}}` strings** from CMS pages and blocks into a text file.
- **`getTransBy_database.php`**: Script to **scan CMS pages/blocks**, wrap plain text in `{{trans ""}}`, and update DB content safely.

### Requirements

- PHP 7.4+ (CLI)
- Access to your Magento 2 project files (for file-based scripts)
- MySQL/MariaDB access for CMS-related scripts (`cms_translated_line.php`, `getTransBy_database.php`)

### Usage Overview

- **Do a full file/database backup before running any script.**
- Run scripts from the command line:

```bash
cd /path/to/this/repo
php script_name.php
```

For detailed, step‑by‑step instructions see **`USER_GUIDE.md`**.


