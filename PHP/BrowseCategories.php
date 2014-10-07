<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <body>
    <?php
use phpcassa\ColumnFamily;

    $scriptName = "BrowseCategories.php";
    require "PHPprinter.php";
    $startTime = getMicroTime();

    $region = @$_GET['region'];
    $username = @$_GET['nickname'];
    $password = @$_GET['password'];

    getDatabaseLink($link);

    $userId = -1;
    if (($username != null && $username !="") || ($password != null && $password !="")) {
        // Authenticate the user
        $userId = authenticate($username, $password, $link);
        if ($userId == -1) {
            printError($scriptName, $startTime, "Authentication", "You don't have an account on RUBiS!<br>You have to register first.<br>\n");
            exit();
        }
    }

    printHTMLheader("RUBiS available categories");

    // Q: SELECT id, name FROM categories WHERE categories.dummy = 1
    if ($CURRENT_SCHEMA >= SchemaType::UNCONSTRAINED) {
      $cf = $link->I3208103476;
      $cf->return_format = ColumnFamily::ARRAY_FORMAT;
      $categories = array();
      foreach ($cf->get(1) as $row) {
        $categories[$row[0][0]] = array("id" => $row[0][0], "name" => $row[1]);
      }
    } elseif ($CURRENT_SCHEMA >= SchemaType::RELATIONAL) {
      $categories = $link->categories->get_range();
      if (!!current($categories)) {
        $categories = array();
      }
    }
    if (empty($categories)) {
        print("<h2>Sorry, but there is no category available at this time. Database table is empty</h2><br>\n");
    } else {
        print("<h2>Currently available categories</h2><br>\n");

      foreach ($categories as $id => $row) {
        if ($region != NULL)
          print("<a href=\"/PHP/SearchItemsByRegion.php?category=".$id."&categoryName=".urlencode($row["name"])."&region=$region\">".$row["name"]."</a><br>\n");
        else if ($userId != -1)
          print("<a href=\"/PHP/SellItemForm.php?category=".$row["id"]."&user=$userId\">".$row["name"]."</a><br>\n");
        else
          print("<a href=\"/PHP/SearchItemsByCategory.php?category=".$id."&categoryName=".urlencode($row["name"])."\">".$row["name"]."</a><br>\n");
      }
    }

    $link->close();

    printHTMLfooter($scriptName, $startTime);
    ?>
  </body>
</html>
