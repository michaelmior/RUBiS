package edu.rice.rubis.beans;

import java.rmi.RemoteException;
import java.util.Collection;
import javax.ejb.CreateException;
import javax.ejb.EJBLocalHome;
import javax.ejb.FinderException;
import javax.ejb.RemoveException;

/** This is the LocalHome interface of the Item Bean */

public interface OldItemLocalHome extends EJBLocalHome {
  /**
   * This method is used to create a new Item Bean. Note that the item id
   * is automatically generated by the database (AUTO_INCREMENT) on the
   * primary key.
   *
   * @param itemName short item designation
   * @param itemDescription long item description, usually an HTML file
   * @param itemInitialPrice initial price fixed by the seller
   * @param itemQuantity number to sell (of this item)
   * @param itemReservePrice reserve price (minimum price the seller really wants to sell)
   * @param itemBuyNow price if a user wants to buy the item immediatly
   * @param duration duration of the auction in days (start date is when the method is called and end date is computed according to the duration)
   * @param itemSellerId seller id, must match the primary key of table users
   * @param itemCategoryId category id, must match the primary key of table categories
   *
   * @return pk primary key set to null
   */
  public OldItemLocal create(Integer itemId, String itemName, String itemDescription, float itemInitialPrice,
                     int itemQuantity, float itemReservePrice, float itemBuyNow, int duration,
                     Integer itemSellerId, Integer itemCategoryId) throws CreateException;


  /**
   * This method is used to retrieve an Item Bean from its primary key,
   * that is to say its id.
   *
   * @param id Item id (primary key)
   *
   * @return the Item if found else null
   */
  public OldItemLocal findByPrimaryKey(OldItemPK id) throws FinderException;


  /**
   * This method is used to retrieve all Item Beans belonging to
   * a seller. You must provide the user id of the seller.
   *
   * @param id User id of the seller
   *
   * @return List of Items found (eventually empty)
   */
  public Collection findBySeller(Integer id) throws FinderException;

  /**
   * This method is used to retrieve all Item Beans belonging to
   * a specific category. You must provide the category id.
   *
   * @param id Category id
   *
   * @return List of Items found (eventually empty)
   */
  public Collection findByCategory(Integer id) throws FinderException;


  /**
   * This method is used to retrieve all items from the database!
   *
   * @return List of all items (eventually empty)
   */
  public Collection findAllItems() throws FinderException;
}
