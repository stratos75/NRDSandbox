<?php
// Test Schema File Parsing
$schemaFile = __DIR__ . '/database/schema_sqlite.sql';
$schema = file_get_contents($schemaFile);

echo "=== Schema File Content ===\n";
echo "File size: " . strlen($schema) . " bytes\n";
echo "First 500 chars:\n";
echo substr($schema, 0, 500) . "\n";

echo "\n=== Statement Parsing ===\n";
$statements = array_filter(array_map('trim', explode(';', $schema)));
echo "Total statements: " . count($statements) . "\n";

foreach ($statements as $i => $statement) {
    if (!empty($statement) && !preg_match('/^--/', $statement)) {
        echo "\n--- Statement " . ($i + 1) . " ---\n";
        echo "Length: " . strlen($statement) . "\n";
        echo "First 200 chars: " . substr($statement, 0, 200) . "\n";
        if (strpos($statement, 'CREATE TABLE') !== false) {
            echo "*** TABLE CREATION STATEMENT ***\n";
        }
    }
}