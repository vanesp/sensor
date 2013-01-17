<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

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
// <version>1.0</version>
// <email>vanesp@escurio.com</email>
// <date>2012-07-27</date>
// <summary>Accumulate updates database statistics</summary>



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
 * Class Accumulate
 *
 * @package    Controller
 */
class Accumulate extends Frontend
{

	/**
	 * Update Statistics using a number of queries...
	 * @return string
	 */
	public function Statistics()
	{
		// Roomlog records
		$obj = $this->Database->prepare("DROP TABLE IF EXISTS HourlyRoomlog")->execute();
		$obj = $this->Database->prepare("CREATE TABLE HourlyRoomlog SELECT pid, YEAR(FROM_UNIXTIME(tstamp)) as year, MONTH(FROM_UNIXTIME(tstamp)) as month, DAY(FROM_UNIXTIME(tstamp)) as day, HOUR(FROM_UNIXTIME(tstamp)) as hour,
											Max(tstamp) as tstamp,
											round(avg(light),0) as light,
											round(avg(humidity),0) as humidity,
											round(avg(temp),1) as temp
											from Roomlog GROUP BY pid, year, month, day, hour;")->execute();
/*	$obj = $this->Database->prepare("DROP TABLE IF EXISTS DailyRoomlog")->execute();
		$obj = $this->Database->prepare("CREATE TABLE DailyRoomlog SELECT pid, YEAR(FROM_UNIXTIME(tstamp)) as year, MONTH(FROM_UNIXTIME(tstamp)) as month, DAY(FROM_UNIXTIME(tstamp)) as day,
											Max(tstamp) as tstamp,
											round(avg(light),0) as light,
											round(avg(humidity),0) as humidity,
											round(avg(temp),1) as temp
											from Roomlog GROUP BY pid, year, month, day;")->execute();
*/
		// Sensor records
		$obj = $this->Database->prepare("DROP TABLE IF EXISTS HourlySensorlog")->execute();
		$obj = $this->Database->prepare("CREATE TABLE HourlySensorlog SELECT pid, YEAR(FROM_UNIXTIME(tstamp)) as year, MONTH(FROM_UNIXTIME(tstamp)) as month, DAY(FROM_UNIXTIME(tstamp)) as day, HOUR(FROM_UNIXTIME(tstamp)) as hour,
											Max(tstamp) as tstamp,
											round(avg(value),2) as value
											from Sensorlog GROUP BY pid, year, month, day, hour;")->execute();
/*		$obj = $this->Database->prepare("DROP TABLE IF EXISTS DailySensorlog")->execute();
		$obj = $this->Database->prepare("CREATE TABLE DailySensorlog SELECT pid, YEAR(FROM_UNIXTIME(tstamp)) as year, MONTH(FROM_UNIXTIME(tstamp)) as month, DAY(FROM_UNIXTIME(tstamp)) as day,
											Max(tstamp) as tstamp,
											round(avg(value),2) as value
											from Sensorlog GROUP BY pid, year, month, day;")->execute();
*/
		// Electricity records
		$obj = $this->Database->prepare("DROP TABLE IF EXISTS HourlyEleclog")->execute();
		$obj = $this->Database->prepare("CREATE TABLE HourlyEleclog SELECT YEAR(FROM_UNIXTIME(tstamp)) as year, MONTH(FROM_UNIXTIME(tstamp)) as month, DAY(FROM_UNIXTIME(tstamp)) as day, HOUR(FROM_UNIXTIME(tstamp)) as hour,
											Max(tstamp) as tstamp,
											Sum(count) as value
											from Sensorlog 
											WHERE pid=4 GROUP BY year, month, day, hour;")->execute();
		$obj = $this->Database->prepare("DROP TABLE IF EXISTS DailyEleclog")->execute();
		$obj = $this->Database->prepare("CREATE TABLE DailyEleclog SELECT YEAR(FROM_UNIXTIME(tstamp)) as year, MONTH(FROM_UNIXTIME(tstamp)) as month, DAY(FROM_UNIXTIME(tstamp)) as day,
											Max(tstamp) as tstamp,
											Sum(count) as value
											from Sensorlog 
											WHERE pid=4 GROUP BY year, month, day;")->execute();
											
		// Delete old motion logs (over a week old)
		$obj = $this->Database->prepare("DELETE FROM Motionlog WHERE tstamp < UNIX_TIMESTAMP()-604800;")->execute();		


	}
	
}

?>