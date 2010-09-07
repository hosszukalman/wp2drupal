-- phpMyAdmin SQL Dump
-- version 2.11.7.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Sep 07, 2010 at 08:10 PM
-- Server version: 5.0.41
-- PHP Version: 5.2.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `hk_import`
--

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `wp_comment_id` int(11) NOT NULL COMMENT 'WP comment ID',
  `cid` int(11) NOT NULL COMMENT 'Drupal comment ID',
  PRIMARY KEY  (`wp_comment_id`,`cid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
