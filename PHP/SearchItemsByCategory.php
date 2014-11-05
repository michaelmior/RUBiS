<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <body>
    <?php
    use phpcassa\ColumnSlice;
    use phpcassa\ColumnFamily;

    $scriptName = "SearchItemsByCategories.php";
    require "PHPprinter.php";
    $startTime = getMicroTime();

    $categoryName = $_GET['categoryName'];
    if ($categoryName == null) {
        printError(
            $scriptName,
            $startTime,
            "Search Items By Category",
            "You must provide a category name!<br>"
        );
        exit();
    }

    $categoryId = $_GET['category'];
    if ($categoryId == null) {
        printError(
            $scriptName,
            $startTime,
            "Search Items By Category",
            "You must provide a category identifier!<br>"
        );
        exit();
    }

    $page = @$_GET['page'];
    if ($page == null) {
        $page = 0;
    }

    $nbOfItems = @$_GET['nbOfItems'];
    if ($nbOfItems == null) {
        $nbOfItems = 25;
    }

    printHTMLheader("RUBiS: Items in category $categoryName");
    print("<h2>Items in category $categoryName</h2><br><br>");

    getDatabaseLink($link);
    $found = true;

    // Q: SELECT id, name, initial_price, max_bid, nb_of_bids, end_date FROM items WHERE items.category = ? AND items.end_date >= ?
    if ($CURRENT_SCHEMA >= SchemaType::UNCONSTRAINED) {
        try {
            $slice = new ColumnSlice(array('2002-04-'), array('2002-05-'), $count=($page + 1) * $nbOfItems * 4);
            $cf = $link->I326822658;
            $cf->return_format = ColumnFamily::ARRAY_FORMAT;
            $itemResults = $cf->get($categoryId, $slice);
            $items = array();
            foreach ($itemResults as $itemResult) {
                $id = $itemResult[0][1];
                if (!isset($items[$id])) {
                    $items[$id] = array();
                    $items[$id]["end_date"] = $itemResult[0][0];
                }
                $items[$id][$itemResult[0][2]] = $itemResult[1];
            }
        } catch (cassandra\NotFoundException $e) {
            $found = false;
        }
    } elseif ($CURRENT_SCHEMA >= SchemaType::RELATIONAL) {
        try {
            $slice = new ColumnSlice('', '', $count=($page + 1) * $nbOfItems);

            if ($USE_CANNED) {
                $item_ids = array ( 0 => 500015, 1 => 500035, 2 => 500055, 3 => 500075, 4 => 500095, 5 => 500115, 6 => 500135, 7 => 500155, 8 => 500175, 9 => 500195, 10 => 500215, 11 => 500235, 12 => 500255, 13 => 500275, 14 => 500295, 15 => 500315, 16 => 500335, 17 => 500355, 18 => 500375, 19 => 500395, 20 => 500415, 21 => 500435, 22 => 500455, 23 => 500475, 24 => 500495, );
            } else {
              $item_ids = array_keys($link->category_id->get($categoryId, $slice));
            }
            $item_ids = array_slice($item_ids, $page * $nbOfItems, ($page + 1) * $nbOfItems);

            if ($USE_MULTIGET) {
              if ($USE_CANNED) {
                $items = array ( 500015 => array ( 'end_date' => '2002-04-02 01:35:59', 'initial_price' => '1739', 'max_bid' => '1781', 'name' => 'RUBiS automatically generated item #32461', 'nb_of_bids' => '8', ), 500035 => array ( 'end_date' => '2002-04-02 01:35:59', 'initial_price' => '92', 'max_bid' => '115', 'name' => 'RUBiS automatically generated item #32481', 'nb_of_bids' => '2', ), 500055 => array ( 'end_date' => '2002-04-02 01:35:59', 'initial_price' => '2887', 'max_bid' => '2902', 'name' => 'RUBiS automatically generated item #32501', 'nb_of_bids' => '1', ), 500075 => array ( 'end_date' => '2002-04-02 01:35:59', 'initial_price' => '2771', 'max_bid' => '2785', 'name' => 'RUBiS automatically generated item #32521', 'nb_of_bids' => '3', ), 500095 => array ( 'end_date' => '2002-04-02 01:35:59', 'initial_price' => '4543', 'max_bid' => '4550', 'name' => 'RUBiS automatically generated item #32541', 'nb_of_bids' => '5', ), 500115 => array ( 'end_date' => '2002-04-02 01:35:59', 'initial_price' => '3747', 'max_bid' => '3760', 'name' => 'RUBiS automatically generated item #32561', 'nb_of_bids' => '2', ), 500135 => array ( 'end_date' => '2002-04-02 01:35:59', 'initial_price' => '4061', 'max_bid' => '0', 'name' => 'RUBiS automatically generated item #32581', 'nb_of_bids' => '0', ), 500155 => array ( 'end_date' => '2002-04-02 01:35:59', 'initial_price' => '3812', 'max_bid' => '3819', 'name' => 'RUBiS automatically generated item #32601', 'nb_of_bids' => '1', ), 500175 => array ( 'end_date' => '2002-04-02 01:35:59', 'initial_price' => '2861', 'max_bid' => '2882', 'name' => 'RUBiS automatically generated item #32621', 'nb_of_bids' => '2', ), 500195 => array ( 'end_date' => '2002-04-02 01:35:59', 'initial_price' => '219', 'max_bid' => '249', 'name' => 'RUBiS automatically generated item #32641', 'nb_of_bids' => '6', ), 500215 => array ( 'end_date' => '2002-04-02 01:35:59', 'initial_price' => '1507', 'max_bid' => '1533', 'name' => 'RUBiS automatically generated item #32661', 'nb_of_bids' => '3', ), 500235 => array ( 'end_date' => '2002-04-02 01:35:59', 'initial_price' => '873', 'max_bid' => '895', 'name' => 'RUBiS automatically generated item #32681', 'nb_of_bids' => '6', ), 500255 => array ( 'end_date' => '2002-04-02 01:35:59', 'initial_price' => '3324', 'max_bid' => '3340', 'name' => 'RUBiS automatically generated item #32701', 'nb_of_bids' => '3', ), 500275 => array ( 'end_date' => '2002-04-02 01:35:59', 'initial_price' => '3043', 'max_bid' => '3048', 'name' => 'RUBiS automatically generated item #32721', 'nb_of_bids' => '1', ), 500295 => array ( 'end_date' => '2002-04-02 01:35:59', 'initial_price' => '2038', 'max_bid' => '2055', 'name' => 'RUBiS automatically generated item #32741', 'nb_of_bids' => '8', ), 500315 => array ( 'end_date' => '2002-04-02 01:35:59', 'initial_price' => '4333', 'max_bid' => '4336', 'name' => 'RUBiS automatically generated item #32761', 'nb_of_bids' => '1', ), 500335 => array ( 'end_date' => '2002-04-02 01:35:59', 'initial_price' => '1116', 'max_bid' => '1127', 'name' => 'RUBiS automatically generated item #32781', 'nb_of_bids' => '1', ), 500355 => array ( 'end_date' => '2002-04-02 01:35:59', 'initial_price' => '4152', 'max_bid' => '4167', 'name' => 'RUBiS automatically generated item #32801', 'nb_of_bids' => '1', ), 500375 => array ( 'end_date' => '2002-04-02 01:35:59', 'initial_price' => '1870', 'max_bid' => '1892', 'name' => 'RUBiS automatically generated item #32821', 'nb_of_bids' => '3', ), 500395 => array ( 'end_date' => '2002-04-02 01:35:59', 'initial_price' => '1848', 'max_bid' => '1863', 'name' => 'RUBiS automatically generated item #32841', 'nb_of_bids' => '2', ), 500415 => array ( 'end_date' => '2002-04-02 01:35:59', 'initial_price' => '3914', 'max_bid' => '3957', 'name' => 'RUBiS automatically generated item #32861', 'nb_of_bids' => '3', ), 500435 => array ( 'end_date' => '2002-04-02 01:35:59', 'initial_price' => '2917', 'max_bid' => '2928', 'name' => 'RUBiS automatically generated item #32881', 'nb_of_bids' => '5', ), 500455 => array ( 'end_date' => '2002-04-02 01:35:59', 'initial_price' => '1594', 'max_bid' => '1631', 'name' => 'RUBiS automatically generated item #32901', 'nb_of_bids' => '5', ), 500475 => array ( 'end_date' => '2002-04-02 01:35:59', 'initial_price' => '92', 'max_bid' => '106', 'name' => 'RUBiS automatically generated item #32921', 'nb_of_bids' => '6', ), 500495 => array ( 'end_date' => '2002-04-02 01:35:59', 'initial_price' => '1650', 'max_bid' => '1671', 'name' => 'RUBiS automatically generated item #32941', 'nb_of_bids' => '5', ), );
              } else {
                $items = $link->items->multiget($item_ids, $column_slice=null, $column_names=array("name", "initial_price", "max_bid", "nb_of_bids", "end_date"));
              }
            } else {
              $items = array_map(function ($item_id) use($link) { return $link->items->get($item_id, $column_slice=null, $column_names=array("name", "initial_price", "max_bid", "nb_of_bids", "end_date")); }, $item_ids);
            }
            $items = array_filter($items, function($item) { return $item["end_date"] > "2002-04-" && $item["end_date"] < "2002-05"; });
        } catch (cassandra\NotFoundException $e) {
            $found = false;
        }
    }

    if (!$found) {
      if ($page == 0) {
        print("<h2>Sorry, but there are no items available in this category !</h2>");
      } else {
        print("<h2>Sorry, but there are no more items available in this category !</h2>");
        print("<p><CENTER>\n<a href=\"/PHP/SearchItemsByCategory.php?category=$categoryId".
              "&categoryName=".urlencode($categoryName)."&page=".($page-1)."&nbOfItems=$nbOfItems\">Previous page</a>\n</CENTER>\n");
      }
      $link->close();
      printHTMLfooter($scriptName, $startTime);
      exit();
    } else {
      print("<TABLE border=\"1\" summary=\"List of items\">".
            "<THEAD>".
            "<TR><TH>Designation<TH>Price<TH>Bids<TH>End Date<TH>Bid Now".
            "<TBODY>");
    }

    foreach ($items as $id => $row) {
      $maxBid = $row["max_bid"];
      if ($maxBid == 0)
        $maxBid = $row["initial_price"];

      print("<TR><TD><a href=\"/PHP/ViewItem.php?itemId=".$id."\">".$row["name"].
            "<TD>$maxBid".
            "<TD>".$row["nb_of_bids"].
            "<TD>".$row["end_date"].
            "<TD><a href=\"/PHP/PutBidAuth.php?itemId=".$id."\"><IMG SRC=\"/PHP/bid_now.jpg\" height=22 width=90></a>");
    }
    print("</TABLE>");

    if ($page == 0)
      print("<p><CENTER>\n<a href=\"/PHP/SearchItemsByCategory.php?category=$categoryId".
           "&categoryName=".urlencode($categoryName)."&page=".($page+1)."&nbOfItems=$nbOfItems\">Next page</a>\n</CENTER>\n");
    else
      print("<p><CENTER>\n<a href=\"/PHP/SearchItemsByCategory.php?category=$categoryId".
            "&categoryName=".urlencode($categoryName)."&page=".($page-1)."&nbOfItems=$nbOfItems\">Previous page</a>\n&nbsp&nbsp&nbsp".
            "<a href=\"/PHP/SearchItemsByCategory.php?category=$categoryId".
            "&categoryName=".urlencode($categoryName)."&page=".($page+1)."&nbOfItems=$nbOfItems\">Next page</a>\n\n</CENTER>\n");

    $link->close();

    printHTMLfooter($scriptName, $startTime);
    ?>
  </body>
</html>
