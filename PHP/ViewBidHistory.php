<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <body>
    <?php
    use phpcassa\ColumnFamily;

    $scriptName = "ViewBidHistory.php";
    require "PHPprinter.php";
    $startTime = getMicroTime();

    $itemId = $_GET['itemId'];
    if ($itemId == null) {
        printError($scriptName, $startTime, "Bid history", "You must provide an item identifier!<br>");
        exit();
    }

    getDatabaseLink($link);

    // Get the item name
    // Q: SELECT name FROM items WHERE items.id = ?
    if ($CURRENT_SCHEMA >= SchemaType::HALF) {
        try {
            $cf = $link->I2115247770;
            $cf->return_format = ColumnFamily::ARRAY_FORMAT;
            foreach ($cf->get($itemId) as $data) {
                if (strcmp($data[0][1], 'name') === 0) {
                    $itemName = $data[1];
                    break;
                }
            }
        } catch (cassandra\NotFoundException $e) {
            try {
                $cf = $link->I810361528;
                $cf->return_format = ColumnFamily::ARRAY_FORMAT;
                foreach ($cf->get($itemId) as $data) {
                    if (strcmp($data[0][1], name) === 0) {
                        $itemName = $data[1];
                        break;
                    }
                }
            } catch (cassandra\NotFoundException $e) {
                die("<h3>ERROR: Sorry, but this item does not exist.</h3><br>\n");
            }
        }
    } elseif ($CURRENT_SCHEMA >= SchemaType::UNCONSTRAINED) {
        try {
            $itemName = array_values($link->I1123555240->get($itemId));
            $itemName = $itemName[0];
        } catch (cassandra\NotFoundException $e) {
            try {
                $itemName = array_values($link->I862781479->get($itemId));
                $itemName = $itemName[0];
            } catch (cassandra\NotFoundException $e) {
                die("<h3>ERROR: Sorry, but this item does not exist.</h3><br>\n");
            }
        }
    } elseif ($CURRENT_SCHEMA >= SchemaType::RELATIONAL) {
        if ($USE_CANNED) {
          $itemNameRow = array ( 'name' => 'RUBiS automatically generated item #33007', );
        } else {
        try {
            $itemNameRow = $link->items->get($itemId, $column_slice=null, $column_names=array("name"));
        } catch (cassandra\NotFoundException $e) {
            try {
                $itemNameRow = $link->old_items->get($itemId, $column_slice=null, $column_names=array("name"));
            } catch (cassandra\NotFoundException $e) {
                die("<h3>ERROR: Sorry, but this item does not exist.</h3><br>\n");
            }
        }
        }
        $itemName = $itemNameRow["name"];
    }


    // Get the list of bids for this item
    // Q: SELECT id, user_id, item_id, qty, bid, date FROM bids WHERE bids.item_id = ? ORDER BY bids.date
    if ($CURRENT_SCHEMA >= SchemaType::HALF) {
        try {
            $cf = $link->I2589792665;
            $cf->return_format = ColumnFamily::ARRAY_FORMAT;

            $bids = array();
            foreach ($cf->get($itemId) as $bid) {
                $id = $bid[0][0];
                if (!isset($bids[$id])) {
                    $bids[$id] = array();
                }
                $bids[$id][$bid[0][1]] = $bid[1];
            }
            foreach ($bids as $id => $bid) {
                $bidsListResult[] = array_merge(array("id" => $id), $bid);
            }
            uasort($bids, function($bida, $bidb) { return $bidb["bid"] - $bida["bid"]; });

            // Collect usernames
            $cf = $link->I1792628276;
            $cf->return_format = ColumnFamily::ARRAY_FORMAT;
            $userIds = array();
            foreach ($cf->get($itemId) as $user) {
                $userIds[] = $user[1];
            }

            if ($USE_MULTIGET) {
              $users = array_values($link->I3318501374->multiget($userIds));
            } else {
              $users = array_values(array_map(function($userId) use($link) { return $link->I3318501374->get($userId); }, $userIds));
            }

            $usersResult = array();
            foreach ($users as $user) {
                $usersResult = $usersResult + $user;
            }
        } catch (cassandra\NotFoundException $e) {
            print ("<h2>There is no bid for $itemName. </h2><br>");
            $bidsListResult = array();
        }
    } elseif ($CURRENT_SCHEMA >= SchemaType::UNCONSTRAINED) {
        try {
        $cf = $link->I1757158466;
        $cf->return_format = ColumnFamily::ARRAY_FORMAT;

        $bidsListResult = array();
        $bids = array();
            foreach ($cf->get($itemId) as $bid) {
                $id = $bid[0][1];
                if (!isset($bids[$id])) {
                    $bids[$id] = array("date" => $bid[0][0]);
                }
                $bids[$id][$bid[0][2]] = $bid[1];
            }
            foreach ($bids as $id => $bid) {
                $bidsListResult[] = array_merge(array("id" => $id), $bid);
            }

            // Collect usernames
            $cf = $link->I1012998599;
            $cf->return_format = ColumnFamily::ARRAY_FORMAT;
            $usersResult = array();
            foreach ($cf->get($itemId) as $user) {
                $usersResult[$user[0][0]] = $user[1];
            }
        } catch (cassandra\NotFoundException $e) {
            print ("<h2>There is no bid for $itemName. </h2><br>");
            $bidsListResult = array();
        }
    } elseif ($CURRENT_SCHEMA >= SchemaType::RELATIONAL) {
        try {
            if ($USE_CANNED) {
              $bid_ids = array ( 0 => 5054860, 1 => 5058956, 2 => 5059993, 3 => 5060247, );
            } else {
              $bid_ids = array_keys($link->bid_item->get($itemId));
            }

            if ($USE_CANNED) {
              $bidsListResult = array ( 5054860 => array ( 'bid' => '4616', 'date' => '2001-10-19 01:07:33', 'user_id' => '243745', ), 5058956 => array ( 'bid' => '4621', 'date' => '2001-10-19 01:26:40', 'user_id' => '243745', ), 5059993 => array ( 'bid' => '11', 'date' => '2001-10-19 01:35:29', 'user_id' => '243745', ), 5060247 => array ( 'bid' => '6', 'date' => '2001-10-19 01:37:13', 'user_id' => '243745', ), );
            } elseif ($USE_MULTIGET) {
              $bidsListResult = $link->bids->multiget($bid_ids, $column_slice=null, $column_slice=array("bid", "date", "user_id"));
            } else {
              $bidsListResult = array_map(function($bid_id) use($link) { return $link->bids->get($bid_id, $column_slice=null, $column_slice=array("bid", "date", "user_id")); }, $bid_ids);
            }

            print ("<h2><center>Bid history for $itemName</center></h2><br>");
        } catch (cassandra\NotFoundException $e) {
            print ("<h2>There is no bid for $itemName. </h2><br>");
            $bidsListResult = array();
        }
    }

    printHTMLheader("RUBiS: Bid history for $itemName.");
    print("<TABLE border=\"1\" summary=\"List of bids\">\n".
                "<THEAD>\n".
                "<TR><TH>User ID<TH>Bid amount<TH>Date of bid\n".
                "<TBODY>\n");

    foreach ($bidsListResult as $bidsListRow) {
        $bidAmount = $bidsListRow["bid"];
        $bidDate = $bidsListRow["date"];
        $userId = $bidsListRow["user_id"];
        // Get the bidder nickname
        // Q: SELECT id, nickname FROM users WHERE users.bids.item_id = ?
        if ($userId != 0) {
            if ($CURRENT_SCHEMA >= SchemaType::UNCONSTRAINED) {
                $nickname = $usersResult[$userId];
            } elseif ($CURRENT_SCHEMA >= SchemaType::RELATIONAL) {
                if ($USE_CANNED) {
                  $userNameRow = array ( 'nickname' => 'user243745', );
                } else {
                  $userNameRow = $link->users->get($userId, $column_slice=null, $column_names=array("nickname"));
                }
                $nickname = $userNameRow["nickname"];
            }
        } else {
            print("Cannot lookup the user!<br>");
            printHTMLfooter($scriptName, $startTime);
            exit();
        }
        print("<TR><TD><a href=\"/PHP/ViewUserInfo.php?userId=".$userId."\">$nickname</a>"
          ."<TD>".$bidAmount."<TD>".$bidDate."\n");
    }
    print("</TABLE>\n");


    $link->close();

    printHTMLfooter($scriptName, $startTime);
    ?>
  </body>
</html>
