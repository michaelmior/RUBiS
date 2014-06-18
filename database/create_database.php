<?php

require "../PHP/vendor/autoload.php";

use phpcassa\SystemManager;
use phpcassa\Schema\StrategyClass;
use phpcassa\Connection\ConnectionPool;
use phpcassa\ColumnFamily;

// Create a new keyspace and column family
echo "Recreating keyspace\n";
$sys = new SystemManager('127.0.0.1');
$sys->drop_keyspace("RUBBoS");
$sys->create_keyspace("RUBBoS", array(
    "strategy_class" => StrategyClass::SIMPLE_STRATEGY,
    "strategy_options" => array("replication_factor" => "1")));
sleep(5);

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
  $sys->create_column_family("RUBBoS", $cf_name);
  sleep(10);

  echo "Loading data for $cf_name\n";
  $handle = fopen("csv" . DIRECTORY_SEPARATOR . $cf_name . ".csv", "r");
  $pool = new ConnectionPool("RUBBoS");
  $cf = new ColumnFamily($pool, $cf_name);
  $header = array_slice(fgetcsv($handle, 0, ",", "'"), 1);
  while ($data = fgetcsv($handle, 0, ",", "'")) {
    $cf->insert($data[0], array_combine($header, array_slice($data, 1)));
  }
  $pool->close();
}

$sys->close();
