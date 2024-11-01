--
-- Alter for table `#__vikappointments_reservation`
--

ALTER TABLE `#__vikappointments_reservation`
ADD COLUMN `icalhash` varchar(128) DEFAULT NULL AFTER `icaluid`;

--
-- Alter for table `#__vikappointments_option`
--

ALTER TABLE `#__vikappointments_option`
ADD COLUMN `shared` tinyint(1) DEFAULT 0 COMMENT 'when maxqpeople=1, the quantity will be shared by all the options of the same group' AFTER `maxqpeople`;

--
-- Dumping data for table `#__vikappointments_stats_widget`
--

INSERT INTO `#__vikappointments_stats_widget`
(               `widget`, `position`, `location`,  `size`, `ordering`) VALUES
('options_revenue_chart',      'top',  'options',      '',          1),
('options_revenue_count',      'top',  'options', 'small',          2),
('options_revenue_items',   'bottom',  'options',      '',          3),
('options_revenue_table',   'bottom',  'options',      '',          4);