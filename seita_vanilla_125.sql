

# Dump of table wp_shopp_address
# ------------------------------------------------------------

DROP TABLE IF EXISTS `wp_shopp_address`;

CREATE TABLE `wp_shopp_address` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer` bigint(20) unsigned NOT NULL DEFAULT '0',
  `type` enum('billing','shipping') NOT NULL DEFAULT 'billing',
  `name` varchar(100) NOT NULL DEFAULT '',
  `address` varchar(100) NOT NULL DEFAULT '',
  `xaddress` varchar(100) NOT NULL DEFAULT '',
  `city` varchar(100) NOT NULL DEFAULT '',
  `state` varchar(100) NOT NULL DEFAULT '',
  `country` varchar(2) NOT NULL DEFAULT '',
  `postcode` varchar(10) NOT NULL DEFAULT '',
  `geocode` varchar(16) NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `ref` (`customer`,`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table wp_shopp_asset
# ------------------------------------------------------------

DROP TABLE IF EXISTS `wp_shopp_asset`;

CREATE TABLE `wp_shopp_asset` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `data` longblob NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table wp_shopp_customer
# ------------------------------------------------------------

DROP TABLE IF EXISTS `wp_shopp_customer`;

CREATE TABLE `wp_shopp_customer` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `wpuser` bigint(20) unsigned NOT NULL DEFAULT '0',
  `password` varchar(64) NOT NULL DEFAULT '',
  `firstname` varchar(32) NOT NULL DEFAULT '',
  `lastname` varchar(32) NOT NULL DEFAULT '',
  `email` varchar(96) NOT NULL DEFAULT '',
  `phone` varchar(24) NOT NULL DEFAULT '',
  `company` varchar(100) NOT NULL DEFAULT '',
  `marketing` enum('yes','no') NOT NULL DEFAULT 'no',
  `activation` varchar(20) NOT NULL DEFAULT '',
  `type` varchar(100) NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `wordpress` (`wpuser`),
  KEY `type` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table wp_shopp_index
# ------------------------------------------------------------

DROP TABLE IF EXISTS `wp_shopp_index`;

CREATE TABLE `wp_shopp_index` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product` bigint(20) unsigned NOT NULL DEFAULT '0',
  `terms` longtext NOT NULL,
  `factor` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `type` varchar(16) NOT NULL DEFAULT 'description',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `search` (`terms`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table wp_shopp_meta
# ------------------------------------------------------------

DROP TABLE IF EXISTS `wp_shopp_meta`;

CREATE TABLE `wp_shopp_meta` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent` bigint(20) unsigned NOT NULL DEFAULT '0',
  `context` varchar(16) NOT NULL DEFAULT 'product',
  `type` varchar(16) NOT NULL DEFAULT 'meta',
  `name` varchar(255) NOT NULL DEFAULT '',
  `value` longtext NOT NULL,
  `numeral` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `sortorder` int(10) unsigned NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `lookup` (`name`,`parent`,`context`,`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table wp_shopp_price
# ------------------------------------------------------------

DROP TABLE IF EXISTS `wp_shopp_price`;

CREATE TABLE `wp_shopp_price` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product` bigint(20) unsigned NOT NULL DEFAULT '0',
  `context` enum('product','variation','addon') NOT NULL,
  `type` enum('Shipped','Virtual','Download','Donation','Subscription','Membership','N/A') NOT NULL,
  `optionkey` bigint(20) unsigned NOT NULL DEFAULT '0',
  `label` varchar(255) NOT NULL DEFAULT '',
  `sku` varchar(100) NOT NULL DEFAULT '',
  `price` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `saleprice` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `promoprice` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `cost` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `shipfee` decimal(12,6) NOT NULL DEFAULT '0.000000',
  `stock` int(10) NOT NULL DEFAULT '0',
  `stocked` int(10) NOT NULL DEFAULT '0',
  `inventory` enum('off','on') NOT NULL,
  `sale` enum('off','on') NOT NULL,
  `shipping` enum('on','off') NOT NULL,
  `tax` enum('on','off') NOT NULL,
  `discounts` varchar(255) NOT NULL DEFAULT '',
  `sortorder` int(10) unsigned NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `product` (`product`),
  KEY `context` (`context`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table wp_shopp_promo
# ------------------------------------------------------------

DROP TABLE IF EXISTS `wp_shopp_promo`;

CREATE TABLE `wp_shopp_promo` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `status` enum('disabled','enabled') DEFAULT 'disabled',
  `type` enum('Percentage Off','Amount Off','Free Shipping','Buy X Get Y Free') DEFAULT 'Percentage Off',
  `target` enum('Catalog','Cart','Cart Item') DEFAULT 'Catalog',
  `discount` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `buyqty` int(10) NOT NULL DEFAULT '0',
  `getqty` int(10) NOT NULL DEFAULT '0',
  `uses` int(10) NOT NULL DEFAULT '0',
  `search` enum('all','any') DEFAULT 'all',
  `code` varchar(255) NOT NULL DEFAULT '',
  `rules` text NOT NULL,
  `starts` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ends` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `catalog` (`status`,`target`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table wp_shopp_purchase
# ------------------------------------------------------------

DROP TABLE IF EXISTS `wp_shopp_purchase`;

CREATE TABLE `wp_shopp_purchase` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer` bigint(20) unsigned NOT NULL DEFAULT '0',
  `shipping` bigint(20) unsigned NOT NULL DEFAULT '0',
  `billing` bigint(20) unsigned NOT NULL DEFAULT '0',
  `currency` bigint(20) unsigned NOT NULL DEFAULT '0',
  `ip` varchar(15) NOT NULL DEFAULT '0.0.0.0',
  `firstname` varchar(32) NOT NULL DEFAULT '',
  `lastname` varchar(32) NOT NULL DEFAULT '',
  `email` varchar(96) NOT NULL DEFAULT '',
  `phone` varchar(24) NOT NULL DEFAULT '',
  `company` varchar(100) NOT NULL DEFAULT '',
  `card` varchar(4) NOT NULL DEFAULT '',
  `cardtype` varchar(32) NOT NULL DEFAULT '',
  `cardexpires` date NOT NULL DEFAULT '0000-00-00',
  `cardholder` varchar(96) NOT NULL DEFAULT '',
  `address` varchar(100) NOT NULL DEFAULT '',
  `xaddress` varchar(100) NOT NULL DEFAULT '',
  `city` varchar(100) NOT NULL DEFAULT '',
  `state` varchar(100) NOT NULL DEFAULT '',
  `country` varchar(2) NOT NULL DEFAULT '',
  `postcode` varchar(10) NOT NULL DEFAULT '',
  `shipname` varchar(100) NOT NULL DEFAULT '',
  `shipaddress` varchar(100) NOT NULL DEFAULT '',
  `shipxaddress` varchar(100) NOT NULL DEFAULT '',
  `shipcity` varchar(100) NOT NULL DEFAULT '',
  `shipstate` varchar(100) NOT NULL DEFAULT '',
  `shipcountry` varchar(2) NOT NULL DEFAULT '',
  `shippostcode` varchar(10) NOT NULL DEFAULT '',
  `geocode` varchar(16) NOT NULL DEFAULT '',
  `promos` varchar(255) NOT NULL DEFAULT '',
  `subtotal` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `freight` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `tax` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `total` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `discount` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `fees` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `taxing` enum('exclusive','inclusive') DEFAULT 'exclusive',
  `txnid` varchar(64) NOT NULL DEFAULT '',
  `txnstatus` varchar(64) NOT NULL DEFAULT '',
  `gateway` varchar(64) NOT NULL DEFAULT '',
  `paymethod` varchar(100) NOT NULL DEFAULT '',
  `shipmethod` varchar(100) NOT NULL DEFAULT '',
  `shipoption` varchar(100) NOT NULL DEFAULT '',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `data` longtext NOT NULL,
  `secured` text NOT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `customer` (`customer`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table wp_shopp_purchased
# ------------------------------------------------------------

DROP TABLE IF EXISTS `wp_shopp_purchased`;

CREATE TABLE `wp_shopp_purchased` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase` bigint(20) unsigned NOT NULL DEFAULT '0',
  `product` bigint(20) unsigned NOT NULL DEFAULT '0',
  `price` bigint(20) unsigned NOT NULL DEFAULT '0',
  `download` bigint(20) unsigned NOT NULL DEFAULT '0',
  `dkey` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `optionlabel` varchar(255) NOT NULL DEFAULT '',
  `type` varchar(100) NOT NULL DEFAULT '',
  `sku` varchar(100) NOT NULL DEFAULT '',
  `quantity` int(10) unsigned NOT NULL DEFAULT '0',
  `downloads` int(10) unsigned NOT NULL DEFAULT '0',
  `unitprice` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `unittax` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `shipping` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `total` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `addons` enum('yes','no') NOT NULL DEFAULT 'no',
  `variation` text NOT NULL,
  `data` longtext NOT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `purchase` (`purchase`),
  KEY `price` (`price`),
  KEY `product` (`product`),
  KEY `dkey` (`dkey`(8))
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table wp_shopp_shopping
# ------------------------------------------------------------

DROP TABLE IF EXISTS `wp_shopp_shopping`;

CREATE TABLE `wp_shopp_shopping` (
  `session` varchar(32) NOT NULL,
  `customer` bigint(20) unsigned NOT NULL DEFAULT '0',
  `ip` varchar(15) NOT NULL DEFAULT '0.0.0.0',
  `data` longtext NOT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`session`),
  KEY `customer` (`customer`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table wp_shopp_summary
# ------------------------------------------------------------

DROP TABLE IF EXISTS `wp_shopp_summary`;

CREATE TABLE `wp_shopp_summary` (
  `product` bigint(20) unsigned NOT NULL DEFAULT '0',
  `sold` bigint(20) NOT NULL DEFAULT '0',
  `grossed` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `maxprice` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `minprice` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `ranges` char(200) NOT NULL DEFAULT '',
  `taxed` set('max price','min price','max saleprice','min saleprice') DEFAULT NULL,
  `lowstock` enum('none','warning','critical','backorder') NOT NULL,
  `stock` int(10) NOT NULL DEFAULT '0',
  `inventory` enum('off','on') NOT NULL,
  `featured` enum('off','on') NOT NULL,
  `variants` enum('off','on') NOT NULL,
  `addons` enum('off','on') NOT NULL,
  `sale` enum('off','on') NOT NULL,
  `freeship` enum('off','on') NOT NULL,
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`product`),
  KEY `bestselling` (`sold`,`product`),
  KEY `featured` (`featured`,`product`),
  KEY `lowprice` (`minprice`,`product`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `wp_shopp_order_only_cats`;

CREATE TABLE `wp_shopp_order_only_cats` (
  `cat_id` int(11) NOT NULL,
  UNIQUE KEY `cat_id` (`cat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `wp_shopp_order_only_items`;

CREATE TABLE `wp_shopp_order_only_items` (
  `id` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `sku` varchar(100) NOT NULL,
  KEY `sku` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `wp_shopp_edge_category_map`;

CREATE TABLE `wp_shopp_edge_category_map` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category` bigint(20) unsigned NOT NULL DEFAULT '0',
  `edge_category` bigint(20) unsigned NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `product` (`category`),
  KEY `assignment` (`edge_category`)
) ENGINE=MyISAM AUTO_INCREMENT=180 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `wp_shopp_edge_category`;

CREATE TABLE `wp_shopp_edge_category` (
  `id` bigint(20) unsigned NOT NULL,
  `parent` bigint(20) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `slug` varchar(64) NOT NULL DEFAULT '',
  `uri` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `spectemplate` enum('off','on') NOT NULL,
  `facetedmenus` enum('off','on') NOT NULL,
  `variations` enum('off','on') NOT NULL,
  `pricerange` enum('disabled','auto','custom') NOT NULL,
  `priceranges` text NOT NULL,
  `specs` text NOT NULL,
  `options` text NOT NULL,
  `prices` text NOT NULL,
  `priority` int(10) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `parent` (`parent`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `wp_shopp_edge_catalog`;

CREATE TABLE `wp_shopp_edge_catalog` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product` bigint(20) unsigned NOT NULL DEFAULT '0',
  `parent` bigint(20) unsigned NOT NULL DEFAULT '0',
  `type` enum('category','tag') NOT NULL,
  `priority` int(10) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `product` (`product`),
  KEY `assignment` (`parent`,`type`)
) ENGINE=MyISAM AUTO_INCREMENT=9287 DEFAULT CHARSET=latin1;

