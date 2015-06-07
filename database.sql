-- Create syntax for TABLE 'Actionlog'
CREATE TABLE `Actionlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tstamp` int(11) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  `pid` int(11) DEFAULT NULL COMMENT 'Sensor.id',
  `uid` int(11) DEFAULT NULL COMMENT 'tl_user.id owner',
  `comment` varchar(4096) DEFAULT NULL COMMENT 'Comments',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Actionlog';

-- Create syntax for TABLE 'Customer'
CREATE TABLE `Customer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tstamp` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `housenumber` varchar(45) DEFAULT NULL,
  `postalcode` varchar(45) DEFAULT NULL,
  `city` varchar(45) DEFAULT NULL,
  `country` varchar(45) DEFAULT NULL,
  `contactperson` varchar(45) DEFAULT NULL,
  `telephone` varchar(45) DEFAULT NULL,
  `email` varchar(45) DEFAULT NULL,
  `comments` varchar(4096) DEFAULT NULL COMMENT 'Comments',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='Customer details';

-- Create syntax for TABLE 'DailyEleclog'
CREATE TABLE `DailyEleclog` (
  `year` int(11) NOT NULL DEFAULT '0',
  `month` int(11) NOT NULL DEFAULT '0',
  `day` int(11) NOT NULL DEFAULT '0',
  `tstamp` int(11) DEFAULT NULL,
  `value` float DEFAULT NULL,
  PRIMARY KEY (`year`,`month`,`day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Create syntax for TABLE 'DailyRoomlog'
CREATE TABLE `DailyRoomlog` (
  `pid` int(11) NOT NULL DEFAULT '0' COMMENT 'Sensor.id',
  `year` int(11) NOT NULL DEFAULT '0',
  `month` int(11) NOT NULL DEFAULT '0',
  `day` int(11) NOT NULL DEFAULT '0',
  `tstamp` int(11) DEFAULT NULL,
  `light` float DEFAULT NULL,
  `humidity` float DEFAULT NULL,
  `temp` float DEFAULT NULL,
  `hitemp` float DEFAULT NULL,
  `lowtemp` float DEFAULT NULL,
  PRIMARY KEY (`pid`,`year`,`month`,`day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Create syntax for TABLE 'DailySensorlog'
CREATE TABLE `DailySensorlog` (
  `pid` int(11) NOT NULL DEFAULT '0' COMMENT 'Sensor.id',
  `year` int(11) NOT NULL DEFAULT '0',
  `month` int(11) NOT NULL DEFAULT '0',
  `day` int(11) NOT NULL DEFAULT '0',
  `tstamp` int(11) DEFAULT NULL,
  `value` float DEFAULT NULL,
  `hivalue` float DEFAULT NULL,
  `lowvalue` float DEFAULT NULL,
  PRIMARY KEY (`pid`,`year`,`month`,`day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Create syntax for TABLE 'HourlyEleclog'
CREATE TABLE `HourlyEleclog` (
  `year` int(11) NOT NULL DEFAULT '0',
  `month` int(11) NOT NULL DEFAULT '0',
  `day` int(11) NOT NULL DEFAULT '0',
  `hour` int(11) NOT NULL DEFAULT '0',
  `tstamp` int(11) DEFAULT NULL,
  `value` float DEFAULT NULL,
  PRIMARY KEY (`year`,`month`,`day`,`hour`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Create syntax for TABLE 'HourlyRoomlog'
CREATE TABLE `HourlyRoomlog` (
  `pid` int(11) NOT NULL DEFAULT '0' COMMENT 'Sensor.id',
  `year` int(11) NOT NULL DEFAULT '0',
  `month` int(11) NOT NULL DEFAULT '0',
  `day` int(11) NOT NULL DEFAULT '0',
  `hour` int(11) NOT NULL DEFAULT '0',
  `tstamp` int(11) DEFAULT NULL,
  `light` float DEFAULT NULL,
  `humidity` float DEFAULT NULL,
  `temp` float DEFAULT NULL,
  PRIMARY KEY (`pid`,`year`,`month`,`day`,`hour`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Create syntax for TABLE 'HourlySensorlog'
CREATE TABLE `HourlySensorlog` (
  `pid` int(11) NOT NULL DEFAULT '0' COMMENT 'Sensor.id',
  `year` int(11) NOT NULL DEFAULT '0',
  `month` int(11) NOT NULL DEFAULT '0',
  `day` int(11) NOT NULL DEFAULT '0',
  `hour` int(11) NOT NULL DEFAULT '0',
  `tstamp` int(11) DEFAULT NULL,
  `value` float DEFAULT NULL,
  PRIMARY KEY (`pid`,`year`,`month`,`day`,`hour`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Create syntax for TABLE 'Location'
CREATE TABLE `Location` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) DEFAULT NULL COMMENT 'Customer.id',
  `tstamp` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `housenumber` varchar(45) DEFAULT NULL,
  `postalcode` varchar(45) DEFAULT NULL,
  `city` varchar(45) DEFAULT NULL,
  `country` varchar(45) DEFAULT NULL,
  `latitude` varchar(20) DEFAULT NULL,
  `longitude` varchar(20) DEFAULT NULL,
  `contactperson` varchar(45) DEFAULT NULL,
  `telephone` varchar(45) DEFAULT NULL,
  `email` varchar(45) DEFAULT NULL,
  `comments` varchar(4096) DEFAULT NULL COMMENT 'Comments',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='Location details';

-- Create syntax for TABLE 'Motionlog'
CREATE TABLE `Motionlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) DEFAULT NULL COMMENT 'Sensor.id',
  `tstamp` int(11) DEFAULT NULL,
  `movement` int(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=332584 DEFAULT CHARSET=utf8;

-- Create syntax for TABLE 'Roomlog'
CREATE TABLE `Roomlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) DEFAULT NULL COMMENT 'Sensor.id',
  `tstamp` int(11) DEFAULT NULL,
  `light` float DEFAULT NULL,
  `humidity` float DEFAULT NULL,
  `temp` float DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27292 DEFAULT CHARSET=utf8;

-- Create syntax for TABLE 'Sensor'
CREATE TABLE `Sensor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) DEFAULT NULL COMMENT 'Location.id',
  `tstamp` int(11) DEFAULT NULL,
  `uid` int(11) DEFAULT NULL,
  `idsensor` varchar(15) DEFAULT '',
  `idroom` int(10) DEFAULT NULL COMMENT 'roomnode number',
  `location` varchar(255) DEFAULT NULL COMMENT 'location',
  `comments` varchar(4096) DEFAULT NULL COMMENT 'Comments time_on installation, remarks',
  `lobatt` int(1) DEFAULT '0' COMMENT 'Battery status',
  `sensortype` varchar(20) DEFAULT NULL,
  `sensorquantity` varchar(8) DEFAULT NULL,
  `datastream` varchar(15) DEFAULT NULL,
  `sensorscale` varchar(15) DEFAULT NULL,
  `cum_gas_pulse` int(11) DEFAULT NULL,
  `cum_water_pulse` int(11) DEFAULT NULL,
  `cum_elec_pulse` int(11) DEFAULT NULL,
  `highalarm` int(11) DEFAULT NULL,
  `lowalarm` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 COMMENT='Stores sensor details';

-- Create syntax for TABLE 'Sensorlog'
CREATE TABLE `Sensorlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) DEFAULT NULL COMMENT 'Sensor.id',
  `tstamp` int(11) DEFAULT NULL,
  `value` float DEFAULT NULL,
  `count` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=94373 DEFAULT CHARSET=utf8;

-- Create syntax for TABLE 'Switch'
CREATE TABLE `Switch` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) DEFAULT NULL COMMENT 'Location.id',
  `tstamp` int(11) DEFAULT NULL,
  `uid` int(11) DEFAULT NULL COMMENT 'tl_user.id owner',
  `idswitch` varchar(15) DEFAULT '',
  `sensor_id` int(10) DEFAULT NULL COMMENT 'roomnode number',
  `description` varchar(80) DEFAULT NULL COMMENT 'description',
  `comments` varchar(4096) DEFAULT NULL COMMENT 'Comments time_on installation, remarks',
  `strategy` varchar(45) DEFAULT NULL,
  `command` varchar(80) DEFAULT NULL,
  `kaku` varchar(20) DEFAULT NULL,
  `time_on` varchar(8) DEFAULT NULL,
  `time_off` varchar(8) DEFAULT NULL,
  `state` varchar(8) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL COMMENT 'Minutes',
  `olddim` int(1) DEFAULT '0' COMMENT 'Dimmable, old KAKU',
  `nextevent` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='Stores switch details';
