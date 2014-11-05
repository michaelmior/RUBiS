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
    if ($CURRENT_SCHEMA >= SchemaType::HALF) {
      $cf = $link->I3858759750;
      $cf->return_format = ColumnFamily::ARRAY_FORMAT;
      $categories = array();
      foreach ($cf->get(1) as $row) {
        $categories[$row[0][0]] = array("id" => $row[0][0], "name" => $row[1]);
      }
    } elseif ($CURRENT_SCHEMA >= SchemaType::UNCONSTRAINED) {
      $cf = $link->I3208103476;
      $cf->return_format = ColumnFamily::ARRAY_FORMAT;
      $categories = array();
      foreach ($cf->get(1) as $row) {
        $categories[$row[0][0]] = array("id" => $row[0][0], "name" => $row[1]);
      }
    } elseif ($CURRENT_SCHEMA >= SchemaType::RELATIONAL) {
      if ($USE_CANNED) {
        $categories = array ( 0 => array ( 'dummy' => '1', 'id' => '6', 'name' => 'Collectibles', ), 1 => array ( 'dummy' => '1', 'id' => '16', 'name' => 'Sports', ), 2 => array ( 'dummy' => '1', 'id' => '19', 'name' => 'Toys & Hobbies', ), 3 => array ( 'dummy' => '1', 'id' => '13', 'name' => 'Music', ), 4 => array ( 'dummy' => '1', 'id' => '7', 'name' => 'Computers', ), 5 => array ( 'dummy' => '1', 'id' => '17', 'name' => 'Stamps', ), 6 => array ( 'dummy' => '1', 'id' => '9', 'name' => 'Dolls & Bears', ), 7 => array ( 'dummy' => '1', 'id' => '15', 'name' => 'Pottery & Glass', ), 8 => array ( 'dummy' => '1', 'id' => '10', 'name' => 'Home & Garden', ), 9 => array ( 'dummy' => '1', 'id' => '4', 'name' => 'Clothing & Accessories', ), 10 => array ( 'dummy' => '1', 'id' => '3', 'name' => 'Business, Office & Industrial', ), 11 => array ( 'dummy' => '1', 'id' => '5', 'name' => 'Coins', ), 12 => array ( 'dummy' => '1', 'id' => '18', 'name' => 'Tickets & Travel', ), 13 => array ( 'dummy' => '1', 'id' => '14', 'name' => 'Photo', ), 14 => array ( 'dummy' => '1', 'id' => '8', 'name' => 'Consumer Electronics', ), 15 => array ( 'dummy' => '1', 'id' => '20', 'name' => 'Everything Else', ), 16 => array ( 'dummy' => '1', 'id' => '2', 'name' => 'Books', ), 17 => array ( 'dummy' => '1', 'id' => '12', 'name' => 'Movies & Television', ), 18 => array ( 'dummy' => '1', 'id' => '11', 'name' => 'Jewelry, Gems & Watches', ), 19 => array ( 'dummy' => '1', 'id' => '1', 'name' => 'Antiques & Art', ), );
      } else {
        $categories = $link->categories->get_range();
        if (!!current($categories)) {
          $categories = array();
        }
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
