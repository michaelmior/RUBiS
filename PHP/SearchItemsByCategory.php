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

    if ($CURRENT_SCHEMA >= SchemaType::UNCONSTRAINED) {
        try {
            $slice = new ColumnSlice(array('2002-04-'), array('2002-05-'), $count=($page + 1) * $nbOfItems);
            $cf = $link->zg8gDr5;
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
            $item_ids = array_keys($link->category_id->get($categoryId, $slice));
            $item_ids = array_slice($item_ids, $page * $nbOfItems, ($page + 1) * $nbOfItems);
            $items = $link->items->multiget($item_ids, $column_slice=null, $column_names=array("name", "initial_price", "max_bid", "nb_of_bids", "end_date"));
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
