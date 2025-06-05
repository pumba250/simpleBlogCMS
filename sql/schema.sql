CREATE TABLE `{PREFIX_}blogs` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `title` varchar(255) NOT NULL,
 `content` text NOT NULL,
 `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
CREATE TABLE `{PREFIX_}blogs_contacts` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `name` varchar(100) NOT NULL,
 `email` varchar(100) NOT NULL,
 `message` text NOT NULL,
 `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE `{PREFIX_}blogs_tags` (
 `blogs_id` int(11) NOT NULL,
 `tag_id` int(11) NOT NULL,
 PRIMARY KEY (`blogs_id`,`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE `{PREFIX_}comments` (
 `id` int(9) unsigned NOT NULL AUTO_INCREMENT,
 `parent_id` mediumint(9) unsigned NOT NULL DEFAULT '0',
 `f_parent` mediumint(9) unsigned NOT NULL DEFAULT '0',
 `created_at` int(10) unsigned NOT NULL,
 `theme_id` smallint(6) unsigned NOT NULL,
 `user_name` varchar(30) CHARACTER SET utf8 NOT NULL,
 `user_text` varchar(9999) COLLATE utf8_unicode_ci NOT NULL,
 `moderation` tinyint(3) unsigned NOT NULL DEFAULT '0',
 `plus` mediumint(9) NOT NULL DEFAULT '0',
 `minus` mediumint(9) NOT NULL DEFAULT '0',
 PRIMARY KEY (`id`),
 KEY `theme_id` (`theme_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `{PREFIX_}tags` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `name` varchar(255) NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
CREATE TABLE `{PREFIX_}users` (
 `id` int(10) NOT NULL AUTO_INCREMENT,
 `username` varchar(100) NOT NULL,
 `password` varchar(60) NOT NULL,
 `email` varchar(80) NOT NULL,
 `isadmin` tinyint(1) NOT NULL,
 `avatar` varchar(100) NOT NULL DEFAULT '/images/avatar_g.png',
 `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `last_activity` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
 PRIMARY KEY (`id`),
 UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;