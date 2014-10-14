<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <body>
    <?php
    use phpcassa\ColumnFamily;
    use phpcassa\ColumnSlice;

    $scriptName = "ViewItem.php";
    require "PHPprinter.php";
    $startTime = getMicroTime();

    $itemId = @$_GET['itemId'];
    if ($itemId == null) {
         printError($scriptName, $startTime, "Viewing item", "You must provide an item identifier!<br>");
         exit();
    }

    getDatabaseLink($link);

    // Q: SELECT name FROM items WHERE items.id = ?
    // Q: SELECT name FROM olditems WHERE olditems.id = ?
    if ($CURRENT_SCHEMA >= SchemaType::UNCONSTRAINED) {
        try {
            $cf = $link->I2115247770;
            $cf->return_format = ColumnFamily::ARRAY_FORMAT;
            $row = call_user_func_array('array_merge', array_map(function ($elem) {
              return array($elem[0][1] => $elem[1]);
            }, $cf->get($itemId)));
        } catch (cassandra\NotFoundException $e) {
            try {
              $cf = $link->I810361528;
              $cf->return_format = ColumnFamily::ARRAY_FORMAT;
              $row = call_user_func_array('array_merge', array_map(function ($elem) {
                return array($elem[0][1] => $elem[1]);
              }, $cf->get($itemId)));
            } catch (cassandra\NotFoundException $e) {
                die("<h3>ERROR: Sorry, but this item does not exist.</h3><br>\n");
            }
        }
    } elseif ($CURRENT_SCHEMA >= SchemaType::RELATIONAL) {
        try {
            $row = $link->items->get($itemId);
        } catch (cassandra\NotFoundException $e) {
            try {
                $row = $link->old_items->get($itemId);
            } catch (cassandra\NotFoundException $e) {
                die("<h3>ERROR: Sorry, but this item does not exist.</h3><br>\n");
            }
        }
    }

    // Q: SELECT bid FROM bids WHERE bids.item_id = ? ORDER BY bids.bid LIMIT 1
    if ($CURRENT_SCHEMA >= SchemaType::UNCONSTRAINED) {
        try {
            $cf = $link->I2744950719;
            $cf->return_format = ColumnFamily::ARRAY_FORMAT;
            $slice = new ColumnSlice('', '', $count=1);
            $maxBid = $cf->get($itemId, $slice);
            $maxBid = intval($maxBid[0][0][0]);
        } catch (cassandra\NotFoundException $e) {
            $maxBid = 0;
        }
    } elseif ($CURRENT_SCHEMA >= SchemaType::RELATIONAL) {
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
            // Q: SELECT bid, qty FROM bids WHERE bids.item_id = ? ORDER BY bids.bid LIMIT 5
            if ($CURRENT_SCHEMA == SchemaType::UNCONSTRAINED) {
                $cf = $link->I2203934753;
                $cf->return_format = ColumnFamily::ARRAY_FORMAT;

                $nb = 0;
                foreach ($cf->get($itemId) as $bid) {
                    $nb += $bid[1];
                    if ($nb > $row["quantity"]) {
                        $maxBid = $row["max_bid"];
                        break;
                    }
                }
            } elseif ($CURRENT_SCHEMA >= SchemaType::HALF) {
                $cf = $link->__get('lkQoYGr');
                $cf->return_format = ColumnFamily::ARRAY_FORMAT;

                $bids = array();
                foreach ($cf->get($itemId) as $bid) {
                    $id = $bid[0][0];
                    if (!isset($bids[$id])) {
                        $bids[$id] = array();
                    }
                    $bids[$id][$bid[0][1]] = $bid[1];
                }
                uasort($bids, function($bida, $bidb) { return $bidb["bid"] - $bida["bid"]; });

                $nb = 0;
                foreach ($bids as $bid) {
                    $nb += $bid["qty"];
                    if ($nb > $row["quantity"]) {
                        $maxBid = $row["bid"];
                        break;
                    }
                }
            } elseif ($CURRENT_SCHEMA >= SchemaType::RELATIONAL) {
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

                $nb = 0;
                foreach ($xRes as $xRow) {
                    $nb += $xRow["qty"];
                    if ($nb > $row["quantity"]) {
                        $maxBid = $row["bid"];
                        break;
                    }
                }
            }
        }
        $firstBid = $maxBid;

        // Q: SELECT id FROM bids WHERE bids.item_id = ?
        if ($CURRENT_SCHEMA == SchemaType::UNCONSTRAINED) {
            $nbOfBids = $link->I4004689239->get_count($itemId);
        } elseif ($CURRENT_SCHEMA >= SchemaType::RELATIONAL) {
            $nbOfBids = $link->bid_item->get_count($itemId);
        }
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

    if ($CURRENT_SCHEMA >= SchemaType::RELATIONAL) {
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
