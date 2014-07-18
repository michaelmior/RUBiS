<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <body>
    <?php
    $scriptName = "ViewItem.php";
    require "PHPprinter.php";
    $startTime = getMicroTime();

    $itemId = @$_GET['itemId'];
    if ($itemId == null) {
         printError($scriptName, $startTime, "Viewing item", "You must provide an item identifier!<br>");
         exit();
    }

    getDatabaseLink($link);

    if ($CURRENT_SCHEMA == SchemaType::RELATIONAL) {
        try {
            $row = $link->items->get($itemId);
        } catch (cassandra\NotFoundException $e) {
            try {
                $row = $link->old_items->get($itemId);
            } catch (cassandra\NotFoundException $e) {
                die("<h3>ERROR: Sorry, but this item does not exist.</h3><br>\n");
            }
        }

        try {
            $bid_ids = array_keys($link->bid_item->get($itemId));
            $bids = call_user_func_array('array_merge', array_map('array_values', $link->bids->multiget($bid_ids, $column_slice=null, $column_names=array("bid"))));
            $maxBid = count($bids) > 0 ? max($bids) : 0;
        } catch (cassandra\NotFoundException $e) {
            $maxBid = 0;
        }
    }

    if ($maxBid == 0) {
        $maxBid = $row["initial_price"];
        $buyNow = $row["buy_now"];
        $firstBid = "none";
        $nbOfBids = 0;
    } else {
        if ($row["quantity"] > 1) {
            if ($CURRENT_SCHEMA == SchemaType::RELATIONAL) {
                // Fetch bids, sort, and take the top "quantity" number
                $bid_ids = array_keys($link->bid_item->get($itemId));
                $bids = $link->bids->multiget($bid_ids);
                uasort(
                    $bids,
                    function ($a, $b) {
                        return $a["bid"] - $b["bid"];
                    }
                );
                $xRes = array_slice($bids, 0, $row["quantity"]);
            }

            $nb = 0;
            foreach ($xRes as $xRow) {
                $nb += $xRow["qty"];
                if ($nb > $row["quantity"]) {
                    $maxBid = $row["bid"];
                    break;
                }
            }
        }
        $firstBid = $maxBid;
        $nbOfBids = $link->bid_item->get_count($itemId);
    }

    printHTMLheader("RUBiS: Viewing ".$row["name"]);
    printHTMLHighlighted($row["name"]);
    print("<TABLE>\n".
          "<TR><TD>Currently<TD><b><BIG>$maxBid</BIG></b>\n");

    // Check if the reservePrice has been met (if any)
    $reservePrice = $row["reserve_price"];
    if ($reservePrice > 0) {
        if ($maxBid >= $reservePrice) {
            print("(The reserve price has been met)\n");
        } else {
            print("(The reserve price has NOT been met)\n");
        }
    }

    if ($CURRENT_SCHEMA == SchemaType::RELATIONAL) {
        $sellerNameRow = $link->users->get($row["seller"], $column_slice=null, $column_names=array("nickname"));
        $sellerName = $sellerNameRow["nickname"];
    }

    print("<TR><TD>Quantity<TD><b><BIG>".$row["quantity"]."</BIG></b>\n");
    print("<TR><TD>First bid<TD><b><BIG>$firstBid</BIG></b>\n");
    print("<TR><TD># of bids<TD><b><BIG>$nbOfBids</BIG></b> (<a href=\"/PHP/ViewBidHistory.php?itemId=".$itemId."\">bid history</a>)\n");
    print("<TR><TD>Seller<TD><a href=\"/PHP/ViewUserInfo.php?userId=".$row["seller"]."\">$sellerName</a> (<a href=\"/PHP/PutCommentAuth.php?to=".$row["seller"]."&itemId=".$itemId."\">Leave a comment on this user</a>)\n");
    print("<TR><TD>Started<TD>".$row["start_date"]."\n");
    print("<TR><TD>Ends<TD>".$row["end_date"]."\n");
    print("</TABLE>\n");

    // Can the user by this item now ?
    if ($buyNow > 0)
    print("<p><a href=\"/PHP/BuyNowAuth.php?itemId=".$itemId."\">".
              "<IMG SRC=\"/PHP/buy_it_now.jpg\" height=22 width=150></a>".
              "  <BIG><b>You can buy this item right now for only \$$buyNow</b></BIG><br><p>\n");

    print("<a href=\"/PHP/PutBidAuth.php?itemId=".$itemId."\"><IMG SRC=\"/PHP/bid_now.jpg\" height=22 width=90> on this item</a>\n");

    printHTMLHighlighted("Item description");
    print($row["description"]);
    print("<br><p>\n");

    $link->close();

    printHTMLfooter($scriptName, $startTime);
    ?>
  </body>
</html>
