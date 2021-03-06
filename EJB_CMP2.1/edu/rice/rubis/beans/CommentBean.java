package edu.rice.rubis.beans;

import java.rmi.*;
import javax.ejb.*;
import javax.rmi.PortableRemoteObject;
import javax.naming.InitialContext;

/**
 * BidBean is an entity bean with "container managed persistence". 
 * The state of an instance is stored into a relational database. 
 * The following table should exist:<p>
 * <pre>
 * CREATE TABLE comments (
 *   id           INTEGER UNSIGNED NOT NULL UNIQUE,
 *   from_user_id INTEGER,
 *   to_user_id   INTEGER,
 *   item_id      INTEGER,
 *   rating       INTEGER,
 *   date         DATETIME,
 *   comment      TEXT
 *   PRIMARY KEY(id),
 *   INDEX from_user (from_user_id),
 *   INDEX to_user (to_user_id),
 *   INDEX item (item_id)
 * );
 * </pre>
 * @author <a href="mailto:cecchet@rice.edu">Emmanuel Cecchet</a> and <a href="mailto:julie.marguerite@inrialpes.fr">Julie Marguerite</a>
 * @version 1.0
 */

public abstract class CommentBean implements EntityBean 
{
  private EntityContext entityContext;
  private transient boolean isDirty; // used for the isModified function
  

  /*****************************/
  /* Abstract accessor methods */
  /*****************************/

  /**
   * Set comment id.
   *
   * @since 1.0
   */
  public abstract void setId(Integer id);

  /**
   * Get comment's id.
   *
   * @return comment id
   */
  public abstract Integer getId();


  /**
   * Get the rating associated to this comment.
   *
   * @return rating
   */
  public abstract int getRating();


  /**
   * Time of the Comment in the format 'YYYY-MM-DD hh:mm:ss'
   *
   * @return comment time
   */
  public abstract String getDate();

  
  /**
   * Get the comment text.
   *
   * @return comment text
   */
  public abstract String getComment();


  /**
   * Set a new rating for the ToUserId.
   *
   * @param Rating an <code>int</code> value
   */
  public abstract void setRating(int Rating);


  /**
   * Set a new date for this comment
   *
   * @param newDate comment date
   */
  public abstract void setDate(String newDate);


  /**
   * Set a new comment for ToUserId from FromUserId.
   *
   * @param newComment Comment
   */
  public abstract void setComment(String newComment);


  /*****************/
  /* relationships */
  /*****************/

  // This entity bean has one to many relationships with the User entity.

  /**
   * Get the author of the comment
   *
   * @return author
   */
  public abstract UserLocal getFromUser();


  /**
   * Set a new author of the comment.
   *
   * @param newFromUser author
   */
  public abstract void setFromUser(UserLocal newFromUser);


  /**
   * Get the user this comment is about.
   *
   * @return user this comment is about
   */
  public abstract UserLocal getToUser();


  /**
   * Set a new user this comment is about.
   *
   * @param newToUser user this comment is about
   */
  public abstract void setToUser(UserLocal newToUser);


  // This entity bean has a one to many relationship with the Item entity.

  /**
   * Get the item.
   *
   * @return item
   */
  public abstract ItemLocal getItem();


  /**
   * Set a new item.
   *
   * @param newItem item
   */
  public abstract void setItem(ItemLocal newItem);


  /**
   * This method is used to create a new Comment Bean. 
   * The date is automatically set to the current date when the method is called.
   *
   * @param fromUser comment author
   * @param toUser user this comment is about
   * @param item item
   * @param rating rating given by the author
   * @param comment comment text
   *
   * @return pk primary key set to null
   * @exception CreateException if an error occurs
   */
  public Integer ejbCreate(UserLocal fromUser, UserLocal toUser, ItemLocal item, int rating, String comment) throws CreateException
  {
      /*// Connecting to SB_IDManager Home interface thru JNDI
      SB_IDManagerLocalHome home = null;
      SB_IDManagerLocal idManager = null;
      
      try 
      {
        InitialContext initialContext = new InitialContext();
        home = (SB_IDManagerLocalHome)initialContext.lookup(
               "java:comp/env/ejb/SB_IDManager");
      } 
      catch (Exception e)
      {
        throw new EJBException("Cannot lookup SB_IDManager: " +e);
      }
     try 
      {
        idManager = home.create();
        while (true)
        {
          try
          {
            setId(idManager.getNextCommentID());
            break;
          }
          catch (TransactionRolledbackLocalException ex)
          {
            ex.printStackTrace();
          }
        }
        setRating(rating);
        setDate(TimeManagement.currentDateToString());
        setComment(comment);
      } 
      catch (Exception e)
      {
        throw new EJBException("Cannot create comment: " +e);
      }
    return null;*/
    
    setRating(rating);
    setDate(TimeManagement.currentDateToString());
    setComment(comment);
    
    return null;
  }

  /** This method just set an internal flag to 
      reload the id generated by the DB */
  public void ejbPostCreate(UserLocal fromUser, UserLocal toUser, ItemLocal item, int rating, String comment)
  {
    setFromUser(fromUser);
    setToUser(toUser);
    setItem(item);
    
    isDirty = true; // the id has to be reloaded from the DB
  }

  /** Persistence is managed by the container and the bean
      becomes up to date */
  public void ejbLoad()
  {
    isDirty = false;
  }

  /** Persistence is managed by the container and the bean
      becomes up to date */
  public void ejbStore() 
  {
    isDirty = false;
  }

  /** This method is empty because persistence is managed by the container */
  public void ejbActivate(){}
  /** This method is empty because persistence is managed by the container */
  public void ejbPassivate(){}
  /** This method is empty because persistence is managed by the container */
  public void ejbRemove() throws RemoveException {}

  /**
   * Sets the associated entity context. The container invokes this method 
   *  on an instance after the instance has been created. 
   * 
   * This method is called in an unspecified transaction context. 
   * 
   * @param context An EntityContext interface for the instance. The instance should 
   *                store the reference to the context in an instance variable. 
   * @exception EJBException  Thrown by the method to indicate a failure 
   *                          caused by a system-level error.
   */
  public void setEntityContext(EntityContext context)
  {
    entityContext = context;
  }

  /**
   * Unsets the associated entity context. The container calls this method 
   *  before removing the instance. This is the last method that the container 
   *  invokes on the instance. The Java garbage collector will eventually invoke 
   *  the finalize() method on the instance. 
   *
   * This method is called in an unspecified transaction context. 
   * 
   * @exception EJBException  Thrown by the method to indicate a failure 
   *                          caused by a system-level error.
   */
  public void unsetEntityContext()
  {
    entityContext = null;
  }

  /**
   * Returns true if the beans has been modified.
   * It prevents the EJB server from reloading a bean
   * that has not been modified.
   *
   * @return a <code>boolean</code> value
   */
  /*public boolean isModified() 
  {
    return isDirty;
  }*/


  /**
   * Display comment information as an HTML table row
   *
   * @return a <code>String</code> containing HTML code
   * @since 1.0
   */
  public String printComment(String userName)
  {
    return "<DT><b><BIG><a href=\""+BeanConfig.context+"/servlet/ViewUserInfo?userId="+getFromUser().getId()+"\">"+userName+"</a></BIG></b>"+
      " wrote the "+getDate()+"<DD><i>"+getComment()+"</i><p>\n";
  }
}
