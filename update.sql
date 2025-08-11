CREATE TABLE IF NOT EXISTS `{PREFIX_}user_social` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `social_type` varchar(20) NOT NULL,
  `social_id` varchar(255) NOT NULL,
  `social_username` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_social` (`user_id`,`social_type`),
  KEY `social` (`social_type`,`social_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;