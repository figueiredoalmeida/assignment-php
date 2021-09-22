SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

--
-- Database: `lokalise`
--

-- --------------------------------------------------------

--
-- Table structure for table `keyReference`
--

CREATE TABLE `keyReference` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `keyReference`
--

INSERT INTO `keyReference` (`id`, `name`) VALUES
(6, 'index.banner'),
(2, 'index.description'),
(7, 'index.title');

-- --------------------------------------------------------

--
-- Table structure for table `language`
--

CREATE TABLE `language` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `iso_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ltr` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `language`
--

INSERT INTO `language` (`id`, `name`, `iso_code`, `ltr`) VALUES
(1, 'Spanish', 'es', 1),
(2, 'English', 'en', 1),
(3, 'Portuguese', 'pt', 1),
(4, 'Hebrew', 'he', 0);

-- --------------------------------------------------------

--
-- Table structure for table `token`
--

CREATE TABLE `token` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token_access` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `access` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_used` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `token`
--

INSERT INTO `token` (`id`, `user_id`, `token_access`, `access`, `last_used`) VALUES
(1, 3, '7c3336ce3e12fcbf5c34857265cb8d71efca2e8eeeb05db617d53e6c1212b4a8', 'write', '2021-09-22 04:08:36');

-- --------------------------------------------------------

--
-- Table structure for table `translation`
--

CREATE TABLE `translation` (
  `id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `key_reference_id` int(11) NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `translation`
--

INSERT INTO `translation` (`id`, `language_id`, `key_reference_id`, `value`) VALUES
(15, 1, 7, 'Este es el principal descricion ES'),
(16, 2, 7, 'This is the main description EN'),
(17, 3, 7, 'Este é o principal descricão PT'),
(21, 3, 6, 'Este é o principal banner em PT');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `user_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `user_name`, `password`) VALUES
(2, 'sandro', '$argon2id$v=19$m=65536,t=4,p=1$93Nanf+BuBuUIBieRuJN+w$9cJOPS/wE/7FppOauJOwkMcwP0oXNxV7fTW+8u+bY3w'),
(3, 'rodrigo', '$argon2id$v=19$m=65536,t=4,p=1$K3UoWNsY6TIrYugmzXdMsQ$AxgYb2qb/O/qeN2IfDWQuCOrr9rJCBFPrM4PMNXErC0');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `keyReference`
--
ALTER TABLE `keyReference`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_C391455F5E237E06` (`name`);

--
-- Indexes for table `language`
--
ALTER TABLE `language`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `token`
--
ALTER TABLE `token`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_5F37A13BA76ED395` (`user_id`);

--
-- Indexes for table `translation`
--
ALTER TABLE `translation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_B469456F82F1BAF4` (`language_id`),
  ADD KEY `IDX_B469456FF9CBA080` (`key_reference_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_8D93D64924A232CF` (`user_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `keyReference`
--
ALTER TABLE `keyReference`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `language`
--
ALTER TABLE `language`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `token`
--
ALTER TABLE `token`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `translation`
--
ALTER TABLE `translation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `token`
--
ALTER TABLE `token`
  ADD CONSTRAINT `FK_5F37A13BA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Constraints for table `translation`
--
ALTER TABLE `translation`
  ADD CONSTRAINT `FK_B469456F82F1BAF4` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`),
  ADD CONSTRAINT `FK_B469456FF9CBA080` FOREIGN KEY (`key_reference_id`) REFERENCES `keyReference` (`id`);
COMMIT;