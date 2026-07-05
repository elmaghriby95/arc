<?php

/**
 * Generates TranslationCatalog.php
 * Run: php database/seeders/data/build_catalog.php
 */

$root = dirname(__DIR__, 3);
$output = $root . '/database/seeders/data/TranslationCatalog.php';

$subGroups = [
    'settings.users', 'settings.roles', 'settings.org', 'settings.folders',
    'settings.txn_statuses', 'settings.txn_types', 'settings.doc_types', 'settings.ref_numbers',
];

function splitGroupKey(string $full, array $subGroups): array
{
    foreach ($subGroups as $g) {
        if (str_starts_with($full, $g . '.')) {
            return [$g, substr($full, strlen($g) + 1)];
        }
    }
    $pos = strpos($full, '.');
    if ($pos === false) {
        return [$full, $full];
    }

    return [substr($full, 0, $pos), substr($full, $pos + 1)];
}

function flattenLang(array $arr, string $prefix = ''): array
{
    $out = [];
    foreach ($arr as $k => $v) {
        $key = $prefix === '' ? (string) $k : $prefix . '.' . $k;
        if (is_array($v)) {
            $out += flattenLang($v, $key);
        } else {
            $out[$key] = $v;
        }
    }

    return $out;
}

/** @var array<string, array{0:string,1:string,2:string}> */
$known = [];

$set = static function (string $full, string $ar, string $en, string $fr) use (&$known): void {
    $known[$full] = [$ar, $en, $fr];
};

// Load lang/ar files
foreach (glob($root . '/lang/ar/*.php') as $file) {
    $group = basename($file, '.php');
    foreach (flattenLang(require $file) as $k => $ar) {
        $full = $group . '.' . $k;
        if (! isset($known[$full])) {
            $known[$full] = [$ar, $ar, $ar];
        }
    }
}

// Load supplemental translations from data file
require __DIR__ . '/catalog_translations.php';

foreach ($catalogTranslations as $full => $t) {
    $known[$full] = [$t[0], $t[1], $t[2]];
}

// Permission slugs from enum
$permSlugs = [
    'dashboard.view', 'reports.view',
    'documents.view', 'documents.create', 'documents.edit', 'documents.delete', 'documents.download',
    'departments.view', 'departments.create', 'departments.edit', 'departments.delete',
    'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
    'settings.view',
    'settings.users.view', 'settings.users.create', 'settings.users.edit',
    'settings.roles.view', 'settings.roles.create', 'settings.roles.edit', 'settings.roles.delete',
    'settings.organization.view',
    'settings.document-types.view', 'settings.document-types.create', 'settings.document-types.edit', 'settings.document-types.delete',
    'settings.transaction-types.view', 'settings.transaction-types.create', 'settings.transaction-types.edit', 'settings.transaction-types.delete',
    'settings.transaction-statuses.view', 'settings.transaction-statuses.create', 'settings.transaction-statuses.edit', 'settings.transaction-statuses.delete',
    'transactions.view', 'transactions.view-all', 'transactions.create', 'transactions.edit', 'transactions.delete',
    'transactions.status.review', 'transactions.status.approve', 'transactions.status.archive',
    'settings.folders.view', 'settings.folders.create', 'settings.folders.edit', 'settings.folders.delete',
    'settings.languages.view', 'settings.languages.create', 'settings.languages.edit', 'settings.languages.delete',
    'settings.reference-numbers.view', 'settings.reference-numbers.edit',
    'documents.reference-number.duplicate-override',
    'profile.view', 'profile.edit', 'profile.delete',
];

$permAr = flattenLang(require $root . '/lang/ar/permissions.php');
foreach ($permSlugs as $slug) {
    $key = str_replace('.', '_', $slug);
    $ar = $permAr[$key] ?? $slug;
    $en = $permAr[$key] ?? $slug;
    $fr = $permAr[$key] ?? $slug;
    $known['permissions.' . $key] = [$ar, $en, $fr];
}

// Permission groups
$groups = $permAr['groups'] ?? [];
$groups['workflow_stages'] = 'سير عمل المعاملات (مراحل)';
$groupEn = [
    'main_menu' => 'Main menu', 'reports' => 'Reports', 'documents' => 'Documents',
    'departments' => 'Departments', 'categories' => 'Categories', 'settings' => 'Settings',
    'users_management' => 'User management', 'roles_management' => 'Role management',
    'organization' => 'Organization structure', 'document_types' => 'Document types',
    'transaction_types' => 'Transaction types', 'transaction_statuses' => 'Transaction statuses',
    'archive_management' => 'Archive management', 'folder_tree' => 'Folder tree',
    'languages' => 'Languages', 'reference_numbers' => 'Reference numbers',
    'profile' => 'Profile', 'workflow_stages' => 'Transaction workflow (stages)',
];
$groupFr = [
    'main_menu' => 'Menu principal', 'reports' => 'Rapports', 'workflow_stages' => 'Workflow des transactions (étapes)',
];
foreach ($groups as $k => $ar) {
    $known['permissions.groups.' . $k] = [$ar, $groupEn[$k] ?? $ar, $groupFr[$k] ?? ($groupEn[$k] ?? $ar)];
}

$methodMap = [
    'nav' => 'nav', 'auth' => 'auth', 'common' => 'common', 'messages' => 'messages',
    'settings' => 'settings', 'settings.users' => 'settingsUsers', 'settings.roles' => 'settingsRoles',
    'settings.org' => 'settingsOrg', 'settings.folders' => 'settingsFolders',
    'settings.txn_statuses' => 'settingsTxnStatuses', 'settings.txn_types' => 'settingsTxnTypes',
    'settings.doc_types' => 'settingsDocTypes', 'settings.ref_numbers' => 'settingsRefNumbers',
    'dashboard' => 'dashboard', 'languages' => 'languages', 'translations' => 'translations',
    'profile' => 'profile', 'permissions' => 'permissions', 'permissions.groups' => 'permissionsGroups',
    'transactions' => 'transactions', 'documents' => 'documents', 'departments' => 'departments',
    'categories' => 'categories', 'reports' => 'reports', 'workflow' => 'workflow',
    'notifications' => 'notifications', 'validation' => 'validation', 'welcome' => 'welcome',
];

$grouped = [];
$permMap = [];

foreach ($known as $full => $t) {
    if (str_starts_with($full, 'permissions.groups.')) {
        $grouped['permissionsGroups'][] = [
            'group' => 'permissions.groups',
            'key' => substr($full, strlen('permissions.groups.')),
            'values' => ['ar' => $t[0], 'en' => $t[1], 'fr' => $t[2]],
        ];
        continue;
    }
    if (str_starts_with($full, 'permissions.')) {
        $key = substr($full, strlen('permissions.'));
        $slug = str_replace('_', '.', $key);
        // restore hyphens in slug from perm slugs
        foreach ($permSlugs as $ps) {
            if (str_replace('.', '_', $ps) === $key) {
                $slug = $ps;
                break;
            }
        }
        $permMap[$slug] = ['ar' => $t[0], 'en' => $t[1], 'fr' => $t[2]];
        continue;
    }

    [$group, $key] = splitGroupKey($full, $subGroups);
    $method = $methodMap[$group] ?? null;
    if (! $method) {
        continue;
    }
    $grouped[$method][] = [
        'group' => $group,
        'key' => $key,
        'values' => ['ar' => $t[0], 'en' => $t[1], 'fr' => $t[2]],
    ];
}

$methodOrder = ['nav', 'auth', 'common', 'messages', 'settings', 'settingsUsers', 'settingsRoles', 'settingsOrg', 'settingsFolders', 'settingsTxnStatuses', 'settingsTxnTypes', 'settingsDocTypes', 'settingsRefNumbers', 'dashboard', 'languages', 'translations', 'profile', 'permissions', 'permissionsGroups', 'transactions', 'documents', 'departments', 'categories', 'reports', 'workflow', 'notifications', 'validation'];

$total = 0;
$buf = "<?php\n\nnamespace Database\\Seeders\\Data;\n\nclass TranslationCatalog\n{\n    public static function all(): array\n    {\n        return array_merge(\n";
foreach ($methodOrder as $m) {
    $buf .= "            self::{$m}(),\n";
}
$buf .= "        );\n    }\n\n    /** @param array<string, array{ar: string, en: string, fr: string, description?: string}> \$map */\n    private static function permissionEntries(array \$map): array\n    {\n        \$entries = [];\n        foreach (\$map as \$slug => \$values) {\n            \$entries[] = [\n                'group' => 'permissions',\n                'key' => str_replace('.', '_', \$slug),\n                'description' => \$slug,\n                'values' => ['ar' => \$values['ar'], 'en' => \$values['en'], 'fr' => \$values['fr']],\n            ];\n        }\n\n        return \$entries;\n    }\n\n";

foreach ($methodOrder as $m) {
    if ($m === 'permissions') {
        $total += count($permMap);
        $buf .= "    /** @return list<array{group: string, key: string, description?: string, values: array<string, string>}> */\n";
        $buf .= "    private static function permissions(): array\n    {\n        return self::permissionEntries([\n";
        foreach ($permMap as $slug => $v) {
            $ar = var_export($v['ar'], true);
            $en = var_export($v['en'], true);
            $fr = var_export($v['fr'], true);
            $buf .= "            " . var_export($slug, true) . " => ['ar' => {$ar}, 'en' => {$en}, 'fr' => {$fr}],\n";
        }
        $buf .= "        ]);\n    }\n\n";
        continue;
    }

    $entries = $grouped[$m] ?? [];
    $total += count($entries);
    $buf .= "    /** @return list<array{group: string, key: string, description?: string, values: array<string, string>}> */\n";
    $buf .= "    private static function {$m}(): array\n    {\n        return [\n";
    foreach ($entries as $e) {
        $ar = var_export($e['values']['ar'], true);
        $en = var_export($e['values']['en'], true);
        $fr = var_export($e['values']['fr'], true);
        $buf .= "            ['group' => " . var_export($e['group'], true) . ", 'key' => " . var_export($e['key'], true) . ", 'values' => ['ar' => {$ar}, 'en' => {$en}, 'fr' => {$fr}]],\n";
    }
    $buf .= "        ];\n    }\n\n";
}

$buf .= "}\n";
file_put_contents($output, $buf);
echo "Wrote {$total} entries to {$output}\n";
passthru('php -l ' . escapeshellarg($output));
