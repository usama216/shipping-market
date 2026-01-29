-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Jan 04, 2026 at 05:46 PM
-- Server version: 8.0.44
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `marketsz`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel-cache-all_rates_fedex_0.4_10x10x10_VG1110-VG', 'a:3:{i:0;a:13:{s:2:\"id\";s:28:\"FEDEX_INTERNATIONAL_PRIORITY\";s:18:\"carrier_service_id\";i:1;s:12:\"service_name\";s:30:\"FedEx International Priority®\";s:12:\"service_type\";s:28:\"FEDEX_INTERNATIONAL_PRIORITY\";s:5:\"price\";d:263.18;s:11:\"base_charge\";d:0;s:16:\"total_surcharges\";d:13.65;s:19:\"surcharge_breakdown\";a:0:{}s:8:\"currency\";s:3:\"USD\";s:12:\"transit_days\";N;s:13:\"delivery_date\";N;s:12:\"is_live_rate\";b:1;s:7:\"carrier\";s:5:\"fedex\";}i:1;a:13:{s:2:\"id\";s:21:\"INTERNATIONAL_ECONOMY\";s:18:\"carrier_service_id\";N;s:12:\"service_name\";s:29:\"FedEx International Economy®\";s:12:\"service_type\";s:21:\"INTERNATIONAL_ECONOMY\";s:5:\"price\";d:225.47;s:11:\"base_charge\";d:0;s:16:\"total_surcharges\";d:11.86;s:19:\"surcharge_breakdown\";a:0:{}s:8:\"currency\";s:3:\"USD\";s:12:\"transit_days\";N;s:13:\"delivery_date\";N;s:12:\"is_live_rate\";b:1;s:7:\"carrier\";s:5:\"fedex\";}i:2;a:13:{s:2:\"id\";s:32:\"FEDEX_INTERNATIONAL_CONNECT_PLUS\";s:18:\"carrier_service_id\";N;s:12:\"service_name\";s:32:\"FedEx International Connect Plus\";s:12:\"service_type\";s:32:\"FEDEX_INTERNATIONAL_CONNECT_PLUS\";s:5:\"price\";d:204.94;s:11:\"base_charge\";d:0;s:16:\"total_surcharges\";d:10.88;s:19:\"surcharge_breakdown\";a:0:{}s:8:\"currency\";s:3:\"USD\";s:12:\"transit_days\";N;s:13:\"delivery_date\";N;s:12:\"is_live_rate\";b:1;s:7:\"carrier\";s:5:\"fedex\";}}', 1767490018),
('laravel-cache-all_rates_fedex_20_10x10x10_VG1110-VG', 'a:3:{i:0;a:13:{s:2:\"id\";s:28:\"FEDEX_INTERNATIONAL_PRIORITY\";s:18:\"carrier_service_id\";i:1;s:12:\"service_name\";s:30:\"FedEx International Priority®\";s:12:\"service_type\";s:28:\"FEDEX_INTERNATIONAL_PRIORITY\";s:5:\"price\";d:428.51;s:11:\"base_charge\";d:0;s:16:\"total_surcharges\";d:23.21;s:19:\"surcharge_breakdown\";a:0:{}s:8:\"currency\";s:3:\"USD\";s:12:\"transit_days\";N;s:13:\"delivery_date\";N;s:12:\"is_live_rate\";b:1;s:7:\"carrier\";s:5:\"fedex\";}i:1;a:13:{s:2:\"id\";s:21:\"INTERNATIONAL_ECONOMY\";s:18:\"carrier_service_id\";N;s:12:\"service_name\";s:29:\"FedEx International Economy®\";s:12:\"service_type\";s:21:\"INTERNATIONAL_ECONOMY\";s:5:\"price\";d:358.06;s:11:\"base_charge\";d:0;s:16:\"total_surcharges\";d:19.85;s:19:\"surcharge_breakdown\";a:0:{}s:8:\"currency\";s:3:\"USD\";s:12:\"transit_days\";N;s:13:\"delivery_date\";N;s:12:\"is_live_rate\";b:1;s:7:\"carrier\";s:5:\"fedex\";}i:2;a:13:{s:2:\"id\";s:32:\"FEDEX_INTERNATIONAL_CONNECT_PLUS\";s:18:\"carrier_service_id\";N;s:12:\"service_name\";s:32:\"FedEx International Connect Plus\";s:12:\"service_type\";s:32:\"FEDEX_INTERNATIONAL_CONNECT_PLUS\";s:5:\"price\";d:325.55;s:11:\"base_charge\";d:0;s:16:\"total_surcharges\";d:18.3;s:19:\"surcharge_breakdown\";a:0:{}s:8:\"currency\";s:3:\"USD\";s:12:\"transit_days\";N;s:13:\"delivery_date\";N;s:12:\"is_live_rate\";b:1;s:7:\"carrier\";s:5:\"fedex\";}}', 1767489943),
('laravel-cache-carrier_fedex_token', 's:1277:\"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJDWFMtVFAiXSwiUGF5bG9hZCI6eyJjbGllbnRJZGVudGl0eSI6eyJjbGllbnRLZXkiOiJsNzA4NDQzZDc3N2NmZjQwNzc5MDZmNmYwZTg2NDYxNDk5In0sImF1dGhlbnRpY2F0aW9uUmVhbG0iOiJDTUFDIiwiYWRkaXRpb25hbElkZW50aXR5Ijp7InRpbWVTdGFtcCI6IjA0LUphbi0yMDI2IDA3OjU5OjU2IEVTVCIsImdyYW50X3R5cGUiOiJjbGllbnRfY3JlZGVudGlhbHMiLCJhcGltb2RlIjoiU2FuZGJveCIsImN4c0lzcyI6Imh0dHBzOi8vY3hzYXV0aHNlcnZlci1zdGFnaW5nLmFwcC5wYWFzLmZlZGV4LmNvbS90b2tlbi9vYXV0aDIifSwicGVyc29uYVR5cGUiOiJEaXJlY3RJbnRlZ3JhdG9yX0IyQiJ9LCJleHAiOjE3Njc1MzUxOTYsImp0aSI6ImI1ODZmYmMzLTI3ZTgtNGU0Yi04YzBiLWJjMWEwYzFlMmVhZSJ9.vt-VnPm_kFN3BUa9ZesLlqtG9FTnhqAPSR-5RaYIi_V5-pyl0BOpOCsYRhu3sojeOoBHcHPkOYMJxRnA9Gr-gMT6MR5gomh8ixX5rzFKC3pQSMVPvC_Jhj7NpBfjjZcxEi9_eG3FfYVvBt-hftaysjnFsClul41mLxukPPE15JpJGcCpwSsZUTYI5WxsNOA6mjjZ10yVbQ5mfZ6ZW3Dcyh0D09ucqUdOMtPcQMcbci8nFBNXJPpHBUnb59_aLAwyKL_-C63NcKCzooMQwJqGuI9AQQqQhousrhC7JLAUIzzLYhu65PB_OknOAFQp7nyX1HuqSyVUvB-3sm3IHSuRrA-HavPkzKtzpW5OkYVVLumWnLLWNcCV-iNsHk2m0lHBBRyTQJiL5YzAHlx98PMX1BxHKzLBijjLS8Erxp0LMIO0TX_oy8nQnPWNZIqKyHJJTDzY4G5h1LqVR79-tl6Pq0XxDESGu94xO-nSeTD8v40_MIGEyKNNSPC4tQqifLXjEg7ZYwF7xX4JKIwEEWE3f21wr5_D48wE3yhE6agjVOGF1__oYR2OcG4lN6gQY802Wf2O9Hl1obTZweZN8MaedDGIFvaXknqF_dimvQC4sSKiiQctnGKja460OURpWJILJpsk4bRhqiEzMIDWopIKHs7bvzDcfKbbGNAk8G6KfnU\";', 1767535135),
('laravel-cache-illuminate:queue:restart', 'i:1767487158;', 2082847158),
('laravel-cache-shipping_rate:0:0.4:0x0x0:VG1110-VG', 'd:263.18;', 1767490032),
('laravel-cache-shipping_rate:0:20:0x0x0:VG1110-VG', 'd:428.51;', 1767489951);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `carrier_addons`
--

CREATE TABLE `carrier_addons` (
  `id` bigint UNSIGNED NOT NULL,
  `addon_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `carrier_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `display_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price_type` enum('fixed','percentage','carrier_rate') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'carrier_rate',
  `price_value` decimal(10,2) DEFAULT NULL,
  `fallback_price` decimal(10,2) DEFAULT NULL,
  `use_fallback` tinyint(1) NOT NULL DEFAULT '1',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `compatible_services` json DEFAULT NULL,
  `incompatible_addons` json DEFAULT NULL,
  `requires_value_declaration` tinyint(1) NOT NULL DEFAULT '0',
  `min_declared_value` decimal(10,2) DEFAULT NULL,
  `max_declared_value` decimal(10,2) DEFAULT NULL,
  `source` enum('carrier_api','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `carrier_api_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `carrier_addons`
--

INSERT INTO `carrier_addons` (`id`, `addon_code`, `carrier_code`, `display_name`, `description`, `icon`, `price_type`, `price_value`, `fallback_price`, `use_fallback`, `currency`, `compatible_services`, `incompatible_addons`, `requires_value_declaration`, `min_declared_value`, `max_declared_value`, `source`, `is_active`, `sort_order`, `carrier_api_code`, `created_at`, `updated_at`) VALUES
(1, 'extra_handling', 'all', 'Extra Handling', 'Special handling for oversized or irregularly shaped packages', NULL, 'carrier_rate', NULL, 18.00, 1, 'USD', NULL, NULL, 0, NULL, NULL, 'admin', 1, 1, NULL, '2025-12-26 09:37:01', '2025-12-26 09:37:01'),
(2, 'fragile_handling', 'all', 'Fragile Package Protection', 'Special care for fragile items with extra padding', NULL, 'fixed', 15.00, NULL, 1, 'USD', NULL, NULL, 0, NULL, NULL, 'admin', 1, 2, NULL, '2025-12-26 09:37:01', '2025-12-26 09:37:01'),
(3, 'signature_required', 'all', 'Signature Required', 'Recipient signature required upon delivery', NULL, 'carrier_rate', NULL, 6.50, 1, 'USD', NULL, NULL, 0, NULL, NULL, 'admin', 1, 3, NULL, '2025-12-26 09:37:01', '2025-12-26 09:37:01'),
(4, 'insurance_basic', 'all', 'Basic Insurance', 'Coverage up to $500 declared value', NULL, 'percentage', 2.50, NULL, 1, 'USD', NULL, NULL, 1, NULL, 500.00, 'admin', 1, 4, NULL, '2025-12-26 09:37:01', '2025-12-26 09:37:01'),
(5, 'insurance_premium', 'all', 'Premium Insurance', 'Coverage up to $5000 declared value', NULL, 'percentage', 3.50, NULL, 1, 'USD', NULL, NULL, 1, 500.01, 5000.00, 'admin', 1, 5, NULL, '2025-12-26 09:37:01', '2025-12-26 09:37:01'),
(6, 'dangerous_goods', 'fedex', 'Dangerous Goods Handling', 'For shipping hazardous materials (batteries, chemicals)', NULL, 'carrier_rate', NULL, 55.00, 1, 'USD', NULL, NULL, 0, NULL, NULL, 'carrier_api', 1, 10, 'DANGEROUS_GOODS', '2025-12-26 09:37:01', '2025-12-26 09:37:01'),
(7, 'hold_at_location', 'fedex', 'Hold at FedEx Location', 'Package held at nearest FedEx for pickup', NULL, 'carrier_rate', NULL, 8.00, 1, 'USD', NULL, NULL, 0, NULL, NULL, 'carrier_api', 1, 11, 'HOLD_AT_LOCATION', '2025-12-26 09:37:01', '2025-12-26 09:37:01'),
(8, 'saturday_delivery', 'dhl', 'Saturday Delivery', 'Delivery on Saturday (where available)', NULL, 'carrier_rate', NULL, 25.00, 1, 'USD', NULL, NULL, 0, NULL, NULL, 'carrier_api', 1, 12, 'SAT_DELIVERY', '2025-12-26 09:37:01', '2025-12-26 09:37:01');

-- --------------------------------------------------------

--
-- Table structure for table `carrier_services`
--

CREATE TABLE `carrier_services` (
  `id` bigint UNSIGNED NOT NULL,
  `carrier_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `service_code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `logo_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_international` tinyint(1) NOT NULL DEFAULT '1',
  `is_domestic` tinyint(1) NOT NULL DEFAULT '0',
  `base_transit_days` int DEFAULT NULL,
  `max_transit_days` int DEFAULT NULL,
  `max_weight_kg` decimal(8,2) DEFAULT NULL,
  `max_weight_lb` decimal(8,2) DEFAULT NULL,
  `max_length_in` decimal(8,2) DEFAULT NULL,
  `max_length_cm` decimal(8,2) DEFAULT NULL,
  `max_girth_in` decimal(8,2) DEFAULT NULL,
  `max_girth_cm` decimal(8,2) DEFAULT NULL,
  `max_declared_value` decimal(12,2) DEFAULT NULL,
  `accepts_dangerous_goods` tinyint(1) NOT NULL DEFAULT '0',
  `accepts_lithium_batteries` tinyint(1) NOT NULL DEFAULT '1',
  `accepts_fragile` tinyint(1) NOT NULL DEFAULT '1',
  `is_freight` tinyint(1) NOT NULL DEFAULT '0',
  `min_weight_lb` decimal(8,2) DEFAULT NULL,
  `min_weight_kg` decimal(8,2) DEFAULT NULL,
  `supported_countries` json DEFAULT NULL,
  `excluded_countries` json DEFAULT NULL,
  `supported_origin_countries` json DEFAULT NULL,
  `fallback_pricing_rules` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `carrier_specific_options` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `carrier_services`
--

INSERT INTO `carrier_services` (`id`, `carrier_code`, `service_code`, `display_name`, `description`, `logo_url`, `is_international`, `is_domestic`, `base_transit_days`, `max_transit_days`, `max_weight_kg`, `max_weight_lb`, `max_length_in`, `max_length_cm`, `max_girth_in`, `max_girth_cm`, `max_declared_value`, `accepts_dangerous_goods`, `accepts_lithium_batteries`, `accepts_fragile`, `is_freight`, `min_weight_lb`, `min_weight_kg`, `supported_countries`, `excluded_countries`, `supported_origin_countries`, `fallback_pricing_rules`, `is_active`, `is_default`, `sort_order`, `carrier_specific_options`, `created_at`, `updated_at`) VALUES
(1, 'fedex', 'FEDEX_INTERNATIONAL_PRIORITY', 'FedEx International Priority', 'Fast international shipping with customs clearance', NULL, 1, 0, 2, 5, 68.00, 150.00, 119.00, 302.00, 165.00, 419.00, 50000.00, 0, 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, NULL, '2025-12-26 09:37:01', '2025-12-26 09:37:01'),
(2, 'fedex', 'FEDEX_INTERNATIONAL_ECONOMY', 'FedEx International Economy', 'Economical international shipping', NULL, 1, 0, 5, 8, 68.00, 150.00, 119.00, 302.00, 165.00, 419.00, 50000.00, 0, 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 2, NULL, '2025-12-26 09:37:01', '2025-12-26 09:37:01'),
(3, 'fedex', 'FEDEX_GROUND', 'FedEx Ground', 'Reliable ground shipping within the US', NULL, 0, 1, 3, 7, 68.00, 150.00, 108.00, 274.00, 165.00, 419.00, 50000.00, 0, 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 10, NULL, '2025-12-26 09:37:01', '2025-12-26 11:07:17'),
(4, 'dhl', 'EXPRESS_WORLDWIDE', 'DHL Express Worldwide', 'Premium international express service', NULL, 1, 0, 2, 4, 70.00, 154.00, 118.00, 300.00, 157.00, 400.00, 50000.00, 0, 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 3, NULL, '2025-12-26 09:37:01', '2025-12-26 09:37:01'),
(5, 'dhl', 'EXPRESS_ECONOMY', 'DHL Economy Select', 'Cost-effective international shipping', NULL, 1, 0, 5, 10, 70.00, 154.00, 118.00, 300.00, 157.00, 400.00, 25000.00, 0, 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 4, NULL, '2025-12-26 09:37:01', '2025-12-26 09:37:01'),
(6, 'ups', 'EXPRESS', 'UPS Worldwide Express', 'Fast worldwide delivery', NULL, 1, 0, 2, 4, 68.00, 150.00, 108.00, 274.00, 165.00, 419.00, 50000.00, 0, 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 5, NULL, '2025-12-26 09:37:01', '2025-12-26 09:37:01'),
(7, 'ups', 'SAVER', 'UPS Worldwide Saver', 'End-of-day delivery worldwide', NULL, 1, 0, 3, 5, 68.00, 150.00, 108.00, 274.00, 165.00, 419.00, 50000.00, 0, 1, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 6, NULL, '2025-12-26 09:37:01', '2025-12-26 09:37:01'),
(8, 'sea_freight', 'SEA_FREIGHT_STANDARD', 'Sea Freight', 'Economical ocean shipping for large cargo', NULL, 1, 0, 30, 60, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 100.00, 45.00, NULL, NULL, NULL, NULL, 1, 0, 20, NULL, '2025-12-26 09:37:01', '2025-12-26 09:37:01'),
(9, 'air_cargo', 'AIR_CARGO_STANDARD', 'Air Cargo', 'Air freight for larger shipments', NULL, 1, 0, 7, 14, 1000.00, 2200.00, 288.00, 732.00, NULL, NULL, 100000.00, 1, 1, 1, 1, 50.00, 23.00, NULL, NULL, NULL, NULL, 1, 0, 15, NULL, '2025-12-26 09:37:01', '2025-12-26 16:03:32');

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` bigint UNSIGNED NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `discount_type` enum('percentage','fixed') COLLATE utf8mb4_unicode_ci NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `minimum_order_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `usage_limit` int DEFAULT NULL,
  `per_customer_limit` int UNSIGNED DEFAULT NULL,
  `used_count` int NOT NULL DEFAULT '0',
  `expiry_date` date DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `auto_apply` tinyint(1) NOT NULL DEFAULT '0',
  `target_audience` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `is_private` tinyint(1) NOT NULL DEFAULT '0',
  `point_cost` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `discount_type`, `discount_value`, `minimum_order_amount`, `usage_limit`, `per_customer_limit`, `used_count`, `expiry_date`, `start_date`, `is_active`, `auto_apply`, `target_audience`, `is_private`, `point_cost`, `description`, `created_at`, `updated_at`) VALUES
(1, 'R475FGJI', 'percentage', 10.00, 1.00, 69, NULL, 0, '2025-12-29', '2025-11-24 00:00:00', 1, 1, 'all', 0, NULL, 'Culpa elit velit n', '2025-12-23 17:18:53', '2025-12-23 18:27:37'),
(2, 'SUQDLMPJ', 'percentage', 91.00, 1.00, 90, 10, 0, '2025-12-25', '2025-12-22 00:00:00', 1, 1, 'all', 0, NULL, 'Laudantium magna mi', '2025-12-23 17:21:35', '2025-12-23 18:32:34'),
(3, '5LYW0HDV', 'percentage', 20.00, 1.00, NULL, NULL, 0, '2025-12-25', '2025-12-23 00:00:00', 1, 1, 'all', 1, NULL, NULL, '2025-12-23 17:55:34', '2025-12-23 18:33:55'),
(4, '1DUYHBLM', 'fixed', 24.00, 10.00, 92, 3, 0, '2026-01-05', '2025-12-28 00:00:00', 1, 1, 'all', 0, NULL, 'Est labore earum vel', '2025-12-28 03:01:06', '2026-01-03 11:30:47');

-- --------------------------------------------------------

--
-- Table structure for table `coupon_usages`
--

CREATE TABLE `coupon_usages` (
  `id` bigint UNSIGNED NOT NULL,
  `coupon_id` bigint UNSIGNED DEFAULT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `transaction_id` bigint UNSIGNED DEFAULT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `order_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` bigint UNSIGNED NOT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stripe_id` longtext COLLATE utf8mb4_unicode_ci,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `suite` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `warehouse_id` bigint UNSIGNED DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` longtext COLLATE utf8mb4_unicode_ci,
  `zip_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `is_old` tinyint(1) NOT NULL DEFAULT '0',
  `loyalty_points` int UNSIGNED NOT NULL DEFAULT '0',
  `lifetime_spend` decimal(12,2) NOT NULL DEFAULT '0.00',
  `referral_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referred_by_id` bigint UNSIGNED DEFAULT NULL,
  `referred_by` bigint UNSIGNED DEFAULT NULL,
  `lifetime_points_earned` int NOT NULL DEFAULT '0',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `avatar`, `stripe_id`, `first_name`, `last_name`, `email`, `user_name`, `phone`, `password`, `suite`, `warehouse_id`, `country`, `date_of_birth`, `tax_id`, `address`, `zip_code`, `state`, `city`, `email_verified_at`, `is_active`, `is_old`, `loyalty_points`, `lifetime_spend`, `referral_code`, `referred_by_id`, `referred_by`, `lifetime_points_earned`, `remember_token`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, NULL, NULL, 'ben', 'deli', 'testcostumer@gmail.com', NULL, '234324534523', '$2y$12$.ZbpGer7iuicT.841u/vhOWe571b9lk060A7XghoXPtmKaC632/ce', 'MKT000001', 1, 'Dominica', '1998-04-05', NULL, NULL, '00109-8000', 'Dominica', 'Dominica', NULL, 1, 0, 0, 0.00, '8BAC4909', NULL, NULL, 0, NULL, '2025-12-23 11:08:35', '2025-12-24 02:37:13', NULL),
(2, NULL, NULL, 'Victoria', 'Obcaecati quibusdam', 'xutujini@mailinator.com', NULL, '+1 (576) 338-2558', '$2y$12$I3zy3iTYsYmp9HLDEc.wCuUUDbophGoTaga4p1jEbgE37K2OQO3om', 'MKT000002', 1, 'Martinique', '1990-03-17', 'Eos vel dignissimos', NULL, 'Officia proident et', 'Cupiditate et maiore', 'Lorem in nesciunt q', NULL, 1, 0, 0, 0.00, '0FE398D3', NULL, NULL, 0, NULL, '2025-12-23 13:00:43', '2025-12-24 02:37:13', NULL),
(3, NULL, NULL, 'Kendall', 'Dicta incididunt dol', 'sufevix@mailinator.com', NULL, '+1 (821) 768-7329', '$2y$12$DK2WBEbWmnFELmw2ATriY.PgDh0PgmtVvPfRl6ZNQ7dN5NyRvRJeK', 'MKT000003', 1, 'Saint Lucia', '1994-11-28', 'In suscipit suscipit', NULL, 'Quo esse molestias n', 'Qui aliquip exercita', 'Reprehenderit quae f', NULL, 1, 0, 0, 0.00, 'C341E0A0', NULL, NULL, 0, NULL, '2025-12-23 14:54:14', '2025-12-24 02:37:13', NULL),
(4, NULL, NULL, 'Harper', 'Est adipisci sit a', 'nirarun@mailinator.com', NULL, '+1 (796) 965-5191', '$2y$12$LVJstjSMTcKwscUQKFJMT.yQUtWz/nwc6oMDwvTJ/yWzQ5x73qBZS', 'MKT000004', 1, 'Barbuda', '1963-11-14', 'Asperiores harum rei', 'Qui ut et omnis repr', 'Laboris quibusdam de', 'Iure molestias nulla', 'Qui provident et la', NULL, 1, 0, 0, 0.00, '21C3BAE0', NULL, NULL, 0, NULL, '2025-12-23 15:17:57', '2025-12-24 02:37:13', NULL),
(5, NULL, NULL, 'Odette', 'Ut nostrum nulla pro', 'sanuramaqu@mailinator.com', NULL, '+1 (332) 119-9156', '$2y$12$jdUp8j7dv4Qca5MJrBUP7.0OzUvLK2LseJeWNvbaMIKWWnTfwAG5G', 'MKT000005', 1, 'Barbuda', '1932-08-27', 'Minima similique pro', 'Velit ea illo est i', 'Eos nihil optio dol', 'Nisi incididunt non', 'Dolore natus consect', NULL, 1, 0, 0, 0.00, '78DBA09E', NULL, NULL, 0, NULL, '2025-12-23 15:20:14', '2025-12-24 02:37:13', NULL),
(6, NULL, NULL, 'Mona', 'Delectus aute simil', 'xyryfih@mailinator.com', NULL, '+1 (165) 378-7296', '$2y$12$XrPKC7lfFmZg.u1jnF2oJ.vPoGJrII0IehuBVbBMahuHc0IKxra0C', 'MKT000006', 1, 'Sint Maarten', '2014-11-15', 'Repellendus Amet o', 'Incididunt ea fugiat', 'Ut facilis recusanda', 'Culpa nulla ad eos', 'Dolore ut inventore', NULL, 1, 0, 0, 0.00, 'E5B3F341', NULL, NULL, 0, NULL, '2025-12-23 15:34:47', '2025-12-24 02:37:13', NULL),
(7, NULL, NULL, 'Gareth', 'Sunt nisi voluptatem', 'xoxyn@mailinator.com', NULL, '15748278945', '$2y$12$55r8KMQjURrQbIoa.wu6NemEqfF9pSuwc16EQ7IBXediq4fCQ5GRq', 'MKT000007', 1, 'Curaçao', '1952-11-05', 'Magnam voluptatem ir', 'Doloribus nihil opti', 'Sit anim mollit sun', 'Quis voluptatem ips', 'Quis magni ea alias', NULL, 1, 0, 0, 0.00, '92A48FF1', NULL, NULL, 0, NULL, '2025-12-23 15:35:55', '2025-12-24 02:37:13', NULL),
(8, NULL, NULL, 'Tasha', 'Haley', 'qumu@mailinator.com', NULL, '13434535234', '$2y$12$Vnx/8v1sOPo0juS6tvIXyOfcFwp7wEF8x79eKt.baIJGTHpJmYIc2', 'MKT000008', 1, 'Cuba', '1948-06-23', 'Nihil lorem non ut d', 'In quis velit accus', '49970', 'Qui excepturi non au', 'Dolor anim ab minus', NULL, 1, 0, 0, 0.00, '17B19768', NULL, NULL, 0, NULL, '2025-12-23 15:57:51', '2025-12-24 02:37:13', NULL),
(9, NULL, NULL, 'Ingrid', 'Banks', 'qoxacute@mailinator.com', NULL, '12341234132', '$2y$12$qcvjOTwOY7M7ujFb0iJLIO0QunTE9TrtbOpfYgoqpO8CzVsLPZNT.', 'MKT000009', 1, 'Saint Martin', '1956-07-18', 'Eveniet ducimus no', 'Exercitation id exp', '41416', 'Ut necessitatibus nu', 'Corrupti et volupta', NULL, 1, 0, 0, 0.00, '0E6325EC', NULL, NULL, 0, NULL, '2025-12-23 16:04:31', '2025-12-24 02:37:13', NULL),
(10, NULL, NULL, 'Adele', 'Wilder', 'mupiwel@mailinator.com', NULL, '+32 53 45 23 42345', '$2y$12$iIckWzQMzBBR8nDCkmoluOj1ybc8kVv.UfB4GRncloC3We7lfxZem', 'MKT000010', 1, 'Dominica', '2022-02-08', 'Molestiae ipsam non', 'In cupiditate nostru', '31824', 'Dolorem facere reici', 'Est culpa veniam e', NULL, 1, 0, 600, 0.00, '68F025A7', NULL, NULL, 0, NULL, '2025-12-24 02:33:29', '2025-12-24 03:37:15', NULL),
(11, NULL, 'cus_Tj16vgIZXX1bcH', 'Xyla', 'Greene', 'dyge@mailinator.com', NULL, '+92 86876868', '$2y$12$3L1hQnLyLK.gLHFedJCeceGaE2vAq7cr6FxGZpIKi.FDOE849J8X6', 'MKT000011', 1, 'British Virgin Islands', '1980-03-02', '67867868', 'Mollitia optio et i', 'VG1110', 'Tortola', 'Tortola', NULL, 1, 0, 200, 0.00, 'BC323428', NULL, NULL, 0, NULL, '2025-12-26 06:30:32', '2026-01-03 12:57:07', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customer_addresses`
--

CREATE TABLE `customer_addresses` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `address_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line_1` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line_2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `postal_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `country_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_default_us` tinyint(1) NOT NULL DEFAULT '0',
  `is_default_uk` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customer_addresses`
--

INSERT INTO `customer_addresses` (`id`, `user_id`, `customer_id`, `address_name`, `full_name`, `address_line_1`, `address_line_2`, `country`, `state`, `city`, `postal_code`, `country_code`, `phone_number`, `is_default_us`, `is_default_uk`, `created_at`, `updated_at`) VALUES
(1, NULL, 9, 'Primary Address', 'Ingrid Banks', 'Exercitation id exp', NULL, 'Saint Martin', 'Ut necessitatibus nu', 'Corrupti et volupta', '41416', 'MF', '12341234132', 1, 0, '2025-12-23 16:04:31', '2025-12-23 16:04:31'),
(2, NULL, 10, 'Primary Address', 'Adele Wilder', 'In cupiditate nostru', NULL, 'Dominica', 'Saint George', 'Roseau', '00109-8000', 'DM', '+32 53 45 23 42345', 1, 0, '2025-12-24 02:33:29', '2025-12-24 06:01:53'),
(3, NULL, 11, 'Primary Address', 'Xyla Greene', 'Nanny Cay', NULL, 'British Virgin Islands', NULL, 'Road Town', 'VG1110', 'VG', '+92 86876868', 1, 0, '2025-12-26 06:30:32', '2025-12-26 12:49:49');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `international_shipping_options`
--

CREATE TABLE `international_shipping_options` (
  `id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_options`
--

CREATE TABLE `login_options` (
  `id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `is_text_input` tinyint(1) NOT NULL DEFAULT '0',
  `price` double DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_rules`
--

CREATE TABLE `loyalty_rules` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `spend_amount` decimal(10,2) NOT NULL,
  `earn_points` int NOT NULL,
  `redeem_points` int NOT NULL,
  `redeem_value` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_tiers`
--

CREATE TABLE `loyalty_tiers` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `min_lifetime_spend` decimal(10,2) NOT NULL DEFAULT '0.00',
  `min_spend` decimal(10,2) NOT NULL DEFAULT '0.00',
  `earn_multiplier` decimal(3,2) NOT NULL DEFAULT '1.00',
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loyalty_tiers`
--

INSERT INTO `loyalty_tiers` (`id`, `name`, `slug`, `min_lifetime_spend`, `min_spend`, `earn_multiplier`, `icon`, `sort_order`, `is_active`, `color`, `display_order`, `created_at`, `updated_at`) VALUES
(1, 'Bronze', 'bronze', 0.00, 0.00, 1.00, 'medal-bronze', 1, 1, '#CD7F32', 0, '2025-12-24 02:23:41', '2025-12-24 02:23:41'),
(2, 'Silver', 'silver', 500.00, 0.00, 1.25, 'medal-silver', 2, 1, '#C0C0C0', 0, '2025-12-24 02:23:41', '2025-12-24 02:23:41'),
(3, 'Gold', 'gold', 2000.00, 0.00, 1.50, 'medal-gold', 3, 1, '#FFD700', 0, '2025-12-24 02:23:41', '2025-12-24 02:23:41');

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_transactions`
--

CREATE TABLE `loyalty_transactions` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `transaction_id` bigint UNSIGNED DEFAULT NULL,
  `type` enum('earn','redeem') COLLATE utf8mb4_unicode_ci NOT NULL,
  `points` int NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loyalty_transactions`
--

INSERT INTO `loyalty_transactions` (`id`, `user_id`, `customer_id`, `transaction_id`, `type`, `points`, `amount`, `description`, `created_at`, `updated_at`) VALUES
(1, NULL, 10, NULL, 'earn', 100, 0.00, 'Admin: Added 100 points - bonus', '2025-12-24 03:35:00', '2025-12-24 03:35:00'),
(2, NULL, 10, NULL, 'earn', 500, 0.00, 'Admin: Added 500 points - compensation', '2025-12-24 03:37:15', '2025-12-24 03:37:15'),
(3, NULL, 11, NULL, 'earn', 200, 0.00, 'Admin: Added 200 points - falsjdfl', '2025-12-28 03:07:39', '2025-12-28 03:07:39');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2025_01_14_201443_create_transactions_table', 1),
(5, '2025_01_15_000001_add_loyalty_points_to_users_table', 1),
(6, '2025_01_15_000002_create_coupons_table', 1),
(7, '2025_01_15_000003_create_coupon_usages_table', 1),
(8, '2025_01_15_000004_create_loyalty_transactions_table', 1),
(9, '2025_01_15_000005_create_loyalty_rules_table', 1),
(10, '2025_07_20_155248_create_packages_table', 1),
(11, '2025_07_20_161017_create_special_requests_table', 1),
(12, '2025_07_20_161320_create_package_items_table', 1),
(13, '2025_07_20_163257_create_package_invoices_table', 1),
(14, '2025_07_20_163408_create_sender_addresses_table', 1),
(15, '2025_07_21_211233_create_package_files_table', 1),
(16, '2025_07_29_181018_create_permission_tables', 1),
(17, '2025_08_03_091642_create_user_addresses_table', 1),
(18, '2025_08_03_145618_create_user_cards_table', 1),
(19, '2025_08_04_100842_create_shipping_preferences_table', 1),
(20, '2025_08_04_102004_create_preferred_ship_methods_table', 1),
(21, '2025_08_04_102429_create_international_shipping_options_table', 1),
(22, '2025_08_04_102911_create_shipping_preference_option_table', 1),
(23, '2025_08_04_103156_create_packing_options_table', 1),
(24, '2025_08_04_103603_create_proforma_invoice_options_table', 1),
(25, '2025_08_04_103730_create_login_options_table', 1),
(26, '2025_08_05_173933_create_ships_table', 1),
(27, '2025_08_05_174014_create_ship_packages_table', 1),
(28, '2025_08_27_200703_create_shipping_pricing_table', 1),
(29, '2025_11_29_161532_add_city_to_users_table', 1),
(30, '2025_12_21_011800_enhance_packages_for_carriers', 1),
(31, '2025_12_21_011801_enhance_package_items_for_customs', 1),
(32, '2025_12_21_011802_enhance_sender_addresses_for_carriers', 1),
(33, '2025_12_21_011803_add_carrier_fields_to_ships', 1),
(34, '2025_12_21_150000_create_shipment_events_table', 1),
(35, '2025_12_21_163148_create_package_change_requests_table', 1),
(36, '2025_12_23_130000_create_customers_table', 1),
(37, '2025_12_23_173000_migrate_sender_id_to_customer_id', 2),
(38, '2025_12_23_180000_make_user_id_nullable_for_customer_tables', 3),
(39, '2025_12_23_181000_add_customer_id_to_shipping_preferences', 4),
(40, '2025_12_23_185848_rename_sender_addresses_to_warehouses', 5),
(41, '2025_12_23_185851_add_warehouse_id_to_customers_and_packages', 5),
(42, '2025_12_23_202443_rename_user_addresses_to_customer_addresses_table', 6),
(43, '2025_12_24_000000_update_coupons_module', 7),
(44, '2025_12_24_040000_add_per_customer_limit_to_coupons', 8),
(45, '2025_12_23_235338_create_loyalty_tiers_table', 9),
(46, '2025_12_23_235341_add_referral_to_users_table', 9),
(47, '2025_12_23_235344_add_point_cost_to_coupons_table', 9),
(48, '2025_12_24_000001_add_referral_to_customers_table', 10),
(49, '2025_12_24_000002_add_customer_id_to_loyalty_transactions_table', 10),
(50, '2025_12_24_071617_create_loyalty_tiers_table', 11),
(51, '2025_12_24_071619_add_lifetime_spend_and_referral_to_customers_table', 12),
(52, '2025_12_24_071958_add_customer_id_to_loyalty_transactions_table', 12),
(53, '2025_12_24_072315_add_missing_columns_to_loyalty_tiers_table', 13),
(54, '2025_12_24_122133_rename_tracking_id_to_store_tracking_id_in_packages_table', 14),
(55, '2025_12_24_122730_enhance_package_invoices_table', 15),
(56, '2025_12_24_122901_add_dimensions_and_classification_to_package_items', 15),
(57, '2025_12_25_160013_create_package_invoice_files_table', 16),
(58, '2025_12_26_121550_create_package_cached_rates_table', 17),
(59, '2025_12_26_133519_add_rate_fetch_status_to_packages_table', 18),
(60, '2025_12_26_143403_create_carrier_services_table', 19),
(61, '2025_12_26_143503_create_carrier_addons_table', 19),
(62, '2025_12_26_143508_enhance_ships_table_for_consolidation', 19),
(63, '2025_12_26_182242_add_dimension_limits_to_carrier_services', 20),
(64, '2026_01_03_153000_remove_package_rate_infrastructure', 21),
(65, '2026_01_03_153100_create_rate_markup_rules_table', 21),
(66, '2026_01_03_181425_add_fallback_pricing_to_carrier_addons', 22),
(67, '2026_01_04_000000_expand_ships_status_column', 23);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint UNSIGNED NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint UNSIGNED NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 1),
(3, 'App\\Models\\User', 2),
(2, 'App\\Models\\User', 3),
(4, 'App\\Models\\User', 4),
(5, 'App\\Models\\User', 5),
(6, 'App\\Models\\User', 6),
(2, 'App\\Models\\User', 7),
(7, 'App\\Models\\User', 8),
(4, 'App\\Models\\User', 9);

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` bigint UNSIGNED NOT NULL,
  `package_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `store_tracking_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sender_id` bigint UNSIGNED DEFAULT NULL,
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `warehouse_id` bigint UNSIGNED DEFAULT NULL,
  `special_request` bigint UNSIGNED DEFAULT NULL,
  `date_received` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `from` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_value` double NOT NULL DEFAULT '0',
  `weight` double NOT NULL DEFAULT '0',
  `weight_unit` enum('lb','kg') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'lb',
  `length` decimal(8,2) DEFAULT NULL,
  `width` decimal(8,2) DEFAULT NULL,
  `height` decimal(8,2) DEFAULT NULL,
  `dimension_unit` enum('in','cm') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'in',
  `package_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'YOUR_PACKAGING',
  `note` longtext COLLATE utf8mb4_unicode_ci,
  `status` bigint NOT NULL DEFAULT '1',
  `invoice_status` bigint NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `package_id`, `store_tracking_id`, `sender_id`, `customer_id`, `warehouse_id`, `special_request`, `date_received`, `from`, `total_value`, `weight`, `weight_unit`, `length`, `width`, `height`, `dimension_unit`, `package_type`, `note`, `status`, `invoice_status`, `created_at`, `updated_at`) VALUES
(1, '7-4929-69', 'null', 1, 3, NULL, NULL, '2025-12-23T16:09:00.000Z', 'Amazon', 8000, 0, 'lb', 12.00, 12.00, 12.00, 'in', 'YOUR_PACKAGING', 'what are you?', 2, 0, '2025-12-23 11:10:57', '2026-01-04 07:50:32'),
(2, '5-6361-23', '2341234', NULL, 11, 1, NULL, '2025-12-23', 'eBay', 200, 2, 'lb', 10.00, 10.00, 10.00, 'in', 'FEDEX_BOX', NULL, 4, 0, '2025-12-23 13:02:05', '2026-01-03 20:05:26'),
(4, '2-3079-34', '123224', NULL, 11, 2, NULL, '2025-12-26', 'Magnam aut consequat', 1000, 20, 'lb', 19.00, 19.00, 10.00, 'in', 'FEDEX_ENVELOPE', NULL, 4, 0, '2025-12-24 07:59:26', '2026-01-03 20:36:04'),
(15, '1-9922-28', '23423', NULL, 11, 2, NULL, '2025-12-26', 'Dolor qui sunt quide', 760, 0, 'lb', 50.00, 15.00, 74.00, 'in', 'FEDEX_TUBE', NULL, 4, 0, '2025-12-25 15:47:48', '2026-01-03 20:36:04'),
(20, '3-6405-17', '12312', NULL, 11, 2, NULL, '2025-12-26', 'Vel quos quam adipis', 1325, 45, 'lb', 10.00, 20.00, 10.00, 'in', 'FEDEX_TUBE', NULL, 4, 0, '2025-12-26 13:53:51', '2026-01-03 20:36:04'),
(21, '5-9107-61', '234234', NULL, 11, 1, NULL, '2026-01-03', 'Amazon', 20, 0, 'lb', NULL, NULL, NULL, 'in', 'YOUR_PACKAGING', NULL, 4, 0, '2026-01-03 10:08:30', '2026-01-03 20:36:04'),
(23, '4-7820-33', '12313212', NULL, 11, 1, NULL, '2026-01-04', 'Amazon', 1700, 0, 'lb', NULL, NULL, NULL, 'in', 'YOUR_PACKAGING', NULL, 4, 0, '2026-01-03 20:20:20', '2026-01-03 20:36:04');

-- --------------------------------------------------------

--
-- Table structure for table `package_change_requests`
--

CREATE TABLE `package_change_requests` (
  `id` bigint UNSIGNED NOT NULL,
  `package_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `reviewed_by` bigint UNSIGNED DEFAULT NULL,
  `request_type` enum('package','address') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'package',
  `original_values` json DEFAULT NULL,
  `requested_changes` json NOT NULL,
  `customer_notes` text COLLATE utf8mb4_unicode_ci,
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package_files`
--

CREATE TABLE `package_files` (
  `id` bigint UNSIGNED NOT NULL,
  `package_id` bigint UNSIGNED DEFAULT NULL,
  `package_item_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `package_files`
--

INSERT INTO `package_files` (`id`, `package_id`, `package_item_id`, `name`, `file`, `created_at`, `updated_at`) VALUES
(1, 2, 2, 'loremIpsum.jpg', 'storage/app/public/package_items/TOQAUUQZrnPddQZ.jpg', '2025-12-23 13:02:05', '2025-12-23 13:02:05'),
(9, 4, 3, 'loremIpsum.jpg', 'storage/app/public/package_items/V8yhuA0ToM9hLJF.jpg', '2025-12-25 17:24:47', '2025-12-25 17:24:47'),
(12, 4, 3, 'loremIpsum.jpg', 'storage/app/public/package_items/oWXfjG0Zz9w4MY8.jpg', '2025-12-25 17:40:02', '2025-12-25 17:40:02'),
(13, 4, 3, 'loremIpsum.jpg', 'storage/app/public/package_items/RK2gOIVwZBuNbSA.jpg', '2025-12-25 17:43:43', '2025-12-25 17:43:43'),
(14, 4, 3, 'loremIpsum.jpg', 'storage/app/public/package_items/FH0Dm0GldfEfjPy.jpg', '2025-12-25 17:46:36', '2025-12-25 17:46:36'),
(15, 20, 15, 'loremIpsum.jpg', 'package_items/uMExD1xHxI07Bv1.jpg', '2025-12-26 13:53:51', '2025-12-26 13:53:51'),
(16, 21, 16, 'loremIpsum.jpg', 'package_items/jozktSDrQAqOJJy.jpg', '2026-01-03 10:08:30', '2026-01-03 10:08:30'),
(17, 23, 17, 'loremIpsum.jpg', 'package_items/s36yUrZzJniLH1e.jpg', '2026-01-03 20:20:20', '2026-01-03 20:20:20');

-- --------------------------------------------------------

--
-- Table structure for table `package_invoices`
--

CREATE TABLE `package_invoices` (
  `id` bigint UNSIGNED NOT NULL,
  `package_id` bigint UNSIGNED DEFAULT NULL,
  `type` enum('received','customer_submitted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'received',
  `invoice_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vendor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `invoice_amount` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `package_invoices`
--

INSERT INTO `package_invoices` (`id`, `package_id`, `type`, `invoice_number`, `vendor_name`, `invoice_date`, `invoice_amount`, `image`, `notes`, `created_at`, `updated_at`) VALUES
(1, 4, 'received', NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-25 15:33:43', '2025-12-25 15:33:43'),
(3, 20, 'received', '3423', 'Amazon', '2025-12-26', 10.00, 'invoices/0LrPb6snKkvDB4w.pdf', NULL, '2025-12-26 13:53:51', '2025-12-26 13:53:51'),
(4, 2, 'received', NULL, NULL, NULL, NULL, 'uploads/package_invoice/nixWP1SkmQUJMPW.jpg', NULL, '2026-01-03 19:13:32', '2026-01-03 19:13:32');

-- --------------------------------------------------------

--
-- Table structure for table `package_invoice_files`
--

CREATE TABLE `package_invoice_files` (
  `id` bigint UNSIGNED NOT NULL,
  `package_invoice_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Original filename',
  `file` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Stored file path',
  `file_type` enum('image','pdf') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'image',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `package_invoice_files`
--

INSERT INTO `package_invoice_files` (`id`, `package_invoice_id`, `name`, `file`, `file_type`, `created_at`, `updated_at`) VALUES
(1, 3, 'Lorem_ipsum.pdf', 'invoices/0LrPb6snKkvDB4w.pdf', 'pdf', '2025-12-26 13:53:51', '2025-12-26 13:53:51'),
(2, 3, 'loremIpsum.jpg', 'invoices/cHfgVovOEuroM05.jpg', 'image', '2025-12-26 13:53:51', '2025-12-26 13:53:51');

-- --------------------------------------------------------

--
-- Table structure for table `package_items`
--

CREATE TABLE `package_items` (
  `id` bigint UNSIGNED NOT NULL,
  `package_id` bigint UNSIGNED DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `hs_code` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_of_origin` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `material` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manufacturer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_note` longtext COLLATE utf8mb4_unicode_ci,
  `quantity` double DEFAULT NULL,
  `value_per_unit` double DEFAULT NULL,
  `weight_per_unit` decimal(8,3) DEFAULT NULL,
  `weight_unit` enum('lb','kg','oz','g') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'lb',
  `total_line_value` double DEFAULT NULL,
  `total_line_weight` double DEFAULT NULL,
  `length` decimal(8,2) DEFAULT NULL,
  `width` decimal(8,2) DEFAULT NULL,
  `height` decimal(8,2) DEFAULT NULL,
  `dimension_unit` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'in',
  `is_dangerous` tinyint(1) NOT NULL DEFAULT '0',
  `is_fragile` tinyint(1) NOT NULL DEFAULT '0',
  `is_oversized` tinyint(1) NOT NULL DEFAULT '0',
  `classification_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `package_items`
--

INSERT INTO `package_items` (`id`, `package_id`, `title`, `description`, `hs_code`, `country_of_origin`, `material`, `manufacturer`, `item_note`, `quantity`, `value_per_unit`, `weight_per_unit`, `weight_unit`, `total_line_value`, `total_line_weight`, `length`, `width`, `height`, `dimension_unit`, `is_dangerous`, `is_fragile`, `is_oversized`, `classification_notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'mobile', 'phone', '234234', 'JP', 'electronics', NULL, 'this is a phone', 4, 2000, 0.000, 'lb', 8000, 0, NULL, NULL, NULL, 'in', 0, 0, 0, NULL, '2025-12-23 11:10:57', '2025-12-23 11:10:57'),
(2, 2, 'tt', 'telecome', '24234', 'GB', 'cotton', NULL, NULL, 10, 20, 2.000, 'lb', 200, 20, 10.00, 10.00, 10.00, 'in', 0, 0, 0, NULL, '2025-12-23 13:02:05', '2026-01-03 19:12:38'),
(3, 4, 'Perferendis commodi', 'Dolor perspiciatis', '2343425', NULL, 'Velit velit quasi v', NULL, 'Perspiciatis aut se', 20, 50, 1.000, 'lb', 1000, 20, 10.00, 10.00, 10.00, 'in', 1, 0, 0, 'test 1', '2025-12-24 07:59:26', '2025-12-26 12:21:31'),
(12, 15, 'Tempore ex autem vo', 'Ut vel pariatur Ut', '234234', NULL, 'Ratione aut magna ni', NULL, NULL, 10, 76, 2.000, 'lb', 760, 20, 10.00, 10.00, 10.00, 'in', 1, 1, 1, 'test classification', '2025-12-25 15:47:48', '2026-01-03 18:53:35'),
(15, 20, 'Eu ea laborum Sit', 'Adipisci sint sed et', '24234', NULL, 'In architecto omnis', NULL, NULL, 25, 53, 1.000, 'lb', 1325, 25, 99.00, 49.00, 48.00, 'in', 1, 0, 0, 'classic', '2025-12-26 13:53:51', '2025-12-28 04:22:36'),
(16, 21, 'Nike', 'adf', '1234123', NULL, 'Leather', NULL, NULL, 1, 20, 2.000, 'lb', 20, 2, 10.00, 10.00, 10.00, 'in', 0, 0, 0, NULL, '2026-01-03 10:08:30', '2026-01-03 10:08:30'),
(17, 23, 'Iphone pro max', NULL, '851713000000', NULL, 'aluminium', NULL, NULL, 1, 1700, 0.400, 'lb', 1700, 0.4, 7.00, 4.00, 2.00, 'in', 0, 0, 0, NULL, '2026-01-03 20:20:20', '2026-01-03 20:20:20');

-- --------------------------------------------------------

--
-- Table structure for table `packing_options`
--

CREATE TABLE `packing_options` (
  `id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` double NOT NULL DEFAULT '0',
  `value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_text_input` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'dashboard.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(2, 'dashboard.stats.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(3, 'dashboard.reports.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(4, 'dashboard.reports.export', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(5, 'packages.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(6, 'packages.create', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(7, 'packages.update', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(8, 'packages.delete', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(9, 'packages.kanban.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(10, 'packages.kanban.update', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(11, 'packages.items.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(12, 'packages.items.create', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(13, 'packages.items.update', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(14, 'packages.items.delete', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(15, 'packages.files.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(16, 'packages.files.upload', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(17, 'packages.files.delete', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(18, 'packages.notes.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(19, 'packages.notes.create', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(20, 'packages.notes.update', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(21, 'shipments.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(22, 'shipments.create', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(23, 'shipments.update', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(24, 'shipments.delete', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(25, 'shipments.status.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(26, 'shipments.status.update', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(27, 'shipments.outbound.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(28, 'shipments.outbound.process', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(29, 'shipments.labels.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(30, 'shipments.labels.generate', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(31, 'shipments.labels.download', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(32, 'shipments.tracking.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(33, 'shipments.tracking.refresh', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(34, 'transactions.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(35, 'transactions.refunds.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(36, 'transactions.refunds.process', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(37, 'coupons.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(38, 'coupons.create', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(39, 'coupons.update', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(40, 'coupons.delete', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(41, 'coupons.stats.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(42, 'coupons.toggle.update', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(43, 'loyalty.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(44, 'loyalty.rules.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(45, 'loyalty.rules.create', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(46, 'loyalty.rules.update', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(47, 'loyalty.rules.delete', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(48, 'loyalty.transactions.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(49, 'loyalty.users.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(50, 'loyalty.users.adjust', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(51, 'order-tracking.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(52, 'order-tracking.events.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(53, 'order-tracking.events.create', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(54, 'order-tracking.refresh.execute', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(55, 'change-requests.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(56, 'change-requests.approve.execute', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(57, 'change-requests.reject.execute', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(58, 'change-requests.bulk.execute', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(59, 'customers.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(60, 'customers.create', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(61, 'customers.update', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(62, 'customers.delete', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(63, 'customers.packages.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(64, 'customers.transactions.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(65, 'customers.addresses.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(66, 'customers.addresses.update', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(67, 'customers.loyalty.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(68, 'customers.loyalty.adjust', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(69, 'system-users.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(70, 'system-users.create', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(71, 'system-users.update', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(72, 'system-users.delete', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(73, 'system-users.status.toggle', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(74, 'system-users.roles.assign', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(75, 'roles.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(76, 'roles.create', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(77, 'roles.update', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(78, 'roles.delete', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(79, 'roles.permissions.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(80, 'roles.permissions.assign', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(81, 'settings.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(82, 'settings.update', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(83, 'settings.shipping-pricing.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(84, 'settings.shipping-pricing.update', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(85, 'settings.general.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(86, 'settings.general.update', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(87, 'imports.view', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(88, 'imports.execute', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(89, 'warehouses.view', 'web', '2025-12-23 14:49:36', '2025-12-23 14:49:36'),
(90, 'warehouses.create', 'web', '2025-12-23 14:49:36', '2025-12-23 14:49:36'),
(91, 'warehouses.update', 'web', '2025-12-23 14:49:36', '2025-12-23 14:49:36'),
(92, 'warehouses.default.set', 'web', '2025-12-23 14:49:36', '2025-12-23 14:49:36'),
(93, 'warehouses.status.toggle', 'web', '2025-12-23 14:49:36', '2025-12-23 14:49:36'),
(94, 'packages.rates.manage', 'web', '2025-12-26 08:23:43', '2025-12-26 08:23:43'),
(95, 'order-tracking.carrier.retry', 'web', '2025-12-26 08:23:43', '2025-12-26 08:23:43'),
(96, 'order-tracking.carrier.manual', 'web', '2025-12-26 08:23:43', '2025-12-26 08:23:43'),
(97, 'order-tracking.carrier.sync', 'web', '2025-12-26 08:23:43', '2025-12-26 08:23:43');

-- --------------------------------------------------------

--
-- Table structure for table `preferred_ship_methods`
--

CREATE TABLE `preferred_ship_methods` (
  `id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` double NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proforma_invoice_options`
--

CREATE TABLE `proforma_invoice_options` (
  `id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `is_text_input` tinyint(1) NOT NULL DEFAULT '0',
  `value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rate_markup_rules`
--

CREATE TABLE `rate_markup_rules` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` enum('percentage','fixed') COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `carrier` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'null = all carriers',
  `service_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'null = all services',
  `min_weight` decimal(8,2) DEFAULT NULL COMMENT 'Apply above weight (lbs)',
  `max_weight` decimal(8,2) DEFAULT NULL COMMENT 'Apply below weight (lbs)',
  `destination_country` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'null = all countries',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `priority` int NOT NULL DEFAULT '0' COMMENT 'Higher = applied first',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rate_markup_rules`
--

INSERT INTO `rate_markup_rules` (`id`, `name`, `description`, `type`, `value`, `carrier`, `service_code`, `min_weight`, `max_weight`, `destination_country`, `is_active`, `priority`, `created_at`, `updated_at`) VALUES
(1, 'company comission', NULL, 'percentage', 30.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-01-03 10:51:03', '2026-01-03 10:51:17');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'super-admin', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(2, 'operator', 'web', '2025-12-23 03:32:07', '2025-12-23 03:32:07'),
(3, 'warehouse', 'web', '2025-12-23 03:32:08', '2025-12-23 03:32:08'),
(4, 'support', 'web', '2025-12-23 03:32:08', '2025-12-23 03:32:08'),
(5, 'sales', 'web', '2025-12-23 03:32:08', '2025-12-23 03:32:08'),
(6, 'test-view-role', 'web', '2025-12-23 08:33:26', '2025-12-23 08:33:26'),
(7, 'delivery-operator', 'web', '2025-12-28 02:53:12', '2025-12-28 02:53:12');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint UNSIGNED NOT NULL,
  `role_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 1),
(2, 1),
(3, 1),
(4, 1),
(5, 1),
(6, 1),
(7, 1),
(8, 1),
(9, 1),
(10, 1),
(11, 1),
(12, 1),
(13, 1),
(14, 1),
(15, 1),
(16, 1),
(17, 1),
(18, 1),
(19, 1),
(20, 1),
(21, 1),
(22, 1),
(23, 1),
(24, 1),
(25, 1),
(26, 1),
(27, 1),
(28, 1),
(29, 1),
(30, 1),
(31, 1),
(32, 1),
(33, 1),
(34, 1),
(35, 1),
(36, 1),
(37, 1),
(38, 1),
(39, 1),
(40, 1),
(41, 1),
(42, 1),
(43, 1),
(44, 1),
(45, 1),
(46, 1),
(47, 1),
(48, 1),
(49, 1),
(50, 1),
(51, 1),
(52, 1),
(53, 1),
(54, 1),
(55, 1),
(56, 1),
(57, 1),
(58, 1),
(59, 1),
(60, 1),
(61, 1),
(62, 1),
(63, 1),
(64, 1),
(65, 1),
(66, 1),
(67, 1),
(68, 1),
(69, 1),
(70, 1),
(71, 1),
(72, 1),
(73, 1),
(74, 1),
(75, 1),
(76, 1),
(77, 1),
(78, 1),
(79, 1),
(80, 1),
(81, 1),
(82, 1),
(83, 1),
(84, 1),
(85, 1),
(86, 1),
(87, 1),
(88, 1),
(89, 1),
(90, 1),
(91, 1),
(92, 1),
(93, 1),
(94, 1),
(95, 1),
(96, 1),
(97, 1),
(1, 2),
(2, 2),
(3, 2),
(4, 2),
(5, 2),
(6, 2),
(7, 2),
(8, 2),
(9, 2),
(10, 2),
(11, 2),
(12, 2),
(13, 2),
(14, 2),
(15, 2),
(16, 2),
(17, 2),
(18, 2),
(19, 2),
(20, 2),
(21, 2),
(22, 2),
(23, 2),
(24, 2),
(25, 2),
(26, 2),
(27, 2),
(28, 2),
(29, 2),
(30, 2),
(31, 2),
(32, 2),
(33, 2),
(51, 2),
(52, 2),
(53, 2),
(54, 2),
(55, 2),
(56, 2),
(57, 2),
(58, 2),
(59, 2),
(63, 2),
(64, 2),
(94, 2),
(95, 2),
(96, 2),
(97, 2),
(1, 3),
(5, 3),
(7, 3),
(9, 3),
(10, 3),
(11, 3),
(12, 3),
(13, 3),
(14, 3),
(15, 3),
(16, 3),
(17, 3),
(21, 3),
(25, 3),
(26, 3),
(29, 3),
(30, 3),
(31, 3),
(1, 4),
(5, 4),
(18, 4),
(19, 4),
(20, 4),
(21, 4),
(32, 4),
(33, 4),
(34, 4),
(51, 4),
(52, 4),
(53, 4),
(54, 4),
(55, 4),
(59, 4),
(63, 4),
(64, 4),
(65, 4),
(95, 4),
(96, 4),
(97, 4),
(1, 5),
(2, 5),
(37, 5),
(38, 5),
(39, 5),
(40, 5),
(41, 5),
(42, 5),
(43, 5),
(44, 5),
(45, 5),
(46, 5),
(47, 5),
(48, 5),
(49, 5),
(50, 5),
(59, 5),
(60, 5),
(1, 6),
(2, 6),
(3, 6),
(37, 6),
(41, 6),
(55, 6),
(59, 6),
(63, 6),
(64, 6),
(65, 6),
(67, 6),
(87, 6),
(1, 7),
(2, 7),
(3, 7),
(5, 7),
(9, 7),
(11, 7),
(15, 7),
(18, 7),
(21, 7),
(25, 7),
(27, 7),
(29, 7),
(32, 7),
(34, 7),
(35, 7),
(37, 7),
(41, 7),
(43, 7),
(44, 7),
(48, 7),
(49, 7),
(51, 7),
(52, 7),
(55, 7),
(59, 7),
(63, 7),
(64, 7),
(65, 7),
(67, 7),
(69, 7),
(75, 7),
(79, 7),
(81, 7),
(83, 7),
(85, 7),
(87, 7),
(89, 7);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('9xVRlGa2J5IXhNDMrHZ602cg5ajhbUGbQZXuZbsJ', 1, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiWVN1RHJSZGpiWGdiY3JTdXlneUFXV3ZoOE41Q00xRGZGdFM0RTdxSCI7czozOiJ1cmwiO2E6MDp7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC91c2VyLW1hbmFnZW1lbnQ/dGFiPXJvbGVzIjtzOjU6InJvdXRlIjtzOjI3OiJhZG1pbi51c2VyLW1hbmFnZW1lbnQuaW5kZXgiO319', 1767533278),
('MoEMI1UxohdUAA6g04pquVm19YsKssewjERNrjYA', NULL, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiOW5ldnhweDJmNWdiTUFicE5oTmJHZ2FWdUdXd1c5U3ZnbXBPT2hZRSI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czo1MjoiaHR0cDovL2xvY2FsaG9zdDo4MDAwL2N1c3RvbWVyL3NoaXBtZW50L215L3NoaXBtZW50cyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjI3OiJodHRwOi8vbG9jYWxob3N0OjgwMDAvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO319', 1767526685),
('XaZNRhv0agu5RORC74JEGxODeLSYDdl04hRv4hfA', NULL, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoidmFVeWtjYkxXTkt2a2xVR3V4Yk5IbHhEZzBnZXJkM2tDYzFBS3VqdCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1767528605);

-- --------------------------------------------------------

--
-- Table structure for table `shipment_events`
--

CREATE TABLE `shipment_events` (
  `id` bigint UNSIGNED NOT NULL,
  `ship_id` bigint UNSIGNED NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system',
  `event_time` timestamp NULL DEFAULT NULL,
  `raw_data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shipment_events`
--

INSERT INTO `shipment_events` (`id`, `ship_id`, `status`, `description`, `location`, `source`, `event_time`, `raw_data`, `created_at`, `updated_at`) VALUES
(1, 24, 'label_created', NULL, NULL, 'system', '2026-01-03 20:58:01', NULL, '2026-01-03 20:58:01', '2026-01-03 20:58:01');

-- --------------------------------------------------------

--
-- Table structure for table `shipping_preferences`
--

CREATE TABLE `shipping_preferences` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `user_address_id` bigint UNSIGNED DEFAULT NULL,
  `preferred_ship_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `international_shipping_option` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping_preference_option` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `packing_option` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `proforma_invoice_options` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `login_option` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `additional_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `maximum_weight_per_box` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipping_preference_options`
--

CREATE TABLE `shipping_preference_options` (
  `id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `price` double NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipping_pricing`
--

CREATE TABLE `shipping_pricing` (
  `id` bigint UNSIGNED NOT NULL,
  `service` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `range_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `range_to` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ships`
--

CREATE TABLE `ships` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `tracking_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `carrier_tracking_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `carrier_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `carrier_service_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `label_url` text COLLATE utf8mb4_unicode_ci,
  `label_data` longtext COLLATE utf8mb4_unicode_ci,
  `carrier_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `customs_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customs_cleared_at` timestamp NULL DEFAULT NULL,
  `shipment_type` enum('standard','consolidated') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'standard',
  `rate_source` enum('live_api','cached','fallback','manual') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'live_api',
  `submitted_to_carrier_at` timestamp NULL DEFAULT NULL,
  `carrier_response` json DEFAULT NULL,
  `carrier_errors` json DEFAULT NULL,
  `total_weight` double NOT NULL DEFAULT '0',
  `total_price` double NOT NULL DEFAULT '0',
  `total_ship_payment` double NOT NULL DEFAULT '0',
  `customer_address_id` bigint UNSIGNED DEFAULT NULL,
  `international_shipping_option_id` json DEFAULT NULL,
  `carrier_service_id` bigint UNSIGNED DEFAULT NULL,
  `selected_addon_ids` json DEFAULT NULL,
  `addon_charges` decimal(10,2) NOT NULL DEFAULT '0.00',
  `declared_value` decimal(10,2) DEFAULT NULL,
  `declared_value_currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `packing_option_id` json DEFAULT NULL,
  `shipping_preference_option_id` json DEFAULT NULL,
  `national_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `handling_fee` double NOT NULL DEFAULT '10',
  `subtotal` double NOT NULL DEFAULT '0',
  `package_level_charges` double NOT NULL DEFAULT '0',
  `estimated_shipping_charges` double NOT NULL DEFAULT '0',
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `invoice_status` enum('pending','paid','unpaid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ships`
--

INSERT INTO `ships` (`id`, `user_id`, `customer_id`, `tracking_number`, `carrier_tracking_number`, `carrier_name`, `carrier_service_type`, `label_url`, `label_data`, `carrier_status`, `customs_status`, `customs_cleared_at`, `shipment_type`, `rate_source`, `submitted_to_carrier_at`, `carrier_response`, `carrier_errors`, `total_weight`, `total_price`, `total_ship_payment`, `customer_address_id`, `international_shipping_option_id`, `carrier_service_id`, `selected_addon_ids`, `addon_charges`, `declared_value`, `declared_value_currency`, `packing_option_id`, `shipping_preference_option_id`, `national_id`, `handling_fee`, `subtotal`, `package_level_charges`, `estimated_shipping_charges`, `status`, `invoice_status`, `created_at`, `updated_at`) VALUES
(3, NULL, 2, '63676295', NULL, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, 'standard', 'live_api', NULL, NULL, NULL, 2, 200, 0, NULL, NULL, NULL, NULL, 0.00, NULL, 'USD', NULL, NULL, NULL, 10, 0, 0, 0, 'paid', 'pending', '2025-12-23 13:20:29', '2026-01-03 19:18:28'),
(5, NULL, 9, '3161298', NULL, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, 'standard', 'live_api', NULL, NULL, NULL, 2, 200, 0, NULL, NULL, NULL, NULL, 0.00, NULL, 'USD', NULL, NULL, NULL, 10, 0, 0, 0, 'paid', 'pending', '2025-12-23 17:49:23', '2026-01-03 19:18:24'),
(8, NULL, 10, '66675694', NULL, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, 'standard', 'live_api', NULL, NULL, NULL, 2, 200, 0, NULL, NULL, NULL, NULL, 0.00, NULL, 'USD', NULL, NULL, NULL, 10, 0, 0, 0, 'paid', 'pending', '2025-12-24 06:02:07', '2026-01-03 19:18:21'),
(20, NULL, 11, '17011330', NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, 'standard', 'fallback', NULL, NULL, '{\"details\": [], \"message\": \"HTTP request failed: HTTP request returned status code 400:\\n{\\\"transactionId\\\":\\\"f8aec9e0-092b-4a92-8727-e0d797522d97\\\",\\\"errors\\\":[{\\\"code\\\":\\\"PACKAGINGTYPE.INVALID\\\",\\\"message\\\":\\\"We are not  (truncated...)\\n\", \"can_retry\": false, \"failed_at\": \"2026-01-03T23:15:42+00:00\", \"error_type\": \"api_rejection\"}', 2, 20, 0, 3, '0', NULL, NULL, 0.00, 20.00, 'USD', '[]', '[]', NULL, 10, 0, 0, 342.13, 'paid', 'paid', '2026-01-03 14:03:58', '2026-01-03 19:18:17'),
(21, NULL, 11, '54852464', NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, 'standard', 'fallback', NULL, NULL, '{\"details\": [], \"message\": \"HTTP request failed: HTTP request returned status code 400:\\n{\\\"transactionId\\\":\\\"908e7f5a-4f81-4874-9633-9ccb0d16d221\\\",\\\"errors\\\":[{\\\"code\\\":\\\"COUNTRY.POSTALCODEORZIP.INVALID\\\",\\\"message\\\":\\\"W (truncated...)\\n\", \"can_retry\": false, \"failed_at\": \"2026-01-04T13:21:11+00:00\", \"error_type\": \"api_rejection\"}', 25, 1325, 0, 3, '0', NULL, '[1, 3]', 24.50, 1325.00, 'USD', '[]', '[]', NULL, 10, 0, 0, 7679.03, 'failed', 'paid', '2026-01-03 18:31:28', '2026-01-04 08:21:11'),
(22, NULL, 11, '35757104', NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, 'standard', 'fallback', NULL, NULL, '{\"details\": [], \"message\": \"HTTP request failed: HTTP request returned status code 400:\\n{\\\"transactionId\\\":\\\"afb0a019-3739-4c1e-a9ce-81b6f552964c\\\",\\\"errors\\\":[{\\\"code\\\":\\\"COUNTRY.POSTALCODEORZIP.INVALID\\\",\\\"message\\\":\\\"W (truncated...)\\n\", \"can_retry\": false, \"failed_at\": \"2026-01-04T13:19:42+00:00\", \"error_type\": \"api_rejection\"}', 20, 760, 0, 3, '0', NULL, '[3]', 6.50, 760.00, 'USD', '[]', '[]', '345234524', 10, 0, 0, 435.01, 'failed', 'paid', '2026-01-03 18:54:38', '2026-01-04 08:19:42'),
(23, NULL, 11, '48413986', NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, 'standard', 'fallback', NULL, NULL, '{\"details\": [], \"message\": \"HTTP request failed: HTTP request returned status code 400:\\n{\\\"transactionId\\\":\\\"6c08cd5d-0747-4f6c-b076-1855eb670df9\\\",\\\"errors\\\":[{\\\"code\\\":\\\"COUNTRY.POSTALCODEORZIP.INVALID\\\",\\\"message\\\":\\\"W (truncated...)\\n\", \"can_retry\": false, \"failed_at\": \"2026-01-04T13:00:01+00:00\", \"error_type\": \"api_rejection\"}', 20, 1000, 0, 3, '0', NULL, NULL, 0.00, 1000.00, 'USD', '[]', '[]', NULL, 10, 0, 0, 428.51, 'failed', 'paid', '2026-01-03 19:14:19', '2026-01-04 08:00:01'),
(24, NULL, 11, '87860166', NULL, NULL, NULL, NULL, NULL, 'failed', NULL, NULL, 'standard', 'cached', NULL, NULL, '{\"details\": [], \"message\": \"HTTP request failed: HTTP request returned status code 400:\\n{\\\"transactionId\\\":\\\"217ebd25-dc04-4c3d-bf97-aba76b2a86bc\\\",\\\"errors\\\":[{\\\"code\\\":\\\"SHIPMENT.COMMODITY.PROHIBITED\\\",\\\"message\\\":\\\"We  (truncated...)\\n\", \"can_retry\": false, \"failed_at\": \"2026-01-04T01:50:19+00:00\", \"error_type\": \"api_rejection\"}', 20, 200, 0, 3, '0', 1, '[1]', 18.00, 200.00, 'USD', '[]', '[]', NULL, 10, 0, 0, 446.51, 'picked_up', 'paid', '2026-01-03 20:04:39', '2026-01-04 07:53:05'),
(25, NULL, 11, '73352999', '794973665316', 'fedex', 'FEDEX_INTERNATIONAL_PRIORITY', NULL, NULL, 'submitted', NULL, NULL, 'standard', 'live_api', '2026-01-03 20:29:41', '{\"output\": {\"transactionShipments\": [{\"alerts\": [{\"code\": \"REQUESTEDSHIPMENT.SHIPDATESTAMP.NOTALLOWED\", \"message\": \"We are not able to retrieve the message for the warning or error.\", \"alertType\": \"NOTE\", \"parameterList\": []}], \"serviceName\": \"FedEx International Priority®\", \"serviceType\": \"FEDEX_INTERNATIONAL_PRIORITY\", \"shipDatestamp\": \"2026-01-05\", \"pieceResponses\": [{\"currency\": \"USD\", \"netRateAmount\": 175.63, \"baseRateAmount\": 141.12, \"trackingNumber\": \"794973665316\", \"netChargeAmount\": 0, \"packageDocuments\": [{\"url\": \"https://wwwtest.fedex.com/document/v1/cache/retrieve/SH,cbec2ee865edbe2e794973665316_SHIPPING_P?isLabel=true&autoPrint=false\", \"docType\": \"PDF\", \"contentType\": \"LABEL\", \"copiesToPrint\": 1}], \"netDiscountAmount\": 0, \"customerReferences\": [{\"value\": \"73352999\", \"customerReferenceType\": \"CUSTOMER_REFERENCE\"}], \"codcollectionAmount\": 0, \"masterTrackingNumber\": \"794973665316\", \"additionalChargesDiscount\": 0}], \"serviceCategory\": \"EXPRESS\", \"masterTrackingNumber\": \"794973665316\", \"completedShipmentDetail\": {\"usDomestic\": false, \"carrierCode\": \"FDXE\", \"shipmentRating\": {\"actualRateType\": \"PAYOR_ACCOUNT_SHIPMENT\", \"shipmentRateDetails\": [{\"taxes\": [], \"currency\": \"USD\", \"rateType\": \"PAYOR_ACCOUNT_SHIPMENT\", \"rateZone\": \"I\", \"rateScale\": \"US001OFI_2P_YOUR_PACKAGING\", \"dimDivisor\": 139, \"surcharges\": [{\"level\": \"SHIPMENT\", \"amount\": 1, \"description\": \"Demand Surcharge\", \"surchargeType\": \"DEMAND\"}, {\"level\": \"SHIPMENT\", \"amount\": 26.4, \"description\": \"Insured value\", \"surchargeType\": \"INSURED_VALUE\"}, {\"level\": \"SHIPMENT\", \"amount\": 7.11, \"description\": \"Fuel\", \"surchargeType\": \"FUEL\"}], \"totalTaxes\": 0, \"pricingCode\": \"\", \"totalRebates\": 0, \"totalNetCharge\": 175.63, \"totalBaseCharge\": 141.12, \"totalNetFreight\": 141.12, \"totalSurcharges\": 34.51, \"freightDiscounts\": [], \"ratedWeightMethod\": \"ACTUAL\", \"totalBillingWeight\": {\"units\": \"LB\", \"value\": 1}, \"totalDutiesAndTaxes\": 0, \"totalNetFedExCharge\": 175.63, \"currencyExchangeRate\": {\"rate\": 1, \"fromCurrency\": \"USD\", \"intoCurrency\": \"USD\"}, \"fuelSurchargePercent\": 5, \"totalFreightDiscounts\": 0, \"totalDutiesTaxesAndFees\": 0, \"totalAncillaryFeesAndTaxes\": 0, \"totalNetChargeWithDutiesAndTaxes\": 175.63}]}, \"masterTrackingId\": {\"formId\": \"0430\", \"trackingIdType\": \"FEDEX\", \"trackingNumber\": \"794973665316\"}, \"operationalDetail\": {\"scac\": \"\", \"airportId\": \"SJU\", \"commitDay\": \"\", \"commitDate\": \"\", \"postalCode\": \"472\", \"countryCode\": \"VG\", \"deliveryDay\": \"\", \"serviceCode\": \"2P\", \"deliveryDate\": \"\", \"packagingCode\": \"01\", \"ursaPrefixCode\": \"5R\", \"ursaSuffixCode\": \"EISA \", \"astraDescription\": \"IP EOD\", \"originLocationId\": \"CTYA \", \"originServiceArea\": \"A1\", \"stateOrProvinceCode\": \"  \", \"originLocationNumber\": 0, \"destinationLocationId\": \"EISA \", \"publishedDeliveryTime\": \"\", \"destinationServiceArea\": \"PM\", \"astraPlannedServiceLevel\": \"PM\", \"destinationLocationNumber\": 0, \"ineligibleForMoneyBackGuarantee\": false, \"destinationLocationStateOrProvinceCode\": \"  \"}, \"serviceDescription\": {\"code\": \"2P\", \"names\": [{\"type\": \"long\", \"value\": \"FedEx International Priority®\", \"encoding\": \"utf-8\"}, {\"type\": \"long\", \"value\": \"FedEx International Priority\", \"encoding\": \"ascii\"}, {\"type\": \"medium\", \"value\": \"FedEx International Priority\", \"encoding\": \"utf-8\"}, {\"type\": \"medium\", \"value\": \"FedEx International Priority\", \"encoding\": \"ascii\"}, {\"type\": \"short\", \"value\": \"IPED\", \"encoding\": \"utf-8\"}, {\"type\": \"short\", \"value\": \"IPED\", \"encoding\": \"ascii\"}, {\"type\": \"abbrv\", \"value\": \"OA\", \"encoding\": \"ascii\"}], \"serviceId\": \"EP1000000300\", \"description\": \"International Priority EOD (IP EOD)\", \"serviceType\": \"FEDEX_INTERNATIONAL_PRIORITY\", \"serviceCategory\": \"parcel\", \"astraDescription\": \"IP EOD\", \"operatingOrgCodes\": [\"FXE\"]}, \"documentRequirements\": {\"generationDetails\": [{\"type\": \"PRO_FORMA_INVOICE\", \"letterhead\": \"OPTIONAL\", \"electronicSignature\": \"OPTIONAL\", \"minimumCopiesRequired\": 3}, {\"type\": \"COMMERCIAL_INVOICE\", \"letterhead\": \"OPTIONAL\", \"electronicSignature\": \"OPTIONAL\", \"minimumCopiesRequired\": 3}, {\"type\": \"AIR_WAYBILL\", \"minimumCopiesRequired\": 3}], \"requiredDocuments\": [\"COMMERCIAL_OR_PRO_FORMA_INVOICE\", \"AIR_WAYBILL\"], \"prohibitedDocuments\": [\"USMCA_COMMERCIAL_INVOICE_CERTIFICATION_OF_ORIGIN\", \"USMCA_CERTIFICATION_OF_ORIGIN\"]}, \"packagingDescription\": \"Customer Packaging\", \"completedPackageDetails\": [{\"groupNumber\": 0, \"trackingIds\": [{\"formId\": \"0430\", \"trackingIdType\": \"FEDEX\", \"trackingNumber\": \"794973665316\"}], \"sequenceNumber\": 1, \"signatureOption\": \"SERVICE_DEFAULT\", \"operationalDetail\": {\"barcodes\": {\"binaryBarcodes\": [{\"type\": \"COMMON_2D\", \"value\": \"Wyk+HjAxHTAyVkcxMTEwHTA5Mh0yUB03OTQ5NzM2NjUzMTYwNDMwHUZERR03NDA1NjEwNzMdMDA1HR0xLzEdMS4wMExCHU4dTmFubnkgQ2F5HVJvYWQgVG93bh0gIB1YeWxhIEdyZWVuZR4wNh0xMFpFSU8wNx0xMlo5Mjg2ODc2ODY4HTE1WjExNDA2NDg2MB0zMVoxMjIxNTcyODQwNzcwMDExMDQ3MjAwNzk0OTczNjY1MzE2HTMyWjAyHTM5WkNUWUEdOTlaRUkwMDA3HFVTHDE3MDAcVVNEHElwaG9uZSBwcm8gbWF4HE5PIEVFSSAzMC4zNyhhKRwxNzAwHDc0MDU2MTA3Mx0eMDkdRkRYHXodOB03PAUoMhR/QB4E\"}], \"stringBarcodes\": [{\"type\": \"FEDEX_1D\", \"value\": \"1221572840770011047200794973665316\"}]}, \"astraHandlingText\": \"\", \"operationalInstructions\": [{\"number\": 2, \"content\": \"TRK#\"}, {\"number\": 3, \"content\": \"0430\"}, {\"number\": 5, \"content\": \"5R EISA \"}, {\"number\": 7, \"content\": \"1221572840770011047200794973665316\"}, {\"number\": 8, \"content\": \"58HJ2/7C58/59F2\"}, {\"number\": 10, \"content\": \"7949 7366 5316\"}, {\"number\": 12, \"content\": \"PM\"}, {\"number\": 13, \"content\": \"IP EOD\"}, {\"number\": 15, \"content\": \"472\"}, {\"number\": 16, \"content\": \"  -VG\"}, {\"number\": 17, \"content\": \"SJU\"}]}}], \"exportComplianceStatement\": \"NO EEI 30.37(a)\"}, \"shipmentAdvisoryDetails\": []}]}, \"transactionId\": \"89c8356a-372c-4652-aa27-89fde8ff09b2\"}', NULL, 0.4, 1700, 0, 3, '0', 1, '[1]', 18.00, 1700.00, 'USD', '[]', '[]', NULL, 10, 0, 0, 281.18, 'submitted', 'paid', '2026-01-03 20:21:48', '2026-01-03 20:29:41');

-- --------------------------------------------------------

--
-- Table structure for table `ship_packages`
--

CREATE TABLE `ship_packages` (
  `id` bigint UNSIGNED NOT NULL,
  `ship_id` bigint UNSIGNED NOT NULL,
  `package_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ship_packages`
--

INSERT INTO `ship_packages` (`id`, `ship_id`, `package_id`, `created_at`, `updated_at`) VALUES
(3, 3, 2, NULL, NULL),
(5, 5, 2, NULL, NULL),
(8, 8, 2, NULL, NULL),
(23, 20, 21, NULL, NULL),
(24, 21, 20, NULL, NULL),
(25, 22, 15, NULL, NULL),
(26, 23, 4, NULL, NULL),
(27, 24, 2, NULL, NULL),
(28, 25, 23, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `special_requests`
--

CREATE TABLE `special_requests` (
  `id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `price` double NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `status` tinyint NOT NULL COMMENT '1: Succeeded | 2: Failed | 3: Refunded',
  `card` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `customer_id`, `status`, `card`, `amount`, `description`, `transaction_id`, `transaction_date`, `created_at`, `updated_at`) VALUES
(1, NULL, 11, 1, '4242', '342.13', 'Payment by Xyla Greene to create shipment.', 'ch_3Sle2jGi5AwSjR950F7h9CD1', '2026-01-03 23:15:33', '2026-01-03 18:15:33', '2026-01-03 18:15:33'),
(2, NULL, 11, 1, '4242', '7679.03', 'Payment by Xyla Greene to create shipment.', 'ch_3SleIrGi5AwSjR950hRtY1sX', '2026-01-03 23:32:13', '2026-01-03 18:32:13', '2026-01-03 18:32:13'),
(5, NULL, 11, 1, '4242', '435.01', 'Payment by Xyla Greene to create shipment.', 'ch_3SleiRGi5AwSjR951xDmbwWR', '2026-01-03 23:58:39', '2026-01-03 18:58:40', '2026-01-03 18:58:40'),
(6, NULL, 11, 1, '4242', '428.51', 'Payment by Xyla Greene to create shipment.', 'ch_3Sley2Gi5AwSjR950qGCIlWW', '2026-01-04 00:14:46', '2026-01-03 19:14:46', '2026-01-03 19:14:46'),
(7, NULL, 11, 1, '4242', '446.51', 'Payment by Xyla Greene to create shipment.', 'ch_3Slfl4Gi5AwSjR951VB8tLgU', '2026-01-04 01:05:26', '2026-01-03 20:05:26', '2026-01-03 20:05:26'),
(8, NULL, 11, 1, '4242', '281.18', 'Payment by Xyla Greene to create shipment.', 'ch_3Slg1PGi5AwSjR950DGR9Ytg', '2026-01-04 01:22:19', '2026-01-03 20:22:19', '2026-01-03 20:22:19');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stripe_id` longtext COLLATE utf8mb4_unicode_ci,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `referral_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referred_by` bigint UNSIGNED DEFAULT NULL,
  `user_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` bigint DEFAULT '2',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `suite` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` longtext COLLATE utf8mb4_unicode_ci,
  `zip_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `is_old` tinyint(1) NOT NULL DEFAULT '0',
  `loyalty_points` int NOT NULL DEFAULT '0',
  `lifetime_points_earned` int NOT NULL DEFAULT '0',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `avatar`, `stripe_id`, `first_name`, `last_name`, `email`, `referral_code`, `referred_by`, `user_name`, `phone`, `type`, `password`, `suite`, `country`, `date_of_birth`, `tax_id`, `address`, `zip_code`, `state`, `city`, `email_verified_at`, `is_active`, `is_old`, `loyalty_points`, `lifetime_points_earned`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, NULL, NULL, 'Super', 'Admin', 'admin@marketz.com', NULL, NULL, NULL, NULL, 1, '$2y$12$i8zAED14chiVgEFmoKqCGugAM8Ktzluu97P29SfaN/xRrSk2Ng0U6', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0, NULL, '2025-12-23 03:54:00', '2025-12-23 03:54:00'),
(2, NULL, NULL, 'Warehouse', 'Staff', 'warehouse@marketz.com', NULL, NULL, NULL, NULL, 3, '$2y$12$BWjTEhOyYdy5llSp0HP0LOS0HMvJXLBJ4l5kLoMC73XO87aXLr2iC', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0, NULL, '2025-12-23 03:54:01', '2025-12-23 03:54:01'),
(3, NULL, NULL, 'Operator', 'User', 'operator@marketz.com', NULL, NULL, NULL, NULL, 4, '$2y$12$XVCLy3T2aX3BbSqo3Dn4d.yeUAFZIq6Eyx995jDgruVwDs2xmobC2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0, NULL, '2025-12-23 03:54:01', '2025-12-23 04:20:38'),
(4, NULL, NULL, 'Support', 'Staff', 'support@marketz.com', NULL, NULL, NULL, NULL, 5, '$2y$12$nBh5Oy8rXMUJ3KYKb2l2HeSB2s7GXJ2g7rPox8KN4Ip7aLtZ8zLUe', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0, NULL, '2025-12-23 03:54:01', '2025-12-23 03:54:01'),
(5, NULL, NULL, 'Sales', 'Rep', 'sales@marketz.com', NULL, NULL, NULL, NULL, 6, '$2y$12$Z75RQ1PS2lIUmNGIrdaBNuuQXoq3DzkiAAVj8GHN5OnSfCPQl2NdK', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0, NULL, '2025-12-23 03:54:02', '2025-12-23 08:32:21'),
(6, NULL, NULL, 'test', 'test', 'test@gmail.com', NULL, NULL, NULL, NULL, 4, '$2y$12$ZKC5y25KGvs/2UOip5rBQ.JgLWvM.sIVSdfwB/4fm5nzVwkg5DE8W', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0, NULL, '2025-12-23 08:34:17', '2025-12-23 08:34:17'),
(7, NULL, NULL, 'test', 'user', 'test@abcd.com', NULL, NULL, NULL, NULL, 1, '$2y$12$OSeu9LRM9FYW9zZdVw/mkO/lluFxwcMQdUSKJx5gab/GE7L0SUFNy', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0, NULL, '2025-12-23 08:46:50', '2025-12-23 08:54:45'),
(8, NULL, NULL, 'jhon', 'Doe', 'test@test.com', NULL, NULL, NULL, NULL, 4, '$2y$12$RtPEDs3YsArJJHhWE5qNpuUiMQ3LOikVjv1vCJTh8TqKlY/zfxNuG', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0, NULL, '2025-12-28 02:54:30', '2025-12-28 02:54:30'),
(9, NULL, NULL, 'Hamid', 'Ayub', 'hamidgujjar33@gmail.com', NULL, NULL, NULL, NULL, 3, '$2y$12$jq31V7DnMy51SwJqNE29xuNnVv49OBHxYd2Ju4q/Gjf.bzjuMg.6u', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0, NULL, '2026-01-02 14:28:41', '2026-01-02 14:28:41');

-- --------------------------------------------------------

--
-- Table structure for table `user_cards`
--

CREATE TABLE `user_cards` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `card_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exp_month` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exp_year` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `brand` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last4` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `card_holder_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_cards`
--

INSERT INTO `user_cards` (`id`, `user_id`, `customer_id`, `card_id`, `exp_month`, `exp_year`, `brand`, `last4`, `card_holder_name`, `address_line1`, `address_line2`, `country`, `state`, `city`, `postal_code`, `country_code`, `phone_number`, `is_default`, `created_at`, `updated_at`) VALUES
(1, NULL, 11, 'card_1SlZ4WGi5AwSjR95joqpqrHp', '12', '2034', 'Visa', '4242', 'Xyla Greene', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-01-03 12:57:07', '2026-01-03 12:57:07');

-- --------------------------------------------------------

--
-- Table structure for table `warehouses`
--

CREATE TABLE `warehouses` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_code` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` longtext COLLATE utf8mb4_unicode_ci,
  `address_line_2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `warehouses`
--

INSERT INTO `warehouses` (`id`, `name`, `code`, `company_name`, `full_name`, `country`, `country_code`, `state`, `city`, `zip`, `address`, `address_line_2`, `phone_number`, `is_default`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Main Warehouse', 'MAIN-US', NULL, NULL, 'US', NULL, 'FL', 'Fort Lauderdale', NULL, NULL, NULL, NULL, 1, 1, '2025-12-23 14:46:37', '2025-12-23 14:46:37'),
(2, 'Miami Warehouse', 'MIA', 'Marketsz Miami', 'Jhon', 'United States', 'US', 'Florida', 'Miami', '52000', '2800', 'Unit2', '34352345234', 0, 1, '2025-12-23 14:53:05', '2025-12-23 14:53:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `carrier_addons`
--
ALTER TABLE `carrier_addons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `carrier_addons_addon_code_carrier_code_unique` (`addon_code`,`carrier_code`),
  ADD KEY `carrier_addons_carrier_code_index` (`carrier_code`),
  ADD KEY `carrier_addons_is_active_index` (`is_active`);

--
-- Indexes for table `carrier_services`
--
ALTER TABLE `carrier_services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `carrier_services_carrier_code_service_code_unique` (`carrier_code`,`service_code`),
  ADD KEY `carrier_services_carrier_code_index` (`carrier_code`),
  ADD KEY `carrier_services_is_active_index` (`is_active`),
  ADD KEY `carrier_services_is_international_index` (`is_international`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `coupons_code_unique` (`code`),
  ADD KEY `coupons_code_is_active_index` (`code`,`is_active`),
  ADD KEY `coupons_expiry_date_index` (`expiry_date`);

--
-- Indexes for table `coupon_usages`
--
ALTER TABLE `coupon_usages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coupon_usages_user_id_foreign` (`user_id`),
  ADD KEY `coupon_usages_coupon_id_user_id_index` (`coupon_id`,`user_id`),
  ADD KEY `coupon_usages_transaction_id_index` (`transaction_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customers_email_unique` (`email`),
  ADD UNIQUE KEY `customers_suite_unique` (`suite`),
  ADD UNIQUE KEY `customers_referral_code_unique` (`referral_code`),
  ADD KEY `customers_email_index` (`email`),
  ADD KEY `customers_is_active_index` (`is_active`),
  ADD KEY `customers_warehouse_id_foreign` (`warehouse_id`),
  ADD KEY `customers_referred_by_foreign` (`referred_by`),
  ADD KEY `customers_referred_by_id_foreign` (`referred_by_id`);

--
-- Indexes for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_addresses_user_id_foreign` (`user_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `international_shipping_options`
--
ALTER TABLE `international_shipping_options`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login_options`
--
ALTER TABLE `login_options`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `loyalty_rules`
--
ALTER TABLE `loyalty_rules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `loyalty_tiers`
--
ALTER TABLE `loyalty_tiers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `loyalty_tiers_slug_unique` (`slug`);

--
-- Indexes for table `loyalty_transactions`
--
ALTER TABLE `loyalty_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loyalty_transactions_user_id_type_index` (`user_id`,`type`),
  ADD KEY `loyalty_transactions_transaction_id_index` (`transaction_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `packages_warehouse_id_foreign` (`warehouse_id`);

--
-- Indexes for table `package_change_requests`
--
ALTER TABLE `package_change_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `package_change_requests_reviewed_by_foreign` (`reviewed_by`),
  ADD KEY `package_change_requests_package_id_status_index` (`package_id`,`status`),
  ADD KEY `package_change_requests_user_id_status_index` (`user_id`,`status`),
  ADD KEY `package_change_requests_status_index` (`status`);

--
-- Indexes for table `package_files`
--
ALTER TABLE `package_files`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `package_invoices`
--
ALTER TABLE `package_invoices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `package_invoice_files`
--
ALTER TABLE `package_invoice_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `package_invoice_files_package_invoice_id_index` (`package_invoice_id`);

--
-- Indexes for table `package_items`
--
ALTER TABLE `package_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `packing_options`
--
ALTER TABLE `packing_options`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `preferred_ship_methods`
--
ALTER TABLE `preferred_ship_methods`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `proforma_invoice_options`
--
ALTER TABLE `proforma_invoice_options`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rate_markup_rules`
--
ALTER TABLE `rate_markup_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rate_markup_rules_is_active_priority_index` (`is_active`,`priority`),
  ADD KEY `rate_markup_rules_carrier_service_code_index` (`carrier`,`service_code`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `shipment_events`
--
ALTER TABLE `shipment_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shipment_events_ship_id_event_time_index` (`ship_id`,`event_time`),
  ADD KEY `shipment_events_status_index` (`status`);

--
-- Indexes for table `shipping_preferences`
--
ALTER TABLE `shipping_preferences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shipping_preferences_user_id_foreign` (`user_id`),
  ADD KEY `shipping_preferences_user_address_id_foreign` (`user_address_id`);

--
-- Indexes for table `shipping_preference_options`
--
ALTER TABLE `shipping_preference_options`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shipping_pricing`
--
ALTER TABLE `shipping_pricing`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ships`
--
ALTER TABLE `ships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ships_user_id_foreign` (`user_id`),
  ADD KEY `ships_customer_address_id_foreign` (`customer_address_id`),
  ADD KEY `ships_carrier_service_id_index` (`carrier_service_id`),
  ADD KEY `ships_customs_status_index` (`customs_status`);

--
-- Indexes for table `ship_packages`
--
ALTER TABLE `ship_packages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ship_packages_ship_id_foreign` (`ship_id`),
  ADD KEY `ship_packages_package_id_foreign` (`package_id`);

--
-- Indexes for table `special_requests`
--
ALTER TABLE `special_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transactions_user_id_foreign` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_referral_code_unique` (`referral_code`),
  ADD KEY `users_referred_by_foreign` (`referred_by`);

--
-- Indexes for table `user_cards`
--
ALTER TABLE `user_cards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_cards_user_id_foreign` (`user_id`);

--
-- Indexes for table `warehouses`
--
ALTER TABLE `warehouses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `warehouses_code_unique` (`code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `carrier_addons`
--
ALTER TABLE `carrier_addons`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `carrier_services`
--
ALTER TABLE `carrier_services`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `coupon_usages`
--
ALTER TABLE `coupon_usages`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `international_shipping_options`
--
ALTER TABLE `international_shipping_options`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `login_options`
--
ALTER TABLE `login_options`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loyalty_rules`
--
ALTER TABLE `loyalty_rules`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loyalty_tiers`
--
ALTER TABLE `loyalty_tiers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `loyalty_transactions`
--
ALTER TABLE `loyalty_transactions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `package_change_requests`
--
ALTER TABLE `package_change_requests`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `package_files`
--
ALTER TABLE `package_files`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `package_invoices`
--
ALTER TABLE `package_invoices`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `package_invoice_files`
--
ALTER TABLE `package_invoice_files`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `package_items`
--
ALTER TABLE `package_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `packing_options`
--
ALTER TABLE `packing_options`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT for table `preferred_ship_methods`
--
ALTER TABLE `preferred_ship_methods`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `proforma_invoice_options`
--
ALTER TABLE `proforma_invoice_options`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rate_markup_rules`
--
ALTER TABLE `rate_markup_rules`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `shipment_events`
--
ALTER TABLE `shipment_events`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `shipping_preferences`
--
ALTER TABLE `shipping_preferences`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipping_preference_options`
--
ALTER TABLE `shipping_preference_options`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipping_pricing`
--
ALTER TABLE `shipping_pricing`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ships`
--
ALTER TABLE `ships`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `ship_packages`
--
ALTER TABLE `ship_packages`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `special_requests`
--
ALTER TABLE `special_requests`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user_cards`
--
ALTER TABLE `user_cards`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `warehouses`
--
ALTER TABLE `warehouses`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `coupon_usages`
--
ALTER TABLE `coupon_usages`
  ADD CONSTRAINT `coupon_usages_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `coupon_usages_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `coupon_usages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_referred_by_foreign` FOREIGN KEY (`referred_by`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `customers_referred_by_id_foreign` FOREIGN KEY (`referred_by_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `customers_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD CONSTRAINT `user_addresses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `loyalty_transactions`
--
ALTER TABLE `loyalty_transactions`
  ADD CONSTRAINT `loyalty_transactions_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `loyalty_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `packages`
--
ALTER TABLE `packages`
  ADD CONSTRAINT `packages_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `package_change_requests`
--
ALTER TABLE `package_change_requests`
  ADD CONSTRAINT `package_change_requests_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `package_change_requests_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `package_change_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `package_invoice_files`
--
ALTER TABLE `package_invoice_files`
  ADD CONSTRAINT `package_invoice_files_package_invoice_id_foreign` FOREIGN KEY (`package_invoice_id`) REFERENCES `package_invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipment_events`
--
ALTER TABLE `shipment_events`
  ADD CONSTRAINT `shipment_events_ship_id_foreign` FOREIGN KEY (`ship_id`) REFERENCES `ships` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipping_preferences`
--
ALTER TABLE `shipping_preferences`
  ADD CONSTRAINT `shipping_preferences_user_address_id_foreign` FOREIGN KEY (`user_address_id`) REFERENCES `customer_addresses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shipping_preferences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ships`
--
ALTER TABLE `ships`
  ADD CONSTRAINT `ships_carrier_service_id_foreign` FOREIGN KEY (`carrier_service_id`) REFERENCES `carrier_services` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ships_customer_address_id_foreign` FOREIGN KEY (`customer_address_id`) REFERENCES `customer_addresses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ships_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ship_packages`
--
ALTER TABLE `ship_packages`
  ADD CONSTRAINT `ship_packages_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ship_packages_ship_id_foreign` FOREIGN KEY (`ship_id`) REFERENCES `ships` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_referred_by_foreign` FOREIGN KEY (`referred_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_cards`
--
ALTER TABLE `user_cards`
  ADD CONSTRAINT `user_cards_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
