<?php

/**
 * Alle SQL updates dienen deze interface te implementeren (om up() en down() te verplichten).
 * Een voorbeeld van zo'n class:
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
 * De updates worden altijd chronologisch uitgevoerd, en bij terugdraaien van de updates (down()) omgekeerd chronologisch.
 * Het is dus mogelijk om tabellen die in een oudere file zijn gemaakt te updaten met een nieuwere (bv. alter table...).
 */
interface SQL_update
{
	/**
	 * Geeft de SQL statements terug die moeten worden uitgevoerd om de database te upgraden naar deze timestamp
	 *
	 * @returns string
	 */
	public function up();

	/**
	 * Geeft de SQL statements terug die de wijzigingen van up() ongedaan maken
	 *
	 * @returns string
	 */
	public function down();
}
