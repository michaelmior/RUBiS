package edu.rice.rubis.servlets;

import java.io.IOException;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;

import javax.servlet.ServletException;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;

/** Builds the html page with the list of all region in the database */
public class BrowseRegions extends RubisHttpServlet
{
  private ServletPrinter sp = null;
  private PreparedStatement stmt = null;
  private Connection conn = null;

  public int getPoolSize()
  {
    return Config.BrowseRegionsPoolSize;
  }

  private void closeConnection()
  {
    try
    {
      if (stmt != null)
        stmt.close(); // close statement
      if (conn != null)
        releaseConnection(conn);
    }
    catch (Exception ignore)
    {
    }
  }

  private void regionList()
  {
    String regionName;
    ResultSet rs = null;

    // get the list of regions
    try
    {
      conn = getConnection();
      // conn.setAutoCommit(false);	// faster if made inside a Tx

      stmt = conn.prepareStatement("SELECT name, id FROM regions");
      rs = stmt.executeQuery();
    }
    catch (Exception e)
    {
      sp.printHTML("Failed to executeQuery for the list of regions" + e);
      closeConnection();
      return;
    }
    try
    {
      if (!rs.first())
      {
        sp.printHTML(
          "<h2>Sorry, but there is no region available at this time. Database table is empty</h2><br>");
        closeConnection();
        return;
      }
      else
        sp.printHTML("<h2>Currently available regions</h2><br>");

      do
      {
        regionName = rs.getString("name");
        sp.printRegion(regionName);
      }
      while (rs.next());
      //conn.commit();

    }
    catch (Exception e)
    {
      sp.printHTML("Exception getting region list: " + e + "<br>");
      //       try
      //       {
      //         conn.rollback();
      //       }
      //       catch (Exception se) 
      //       {
      //         sp.printHTML("Transaction rollback failed: " + e +"<br>");
      //       }
      closeConnection();
    }
  }

  public void doGet(HttpServletRequest request, HttpServletResponse response)
    throws IOException, ServletException
  {
    sp = new ServletPrinter(response, "BrowseRegions");
    sp.printHTMLheader("RUBiS: Available regions");

    regionList();
    closeConnection();
    sp.printHTMLfooter();
  }

  /**
   * Clean up the connection pool.
   */
  public void destroy()
  {
    super.destroy();
  }

}
