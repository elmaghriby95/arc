<?php

/** One-off generator — run then delete. */
$out = fopen(__DIR__ . '/TranslationCatalog.php', 'w');

fwrite($out, <<<'HDR'
<?php

namespace Database\Seeders\Data;

class TranslationCatalog
{
    public static function all(): array
    {
        return array_merge(
            self::nav(),
            self::auth(),
            self::common(),
            self::messages(),
            self::settings(),
            self::settingsUsers(),
            self::settingsRoles(),
            self::settingsOrg(),
            self::settingsFolders(),
            self::settingsTxnStatuses(),
            self::settingsTxnTypes(),
            self::settingsDocTypes(),
            self::settingsRefNumbers(),
            self::dashboard(),
            self::languages(),
            self::translations(),
            self::profile(),
            self::permissions(),
            self::permissionsGroups(),
            self::transactions(),
            self::documents(),
            self::departments(),
            self::categories(),
            self::reports(),
            self::workflow(),
            self::notifications(),
            self::validation(),
        );
    }

    /** @param array<string, array{ar: string, en: string, fr: string, description?: string}> $map */
    private static function permissionEntries(array $map): array
    {
        $entries = [];
        foreach ($map as $slug => $values) {
            $entry = [
                'group' => 'permissions',
                'key' => str_replace('.', '_', $slug),
                'description' => $slug,
                'values' => ['ar' => $values['ar'], 'en' => $values['en'], 'fr' => $values['fr']],
            ];
            $entries[] = $entry;
        }

        return $entries;
    }

    /** @param list<array{group: string, key: string, description?: string, values: array<string, string>}> $entries */
    private static function e(string $group, string $key, string $ar, string $en, string $fr, ?string $description = null): array
    {
        $entry = ['group' => $group, 'key' => $key, 'values' => ['ar' => $ar, 'en' => $en, 'fr' => $fr]];
        if ($description !== null) {
            $entry['description'] = $description;
        }

        return $entry;
    }

HDR);

$sections = require __DIR__ . '/_catalog_data.php';

foreach ($sections as $method => $entries) {
    fwrite($out, "\n    /** @return list<array{group: string, key: string, description?: string, values: array<string, string>}> */\n");
    fwrite($out, "    private static function {$method}(): array\n    {\n");
    fwrite($out, "        return [\n");
    foreach ($entries as $entry) {
        $desc = isset($entry['description']) ? ", 'description' => " . var_export($entry['description'], true) : '';
        $ar = var_export($entry['values']['ar'], true);
        $en = var_export($entry['values']['en'], true);
        $fr = var_export($entry['values']['fr'], true);
        fwrite($out, "            ['group' => " . var_export($entry['group'], true) . ", 'key' => " . var_export($entry['key'], true) . $desc . ", 'values' => ['ar' => {$ar}, 'en' => {$en}, 'fr' => {$fr}]],\n");
    }
    fwrite($out, "        ];\n    }\n");
}

fwrite($out, "}\n");
fclose($out);

$count = count(require __DIR__ . '/_catalog_data_flat.php');
echo "Generated with {$count} entries\n";
