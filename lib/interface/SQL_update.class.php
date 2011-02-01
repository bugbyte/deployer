<?php

/**
 * All SQL updates should implement this interface so you don't forget to add up() and down().
 * 
 * @author Bert-Jan de Lange <bert-jan@bugbyte.nl>
 * 
 * An example of an SQL update class:
 *

	sql_20110104_164856.class.php:

	<?php
	class sql_20110104_164856 implements SQL_update
	{
		public function up()
		{
			return "
				CREATE TABLE `tag` (
				  `id` int(11) NOT NULL auto_increment,
				  `name` varchar(100) collate utf8_unicode_ci default NULL,
				  `seo_title` varchar(255) collate utf8_unicode_ci default NULL,
				  `seo_description` varchar(255) collate utf8_unicode_ci default NULL,
				  `confirmed` smallint(5) unsigned NOT NULL default '0',
				  PRIMARY KEY  (`id`),
				  UNIQUE KEY `name` (`name`)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			";
		}

		public function down()
		{
			return "
				DROP TABLE `tag`;
			";
		}
	}

 *
 * The updates are always run in chronologic order, and in case of a rollback (down()) the order is reversed.
 * This ensures you can have updates changing things from earlier updates even during one deployment.
 */
interface SQL_update
{
	/**
	 * Returns the SQL statements needed to upgrade the database to this timestamp.
	 *
	 * @returns string
	 */
	public function up();

	/**
	 * Returms the SQL statements that rollback the up()-method's changes.
	 *
	 * @returns string
	 */
	public function down();
}
