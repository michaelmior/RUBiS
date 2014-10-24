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
 * Contributor(s): 
 */

package edu.rice.rubis.client;

import java.util.concurrent.atomic.AtomicInteger;
import java.util.concurrent.atomic.AtomicLong;

/**
 * This class provides thread-safe statistics. Each statistic entry is composed
 * as follow:
 * 
 * <pre>
 * count     : statistic counter
 * error     : statistic error counter
 * minTime   : minimum time for this entry (automatically computed)
 * maxTime   : maximum time for this entry (automatically computed)
 * totalTime : total time for this entry
 * </pre>
 * 
 * @author <a href="mailto:cecchet@rice.edu">Emmanuel Cecchet</a> and <a
 *         href="mailto:julie.marguerite@inrialpes.fr">Julie Marguerite</a>
 * @version 1.0
 */

public class Stats
{
  private int  nbOfStats;
  private AtomicInteger count[];
  private AtomicInteger  error[];
  private AtomicLong minTime[];
  private AtomicLong maxTime[];
  private AtomicLong totalTime[];
  private AtomicInteger nbSessions;  // Number of sessions succesfully ended
  private AtomicLong sessionsTime; // Sessions total duration

  /**
   * Creates a new <code>Stats</code> instance. The entries are reset to 0.
   * 
   * @param NbOfStats number of entries to create
   */
  public Stats(int NbOfStats)
  {
    nbOfStats = NbOfStats;

    count = new AtomicInteger[nbOfStats];
    error = new AtomicInteger[nbOfStats];
    minTime = new AtomicLong[nbOfStats];
    maxTime = new AtomicLong[nbOfStats];
    totalTime = new AtomicLong[nbOfStats];
    for (int i=0; i < nbOfStats; i++) {
        count[i] = new AtomicInteger();
        error[i] = new AtomicInteger();
        minTime[i] = new AtomicLong();
        maxTime[i] = new AtomicLong();
        totalTime[i] = new AtomicLong();
    }

    reset();
  }

  /**
   * Resets all entries to 0
   */
  public synchronized void reset()
  {
    int i;

    for (i = 0; i < nbOfStats; i++)
    {
      count[i] = new AtomicInteger();
      error[i] = new AtomicInteger();
      minTime[i] = new AtomicLong(Long.MAX_VALUE);
      maxTime[i] = new AtomicLong();
      totalTime[i] =new AtomicLong();
    }
    nbSessions = new AtomicInteger();
    sessionsTime = new AtomicLong();
  }

  /**
   * Add a session duration to the total sessions duration and increase the
   * number of succesfully ended sessions.
   * 
   * @param time duration of the session
   */
  public void addSessionTime(long time)
  {
    nbSessions.incrementAndGet();
    if (time < 0)
    {
      System.err.println("Negative time received in Stats.addSessionTime("
          + time + ")<br>\n");
      return;
    }
    sessionsTime.addAndGet(time);
  }

  /**
   * Increment the number of succesfully ended sessions.
   */
  public void addSession()
  {
    nbSessions.incrementAndGet();
  }

  /**
   * Increment an entry count by one.
   * 
   * @param index index of the entry
   */
  public void incrementCount(int index)
  {
    count[index].incrementAndGet();
  }

  /**
   * Increment an entry error by one.
   * 
   * @param index index of the entry
   */
  public void incrementError(int index)
  {
    error[index].incrementAndGet();
  }

  /**
   * Add a new time sample for this entry. <code>time</code> is added to total
   * time and both minTime and maxTime are updated if needed.
   * 
   * @param index index of the entry
   * @param time time to add to this entry
   */
  public void updateTime(int index, long time)
  {
    if (time < 0)
    {
      System.err.println("Negative time received in Stats.updateTime(" + time
          + ")<br>\n");
      return;
    }
    totalTime[index].addAndGet(time);

    long extremeTime;

    extremeTime = maxTime[index].get();
    if (time > extremeTime)
      maxTime[index].compareAndSet(extremeTime, time);

    extremeTime = minTime[index].get();
    if (time < extremeTime)
        minTime[index].compareAndSet(extremeTime, time);
  }

  /**
   * Get current count of an entry
   * 
   * @param index index of the entry
   * @return entry count value
   */
  public int getCount(int index)
  {
    return count[index].get();
  }

  /**
   * Get current error count of an entry
   * 
   * @param index index of the entry
   * @return entry error value
   */
  public int getError(int index)
  {
    return error[index].get();
  }

  /**
   * Get the minimum time of an entry
   * 
   * @param index index of the entry
   * @return entry minimum time
   */
  public long getMinTime(int index)
  {
    return minTime[index].get();
  }

  /**
   * Get the maximum time of an entry
   * 
   * @param index index of the entry
   * @return entry maximum time
   */
  public long getMaxTime(int index)
  {
    return maxTime[index].get();
  }

  /**
   * Get the total time of an entry
   * 
   * @param index index of the entry
   * @return entry total time
   */
  public long getTotalTime(int index)
  {
    return totalTime[index].get();
  }

  /**
   * Get the total number of entries that are collected
   * 
   * @return total number of entries
   */
  public int getNbOfStats()
  {
    return nbOfStats;
  }

  /**
   * Adds the entries of another Stats object to this one.
   * 
   * @param anotherStat stat to merge with current stat
   */
  public synchronized void merge(Stats anotherStat)
  {
    if (this == anotherStat)
    {
      System.out.println("You cannot merge a stats with itself");
      return;
    }
    if (nbOfStats != anotherStat.getNbOfStats())
    {
      System.out.println("Cannot merge stats of differents sizes.");
      return;
    }
    for (int i = 0; i < nbOfStats; i++)
    {
      count[i].set(count[i].get() + anotherStat.getCount(i));
      error[i].set(error[i].get() + anotherStat.getError(i));
      if (minTime[i].get() > anotherStat.getMinTime(i))
        minTime[i].set(anotherStat.getMinTime(i));
      if (maxTime[i].get() < anotherStat.getMaxTime(i))
        maxTime[i].set(anotherStat.getMaxTime(i));
      totalTime[i].addAndGet(anotherStat.getTotalTime(i));
    }
    nbSessions.addAndGet(anotherStat.nbSessions.get());
    sessionsTime.addAndGet(anotherStat.sessionsTime.get());
  }

  /**
   * Display an HTML table containing the stats for each state. Also compute the
   * totals and average throughput
   * 
   * @param title table title
   * @param sessionTime total time for this session
   * @param exclude0Stat true if you want to exclude the stat with a 0 value
   *          from the output
   */
  public void display_stats(String title, long sessionTime, boolean exclude0Stat)
  {
    int counts = 0;
    int errors = 0;
    long time = 0;

    System.out.println("<br><h3>" + title + " statistics</h3><p>");
    System.out.println("<TABLE BORDER=1>");
    System.out
        .println("<THEAD><TR><TH>State name<TH>% of total<TH>Count<TH>Errors<TH>Minimum Time<TH>Maximum Time<TH>Average Time<TBODY>");
    // Display stat for each state
    for (int i = 0; i < getNbOfStats(); i++)
    {
      counts += count[i].get();
      errors += error[i].get();
      time += totalTime[i].get();
    }

    for (int i = 0; i < getNbOfStats(); i++)
    {
      if ((exclude0Stat && count[i].get() != 0) || (!exclude0Stat))
      {
        System.out.print("<TR><TD><div align=left>"
            + TransitionTable.getStateName(i) + "</div><TD><div align=right>");
        if ((counts > 0) && (count[i].get() > 0))
          System.out.print(100 * count[i].get() / counts + " %");
        else
          System.out.print("0 %");
        System.out.print("</div><TD><div align=right>" + count[i].get()
            + "</div><TD><div align=right>");
        if (error[i].get() > 0)
          System.out.print("<B>" + error[i].get() + "</B>");
        else
          System.out.print(error[i]);
        System.out.print("</div><TD><div align=right>");
        if (minTime[i].get() != Long.MAX_VALUE)
          System.out.print(minTime[i].get());
        else
          System.out.print("0");
        System.out.print(" ms</div><TD><div align=right>" + maxTime[i].get()
            + " ms</div><TD><div align=right>");
        if (count[i].get() != 0)
          System.out.println(totalTime[i].get() / count[i].get() + " ms</div>");
        else
          System.out.println("0 ms</div>");
      }
    }

    // Display total
    if (counts > 0)
    {
      System.out
          .print("<TR><TD><div align=left><B>Total</B></div><TD><div align=right><B>100 %</B></div><TD><div align=right><B>"
              + counts
              + "</B></div><TD><div align=right><B>"
              + errors
              + "</B></div><TD><div align=center>-</div><TD><div align=center>-</div><TD><div align=right><B>");
      counts += errors;
      System.out.println(time / counts + " ms</B></div>");
      // Display stats about sessions
      System.out
          .println("<TR><TD><div align=left><B>Average throughput</div></B><TD colspan=6><div align=center><B>"
              + 1000 * counts / sessionTime + " req/s</B></div>");
      System.out
          .println("<TR><TD><div align=left>Completed sessions</div><TD colspan=6><div align=left>"
              + nbSessions.get() + "</div>");
      System.out
          .println("<TR><TD><div align=left>Total time</div><TD colspan=6><div align=left>"
              + sessionsTime.get() / 1000L + " seconds</div>");
      System.out
          .print("<TR><TD><div align=left><B>Average session time</div></B><TD colspan=6><div align=left><B>");
      if (nbSessions.get() > 0)
        System.out.print(sessionsTime.get() / (long) nbSessions.get() / 1000L + " seconds");
      else
        System.out.print("0 second");
      System.out.println("</B></div>");
    }
    System.out.println("</TABLE><p>");
  }

}
