#!/usr/bin/env php

<?php

require "../PHP/vendor/autoload.php";

use phpcassa\SystemManager;
use phpcassa\Connection\ConnectionPool;
use phpcassa\ColumnFamily;

$sys = new SystemManager(gethostbyname(php_uname('n')));
$pool = new ConnectionPool("RUBiS", array(gethostbyname(php_uname('n'))));

foreach (array_slice($argv, 1) as $json_filename) {
    $json = json_decode(file_get_contents($json_filename), true);

    foreach ($json["indexes"] as $index) {
        if (count($index["hash_fields"]) > 1) {
            $key_validation_class = "CompositeType(". implode(", ", array_fill(0, count($index["hash_fields"]), "AsciiType")) .")";
        } else {
            $key_validation_class = "AsciiType";
        }

        $column_parts = count($index["order_fields"]) + 1;
        if (count($index["extra"]) > 1) {
            $column_parts++;
        }
        if ($column_parts == 1) {
            $comparator_type = "AsciiType";
        } else {
            $comparator_type = "CompositeType(". implode(", ", array_fill(0, $column_parts, "AsciiType")) .")";
        }

        $index_name = $index["key"];

        $exists = true;
        try {
            $cf = new ColumnFamily($pool, $index_name);
        } catch (cassandra\NotFoundException $e) {
            $exists = false;
        }
        if ($exists) { continue; }

        echo "Creating column family $index_name\n";
        $sys->create_column_family(
            "RUBiS",
            $index_name,
            array(
                "comparator_type" => $comparator_type,
                "key_validation_class" => $key_validation_class
            )
        );
    }
}

$sys->close();
