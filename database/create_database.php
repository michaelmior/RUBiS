<?php

require "../PHP/vendor/autoload.php";

use phpcassa\SystemManager;
use phpcassa\Schema\StrategyClass;

// Create a new keyspace and column family
$sys = new SystemManager('127.0.0.1');
$sys->drop_keyspace("RUBBoS");
$sys->create_keyspace("RUBBoS", array(
    "strategy_class" => StrategyClass::SIMPLE_STRATEGY,
    "strategy_options" => array("replication_factor" => "1")));

foreach (array(
    "categories",
    "regions",
    "users",
    "items",
    "olditems",
    "bids",
    "comments",
    "buynow") as $cf) {
  $sys->create_column_family("RUBBoS", $cf);
}
