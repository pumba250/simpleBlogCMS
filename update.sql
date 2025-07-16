ALTER TABLE `{PREFIX_}blogs`
ADD COLUMN `author_id` int(11) DEFAULT NULL AFTER `created_at`;