<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <body>
    <?php
    $scriptName = "BrowseRegions.php";
    require "PHPprinter.php";
    $startTime = getMicroTime();

    printHTMLheader("RUBiS available regions");

    getDatabaseLink($link);
    if ($CURRENT_SCHEMA == SchemaType::RELATIONAL) {
      $regions = $link->regions->get_range();
      if ($regions->current() === FALSE)
        print("<h2>Sorry, but there is no region available at this time. Database table is empty</h2><br>");
      else
        print("<h2>Currently available regions</h2><br>");

      foreach ($regions as $id => $row) {
          print("<a href=\"/PHP/BrowseCategories.php?region=".$id."\">".$row["name"]."</a><br>\n");
      }
    }


    $link->close();

    printHTMLfooter($scriptName, $startTime);
    ?>
  </body>
</html>
