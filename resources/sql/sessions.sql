CREATE TABLE `sessions` (
  `id` varchar(75) NOT NULL DEFAULT '0',
  `site` varchar(45) NOT NULL,
  `ip` varchar(45) NOT NULL DEFAULT '0',
  `agent` varchar(120) DEFAULT NULL,
  `activity` DATETIME NOT NULL,
  `data` blob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`,`site`),
  ADD KEY `last_activity_idx` (`last_activity`);