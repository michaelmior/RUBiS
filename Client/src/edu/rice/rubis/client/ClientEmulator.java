/*
 * RUBiS
 * Copyright (C) 2002, 2003, 2004 French National Institute For Research In Computer
 * Science And Control (INRIA).
 * Contact: jmob@objectweb.org
 * 
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the GNU Lesser General Public License as published by the
 * Free Software Foundation; either version 2.1 of the License, or any later
 * version.
 * 
 * This library is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 * for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this library; if not, write to the Free Software Foundation,
 * Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA.
 *
 * Initial developer(s): Emmanuel Cecchet, Julie Marguerite
 * Contributor(s): Jeremy Philippe, Niraj Tolia
 */

package edu.rice.rubis.client;

import java.io.BufferedReader;
import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.PrintStream;
import java.util.GregorianCalendar;
import java.util.concurrent.atomic.AtomicReference;

import edu.rice.rubis.beans.TimeManagement;
import edu.rice.rubis.client.RUBiSProperties;
import edu.rice.rubis.client.Stats;
import edu.rice.rubis.client.TransitionTable;
import edu.rice.rubis.client.URLGenerator;
import edu.rice.rubis.client.UserSession;

/**
 * RUBiS client emulator. This class plays random user sessions emulating a Web
 * browser.
 * 
 * @author <a href="mailto:cecchet@rice.edu">Emmanuel Cecchet</a> and <a
 *         href="mailto:julie.marguerite@inrialpes.fr">Julie Marguerite</a>
 * @version 1.0
 */
public class ClientEmulator
{
  private RUBiSProperties rubis           = null; // access to rubis.properties
  // file

  private URLGenerator    urlGen          = null;

  // URL generator corresponding to the version to be used (PHP)
  private static AtomicReference<Float> slowdownFactor = new AtomicReference<Float>(new Float(0));

  private static volatile boolean  endOfSimulation = false;

  /**
   * Creates a new <code>ClientEmulator</code> instance. The program is
   * stopped on any error reading the configuration files.
   */
  public ClientEmulator(String propertiesFileName)
  {
    // Initialization, check that all files are ok
    rubis = new RUBiSProperties(propertiesFileName);
    urlGen = rubis.checkPropertiesFileAndGetURLGenerator();
    if (urlGen == null)
      Runtime.getRuntime().exit(1);
    // Check that the transition table is ok and print it
    TransitionTable transition = new TransitionTable(rubis.getNbOfColumns(),
        rubis.getNbOfRows(), null, rubis.useTPCWThinkTime());
    if (!transition.ReadExcelTextFile(rubis.getTransitionTable()))
      Runtime.getRuntime().exit(1);
    else
      transition.displayMatrix();
  }

  /**
   * Updates the slowdown factor.
   * 
   * @param newValue new slowdown value
   */
  private void setSlowDownFactor(float newValue)
  {
    slowdownFactor.lazySet(new Float(newValue));
  }

  /**
   * Get the slowdown factor corresponding to current ramp (up, session or
   * down).
   * 
   * @return slowdown factor of current ramp
   */
  public static float getSlowDownFactor()
  {
    return slowdownFactor.get();
  }

  /**
   * Set the end of the current simulation
   */
  private void setEndOfSimulation()
  {
    endOfSimulation = true;
  }

  /**
   * True if end of simulation has been reached.
   * 
   * @return true if end of simulation
   */
  public static boolean isEndOfSimulation()
  {
    return endOfSimulation;
  }

  /**
   * Run the node_info.sh script on the remote node and just forward what we get
   * from standard output.
   * 
   * @param node node to get information from
   */
  private void printNodeInformation(String node)
  {
    try
    {
      File dir = new File(".");
      /*
       * String nodeInfoProgram = dir.getCanonicalPath() +
       * "/bench/node_info.sh";
       */
      String nodeInfoProgram = "/bin/echo \"Host  : \"`/bin/hostname` ; "
          + "/bin/echo \"Kernel: \"`/bin/cat /proc/version` ; "
          + "/bin/grep net /proc/pci ; "
          + "/bin/grep processor /proc/cpuinfo ; "
          + "/bin/grep vendor_id /proc/cpuinfo ; "
          + "/bin/grep model /proc/cpuinfo ; "
          + "/bin/grep MHz /proc/cpuinfo ; "
          + "/bin/grep cache /proc/cpuinfo ; "
          + "/bin/grep MemTotal /proc/meminfo ; "
          + "/bin/grep SwapTotal /proc/meminfo ";

      String[] cmd = new String[4];
      cmd[0] = rubis.getMonitoringRsh();
      cmd[1] = "-x";
      cmd[2] = node;
      cmd[3] = nodeInfoProgram;
      Process p = Runtime.getRuntime().exec(cmd);
      BufferedReader read = new BufferedReader(new InputStreamReader(p
          .getInputStream()));
      String msg;
      while ((msg = read.readLine()) != null)
      {
        System.out.println(msg + "<br>");
      }
      read.close();
    }
    catch (Exception ioe)
    {
      System.out.println("An error occured while getting node information ("
          + ioe.getMessage() + ")");
    }
  }

  /**
   * Main program take an optional output file argument only if it is run on as
   * a remote client.
   * 
   * @param args optional output file if run as remote client
   */
  public static void main(String[] args)
  {
    GregorianCalendar startDate;
    GregorianCalendar endDate;
    GregorianCalendar upRampDate;
    GregorianCalendar runSessionDate;
    GregorianCalendar downRampDate;
    GregorianCalendar endDownRampDate;
    String reportDir = "";
    String tmpDir = "/tmp/";
    boolean isMainClient = (args.length <= 2); // Check if we are the main
    // client
    String propertiesFileName;

    if (isMainClient)
    {
      // Start by creating a report directory and redirecting output to an
      // index.html file
      System.out
          .println("RUBiS client emulator - (C) Rice University/INRIA 2001\n");

      if (args.length <= 1)
      {
        reportDir = "bench/" + TimeManagement.currentDateToString() + "/";
        reportDir = reportDir.replace(' ', '@');
      }
      else
      {
        reportDir = "bench/" + args[1];
      }
      try
      {
        System.out.println("Creating report directory " + reportDir);
        File dir = new File(reportDir);
        dir.mkdirs();
        if (!dir.isDirectory())
        {
          System.out.println("Unable to create " + reportDir
              + " using current directory instead");
          reportDir = "./";
        }
        else
          reportDir = dir.getCanonicalPath() + "/";
        System.out.println("Redirecting output to '" + reportDir
            + "index.html'");
        PrintStream outputStream = new PrintStream(new FileOutputStream(
            reportDir + "index.html"));
        System.out.println("Please wait while experiment is running ...");
        System.setOut(outputStream);
        System.setErr(outputStream);
      }
      catch (Exception e)
      {
        System.out
            .println("Output redirection failed, displaying results on standard output ("
                + e.getMessage() + ")");
      }
      System.out
          .println("<h2>RUBiS client emulator - (C) Rice University/INRIA 2001</h2><p>\n");
      startDate = new GregorianCalendar();
      System.out.println("<h3>Test date: "
          + TimeManagement.dateToString(startDate) + "</h3><br>\n");

      System.out.println("<A HREF=\"#config\">Test configuration</A><br>");
      System.out.println("<A HREF=\"trace_client0.html\">Test trace</A><br>");
      System.out
          .println("<A HREF=\"perf.html\">Test performance report</A><br><p>");
      System.out.println("<p><hr><p>");

      System.out
          .println("<CENTER><A NAME=\"config\"></A><h2>*** Test configuration ***</h2></CENTER>");
      if (args.length == 0)
        propertiesFileName = "rubis";
      else
        propertiesFileName = args[0];
    }
    else
    {
      System.out
          .println("RUBiS remote client emulator - (C) Rice University/INRIA 2001\n");
      startDate = new GregorianCalendar();
      propertiesFileName = args[2];
    }

    ClientEmulator client = new ClientEmulator(propertiesFileName);
    // Get also rubis.properties info

    Stats stats = new Stats(client.rubis.getNbOfRows());
    Stats upRampStats = new Stats(client.rubis.getNbOfRows());
    Stats runSessionStats = new Stats(client.rubis.getNbOfRows());
    Stats downRampStats = new Stats(client.rubis.getNbOfRows());
    Stats allStats = new Stats(client.rubis.getNbOfRows());
    UserSession[] sessions = new UserSession[client.rubis.getNbOfClients()];
    boolean cjdbcFlag = client.rubis.getCJDBCServerName() != null
        && !client.rubis.getCJDBCServerName().equals("");
    System.out.println("<p><hr><p>");

    if (isMainClient)
    {
      // Start remote clients
      System.out.println("Total number of clients for this experiment: "
          + (client.rubis.getNbOfClients()) + "<br>");

      // Redirect output for traces
      try
      {
        PrintStream outputStream = new PrintStream(new FileOutputStream(
            reportDir + "trace_client0.html"));
        System.setOut(outputStream);
        System.setErr(outputStream);
      }
      catch (FileNotFoundException fnf)
      {
        System.err.println("Unable to redirect main client output, got error ("
            + fnf.getMessage() + ")<br>");
      }
    }

    // #############################
    // ### TEST TRACE BEGIN HERE ###
    // #############################

    System.out
        .println("<CENTER></A><A NAME=\"trace\"><h2>*** Test trace ***</h2></CENTER><p>");
    System.out
        .println("<A HREF=\"trace_client0.html\">Main client traces</A><br>");
    System.out.println("<br><p>");
    System.out.println("&nbsp&nbsp&nbsp<A HREF=\"#up\">Up ramp trace</A><br>");
    System.out
        .println("&nbsp&nbsp&nbsp<A HREF=\"#run\">Runtime session trace</A><br>");
    System.out
        .println("&nbsp&nbsp&nbsp<A HREF=\"#down\">Down ramp trace</A><br><p><p>");

    // Run user sessions
    System.out.println("ClientEmulator: Starting "
        + client.rubis.getNbOfClients() + " session threads<br>");
    for (int i = 0; i < client.rubis.getNbOfClients(); i++)
    {
      sessions[i] = new UserSession("UserSession" + i, client.urlGen,
          client.rubis, stats);
      sessions[i].start();
    }

    // Start up-ramp
    System.out.println("<br><A NAME=\"up\"></A>");
    System.out
        .println("<h3>ClientEmulator: Switching to ** UP RAMP **</h3><br><p>");
    client.setSlowDownFactor(client.rubis.getUpRampSlowdown());
    upRampDate = new GregorianCalendar();
    try
    {
      Thread.currentThread().sleep(client.rubis.getUpRampTime());
    }
    catch (java.lang.InterruptedException ie)
    {
      System.err.println("ClientEmulator has been interrupted.");
    }
    upRampStats.merge(stats);
    stats.reset();
    // Note that as this is not atomic we may lose some stats here ...

    // Start runtime session
    System.out.println("<br><A NAME=\"run\"></A>");
    System.out
        .println("<h3>ClientEmulator: Switching to ** RUNTIME SESSION **</h3><br><p>");
    client.setSlowDownFactor(1);
    runSessionDate = new GregorianCalendar();
    try
    {
      Thread.currentThread().sleep(client.rubis.getSessionTime());
    }
    catch (java.lang.InterruptedException ie)
    {
      System.err.println("ClientEmulator has been interrupted.");
    }
    runSessionStats.merge(stats);
    stats.reset();
    // Note that as this is not atomic we may lose some stats here ...

    // Start down-ramp
    System.out.println("<br><A NAME=\"down\"></A>");
    System.out
        .println("<h3>ClientEmulator: Switching to ** DOWN RAMP **</h3><br><p>");
    client.setSlowDownFactor(client.rubis.getDownRampSlowdown());
    downRampDate = new GregorianCalendar();
    try
    {
      Thread.currentThread().sleep(client.rubis.getDownRampTime());
    }
    catch (java.lang.InterruptedException ie)
    {
      System.err.println("ClientEmulator has been interrupted.");
    }
    downRampStats.merge(stats);
    endDownRampDate = new GregorianCalendar();

    // Wait for completion
    client.setEndOfSimulation();
    System.out.println("ClientEmulator: Shutting down threads ...<br>");
    for (int i = 0; i < client.rubis.getNbOfClients(); i++)
    {
      try
      {
        sessions[i].join(2000);
      }
      catch (java.lang.InterruptedException ie)
      {
        System.err.println("ClientEmulator: Thread " + i
            + " has been interrupted.");
      }
    }
    System.out.println("Done\n");
    endDate = new GregorianCalendar();
    allStats.merge(stats);
    allStats.merge(runSessionStats);
    allStats.merge(upRampStats);
    System.out.println("<p><hr><p>");

    // #############################################
    // ### EXPERIMENT IS OVER, COLLECT THE STATS ###
    // #############################################

    // All clients completed, here is the performance report !
    // but first redirect the output
    try
    {
      PrintStream outputStream;
      if (isMainClient)
        outputStream = new PrintStream(new FileOutputStream(reportDir
            + "perf.html"));
      else
        outputStream = new PrintStream(new FileOutputStream(args[1]));
      System.setOut(outputStream);
      System.setErr(outputStream);
    }
    catch (Exception e)
    {
      System.out
          .println("Output redirection failed, displaying results on standard output ("
              + e.getMessage() + ")");
    }

    System.out
        .println("<center><h2>*** Performance Report ***</h2></center><br>");
    System.out
        .println("<A HREF=\"perf.html\">Overall performance report</A><br>");
    System.out
        .println("<A HREF=\"stat_client0.html\">Main client (localhost) statistics</A><br>");

    System.out
        .println("<p><br>&nbsp&nbsp&nbsp<A HREF=\"perf.html#node\">Node information</A><br>");
    System.out
        .println("&nbsp&nbsp&nbsp<A HREF=\"#time\">Test timing information</A><br>");
    System.out
        .println("&nbsp&nbsp&nbsp<A HREF=\"#up_stat\">Up ramp statistics</A><br>");
    System.out
        .println("&nbsp&nbsp&nbsp<A HREF=\"#run_stat\">Runtime session statistics</A><br>");
    System.out
        .println("&nbsp&nbsp&nbsp<A HREF=\"#down_stat\">Down ramp statistics</A><br>");
    System.out
        .println("&nbsp&nbsp&nbsp<A HREF=\"#all_stat\">Overall statistics</A><br>");

    if (isMainClient)
    {
      // Get information about each node
      System.out
          .println("<br><A NAME=\"node\"></A><h3>Node Information</h3><br>");

      // Web server
      System.out.println("<B>Web server</B><br>");
      client.printNodeInformation(client.rubis.getWebServerName());

      // Database server
      System.out.println("<br><B>Database server</B><br>");
      client.printNodeInformation((String) client.rubis.getDBServerNames().get(
          0));

      // Client
      System.out.println("<br><B>Local client</B><br>");
      client.printNodeInformation("localhost");

      System.out
          .println("<A HREF=\"perf.html\">Overall performance report</A><br>");
      System.out
          .println("<A HREF=\"stat_client0.html\">Main client (localhost) statistics</A><br>");

      System.out
          .println("<p><br>&nbsp&nbsp&nbsp<A HREF=\"#node\">Node information</A><br>");

      System.out
          .println("<br><A NAME=\"node\"></A><h3>Node Information</h3><br>");
      for (int i = 0; i < client.rubis.getDBServerNames().size(); i++)
      {
        System.out.println("<br><B>Database server " + i + "</B><br>");
        client.printNodeInformation((String) client.rubis.getDBServerNames()
            .get(i));
      }

      try
      {
        PrintStream outputStream = new PrintStream(new FileOutputStream(
            reportDir + "stat_client0.html"));
        System.setOut(outputStream);
        System.setErr(outputStream);
        System.out
            .println("<center><h2>*** Performance Report ***</h2></center><br>");
        System.out
            .println("<A HREF=\"perf.html\">Overall performance report</A><br>");
        System.out
            .println("<A HREF=\"stat_client0.html\">Main client (localhost) statistics</A><br>");

        System.out
            .println("<p><br>&nbsp&nbsp&nbsp<A HREF=\"perf.html#node\">Node information</A><br>");
        System.out
            .println("&nbsp&nbsp&nbsp<A HREF=\"#time\">Test timing information</A><br>");
        System.out
            .println("&nbsp&nbsp&nbsp<A HREF=\"#up_stat\">Up ramp statistics</A><br>");
        System.out
            .println("&nbsp&nbsp&nbsp<A HREF=\"#run_stat\">Runtime session statistics</A><br>");
        System.out
            .println("&nbsp&nbsp&nbsp<A HREF=\"#down_stat\">Down ramp statistics</A><br>");
        System.out
            .println("&nbsp&nbsp&nbsp<A HREF=\"#all_stat\">Overall statistics</A><br>");
      }
      catch (Exception ioe)
      {
        System.out.println("An error occured while getting node information ("
            + ioe.getMessage() + ")");
      }
    }

    // Test timing information
    System.out
        .println("<br><p><A NAME=\"time\"></A><h3>Test timing information</h3><p>");
    System.out.println("<TABLE BORDER=1>");
    System.out.println("<TR><TD><B>Test start</B><TD>"
        + TimeManagement.dateToString(startDate));
    System.out.println("<TR><TD><B>Up ramp start</B><TD>"
        + TimeManagement.dateToString(upRampDate));
    System.out.println("<TR><TD><B>Runtime session start</B><TD>"
        + TimeManagement.dateToString(runSessionDate));
    System.out.println("<TR><TD><B>Down ramp start</B><TD>"
        + TimeManagement.dateToString(downRampDate));
    System.out.println("<TR><TD><B>Test end</B><TD>"
        + TimeManagement.dateToString(endDate));
    System.out.println("<TR><TD><B>Up ramp length</B><TD>"
        + TimeManagement.diffTime(upRampDate, runSessionDate) + " (requested "
        + client.rubis.getUpRampTime() + " ms)");
    System.out.println("<TR><TD><B>Runtime session length</B><TD>"
        + TimeManagement.diffTime(runSessionDate, downRampDate)
        + " (requested " + client.rubis.getSessionTime() + " ms)");
    System.out.println("<TR><TD><B>Down ramp length</B><TD>"
        + TimeManagement.diffTime(downRampDate, endDownRampDate)
        + " (requested " + client.rubis.getDownRampTime() + " ms)");
    System.out.println("<TR><TD><B>Total test length</B><TD>"
        + TimeManagement.diffTime(startDate, endDate));
    System.out.println("</TABLE><p>");

    // Stats for each ramp
    System.out.println("<br><A NAME=\"up_stat\"></A>");
    upRampStats.display_stats("Up ramp", TimeManagement.diffTimeInMs(
        upRampDate, runSessionDate), false);
    System.out.println("<br><A NAME=\"run_stat\"></A>");
    runSessionStats.display_stats("Runtime session", TimeManagement
        .diffTimeInMs(runSessionDate, downRampDate), false);
    System.out.println("<br><A NAME=\"down_stat\"></A>");
    downRampStats.display_stats("Down ramp", TimeManagement.diffTimeInMs(
        downRampDate, endDownRampDate), false);
    System.out.println("<br><A NAME=\"all_stat\"></A>");
    allStats.display_stats("Overall", TimeManagement.diffTimeInMs(upRampDate,
        endDownRampDate), false);

    Runtime.getRuntime().exit(0);
  }

}
