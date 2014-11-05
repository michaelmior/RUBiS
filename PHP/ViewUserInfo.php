<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <body>
    <?php
    use phpcassa\ColumnFamily;

    $scriptName = "ViewUserInfo.php";
    require "PHPprinter.php";
    $startTime = getMicroTime();

    $userId = $_GET['userId'];
    if ($userId == null)
    {
      $userId = $_GET['userId'];
      if ($userId == null)
      {
         printError($scriptName, $startTime, "Viewing user information", "You must provide an item identifier!<br>");
         exit();
      }
    }

    getDatabaseLink($link);

    if ($CURRENT_SCHEMA >= SchemaType::RELATIONAL) {
        try {
            if ($USE_CANNED) {
              $userRow = array ( 'balance' => '0', 'creation_date' => '2001-10-17 08:27:50', 'email' => 'Great549946.User549946@rubis.com', 'firstname' => 'Great549946', 'lastname' => 'User549946', 'nickname' => 'user549946', 'password' => 'password549946', 'rating' => '-5', 'region' => '6', );
            } else {
              $userRow = $link->users->get($userId);
            }
        } catch (cassandra\NotFoundException $e) {
            $userRow = null;
        }
    }

    if (!$userRow) {
      die("<h3>ERROR: Sorry, but this user does not exist.</h3><br>\n");
    }

    printHTMLheader("RUBiS: View user information");

      // Get general information about the user
    $firstname = $userRow["firstname"];
    $lastname = $userRow["lastname"];
    $nickname = $userRow["nickname"];
    $email = $userRow["email"];
    $creationDate = $userRow["creation_date"];
    $rating = $userRow["rating"];

    print("<h2>Information about ".$nickname."<br></h2>");
    print("Real life name : ".$firstname." ".$lastname."<br>");
    print("Email address  : ".$email."<br>");
    print("User since     : ".$creationDate."<br>");
    print("Current rating : <b>".$rating."</b><br>");

    // Get the comments about the user
    // Q: SELECT id, to_user_id, item_id, rating, date, comment FROM comments WHERE comments.to_user_id = ?
    if ($CURRENT_SCHEMA >= SchemaType::UNCONSTRAINED) {
        $comments = array();

        if ($USE_CANNED) {
          $fetchedComments = array ( 0 => array ( 0 => array ( 0 => '3', 1 => 'comment', ), 1 => 'This is a very bad comment. Stay', ), 1 => array ( 0 => array ( 0 => '3', 1 => 'date', ), 1 => '2001-10-17 19:39:25', ), 2 => array ( 0 => array ( 0 => '3', 1 => 'from_user_id', ), 1 => '776067', ), 3 => array ( 0 => array ( 0 => '3', 1 => 'item_id', ), 1 => '3', ), 4 => array ( 0 => array ( 0 => '3', 1 => 'rating', ), 1 => '-5', ), );
        } else {
        try {
            $cf = $link->I2607716123;
            $cf->return_format = ColumnFamily::ARRAY_FORMAT;
            $fetchedComments = $cf->get($userId);
        } catch (cassandra\NotFoundException $e) {
            $fetchedComments = array();
        }
        }

        foreach ($fetchedComments as $comment) {
            $id = $comment[0][0];
            if (!isset($comments[$id])) {
                $comments[$id] = array();
            }
            $comments[$id][$comment[0][1]] = $comment[1];
        }
        $commentsResult = array();
        foreach ($comments as $id => $comment) {
            $commentsResult[] = array_merge(array("id" => $id), $comment);
        }
    } elseif ($CURRENT_SCHEMA >= SchemaType::RELATIONAL) {
        try {
            if ($USE_CANNED) {
              $comment_ids = array ( 0 => 3, );
            } else {
              $comment_ids = array_keys($link->to_user->get($userId));
            }

            if ($USE_MULTIGET) {
              if ($USE_CANNED) {
                $commentsResult = array ( 3 => array ( 'comment' => 'This is a very bad comment. Stay', 'date' => '2001-10-17 19:39:25', 'from_user_id' => '776067', 'item_id' => '3', 'rating' => '-5', 'to_user_id' => '549946', ), );
              } else {
                $commentsResult = $link->comments->multiget($comment_ids);
              }
            } else {
              $commentsResult = array_map(function ($comment_id) use($link) { return $link->comments->get($comment_id); }, $comment_ids);
            }
        } catch (cassandra\NotFoundException $e) {
            $commentsResult = array();
        }
    }

    if (count($commentsResult) == 0) {
        print("<h2>There is no comment for this user.</h2><br>\n");
    } else {
    print("<DL>\n");
    foreach ($commentsResult as $commentsRow) {
        $authorId = $commentsRow["from_user_id"];

        // Q: SELECT id, nickname FROM users WHERE users.id = ?
        if ($CURRENT_SCHEMA >= SchemaType::UNCONSTRAINED) {
            try {
                if ($USE_CANNED) {
                  $authorName = array ( 0 => 'user776067', );
                } else {
                  $authorName = array_values($link->I3318501374->get($authorId));
                }
                $authorRow = array("nickname" => $authorName[0]);
            } catch (cassandra\NotFoundException $e) {
                $authorRow = null;
            }
        } elseif ($CURRENT_SCHEMA >= SchemaType::RELATIONAL) {
            try {
                if ($USE_CANNED) {
                  $authorRow = array ( 'nickname' => 'user776067', );
                } else {
                  $authorRow = $link->users->get($authorId, $column_slice=null, $column_names=array("nickname"));
                }
            } catch (cassandra\NotFoundException $e) {
                $authorRow = null;
            }
        }

        if (!$authorRow) {
            die("ERROR: This author does not exist.<br>\n");
        } else {
            $authorName = $authorRow["nickname"];
        }
        $date = $commentsRow["date"];
        $comment = $commentsRow["comment"];
        print("<DT><b><BIG><a href=\"/PHP/ViewUserInfo.php?userId=".$authorId."\">$authorName</a></BIG></b>"." wrote the ".$date."<DD><i>".$comment."</i><p>\n");
    }
    print("</DL>\n");

    }
    $link->close();

    printHTMLfooter($scriptName, $startTime);
    ?>
  </body>
</html>
