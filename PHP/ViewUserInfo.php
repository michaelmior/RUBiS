<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <body>
    <?php
    $scriptName = "ViewUserInfo.php";
    require "PHPprinter.php";
    $startTime = getMicroTime();

    $userId = $_POST['userId'];
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

    if ($CURRENT_SCHEMA == SchemaType::RELATIONAL) {
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
    if ($CURRENT_SCHEMA == SchemaType::RELATIONAL) {
        $comment_ids = array_keys($link->to_user->get($userId));
        if (count($comment_ids) == 0) {
            $commentsResult = array();
        } else {
            $commentsResult = $link->comments->multiget($comment_ids);
        }
    }

    if (count($commentsResult) == 0) {
        print("<h2>There is no comment for this user.</h2><br>\n");
    } else {
    print("<DL>\n");
    foreach ($commentsResult as $commentsRow) {
        $authorId = $commentsRow["from_user_id"];

        if ($CURRENT_SCHEMA == SchemaType::RELATIONAL) {
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
