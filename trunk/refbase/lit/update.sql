# Project:    Web Reference Database (refbase) <http://www.refbase.net>
# Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
#             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
#             Please see the GNU General Public License for more details.
# File:       ./update.sql
# Created:    01-Mar-05, 16:54
# Modified:   02-Mar-05, 01:35

# This MySQL database structure file will update any refbase v0.7 database to v0.8

# --------------------------------------------------------

#
# alter table `deleted`
#

ALTER TABLE `deleted` 
  MODIFY COLUMN `series_volume` varchar(50) default NULL,
  ADD COLUMN `series_volume_numeric` smallint(5) unsigned default NULL AFTER `series_volume`;

#
# copy contents from field `series_volume` to field `series_volume_numeric`
#

UPDATE `deleted` SET `series_volume_numeric` = `series_volume` WHERE `series_volume` RLIKE ".+";

# --------------------------------------------------------

#
# add table `depends`
#

DROP TABLE IF EXISTS `depends`;
CREATE TABLE `depends` (
  `depends_id` mediumint(8) unsigned NOT NULL auto_increment,
  `depends_external` varchar(100) default NULL,
  `depends_enabled` enum('true','false') NOT NULL default 'true',
  `depends_path` varchar(255) default NULL,
  PRIMARY KEY  (`depends_id`)
) TYPE=MyISAM AUTO_INCREMENT=3 ;

#
# data for table `depends`
#

INSERT INTO `depends` VALUES (1, 'refbase', 'true', NULL),
(2, 'bibutils', 'true', NULL);

# --------------------------------------------------------

#
# add table `formats`
#

DROP TABLE IF EXISTS `formats`;
CREATE TABLE `formats` (
  `format_id` mediumint(8) unsigned NOT NULL auto_increment,
  `format_name` varchar(100) default NULL,
  `format_type` enum('export','import') NOT NULL default 'export',
  `format_enabled` enum('true','false') NOT NULL default 'true',
  `format_spec` varchar(255) default NULL,
  `order_by` varchar(25) default NULL,
  `depends_id` mediumint(8) unsigned NOT NULL default '0',
  PRIMARY KEY  (`format_id`),
  KEY `format_name` (`format_name`)
) TYPE=MyISAM AUTO_INCREMENT=12 ;

#
# data for table `formats`
#

INSERT INTO `formats` VALUES (1, 'MODS XML','import', 'true', 'import_modsxml.php', '6', 1),
(2, 'MODS XML','export', 'true', 'export_modsxml.php', '6', 1),
(3, 'Text (CSV)','export', 'true', 'export_textcsv.php', '7', 1),
(4, 'Bibtex','import', 'true', 'bibutils/import_bib2xml.php', '1', 2),
(5, 'Bibtex','export', 'true', 'bibutils/export_xml2bib.php', '1', 2),
(6, 'Endnote','import', 'true', 'bibutils/import_end2xml.php', '2', 2),
(7, 'Endnote','export', 'true', 'bibutils/export_xml2end.php', '2', 2),
(8, 'Pubmed XML','import', 'true', 'bibutils/import_med2xml.php', '5', 2),
(9, 'RIS', 'import','true', 'bibutils/import_ris2xml.php', '3', 2),
(10, 'RIS', 'export','true', 'bibutils/export_xml2ris.php', '3', 2),
(11, 'RIS (ISI)','import', 'true', 'bibutils/import_isi2xml.php', '4', 2);

# --------------------------------------------------------

#
# add table `languages`
#

DROP TABLE IF EXISTS `languages`;
CREATE TABLE `languages` (
  `language_id` mediumint(8) unsigned NOT NULL auto_increment,
  `language_name` varchar(50) default NULL,
  `language_enabled` enum('true','false') NOT NULL default 'true',
  `order_by` varchar(25) default NULL,
  PRIMARY KEY  (`language_id`),
  KEY `language_name` (`language_name`)
) TYPE=MyISAM AUTO_INCREMENT=3 ;

#
# data for table `languages`
#

INSERT INTO `languages` VALUES (1,'en','true','1'),
(2,'de','false','2');

# --------------------------------------------------------

#
# add table `queries`
#

DROP TABLE IF EXISTS `queries`;
CREATE TABLE `queries` (
  `query_id` mediumint(8) unsigned NOT NULL auto_increment,
  `user_id` mediumint(8) unsigned NOT NULL default '0',
  `query_name` varchar(255) default NULL,
  `display_type` varchar(25) default NULL,
  `view_type` varchar(25) default NULL,
  `query` text,
  `show_query` tinyint(3) unsigned default NULL,
  `show_links` tinyint(3) unsigned default NULL,
  `show_rows` mediumint(8) unsigned default NULL,
  `cite_style_selector` varchar(50) default NULL,
  `cite_order` varchar(25) default NULL,
  `last_execution` datetime default NULL,
  PRIMARY KEY  (`query_id`),
  KEY `user_id` (`user_id`,`query_name`)
) TYPE=MyISAM AUTO_INCREMENT=5 ;

#
# data for table `queries`
#

INSERT INTO `queries` VALUES (1, 1, 'My refs edited today', '', 'Web', 'SELECT author, title, year, publication, modified_by, modified_time FROM refs WHERE location RLIKE "user@refbase.net" AND modified_date = CURDATE() ORDER BY modified_time DESC', 0, 1, 5, '', '', '2004-06-02 18:37:07'),
(2, 1, 'My refs (print view)', 'Show', 'Print', 'SELECT author, title, year, publication, volume, pages FROM refs LEFT JOIN user_data ON serial = record_id AND user_id = 1 WHERE location RLIKE "user@refbase.net" ORDER BY author, year DESC, publication', 0, 1, 50, '', '', '2004-07-30 22:37:02'),
(3, 1, 'My refs (keys & groups)', '', 'Web', 'SELECT author, title, year, publication, user_keys, user_groups FROM refs LEFT JOIN user_data ON serial = record_id AND user_id = 1 WHERE location RLIKE "user@refbase.net" ORDER BY author, year DESC, publication', 0, 1, 5, '', '', '2004-07-30 23:24:28'),
(4, 1, 'Abstracts (print view)', '', 'Print', 'SELECT author, year, abstract FROM refs WHERE serial RLIKE ".+" ORDER BY author, year DESC, publication', 0, 1, 5, '', '', '2004-07-30 22:36:48');

# --------------------------------------------------------

#
# alter table `refs`
#

ALTER TABLE `refs` 
  MODIFY COLUMN `series_volume` varchar(50) default NULL,
  ADD COLUMN `series_volume_numeric` smallint(5) unsigned default NULL AFTER `series_volume`;

#
# copy contents from field `series_volume` to field `series_volume_numeric`
#

UPDATE `refs` SET `series_volume_numeric` = `series_volume` WHERE `series_volume` RLIKE ".+";

# --------------------------------------------------------

#
# add table `styles`
#

DROP TABLE IF EXISTS `styles`;
CREATE TABLE `styles` (
  `style_id` mediumint(8) unsigned NOT NULL auto_increment,
  `style_name` varchar(100) default NULL,
  `style_enabled` enum('true','false') NOT NULL default 'true',
  `style_spec` varchar(255) default NULL,
  `order_by` varchar(25) default NULL,
  `depends_id` mediumint(8) unsigned NOT NULL default '0',
  PRIMARY KEY  (`style_id`),
  KEY `style_name` (`style_name`)
) TYPE=MyISAM AUTO_INCREMENT=6 ;

#
# data for table `styles`
#

INSERT INTO `styles` VALUES (1, 'Polar Biol', 'true', 'cite_PolarBiol_MarBiol_MEPS.php', '1', 1),
(2, 'Mar Biol', 'true', 'cite_PolarBiol_MarBiol_MEPS.php', '2', 1),
(3, 'MEPS', 'true', 'cite_PolarBiol_MarBiol_MEPS.php', '3', 1),
(4, 'Deep Sea Res', 'true', 'cite_DeepSeaRes.php', '4', 1),
(5, 'Text Citation', 'true', 'cite_TextCitation.php', '5', 1);

# --------------------------------------------------------

#
# add table `types`
#

DROP TABLE IF EXISTS `types`;
CREATE TABLE `types` (
  `type_id` mediumint(8) unsigned NOT NULL auto_increment,
  `type_name` varchar(100) default NULL,
  `type_enabled` enum('true','false') NOT NULL default 'true',
  `base_type_id` mediumint(8) unsigned default NULL,
  `order_by` varchar(25) default NULL,
  PRIMARY KEY  (`type_id`),
  KEY `type_name` (`type_name`)
) TYPE=MyISAM AUTO_INCREMENT=7 ;

#
# data for table `types`
#

INSERT INTO `types` VALUES (1, 'Journal Article', 'true', 1, '1'),
(2, 'Book Chapter', 'true', 2, '2'),
(3, 'Book Whole', 'true', 3, '3'),
(4, 'Journal', 'true', 3, '4'),
(5, 'Manuscript', 'true', 3, '5'),
(6, 'Map', 'true', 3, '6');

# --------------------------------------------------------

#
# alter table `user_data`
#

ALTER TABLE `user_data` 
  MODIFY COLUMN `selected` enum('no','yes') NOT NULL default 'no' AFTER `copy`,
  MODIFY COLUMN `user_file` varchar(255) default NULL AFTER `user_notes`,
  ADD COLUMN `user_groups` text AFTER `user_file`,
  ADD COLUMN `cite_key` varchar(255) default NULL,
  ADD COLUMN `related` text;

# --------------------------------------------------------

#
# add table `user_formats`
#

DROP TABLE IF EXISTS `user_formats`;
CREATE TABLE `user_formats` (
  `user_format_id` mediumint(8) unsigned NOT NULL auto_increment,
  `format_id` mediumint(8) unsigned NOT NULL default '0',
  `user_id` mediumint(8) unsigned NOT NULL default '0',
  `show_format` enum('true','false') NOT NULL default 'true',
  PRIMARY KEY  (`user_format_id`),
  KEY `format_id` (`format_id`,`user_id`)
) TYPE=MyISAM AUTO_INCREMENT=12 ;

#
# data for table `user_formats`
#

INSERT INTO `user_formats` VALUES (1, 1, 0, 'true'),
(2, 2, 0, 'false'),
(3, 3, 0, 'false'),
(4, 4, 0, 'true'),
(5, 5, 0, 'false'),
(6, 6, 0, 'true'),
(7, 7, 0, 'false'),
(8, 8, 0, 'true'),
(9, 9, 0, 'true'),
(10, 10, 0, 'false'),
(11, 11, 0, 'true');

# --------------------------------------------------------

#
# add table `user_permissions`
#

DROP TABLE IF EXISTS `user_permissions`;
CREATE TABLE `user_permissions` (
  `user_permission_id` mediumint(8) unsigned NOT NULL auto_increment,
  `user_id` mediumint(8) unsigned NOT NULL default '0',
  `allow_add` enum('yes','no') NOT NULL default 'yes',
  `allow_edit` enum('yes','no') NOT NULL default 'yes',
  `allow_delete` enum('yes','no') NOT NULL default 'yes',
  `allow_download` enum('yes','no') NOT NULL default 'yes',
  `allow_upload` enum('yes','no') NOT NULL default 'yes',
  `allow_details_view` enum('yes','no') NOT NULL default 'yes',
  `allow_print_view` enum('yes','no') NOT NULL default 'yes',
  `allow_cite` enum('yes','no') NOT NULL default 'yes',
  `allow_import` enum('yes','no') NOT NULL default 'yes',
  `allow_batch_import` enum('yes','no') NOT NULL default 'yes',
  `allow_export` enum('yes','no') NOT NULL default 'yes',
  `allow_batch_export` enum('yes','no') NOT NULL default 'yes',
  `allow_user_groups` enum('yes','no') NOT NULL default 'yes',
  `allow_user_queries` enum('yes','no') NOT NULL default 'yes',
  `allow_rss_feeds` enum('yes','no') NOT NULL default 'yes',
  `allow_sql_search` enum('yes','no') NOT NULL default 'yes',
  `allow_modify_options` enum('yes','no') NOT NULL default 'yes',
  `allow_edit_call_number` enum('no','yes') NOT NULL default 'no',
  PRIMARY KEY  (`user_permission_id`),
  KEY `user_id` (`user_id`)
) TYPE=MyISAM AUTO_INCREMENT=2 ;

#
# data for table `user_permissions`
#

INSERT INTO `user_permissions` VALUES (1, 0, 'no', 'no', 'no', 'no', 'no', 'yes', 'yes', 'yes', 'no', 'no', 'no', 'no', 'no', 'no', 'yes', 'no', 'no', 'no');

# --------------------------------------------------------

#
# add table `user_styles`
#

DROP TABLE IF EXISTS `user_styles`;
CREATE TABLE `user_styles` (
  `user_style_id` mediumint(8) unsigned NOT NULL auto_increment,
  `style_id` mediumint(8) unsigned NOT NULL default '0',
  `user_id` mediumint(8) unsigned NOT NULL default '0',
  `show_style` enum('true','false') NOT NULL default 'true',
  PRIMARY KEY  (`user_style_id`),
  KEY `style_id` (`style_id`,`user_id`)
) TYPE=MyISAM AUTO_INCREMENT=6 ;

#
# data for table `user_styles`
#

INSERT INTO `user_styles` VALUES (1, 1, 0, 'true'),
(2, 2, 0, 'true'),
(3, 3, 0, 'true'),
(4, 4, 0, 'true'),
(5, 5, 0, 'true');

# --------------------------------------------------------

#
# add table `user_types`
#

DROP TABLE IF EXISTS `user_types`;
CREATE TABLE `user_types` (
  `user_type_id` mediumint(8) unsigned NOT NULL auto_increment,
  `type_id` mediumint(8) unsigned NOT NULL default '0',
  `user_id` mediumint(8) unsigned NOT NULL default '0',
  `show_type` enum('true','false') NOT NULL default 'true',
  PRIMARY KEY  (`user_type_id`),
  KEY `type_id` (`type_id`,`user_id`)
) TYPE=MyISAM AUTO_INCREMENT=7 ;

#
# data for table `user_types`
#

INSERT INTO `user_types` VALUES (1, 1, 0, 'true'),
(2, 2, 0, 'true'),
(3, 3, 0, 'true'),
(4, 4, 0, 'true'),
(5, 5, 0, 'true'),
(6, 6, 0, 'true');

# --------------------------------------------------------

#
# alter table `users`
#

ALTER TABLE `users` 
  MODIFY COLUMN `language` varchar(50) default 'en',
  ADD COLUMN `user_groups` text AFTER `user_id`,
  DROP COLUMN `address`,
  DROP COLUMN `records`,
  DROP COLUMN `queries`,
  DROP COLUMN `permissions`;
