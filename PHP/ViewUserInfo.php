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
            $userRow = $link->users->get($userId);
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
        $cf = $link->I2607716123;
        $cf->return_format = ColumnFamily::ARRAY_FORMAT;
        $comments = array();
        foreach ($cf->get($userId) as $comment) {
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
            $comment_ids = array_keys($link->to_user->get($userId));
            $commentsResult = $link->comments->multiget($comment_ids);
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
                $authorName = array_values($link->I3318501374->get($authorId));
                $authorRow = array("nickname" => $authorName[0]);
            } catch (cassandra\NotFoundException $e) {
                $authorRow = null;
            }
        } elseif ($CURRENT_SCHEMA >= SchemaType::RELATIONAL) {
            try {
                $authorRow = $link->users->get($authorId, $column_slice=null, $column_names=array("nickname"));
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
