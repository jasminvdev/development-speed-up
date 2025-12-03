## User Guide – Magento 2 Dev Utilities

This guide explains **what each file does** and **how to use it safely**.

---

### 1. `magento2_utility_commands.txt`

**Purpose**: Reference sheet of useful commands for:

- Magento 2 setup/upgrade, deploy modes, caches, cron
- Database backup/restore
- File backups and permissions
- Nginx virtual host configuration
- SSH/OS utilities

**How to use**:

- Open the file in your editor and **copy‑paste** commands as needed.
- Adjust placeholders like `<user_name>`, `<database_name>`, `<module_name>`, `project_name`, `sitename`, etc. before running.

> This file is **documentation only** – you don’t execute it directly.

---

### 2. `find_replace.php`

**Purpose**: Recursively **find and replace a string** in all files under a target directory.

**Key configuration (top of file)**:

- `$targetDir`: path to the directory you want to scan, e.g. `__DIR__ . '/InvigorateSystems/Popup';`
- `$find`: text to search for.
- `$replace`: replacement text.

**Usage**:

```bash
cd /path/to/this/repo
php find_replace.php
```

The script will:

- Walk all files under `$targetDir`
- Replace all occurrences of `$find` with `$replace`
- Print each file path where replacement happened

**Safety tips**:

- **Always back up** your project before running.
- Consider testing on a **copy** of the module first.
- Start with a **very specific** search string to avoid accidental over‑replacement.

---

### 3. `module_rename.php`

**Purpose**: Semi‑automated **Magento 2 module rename tool**.

It updates:

- Namespaces, vendor/module strings, and short prefixes inside files
- Layout XML file names (e.g. `vendor_module_*.xml` → `newvendor_newmodule_*.xml`)
- Folder structure: `OldVendor/OldModule` → `NewVendor/NewModule`

**Key configuration (top of file)**:

- `$oldVendor`, `$oldModule`: current module vendor/name.
- `$newVendor`, `$newModule`: target vendor/name.
- `$OldShortName`, `$NewShortName`: short prefixes used in code (e.g. `some_` → `invi_`).
- `$moduleBasePath`: base path of the module (defaults to `__DIR__ . "/$oldVendor/$oldModule"`).

**Usage**:

```bash
cd /path/to/this/repo
php module_rename.php
```

The script will:

- Replace vendor/module strings in all files under `$moduleBasePath`
- Rename layout XML files starting with `oldvendor_oldmodule_`
- Move the module directory from `OldVendor/OldModule` to `NewVendor/NewModule`

**Safety tips**:

- Run on a **git‑tracked project** so you can revert easily.
- Ensure the **old path exists** and the **new vendor folder** is correct.
- Double‑check the values for `$OldShortName`/`$NewShortName` – they can affect prefixes in DB or config.

---

### 4. `cms_translated_line.php`

**Purpose**: Scan CMS pages and blocks to **extract all `{{trans ""}}` strings** into a text file (`cms_translated_line.txt`).

**Key configuration (top of file)**:

- `$host`, `$dbname`, `$user`, `$pass`: database connection details.
- `$getCmsPage`, `$getCmsBlock`: booleans to enable scanning of pages/blocks.
- `$cms_page`, `$cms_block`: table names (e.g. `cms_page`, `cms_block`).

**Usage**:

```bash
cd /path/to/this/repo
php cms_translated_line.php
```

The script will:

- Connect to the DB
- Read CMS page/block `content`
- Collect unique strings used in `{{trans "..."}}`
- Save them line‑by‑line to `cms_translated_line.txt`

**Use cases**:

- Preparing translation CSVs
- Auditing which strings are wrapped in `{{trans}}`

---

### 5. `getTransBy_database.php`

**Purpose**: Process CMS pages and blocks so that **plain text content is wrapped with `{{trans ""}}`**, while preserving existing Magento directives.

**Key configuration (top of file)**:

- `$host`, `$dbname`, `$user`, `$pass`: database connection details.
- `$doCmsPage`, `$doCmsBlock`: booleans to enable processing pages/blocks.
- `$cms_page`, `$cms_block`: actual table names in your DB (e.g. `cms_page`, `cms_block`).

**What it does (high level)**:

- Connects to DB and loads CMS page/block content.
- Skips entries based on store assignment logic (e.g. shared across all stores or specific count).
- Temporarily replaces Magento directives (`{{ ... }}`) with placeholders.
- Loads HTML with `DOMDocument` and:
  - Wraps text nodes (outside `style`/`script`) in `{{trans ""}}`
  - Wraps `title` and `alt` attributes in `{{trans ""}}`
- Restores original directives from placeholders.
- Optionally preserves/ restores the surrounding `<div data-content-type="html" ...>` wrapper.
- Updates the `content` column back into the DB.

**Usage**:

```bash
cd /path/to/this/repo
php getTransBy_database.php
```

**Safety tips**:

- **Full DB backup is mandatory** before using this script.
- Test on a **staging copy** of the database first.
- Review a few updated rows manually to confirm the HTML and `{{trans}}` wrapping are correct.

---

### General Best Practices

- Always **run these scripts in a development or staging environment first**.
- Use **version control (git)** and commit before running to easily revert changes.
- Keep your **database credentials** secure and do not commit real passwords to public repositories.


