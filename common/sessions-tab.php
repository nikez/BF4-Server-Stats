<?php
// sessions-tab for server stats page by Ty_ger07 at http://open-web-community.com/

// DON'T EDIT ANYTHING BELOW UNLESS YOU KNOW WHAT YOU ARE DOING

// first connect to the database
// and include necessary files
require_once('../config/config.php');
require_once('../common/connect.php');
require_once('../common/case.php');

// default variable to null
$ServerID = null;
$GameID = null;

// get values
if(!empty($sid))
{
	$ServerID = $sid;
}
if(!empty($gid))
{
	$GameID = $gid;
}

// if there is a ServerID, this is a server stats page
if(!empty($ServerID))
{
	// get the info from the db 
	$Players_q  = @mysqli_query($BF4stats,"
		SELECT tpd.`PlayerID`, tpd.`SoldierName`, SUM(tss.`Score`) AS Score, SUM(tss.`Kills`) AS Kills, SUM(tss.`Deaths`) AS Deaths, (SUM(tss.`Kills`)/SUM(tss.`Deaths`)) AS KDR, SUM(tss.`Headshots`) AS Headshots, (SUM(tss.`Headshots`)/SUM(tss.`Kills`)) AS HSR
		FROM `tbl_sessions` tss
		INNER JOIN `tbl_server_player` tsp ON tss.`StatsID` = tsp.`StatsID`
		INNER JOIN `tbl_playerdata` tpd ON tsp.`PlayerID` = tpd.`PlayerID`
		WHERE tsp.`ServerID` = {$ServerID}
		AND tss.`Starttime` BETWEEN CURDATE() - INTERVAL 7 DAY AND CURDATE()
		AND tpd.`GameID` = {$GameID}
		GROUP BY tpd.`PlayerID`
		ORDER BY Score DESC, tpd.`SoldierName` ASC
		LIMIT 0, 20
	");
}
// or else this is a global stats page
else
{
	// get the info from the db 
	$Players_q  = @mysqli_query($BF4stats,"
		SELECT tpd.`PlayerID`, tpd.`SoldierName`, SUM(tss.`Score`) AS Score, SUM(tss.`Kills`) AS Kills, SUM(tss.`Deaths`) AS Deaths, (SUM(tss.`Kills`)/SUM(tss.`Deaths`)) AS KDR, SUM(tss.`Headshots`) AS Headshots, (SUM(tss.`Headshots`)/SUM(tss.`Kills`)) AS HSR
		FROM `tbl_sessions` tss
		INNER JOIN `tbl_server_player` tsp ON tss.`StatsID` = tsp.`StatsID`
		INNER JOIN `tbl_playerdata` tpd ON tsp.`PlayerID` = tpd.`PlayerID`
		WHERE tss.`Starttime` BETWEEN CURDATE() - INTERVAL 7 DAY AND CURDATE()
		AND tpd.`GameID` = {$GameID}
		GROUP BY tpd.`PlayerID`
		ORDER BY Score DESC, tpd.`SoldierName` ASC
		LIMIT 0, 20
	");
}
// check if there are rows returned
if(@mysqli_num_rows($Players_q) != 0)
{
	echo '
	<table class="prettytable">
	<tr>
	<th width="5%" class="countheader">#</th>
	<th width="17%">Player</th>
	<th width="13%"><span class="orderedDESCheader">Score</span></th>
	<th width="13%">Kills</th>
	<th width="13%">Deaths</th>
	<th width="13%">Kill/Death</th>
	<th width="13%">Headshots</th>
	<th width="13%">Headshot/Kill</th>
	</tr>
	';
	// while there are rows to be fetched...
	while($Player_r = @mysqli_fetch_assoc($Players_q))
	{
		$count++;
		$Soldier_Name = $Player_r['SoldierName'];
		$Player_ID = $Player_r['PlayerID'];
		$Score = $Player_r['Score'];
		$Kills = $Player_r['Kills'];
		$Deaths = $Player_r['Deaths'];
		$KDR = round($Player_r['KDR'],2);
		$Headshots = $Player_r['Headshots'];
		$HSR = round(($Player_r['HSR']*100),2);
		echo '
		<tr>
		<td width="5%" class="count"><span class="information">' . $count . '</span></td>
		';
		// if there is a ServerID, this is a server stats page
		if(!empty($ServerID))
		{
			echo '<td width="17%" class="tablecontents" style="text-align: left;"><a href="./index.php?p=player&amp;sid=' . $ServerID . '&amp;pid=' . $Player_ID . '">' . $Soldier_Name . '</a></td>';
		}
		// or else this is a global stats page
		else
		{
			echo '<td width="17%" class="tablecontents" style="text-align: left;"><a href="./index.php?p=player&amp;pid=' . $Player_ID . '">' . $Soldier_Name . '</a></td>';
		}
		echo '
		<td width="13%" class="tablecontents">' . $Score . '</td>
		<td width="13%" class="tablecontents">' . $Kills . '</td>
		<td width="13%" class="tablecontents">' . $Deaths . '</td>
		<td width="13%" class="tablecontents">' . $KDR . '</td>
		<td width="13%" class="tablecontents">' . $Headshots . '</td>
		<td width="13%" class="tablecontents">' . $HSR . '<span class="information"> %</span></td>
		</tr>
		';
	}
	echo '
	</table>
	';
}
else
{
	echo '
	<table class="prettytable">
	<tr>
	<td class="tablecontents">
	<div class="headline">
	';
	// if there is a ServerID, this is a server stats page
	if(!empty($ServerID))
	{
		echo 'No session stats found for this server this week.';
	}
	// or else this is a global stats page
	else
	{
		echo 'No session stats found for these servers this week.';
	}
	echo '
	</div>
	</td>
	</tr>
	</table>
	';
}
// free up players query memory
@mysqli_free_result($Players_q);

?>
