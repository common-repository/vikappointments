--
-- Alter for table `#__vikappointments_conversion`
--

ALTER TABLE `#__vikappointments_conversion` 
ADD COLUMN `attributes` varchar(1024) DEFAULT NULL COMMENT 'a JSON map holding the JS file attributes' AFTER `jsfile`;

--
-- Dumping data for table `#__vikappointments_config`
--

INSERT INTO `#__vikappointments_config`
(                       `param`, `setting`) VALUES
(               'calhourheight',        60),
(        'currency_ecb_enabled',         0),
( 'currency_floatrates_enabled',         0),
('currency_currencyapi_enabled',         0),
(    'currency_currencyapi_key',        ''),
(  'currency_currencyapi_cache',       240);