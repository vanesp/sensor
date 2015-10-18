<?php

// <copyright> Copyright (c) 2012-2013 All Rights Reserved,
// Escurio BV
// http://www.escurio.com/
//
// THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY 
// KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
// IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
// PARTICULAR PURPOSE.
//
// </copyright>
// <author>Peter van Es</author>
// <version>1.2</version>
// <email>vanesp@escurio.com</email>
// <date>2015-10-17</date>
// <summary>Accumulate updates database statistics</summary>

// Version 1.1 -- remove year, month, day, hour fields from summary tables
//                change drop table commands to temporary tables
//                use replace table to insert values
//                create daily table for min, max temperatures
//		  2013-07-31

// Version 1.2 -- added P1 update tables

/*
// SQL code to delete duplicate records from a table once an auto-increment id has been added as primary key. Can be deleted afterwards
drop temporary table to_delete;
create temporary table to_delete (tstamp int not null, pid int, min_id int not null);
insert into to_delete(tstamp, pid, min_id) select tstamp, pid, MIN(id) from Sensorlog group by tstamp, pid  HAVING count(*) > 1;
delete from Sensorlog where exists(select * from to_delete where to_delete.tstamp = Sensorlog.tstamp and to_delete.min_id <> Sensorlog.id);

drop temporary table to_delete;
create temporary table to_delete (tstamp int not null, pid int, min_id int not null);
insert into to_delete(tstamp, pid, min_id) select tstamp, pid, MIN(id) from Roomlog group by tstamp, pid  HAVING count(*) > 1;
delete from Roomlog where exists(select * from to_delete where to_delete.tstamp = Roomlog.tstamp and to_delete.min_id <> Roomlog.id);
*/

/**
 * Run in a custom namespace, so the class can be replaced
 */
namespace Contao;


 /**
 * Class Accumulate
 *
 * @package    Controller
 */
class Accumulate extends \Frontend
{

	/**
	 * Update Statistics using a number of queries...
	 * @return string
	 */
	public function Statistics()
	{
		// Roomlog records
		$obj = $this->Database->prepare("DROP TABLE IF EXISTS THRoomlog")->execute();
		$obj = $this->Database->prepare("CREATE TABLE THRoomlog SELECT pid, YEAR(FROM_UNIXTIME(tstamp)) as year, MONTH(FROM_UNIXTIME(tstamp)) as month, DAY(FROM_UNIXTIME(tstamp)) as day, HOUR(FROM_UNIXTIME(tstamp)) as hour,
											Max(tstamp) as tstamp,
											round(avg(light),0) as light,
											round(avg(humidity),0) as humidity,
											round(avg(temp),1) as temp
											from Roomlog GROUP BY pid, year, month, day, hour")->execute();

        // Replace into table, but only data that is recent (i.e. within last week), to avoid overwriting old averages
        // but also exclude data that is less than an hour (or day) old, otherwise duplicate timestamp entries will be inserted
        // this counts for all
		$obj = $this->Database->prepare("REPLACE INTO HourlyRoomlog
		                                    SELECT pid, year, month, day, hour, tstamp, light, humidity, temp
											FROM THRoomlog WHERE tstamp > UNIX_TIMESTAMP()-604800 AND tstamp < UNIX_TIMESTAMP()-3600")->execute();

        // Same for daily room log
		$obj = $this->Database->prepare("DROP TABLE IF EXISTS TDRoomlog")->execute();
		$obj = $this->Database->prepare("CREATE TABLE TDRoomlog SELECT pid, YEAR(FROM_UNIXTIME(tstamp)) as year, MONTH(FROM_UNIXTIME(tstamp)) as month, DAY(FROM_UNIXTIME(tstamp)) as day,
											Max(tstamp) as tstamp,
											round(avg(light),0) as light,
											round(avg(humidity),0) as humidity,
											round(avg(temp),1) as temp,
											Max(temp) as hitemp,
											Min(temp) as lowtemp
											from Roomlog GROUP BY pid, year, month, day")->execute();

        // Replace into table, but only data that is recent (i.e. within last week), to avoid overwriting old averages
		$obj = $this->Database->prepare("REPLACE INTO DailyRoomlog
		                                    SELECT pid, year, month, day, tstamp, light, humidity, temp, hitemp, lowtemp
											FROM TDRoomlog WHERE tstamp > UNIX_TIMESTAMP()-604800  AND tstamp < UNIX_TIMESTAMP()-3600*24")->execute();


        // Delete older Roomlog records (over 31 days old)
		$obj = $this->Database->prepare("DELETE FROM Roomlog WHERE tstamp < UNIX_TIMESTAMP()-2678400")->execute();		
		// And drop the table to preserve space
		$obj = $this->Database->prepare("DROP TABLE IF EXISTS THRoomlog")->execute();
		$obj = $this->Database->prepare("DROP TABLE IF EXISTS TDRoomlog")->execute();


		// Sensor records
		$obj = $this->Database->prepare("DROP TABLE IF EXISTS THSensorlog")->execute();
		$obj = $this->Database->prepare("CREATE TABLE THSensorlog SELECT pid, YEAR(FROM_UNIXTIME(tstamp)) as year, MONTH(FROM_UNIXTIME(tstamp)) as month, DAY(FROM_UNIXTIME(tstamp)) as day, HOUR(FROM_UNIXTIME(tstamp)) as hour,
											Max(tstamp) as tstamp,
											round(avg(value),2) as value
											from Sensorlog GROUP BY pid, year, month, day, hour")->execute();

                // Replace into table, but only data that is recent (i.e. within last week), to avoid overwriting old averages
		$obj = $this->Database->prepare("REPLACE INTO HourlySensorlog
		                                    SELECT pid, year, month, day, hour, tstamp, value
 											FROM THSensorlog WHERE tstamp > UNIX_TIMESTAMP()-604800  AND tstamp < UNIX_TIMESTAMP()-3600")->execute();

		$obj = $this->Database->prepare("DROP TABLE IF EXISTS TDSensorlog")->execute();
		$obj = $this->Database->prepare("CREATE TABLE TDSensorlog SELECT pid, YEAR(FROM_UNIXTIME(tstamp)) as year, MONTH(FROM_UNIXTIME(tstamp)) as month, DAY(FROM_UNIXTIME(tstamp)) as day,
											Max(tstamp) as tstamp,
											round(avg(value),2) as value,
											Max(value) as hivalue,
											Min(value) as lowvalue
											from Sensorlog GROUP BY pid, year, month, day")->execute();
											
                // Replace into table, but only data that is recent (i.e. within last week), to avoid overwriting old averages
		$obj = $this->Database->prepare("REPLACE INTO DailySensorlog
		                                    SELECT pid, year, month, day, tstamp, value, hivalue, lowvalue
											FROM TDSensorlog WHERE tstamp > UNIX_TIMESTAMP()-604800 AND tstamp < UNIX_TIMESTAMP()-3600*24")->execute();


		// Electricity records
		$obj = $this->Database->prepare("DROP TABLE IF EXISTS THEleclog")->execute();
		// use the P1 log data to fill this table
		// $obj = $this->Database->prepare("CREATE TABLE THEleclog SELECT YEAR(FROM_UNIXTIME(tstamp)) as year, MONTH(FROM_UNIXTIME(tstamp)) as month, DAY(FROM_UNIXTIME(tstamp)) as day, HOUR(FROM_UNIXTIME(tstamp)) as hour,
		// 									Max(tstamp) as tstamp,
		// 									Sum(count) as value
		//									from Sensorlog 
		//									WHERE pid=8 GROUP BY year, month, day, hour")->execute();
		$obj = $this->Database->prepare("CREATE TABLE THEleclog SELECT YEAR(FROM_UNIXTIME(tstamp)) as year, MONTH(FROM_UNIXTIME(tstamp)) as month, DAY(FROM_UNIXTIME(tstamp)) as day, HOUR(FROM_UNIXTIME(tstamp)) as hour,
											Max(tstamp) as tstamp,
											(Max(use1)-Min(use1) + Max(use2)-Min(use2))/1000 as value
											from P1log 
											WHERE pid=18 GROUP BY year, month, day, hour")->execute();
                // Replace into table, but only data that is recent (i.e. within last week), to avoid overwriting old averages
		$obj = $this->Database->prepare("REPLACE INTO HourlyEleclog
		                                    SELECT year, month, day, hour, tstamp, value
 											FROM THEleclog WHERE tstamp > UNIX_TIMESTAMP()-604800  AND tstamp < UNIX_TIMESTAMP()-3600")->execute();


		$obj = $this->Database->prepare("DROP TABLE IF EXISTS TDEleclog")->execute();
		$obj = $this->Database->prepare("CREATE TABLE TDEleclog SELECT YEAR(FROM_UNIXTIME(tstamp)) as year, MONTH(FROM_UNIXTIME(tstamp)) as month, DAY(FROM_UNIXTIME(tstamp)) as day,
											Max(tstamp) as tstamp,
											Sum(value) as value
											from HourlyEleclog 
											GROUP BY year, month, day")->execute();
                // Replace into table, but only data that is recent (i.e. within last week), to avoid overwriting old averages
		$obj = $this->Database->prepare("REPLACE INTO DailyEleclog
		                                    SELECT year, month, day, tstamp, value
											FROM TDEleclog WHERE tstamp > UNIX_TIMESTAMP()-604800 AND tstamp < UNIX_TIMESTAMP()-3600*24")->execute();

		// Gas records
		$obj = $this->Database->prepare("DROP TABLE IF EXISTS THGaslog")->execute();
		// use the P1 log data to fill this table
		$obj = $this->Database->prepare("CREATE TABLE THGaslog SELECT YEAR(FROM_UNIXTIME(tstamp)) as year, MONTH(FROM_UNIXTIME(tstamp)) as month, DAY(FROM_UNIXTIME(tstamp)) as day, HOUR(FROM_UNIXTIME(tstamp)) as hour,
											Max(tstamp) as tstamp,
											(Max(gas)-Min(gas))/1000 as value
											from P1log 
											WHERE pid=18 GROUP BY year, month, day, hour")->execute();
                // Replace into table, but only data that is recent (i.e. within last week), to avoid overwriting old averages
		$obj = $this->Database->prepare("REPLACE INTO HourlyGaslog
		                                    SELECT year, month, day, hour, tstamp, value
 											FROM THGaslog WHERE tstamp > UNIX_TIMESTAMP()-604800  AND tstamp < UNIX_TIMESTAMP()-3600")->execute();


		$obj = $this->Database->prepare("DROP TABLE IF EXISTS TDGaslog")->execute();
		$obj = $this->Database->prepare("CREATE TABLE TDGaslog SELECT YEAR(FROM_UNIXTIME(tstamp)) as year, MONTH(FROM_UNIXTIME(tstamp)) as month, DAY(FROM_UNIXTIME(tstamp)) as day,
											Max(tstamp) as tstamp,
											Sum(value) as value
											from HourlyGaslog 
											GROUP BY year, month, day")->execute();
                // Replace into table, but only data that is recent (i.e. within last week), to avoid overwriting old averages
		$obj = $this->Database->prepare("REPLACE INTO DailyGaslog
		                                    SELECT year, month, day, tstamp, value
											FROM TDGaslog WHERE tstamp > UNIX_TIMESTAMP()-604800 AND tstamp < UNIX_TIMESTAMP()-3600*24")->execute();



                // P1 records
		$obj = $this->Database->prepare("DROP TABLE IF EXISTS THP1log")->execute();
		// use the P1 log data to fill this table
		$obj = $this->Database->prepare("CREATE TABLE THP1log SELECT YEAR(FROM_UNIXTIME(tstamp)) as year, MONTH(FROM_UNIXTIME(tstamp)) as month, DAY(FROM_UNIXTIME(tstamp)) as day, HOUR(FROM_UNIXTIME(tstamp)) as hour,
											Max(tstamp) as tstamp,
											Max(use1)-Min(use1) as use1,
											Max(use2)-Min(use2) as use2,
											Max(gen1)-Min(gen1) as gen1,
											Max(gen2)-Min(gen2) as gen2,
											Max(mode) as mode,
											Sum(usew) as usew,
											Sum(genw) as genw,
											Max(gas)-Min(gas) as gas
											from P1log 
											WHERE pid=18 GROUP BY year, month, day, hour")->execute();
                // Replace into table, but only data that is recent (i.e. within last week), to avoid overwriting old averages
		$obj = $this->Database->prepare("REPLACE INTO HourlyP1log
		                                    SELECT year, month, day, hour, tstamp, use1, use2, gen1, gen2, mode, usew, genw, gas
 											FROM THP1log WHERE tstamp > UNIX_TIMESTAMP()-604800  AND tstamp < UNIX_TIMESTAMP()-3600")->execute();


		$obj = $this->Database->prepare("DROP TABLE IF EXISTS TDP1log")->execute();
		$obj = $this->Database->prepare("CREATE TABLE TDP1log SELECT YEAR(FROM_UNIXTIME(tstamp)) as year, MONTH(FROM_UNIXTIME(tstamp)) as month, DAY(FROM_UNIXTIME(tstamp)) as day,
											Max(tstamp) as tstamp,
											Max(use1)-Min(use1) as use1,
											Max(use2)-Min(use2) as use2,
											Max(gen1)-Min(gen1) as gen1,
											Max(gen2)-Min(gen2) as gen2,
											Max(mode) as mode,
											Sum(usew) as usew,
											Sum(genw) as genw,
											Max(gas)-Min(gas) as gas
											from P1log 
											GROUP BY year, month, day")->execute();
                // Replace into table, but only data that is recent (i.e. within last week), to avoid overwriting old averages
		$obj = $this->Database->prepare("REPLACE INTO DailyP1log
		                                    SELECT year, month, day, tstamp, use1, use2, gen1, gen2, mode, usew, genw, gas
											FROM TDP1log WHERE tstamp > UNIX_TIMESTAMP()-604800 AND tstamp < UNIX_TIMESTAMP()-3600*24")->execute();


                // Delete older Roomlog records (over 31 days old)
		$obj = $this->Database->prepare("DELETE FROM Sensorlog WHERE tstamp < UNIX_TIMESTAMP()-2678400")->execute();		
		// And drop the table to preserve space										
		$obj = $this->Database->prepare("DROP TABLE IF EXISTS THSensorlog")->execute();
		$obj = $this->Database->prepare("DROP TABLE IF EXISTS TDSensorlog")->execute();
		$obj = $this->Database->prepare("DROP TABLE IF EXISTS THEleclog")->execute();
		$obj = $this->Database->prepare("DROP TABLE IF EXISTS TDEleclog")->execute();
		$obj = $this->Database->prepare("DROP TABLE IF EXISTS THGaslog")->execute();
		$obj = $this->Database->prepare("DROP TABLE IF EXISTS TDGaslog")->execute();
		$obj = $this->Database->prepare("DROP TABLE IF EXISTS THP1log")->execute();
		$obj = $this->Database->prepare("DROP TABLE IF EXISTS TDP1log")->execute();

		// Delete old motion logs (over a week old)
		$obj = $this->Database->prepare("DELETE FROM Motionlog WHERE tstamp < UNIX_TIMESTAMP()-604800")->execute();		

        // Here's a set of queries to clean them all up
        /* 
        DELETE FROM DailyEleclog WHERE tstamp > UNIX_TIMESTAMP()-3600*24;
        DELETE FROM DailyRoomlog WHERE tstamp > UNIX_TIMESTAMP()-3600*24;
        DELETE FROM DailySensorlog WHERE tstamp > UNIX_TIMESTAMP()-3600*24;
        DELETE FROM HourlyEleclog WHERE tstamp > UNIX_TIMESTAMP()-3600*24;
        DELETE FROM HourlyRoomlog WHERE tstamp > UNIX_TIMESTAMP()-3600*24;
        DELETE FROM HourlySensorlog WHERE tstamp > UNIX_TIMESTAMP()-3600*24;
        */

	}
	
}

?>