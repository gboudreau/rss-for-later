
--
-- Table structure for table `local_articles`
--

CREATE TABLE IF NOT EXISTS `local_articles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `feed_id` int(10) unsigned NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_userid` (`id`,`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(32) NOT NULL,
  `email` tinytext NOT NULL,
  `pocket_request_token` char(30) DEFAULT NULL,
  `pocket_access_token` char(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_uuid` (`uuid`),
  KEY `fast_lookup` (`uuid`,`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users_articles`
--

CREATE TABLE IF NOT EXISTS `users_articles` (
  `user_id` int(10) unsigned NOT NULL,
  `feed_id` int(10) unsigned NOT NULL,
  `hash` char(32) NOT NULL,
  `first_viewed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniqness` (`user_id`,`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users_feeds`
--

CREATE TABLE IF NOT EXISTS `users_feeds` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `title` tinytext NOT NULL,
  `xmlUrl` tinytext NOT NULL,
  `mirror_articles_locally` enum('true','false') NOT NULL DEFAULT 'false',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniqness` (`user_id`,`xmlUrl`(255)),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
