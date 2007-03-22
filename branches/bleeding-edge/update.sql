# Project:    Web Reference Database (refbase) <http://www.refbase.net>
# Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
#             original author(s).
#
#             This code is distributed in the hope that it will be useful,
#             but WITHOUT ANY WARRANTY. Please see the GNU General Public
#             License for more details.
#
# File:       ./update.sql
# Repository: $HeadURL$
# Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
#
# Created:    01-Mar-05, 16:54
# Modified:   $Date$
#             $Author$
#             $Revision$

# This MySQL database structure file will update any refbase v0.8.0 database to v0.9.0

# --------------------------------------------------------

#
# replace table `formats`
#

DROP TABLE IF EXISTS `formats`;
CREATE TABLE `formats` (
  `format_id` mediumint(8) unsigned NOT NULL auto_increment,
  `format_name` varchar(100) default NULL,
  `format_type` enum('export','import','cite') NOT NULL default 'export',
  `format_enabled` enum('true','false') NOT NULL default 'true',
  `format_spec` varchar(255) default NULL,
  `order_by` varchar(25) default NULL,
  `depends_id` mediumint(8) unsigned NOT NULL default '0',
  PRIMARY KEY  (`format_id`),
  KEY `format_name` (`format_name`)
) TYPE=MyISAM;

#
# data for table `formats`
#

INSERT INTO `formats` VALUES (1, 'MODS XML', 'import', 'true', 'bibutils/import_modsxml2refbase.php', '06', 2),
(2, 'MODS XML', 'export', 'true', 'export_modsxml.php', '06', 1),
(3, 'Text (CSV)', 'export', 'false', 'export_textcsv.php', '07', 1),
(4, 'BibTeX', 'import', 'true', 'bibutils/import_bib2refbase.php', '01', 2),
(5, 'BibTeX', 'export', 'true', 'bibutils/export_xml2bib.php', '01', 2),
(6, 'Endnote', 'import', 'true', 'bibutils/import_end2refbase.php', '02', 2),
(7, 'Endnote XML', 'import', 'true', 'bibutils/import_endx2refbase.php', '02', 2),
(8, 'Endnote', 'export', 'true', 'bibutils/export_xml2end.php', '02', 2),
(9, 'Pubmed Medline', 'import', 'true', 'import_medline2refbase.php', '08', 1),
(10, 'Pubmed XML', 'import', 'true', 'bibutils/import_med2refbase.php', '09', 2),
(11, 'RIS', 'import', 'true', 'import_ris2refbase.php', '03', 1),
(12, 'RIS', 'export', 'true', 'bibutils/export_xml2ris.php', '03', 2),
(13, 'ISI', 'import', 'true', 'import_isi2refbase.php', '04', 1),
(14, 'ISI', 'export', 'true', 'bibutils/export_xml2isi.php', '04', 2),
(15, 'CSA', 'import', 'true', 'import_csa2refbase.php', '05', 1),
(16, 'Copac', 'import', 'true', 'bibutils/import_copac2refbase.php', '10', 2),
(17, 'SRW XML', 'export', 'true', 'export_srwxml.php', '11', 1),
(18, 'ODF XML', 'export', 'true', 'export_odfxml.php', '12', 1),
(19, 'OpenSearch RSS', 'export', 'false', 'export_osrss.php', '13', 1),
(20, 'html', 'cite', 'true', 'formats/cite_html.php', '14', 1),
(21, 'RTF', 'cite', 'true', 'formats/cite_rtf.php', '15', 1),
(22, 'PDF', 'cite', 'true', 'formats/cite_pdf.php', '16', 1),
(23, 'LaTeX', 'cite', 'true', 'formats/cite_latex.php', '17', 1),
(24, 'Markdown', 'cite', 'true', 'formats/cite_markdown.php', '18', 1),
(25, 'ASCII', 'cite', 'true', 'formats/cite_ascii.php', '19', 1),
(26, 'RefWorks', 'import', 'true', 'import_refworks2refbase.php', '20', 1),
(27, 'SciFinder', 'import', 'true', 'import_scifinder2refbase.php', '21', 1),
(28, 'Word XML', 'export', 'true', 'bibutils/export_xml2word.php', '22', 2);

# --------------------------------------------------------

#
# update table `languages`
#

INSERT INTO `languages` VALUES (NULL, 'fr', 'true', '3');

UPDATE `languages` SET `language_enabled` = 'true' WHERE `language_name` = 'de';

# --------------------------------------------------------

#
# update table `styles`
#

UPDATE `styles` SET `style_spec` = REPLACE(`style_spec`,"cite_","styles/cite_") WHERE `style_spec` RLIKE "^cite_";

INSERT INTO `styles` VALUES (NULL, 'Ann Glaciol', 'true', 'styles/cite_AnnGlaciol_JGlaciol.php', 'B050', 1),
(NULL, 'J Glaciol', 'true', 'styles/cite_AnnGlaciol_JGlaciol.php', 'B060', 1),
(NULL, 'APA', 'true', 'styles/cite_APA.php', 'A010', 1),
(NULL, 'MLA', 'true', 'styles/cite_MLA.php', 'A030', 1);

UPDATE `styles` SET `order_by` = 'C010' WHERE `style_name` = 'Text Citation';
UPDATE `styles` SET `order_by` = 'B010' WHERE `style_name` = 'Polar Biol';
UPDATE `styles` SET `order_by` = 'B020' WHERE `style_name` = 'Mar Biol';
UPDATE `styles` SET `order_by` = 'B030' WHERE `style_name` = 'MEPS';
UPDATE `styles` SET `order_by` = 'B040' WHERE `style_name` = 'Deep Sea Res';

# --------------------------------------------------------

#
# update table `types`
#

INSERT INTO `types` VALUES (NULL, 'Conference Article', 'true', 2, '4'),
(NULL, 'Conference Volume', 'true', 3, '5');

UPDATE `types` SET `order_by` = '6' WHERE `type_name` = 'Journal';
UPDATE `types` SET `order_by` = '7' WHERE `type_name` = 'Manuscript';
UPDATE `types` SET `order_by` = '8' WHERE `type_name` = 'Map';

# --------------------------------------------------------

#
# add table `user_options`
#

DROP TABLE IF EXISTS `user_options`;
CREATE TABLE `user_options` (
  `option_id` mediumint(8) unsigned NOT NULL auto_increment,
  `user_id` mediumint(8) unsigned NOT NULL default '0',
  `export_cite_keys` enum('yes','no') NOT NULL default 'yes',
  `autogenerate_cite_keys` enum('yes','no') NOT NULL default 'yes',
  `prefer_autogenerated_cite_keys` enum('no','yes') NOT NULL default 'no',
  `use_custom_cite_key_format` enum('no','yes') NOT NULL default 'no',
  `cite_key_format` varchar(255) default NULL,
  `uniquify_duplicate_cite_keys` enum('yes','no') NOT NULL default 'yes',
  `nonascii_chars_in_cite_keys` enum('transliterate','strip','keep') default NULL,
  `use_custom_text_citation_format` enum('no','yes') NOT NULL default 'no',
  `text_citation_format` varchar(255) default NULL,
  PRIMARY KEY  (`option_id`),
  KEY `user_id` (`user_id`)
) TYPE=MyISAM;

#
# data for table `user_options`
#

INSERT INTO `user_options` VALUES (1, 0, 'yes', 'yes', 'no', 'no', '<:authors:><:year:>', 'yes', NULL, 'no', '<:authors[2| & | et al.]:>< :year:>< {:recordIdentifier:}>'),
(2, 1, 'yes', 'yes', 'no', 'no', '<:firstAuthor:><:year:>', 'yes', NULL, 'no', '<:authors[2| & | et al.]:>< :year:>< {:recordIdentifier:}>');

# --------------------------------------------------------

#
# alter table `user_permissions`
#

ALTER TABLE `user_permissions` ADD COLUMN `allow_browse_view` ENUM('yes', 'no') NOT NULL AFTER `allow_print_view`;

#
# update table `user_permissions`
#

UPDATE `user_permissions` SET `allow_browse_view` = 'no';

