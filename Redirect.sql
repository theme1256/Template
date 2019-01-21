-- phpMyAdmin SQL Dump
-- version 4.8.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 21, 2019 at 08:30 PM
-- Server version: 5.7.24-0ubuntu0.16.04.1-log
-- PHP Version: 7.0.32-0ubuntu0.16.04.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `webpages_faelles`
--

-- --------------------------------------------------------

--
-- Table structure for table `Redirect`
--

CREATE TABLE `Redirect` (
  `url_id` int(5) NOT NULL,
  `sourceUrl` varchar(256) NOT NULL,
  `destinationUrl` varchar(256) NOT NULL,
  `fk_urlID` int(5) DEFAULT NULL,
  `type` int(5) NOT NULL,
  `noindex` int(2) NOT NULL DEFAULT '0',
  `descrip` varchar(75) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `Redirect`
--
ALTER TABLE `Redirect`
  ADD PRIMARY KEY (`url_id`),
  ADD KEY `Redirect_url_id_index` (`url_id`) USING BTREE,
  ADD KEY `fk_urlID` (`fk_urlID`) USING BTREE;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `Redirect`
--
ALTER TABLE `Redirect`
  MODIFY `url_id` int(5) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `Redirect`
--
ALTER TABLE `Redirect`
  ADD CONSTRAINT `Redirect_ibfk_2` FOREIGN KEY (`fk_urlID`) REFERENCES `Redirect` (`url_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
