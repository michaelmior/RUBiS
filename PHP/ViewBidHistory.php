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
    if ($CURRENT_SCHEMA >= SchemaType::RELATIONAL) {
        try {
            $itemNameRow = $link->items->get($itemId, $column_slice=null, $column_names=array("name"));
        } catch (cassandra\NotFoundException $e) {
            try {
                $itemNameRow = $link->old_items->get($itemId, $column_slice=null, $column_names=array("name"));
            } catch (cassandra\NotFoundException $e) {
                die("<h3>ERROR: Sorry, but this item does not exist.</h3><br>\n");
            }
        }
        $itemName = $itemNameRow["name"];
    }


    // Get the list of bids for this item
    if ($CURRENT_SCHEMA >= SchemaType::UNCONSTRAINED) {
        try {
        $cf = $link->qz7kz2;
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
            $cf = $link->zmLEBQZ;
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
            $bid_ids = array_keys($link->bid_item->get($itemId));
            $bidsListResult = $link->bids->multiget($bid_ids, $column_slice=null, $column_slice=array("bid", "date", "user_id"));
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
        if ($userId != 0) {
            if ($CURRENT_SCHEMA >= SchemaType::UNCONSTRAINED) {
                $nickname = $usersResult[$userId];
            } elseif ($CURRENT_SCHEMA >= SchemaType::RELATIONAL) {
                $userNameRow = $link->users->get($userId, $column_slice=null, $column_names=array("nickname"));
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
