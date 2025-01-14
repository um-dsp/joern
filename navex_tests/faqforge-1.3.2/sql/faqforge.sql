# $Id: faqforge.sql 3 2006-02-22 10:09:01Z sgrayban $
#
# Table structure for table 'Faq'
#
CREATE TABLE Faq (
  id int(10) unsigned DEFAULT '0' NOT NULL auto_increment,
  title varchar(32) DEFAULT '' NOT NULL,
  parent_id int(10) unsigned DEFAULT '0',
  context varchar(32) DEFAULT '' NOT NULL,
  list_order int(10) unsigned DEFAULT '10000' NOT NULL,
  publish enum('y','n') DEFAULT 'n',
  PRIMARY KEY (id)
);


#
# Table structure for table 'FaqPage'
#
CREATE TABLE FaqPage (
  id int(10) unsigned DEFAULT '0' NOT NULL auto_increment,
  faqText text,
  page_num int(10) unsigned DEFAULT '0' NOT NULL,
  owner_id int(10) unsigned DEFAULT '0' NOT NULL,
  publish enum('y','n') DEFAULT 'n',
  PRIMARY KEY (id)
);
