#!/usr/bin/env php

<?php

require "../PHP/vendor/autoload.php";

use phpcassa\SystemManager;
use phpcassa\Schema\StrategyClass;
use phpcassa\Connection\ConnectionPool;
use phpcassa\ColumnFamily;

// Create a new keyspace and column family
echo "Recreating keyspace\n";
$sys = new SystemManager(gethostbyname(php_uname('n')));
try {
    $sys->drop_keyspace("RUBiS");
} catch (cassandra\InvalidRequestException $e) {
    // Keyspace doesn't exist
}

$sys->create_keyspace(
    "RUBiS", array(
        "strategy_class" => StrategyClass::SIMPLE_STRATEGY,
        "strategy_options" => array("replication_factor" => "1")
    )
);
sleep(5);

$indices = array(
    "users" => array(
        "auth" => array("nickname", "password"),
        "region" => array("region")
    ),
    "items" => array(
        "seller_id" => array("seller"),
        "category_id" => array("category")
    ),
    "olditems" => array(
        "old_seller_id" => array("seller"),
        "old_category_id" => array("category")
    ),
    "bids" => array(
        "bid_item" => array("item_id"),
        "bid_user" => array("user_id")
    ),
    "comments" => array(
        "from_user" => array("from_user_id"),
        "to_user" => array("to_user_id"),
        "item" => array("item_id")
    ),
    "buynow" => array(
        "buyer" => array("buyer_id", "date"),
        "buy_now_item" => array("item_id")
    )
);

foreach (array(
    "categories",
    "regions",
    "users",
    "items",
    "olditems",
    "bids",
    "comments",
    "buynow") as $cf_name) {

    echo "Creating column family $cf_name\n";
    $sys->create_column_family("RUBiS", $cf_name, array("comparator_type" => "AsciiType"));

    if (array_key_exists($cf_name, $indices)) {
        $cf_indices = $indices[$cf_name];
    } else {
        $cf_indices = array();
    }

    foreach ($cf_indices as $index_name => $columns) {
        if (count($columns) > 1) {
            $key_validation_class = "CompositeType(". implode(", ", array_fill(0, count($columns), "AsciiType")) .")";
        } else {
            $key_validation_class = "AsciiType";
        }

        echo "Creating column family $index_name\n";
        $sys->create_column_family(
            "RUBiS",
            $index_name,
            array(
                "key_validation_class" => $key_validation_class
            )
        );
    }
}

$sys->close();
