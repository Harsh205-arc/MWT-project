-- Database: `roomate_app`

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

CREATE TABLE `users` (
  `u_id` int(11) NOT NULL AUTO_INCREMENT,
  `u_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `passwords` varchar(100) NOT NULL,
  `age` int(11) DEFAULT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`u_id`),
  UNIQUE KEY `email` (`email`)
);

-- --------------------------------------------------------
-- Table structure for table `house`
-- --------------------------------------------------------

CREATE TABLE `house` (
  `h_id` int(11) NOT NULL AUTO_INCREMENT,
  `createdBy` int(11) NOT NULL,
  PRIMARY KEY (`h_id`),
  KEY `createdBy` (`createdBy`),
  CONSTRAINT `house_ibfk_1`
    FOREIGN KEY (`createdBy`) REFERENCES `users` (`u_id`) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- Table structure for table `house_members`
-- --------------------------------------------------------

CREATE TABLE `house_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `house_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `house_id` (`house_id`, `user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `house_members_ibfk_1`
    FOREIGN KEY (`house_id`) REFERENCES `house` (`h_id`) ON DELETE CASCADE,
  CONSTRAINT `house_members_ibfk_2`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- Table structure for table `expense`
-- --------------------------------------------------------

CREATE TABLE `expense` (
  `e_id` int(11) NOT NULL AUTO_INCREMENT,
  `paid_by` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `name` varchar(150) DEFAULT NULL,
  `house_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`e_id`),
  KEY `house_id` (`house_id`),
  KEY `paid_by` (`paid_by`),
  CONSTRAINT `expense_ibfk_1`
    FOREIGN KEY (`house_id`) REFERENCES `house` (`h_id`),
  CONSTRAINT `expense_ibfk_2`
    FOREIGN KEY (`paid_by`) REFERENCES `users` (`u_id`)
);

-- --------------------------------------------------------
-- Table structure for table `expense_split`
-- --------------------------------------------------------

CREATE TABLE `expense_split` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `es_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `split` decimal(10,2) NOT NULL,
  `settled` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `es_id` (`es_id`, `user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `expense_split_ibfk_1`
    FOREIGN KEY (`es_id`) REFERENCES `expense` (`e_id`) ON DELETE CASCADE,
  CONSTRAINT `expense_split_ibfk_2`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- Table structure for table `chores`
-- --------------------------------------------------------

CREATE TABLE `chores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `house_id` int(11) NOT NULL,
  `assinged_to` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `completed` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `house_id` (`house_id`),
  KEY `assinged_to` (`assinged_to`),
  CONSTRAINT `chores_ibfk_1`
    FOREIGN KEY (`house_id`) REFERENCES `house` (`h_id`) ON DELETE CASCADE,
  CONSTRAINT `chores_ibfk_2`
    FOREIGN KEY (`assinged_to`) REFERENCES `users` (`u_id`) ON DELETE CASCADE
);
