<?php
// scoreboard for server stats page by Ty_ger07 at http://open-web-community.com/

// DON'T EDIT ANYTHING BELOW UNLESS YOU KNOW WHAT YOU ARE DOING

// include required files
require_once('../config/config.php');
require_once('../common/connect.php');
require_once('../common/constants.php');
require_once('../common/case.php');

// default variables to null
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

// start creating scoreboard
echo '
<div class="sectionheader" style="position: relative;">
';
// updating text...
echo '
<div id="fadein" style="position: absolute; top: 4px; left: -150px; display: none;">
<div class="subsection" style="width: 100px;">
<center>Updating ...</center>
</div>
</div>
';
// last updated text...
echo '
<div id="fadeaway" style="position: absolute; top: 4px; left: -150px;">
<div class="subsection" style="width: 100px;">
<center>Updated <span id="timestamp"></span></center>
</div>
</div>
<div class="headline">
Live Scoreboard
</div>
</div>
';
// find out client's current time with javascript
echo '
<script type="text/javascript">
var date = new Date();
var hours = date.getHours();
var minutes = date.getMinutes();
if (hours.toString() == "0")
{
hours = "12";
}
if (minutes.toString().length == 1)
{
minutes = "0" + minutes;
}
document.getElementById("timestamp").innerHTML = hours + \':\' + minutes;
$("#fadeaway").finish().show().delay(1000).fadeOut("slow");
$("#fadein").delay(19000).fadeIn("slow");
</script>
';
// query for player in server and order them by team
$Scoreboard_q = @mysqli_query($BF4stats,"
	SELECT `TeamID`
	FROM `tbl_currentplayers`
	WHERE `ServerID` = {$ServerID}
	ORDER BY `TeamID` ASC
");
// no players were found in the server
// display basic server information
if(@mysqli_num_rows($Scoreboard_q) == 0)
{
	$Basic_q = @mysqli_query($BF4stats,"
		SELECT `mapName`, `Gamemode`, `maxSlots`, `usedSlots`, `ServerName`
		FROM `tbl_server`
		WHERE `ServerID` = {$ServerID}
		AND `GameID` = {$GameID}
	");
	// information was found
	if(@mysqli_num_rows($Basic_q) != 0)
	{
		$Basic_r = @mysqli_fetch_assoc($Basic_q);
		$used_slots = $Basic_r['usedSlots'];
		$available_slots = $Basic_r['maxSlots'];
		$name = $Basic_r['ServerName'];
		$battlelog = 'http://battlelog.battlefield.com/bf4/servers/pc/?filtered=1&amp;expand=0&amp;useAdvanced=1&amp;q=' . urlencode($name);
		$mode = $Basic_r['Gamemode'];
		// convert mode to friendly name
		if(in_array($mode,$mode_array))
		{
			$mode_name = array_search($mode,$mode_array);
		}
		// this mode is missing!
		else
		{
			$mode_name = $mode;
		}
		$map = $Basic_r['mapName'];
		// convert map to friendly name
		// first find if this map name is even in the map array
		if(in_array($map,$map_array))
		{
			$map_name = array_search($map,$map_array);
			$map_img = './images/maps/' . $map . '.png';
		}
		// this map is missing!
		else
		{
			$map_name = $map;
			$map_img = './images/maps/missing.png';
		}
		echo '
		<div style="margin-bottom: 4px; position: relative;">
		<div style="position: absolute; z-index: 2; width: 100%; height: 100%; top: 0; left: 0; padding: 0px; margin: 0px;"><a class="fill-div" style="padding: 0px; margin: 0px;" href="' . $battlelog . '" target="_blank"></a></div>
		<table>
		<tr>
		<td class="subsection" style="width: 57px;">
		<img style="height: 32px;" src="' . $map_img . '" alt="map image" />
		</td>
		<td class="subsection" style="width: 57px;">
		<div class="headline" style="text-align: center; font-size: 12px;">Players</div>
		<div style="text-align: center; font-size: 12px;">' . $used_slots . ' / ' . $available_slots . '</div>
		</td>
		<td class="subsection">
		<div class="headline" style="text-align: left; padding: 0px; padding-left: 3px;">
		' . $name . '
		</div>
		<div style="font-size: 12px; padding-left: 4px;">
		' . $mode_name . ' &bull; ' . $map_name . '
		</div>
		</td>
		</tr>
		</table>
		</div>
		';
	}
	// an error occured
	// display blank information
	else
	{
		$battlelog = 'http://battlelog.battlefield.com/bf4/servers/pc/';
		echo '
		<div style="margin-bottom: 4px; position: relative;">
		<div style="position: absolute; z-index: 2; width: 100%; height: 100%; top: 0; left: 0; padding: 0px; margin: 0px;"><a class="fill-div" style="padding: 0px; margin: 0px;" href="' . $battlelog . '" target="_blank"></a></div>
		<table>
		<tr>
		<td class="subsection" style="width: 57px;">
		<img style="height: 32px;" src="./images/maps/missing.png" alt="map image" />
		</td>
		<td class="subsection" style="width: 57px;">
		<div class="headline" style="text-align: center; font-size: 12px;">Players</div>
		<div style="text-align: center; font-size: 12px;">error</div>
		</td>
		<td class="subsection">
		<div class="headline" style="text-align: left; padding: 0px; padding-left: 3px;">
		Unknown Name
		</div>
		<div style="font-size: 12px; padding-left: 4px;">
		Unknown Mode &bull; Unknown Map
		</div>
		</td>
		</tr>
		</table>
		</div>
		';
	}
	// free up basic query memory
	@mysqli_free_result($Basic_q);
}
// players were found in the server
// display teams and players
else
{
	// initialize values
	$mode_name = 'Unknown';
	$map_name = 'Unknown';
	$mode = 'Unknown';
	$count2 = 0;
	// figure out current game mode and map name
	$Basic_q = @mysqli_query($BF4stats,"
		SELECT `mapName`, `Gamemode`, `maxSlots`, `usedSlots`, `ServerName`
		FROM `tbl_server`
		WHERE `ServerID` = {$ServerID}
		AND `GameID` = {$GameID}
	");
	if(@mysqli_num_rows($Basic_q) != 0)
	{
		$Basic_r = @mysqli_fetch_assoc($Basic_q);
		$used_slots = $Basic_r['usedSlots'];
		$available_slots = $Basic_r['maxSlots'];
		$name = $Basic_r['ServerName'];
		$battlelog = 'http://battlelog.battlefield.com/bf4/servers/pc/?filtered=1&amp;expand=0&amp;useAdvanced=1&amp;q=' . urlencode($name);
		$mode = $Basic_r['Gamemode'];
		// convert mode to friendly name
		// first find if this mode is even in the mode array
		if(in_array($mode,$mode_array))
		{
			$mode_name = array_search($mode,$mode_array);
		}
		// this mode is missing!
		else
		{
			$mode_name = $mode;
		}
		$map = $Basic_r['mapName'];
		// convert map to friendly name
		// first find if this map name is even in the map array
		if(in_array($map,$map_array))
		{
			$map_name = array_search($map,$map_array);
			$map_img = './images/maps/' . $map . '.png';
		}
		// this map is missing!
		else
		{
			$map_name = $map;
			$map_img = './images/maps/missing.png';
		}
		echo '
		<div style="margin-bottom: 4px; position: relative;">
		<div style="position: absolute; z-index: 2; width: 100%; height: 100%; top: 0; left: 0; padding: 0px; margin: 0px;"><a class="fill-div" style="padding: 0px; margin: 0px;" href="' . $battlelog . '" target="_blank"></a></div>
		<table>
		<tr>
		<td class="subsection" style="width: 57px;">
		<img style="height: 32px;" src="' . $map_img . '" alt="map image" />
		</td>
		<td class="subsection" style="width: 57px;">
		<div class="headline" style="text-align: center; font-size: 12px;">Players</div>
		<div style="text-align: center; font-size: 12px;">' . $used_slots . ' / ' . $available_slots . '</div>
		</td>
		<td class="subsection">
		<div class="headline" style="text-align: left; padding: 0px; padding-left: 3px;">
		' . $name . '
		</div>
		<div style="font-size: 12px; padding-left: 4px;">
		' . $mode_name . ' &bull; ' . $map_name . '
		</div>
		</td>
		</tr>
		</table>
		</div>
		';
	}
	// an error occured
	// display blank information
	else
	{
		$battlelog = 'http://battlelog.battlefield.com/bf4/servers/pc/';
		echo '
		<div style="margin-bottom: 4px; position: relative;">
		<div style="position: absolute; z-index: 2; width: 100%; height: 100%; top: 0; left: 0; padding: 0px; margin: 0px;"><a class="fill-div" style="padding: 0px; margin: 0px;" href="' . $battlelog . '" target="_blank"></a></div>
		<table>
		<tr>
		<td class="subsection" style="width: 57px;">
		<img style="height: 32px;" src="./images/maps/missing.png" alt="map image" />
		</td>
		<td class="subsection" style="width: 57px;">
		<div class="headline" style="text-align: center; font-size: 12px;">Players</div>
		<div style="text-align: center; font-size: 12px;">error</div>
		</td>
		<td class="subsection">
		<div class="headline" style="text-align: left; padding: 0px; padding-left: 3px;">
		Unknown Name
		</div>
		<div style="font-size: 12px; padding-left: 4px;">
		Unknown Mode &bull; Unknown Map
		</div>
		</td>
		</tr>
		</table>
		</div>
		';
	}
	// free up basic query memory
	@mysqli_free_result($Basic_q);
	
	// initialize values
	$last_team = -1;
	// get current rank query details
	if(!empty($_GET['rank']))
	{
		$rank = $_GET['rank'];
		// filter out SQL injection
		if($rank != 'Score' AND $rank != 'Kills' AND $rank != 'Deaths' AND $rank != 'SquadID')
		{
			// unexpected input detected
			// use default instead
			$rank = 'Score';
		}
	}
	// set default if no rank provided in URL
	else
	{
		$rank = 'Score';
	}
	// get current order query details
	if(!empty($_GET['order']))
	{
		$order = $_GET['order'];
		// filter out SQL injection
		if($order != 'DESC' AND $order != 'ASC')
		{
			// unexpected input detected
			// use default instead
			$order = 'DESC';
			$nextorder = 'ASC';
		}
		else
		{
			if($order == 'DESC')
			{
				$nextorder = 'ASC';
			}
			else
			{
				$nextorder = 'DESC';
			}
		}
	}
	// set default if no order provided in URL
	else
	{
		$order = 'DESC';
		$nextorder = 'ASC';
	}
	
	echo '
	<table style="border-spacing: 0px;">
	<tr>
	';
	// start looping through the scoreboard information
	while($Scoreboard_r = @mysqli_fetch_assoc($Scoreboard_q))
	{
		$this_team = $Scoreboard_r['TeamID'];
		// change to a different collumn or row of the scoreboard when the team number changes
		if($this_team != $last_team)
		{
			// if the game mode has more than 2 teams, the third team should be moved down to the next row of the scoreboard
			if($this_team == 3)
			{
				echo '</tr><tr>';
			}
			// change team name shown depending on team number
			// team 0 is 'loading in'
			if($this_team == 0)
			{
				$team_name = 'Joining ...';
			}
			// player is actually assigned to a team
			else
			{
				// change team name displayed on scoreboard based on team number and game mode
				if(($mode == 'ConquestLarge0') OR ($mode == 'ConquestSmall0') OR ($mode == 'Domination0') OR ($mode == 'Elimination0') OR ($mode == 'Obliteration') OR ($mode == 'TeamDeathMatch0') OR ($mode == 'AirSuperiority0') OR ($mode == 'CaptureTheFlag0'))
				{
					if($this_team == 1)
					{
						if(($map == 'MP_Abandoned') OR ($map == 'MP_Damage') OR ($map == 'MP_Journey') OR ($map == 'MP_TheDish'))
						{
							$team_name = 'RU Army';
						}
						elseif(($map == 'MP_Flooded') OR ($map == 'MP_Naval') OR ($map == 'MP_Prison') OR ($map == 'MP_Resort') OR ($map == 'MP_Siege') OR ($map == 'MP_Tremors') OR ($map == 'XP1_001') OR ($map == 'XP1_002') OR ($map == 'XP1_003') OR ($map == 'XP1_004') OR ($map == 'XP0_Caspian') OR ($map == 'XP0_Firestorm') OR ($map == 'XP0_Metro') OR ($map == 'XP0_Oman'))
						{
							$team_name = 'US Army';
						}
						else
						{
							$team_name = 'US Army';
						}
					}
					elseif($this_team == 2)
					{
						if($map == 'MP_Abandoned')
						{
							$team_name = 'US Army';
						}
						elseif(($map == 'MP_Damage') OR ($map == 'MP_Flooded') OR ($map == 'MP_Journey') OR ($map == 'MP_Naval') OR ($map == 'MP_Resort') OR ($map == 'MP_Siege') OR ($map == 'MP_TheDish') OR ($map == 'MP_Tremors') OR ($map == 'XP1_001') OR ($map == 'XP1_002') OR ($map == 'XP1_003') OR ($map == 'XP1_004'))
						{
							$team_name = 'CN Army';
						}
						elseif(($map == 'MP_Prison') OR ($map == 'XP0_Caspian') OR ($map == 'XP0_Firestorm') OR ($map == 'XP0_Metro') OR ($map == 'XP0_Oman'))
						{
							$team_name = 'RU Army';
						}
						else
						{
							$team_name = 'CN Army';
						}
					}
					// something unexpected occurred and a correct team name was not found
					// just name the team based on team number instead
					else
					{
						$team_name = 'Team ' . $this_team;
					}
				}
				elseif($mode == 'RushLarge0')
				{
					if($this_team == 1)
					{
						if(($map == 'MP_Abandoned') OR ($map == 'MP_Damage') OR ($map == 'MP_Flooded') OR ($map == 'MP_Journey') OR ($map == 'MP_Naval') OR ($map == 'MP_Prison') OR ($map == 'MP_Resort') OR ($map == 'MP_Siege') OR ($map == 'MP_TheDish') OR ($map == 'MP_Tremors') OR ($map == 'XP1_001') OR ($map == 'XP1_002') OR ($map == 'XP1_003') OR ($map == 'XP1_004') OR ($map == 'XP0_Caspian') OR ($map == 'XP0_Firestorm') OR ($map == 'XP0_Metro') OR ($map == 'XP0_Oman'))
						{
							$team_name = 'US Attackers';
						}
						else
						{
							$team_name = 'Attackers';
						}
					}
					elseif($this_team == 2)
					{
						if(($map == 'MP_Abandoned') OR ($map == 'MP_Damage') OR ($map == 'MP_Flooded') OR ($map == 'MP_Journey') OR ($map == 'MP_Naval') OR ($map == 'MP_Prison') OR ($map == 'MP_Resort') OR ($map == 'MP_Siege') OR ($map == 'MP_TheDish') OR ($map == 'MP_Tremors') OR ($map == 'XP1_001') OR ($map == 'XP1_002') OR ($map == 'XP1_003') OR ($map == 'XP1_004'))
						{
							$team_name = 'CN Defenders';
						}
						elseif(($map == 'XP0_Caspian') OR ($map == 'XP0_Firestorm') OR ($map == 'XP0_Metro') OR ($map == 'XP0_Oman'))
						{
							$team_name = 'RU Defenders';
						}
						else
						{
							$team_name = 'Defenders';
						}
					}
					// something unexpected occurred and a correct team name was not found
					// just name the team based on team number instead
					else
					{
						$team_name = 'Team ' . $this_team;
					}
				}
				elseif(($mode == 'SquadDeathMatch0'))
				{
					if($this_team == 1)
					{
						$team_name = 'Alpha';
					}
					elseif($this_team == 2)
					{
						$team_name = 'Bravo';
					}
					elseif($this_team == 3)
					{
						$team_name = 'Charlie';
					}
					elseif($this_team == 4)
					{
						$team_name = 'Delta';
					}
					// something unexpected occurred and a correct team name was not found
					// just name the team based on team number instead
					else
					{
						$team_name = 'Team ' . $this_team;
					}
				}
				// something unexpected occurred and a correct team name was not found
				// just name the team based on team number instead
				else
				{
					$team_name = 'Team ' . $this_team;
				}
			}
			// the player is not on a team yet, the "loading in" collumn is formatted different than the team collumns (it extends over two team collumns)
			if($this_team == 0)
			{
				echo '<td valign="top" colspan="2">';
			}
			// this is a team collumn
			else
			{
				echo '<td valign="top">';
			}
			// the "loading in" team does not have scores
			if($this_team != 0)
			{
				// query for scores
				$Score_q = @mysqli_query($BF4stats,"
					SELECT `Score`, `WinningScore`
					FROM `tbl_teamscores`
					WHERE `ServerID` = {$ServerID}
					AND `TeamID` = {$this_team}
				");
				if(@mysqli_num_rows($Score_q) != 0)
				{
					while($Score_r = @mysqli_fetch_assoc($Score_q))
					{
						$Score = $Score_r['Score'];
						$WinningScore = $Score_r['WinningScore'];
						if($WinningScore == 0)
						{
							echo '
							<table class="prettytable" style="margin-top: 4px;">
							<tr>
							<th style="padding-right: 3px;">
							<span class="teamname">' . $team_name . '</span> &nbsp; <span style="float: right;"><span class="information">Tickets Remaining:</span> ' . $Score . '</span>
							</th>
							</tr>
							</table>
							';
						}
						else
						{
							echo '
							<table class="prettytable" style="margin-top: 4px;">
							<tr>
							<th style="padding-right: 3px;">
							<span class="teamname">' . $team_name . '</span> &nbsp; <span style="float: right;"><span class="information">Tickets:</span> ' . $Score . '<span class="information">/</span>' . $WinningScore . '</span>
							</th>
							</tr>
							</table>
							';
						}
					}
				}
				// an error occured
				// display blank information
				else
				{
					echo '
					<table class="prettytable" style="margin-top: 4px;">
					<tr>
					<th style="padding-right: 3px;">
					<span class="teamname">' . $team_name . '</span>
					</th>
					</tr>
					</table>
					';
				}
				// free up score query memory
				@mysqli_free_result($Score_q);
			}
			echo '
			<table width="100%" align="center" border="0" class="prettytable">
			<tr>
			';
			// this formatting is changed depending on if this is a real team or is the "loading in" team
			// this is the "loading in" team
			if($this_team == 0)
			{
				echo '
				<th width="100%" colspan="3" style="text-align:left"><span class="teamname">' . $team_name . '</span></th>
				';
			}
			// this is a real team
			else
			{
				echo '
				<th width="4%" class="countheader">#</th>
				<th width="40%" colspan="2" style="text-align:left">Player</th>
				';
			}
			// if player is loading in, don't show the score, kills, deaths, or squad name headers
			if($this_team != 0)
			{
				echo '<th width="13%" style="text-align:left;"><a href="./index.php?p=home&amp;sid=' . $ServerID . '&amp;rank=Score&amp;order=';
				if($rank != 'Score')
				{
					echo 'DESC"><span class="orderheader">Score</span></a></th>';
				}
				else
				{
					echo $nextorder . '"><span class="ordered' . $order . 'header">Score</span></a></th>';
				}
				echo '<th width="13%" style="text-align:left;"><a href="./index.php?p=home&amp;sid=' . $ServerID . '&amp;rank=Kills&amp;order=';
				if($rank != 'Kills')
				{
					echo 'DESC"><span class="orderheader">Kills</span></a></th>';
				}
				else
				{
					echo $nextorder . '"><span class="ordered' . $order . 'header">Kills</span></a></th>';
				}
				echo '<th width="13%" style="text-align:left;"><a href="./index.php?p=home&amp;sid=' . $ServerID . '&amp;rank=Deaths&amp;order=';
				if($rank != 'Deaths')
				{
					echo 'DESC"><span class="orderheader">Deaths</span></a></th>';
				}
				else
				{
					echo $nextorder . '"><span class="ordered' . $order . 'header">Deaths</span></a></th>';
				}
				echo '<th width="17%" style="text-align:left;"><a href="./index.php?p=home&amp;sid=' . $ServerID . '&amp;rank=SquadID&amp;order=';
				if($rank != 'SquadID')
				{
					echo 'ASC"><span class="orderheader">Squad</span></a></th>';
				}
				else
				{
					echo $nextorder . '"><span class="ordered' . $order . 'header">Squad</span></a></th>';
				}
			}
			echo'</tr>';
			// query for all players on this team
			$Team_q = @mysqli_query($BF4stats,"
				SELECT `Soldiername`, `Score`, `Kills`, `Deaths`, `TeamID`, `SquadID`, `CountryCode`
				FROM `tbl_currentplayers`
				WHERE `ServerID` = {$ServerID}
				AND `TeamID` = {$this_team}
				ORDER BY {$rank} {$order}
			");
			// if team query worked and players were found on this team
			if(@mysqli_num_rows($Team_q) != 0)
			{
				$count = 1;
				while($Team_r = @mysqli_fetch_assoc($Team_q))
				{
					$player = $Team_r['Soldiername'];
					// see if this player has server stats in this server yet
					$PlayerID_q = @mysqli_query($BF4stats,"
						SELECT tpd.`PlayerID`
						FROM `tbl_playerstats` tps
						INNER JOIN `tbl_server_player` tsp ON tsp.`StatsID` = tps.`StatsID`
						INNER JOIN `tbl_playerdata` tpd ON tsp.`PlayerID` = tpd.`PlayerID`
						WHERE tsp.`ServerID` = {$ServerID}
						AND tpd.`SoldierName` = '{$player}'
						AND tpd.`GameID` = {$GameID}
					");
					// server stats found for this player in this server
					if(@mysqli_num_rows($PlayerID_q) == 1)
					{
						$PlayerID_r = @mysqli_fetch_assoc($PlayerID_q);
						$PlayerID = $PlayerID_r['PlayerID'];
					}
					// this player needs to finish this round to get server stats in this server
					else
					{
						$PlayerID = null;
					}
					$score = $Team_r['Score'];
					$kills = $Team_r['Kills'];
					$deaths = $Team_r['Deaths'];
					$team = $Team_r['TeamID'];
					$squad = $Team_r['SquadID'];
					// convert squad name to friendly name
					// first find out if this squad name is the list of squad names
					if(in_array($squad,$squad_array))
					{
						$squad_name = array_search($squad,$squad_array);
					}
					// this squad is missing!
					else
					{
						$squad_name = $squad;
					}
					$country = strtoupper($Team_r['CountryCode']);
					// convert country name to friendly name
					// and compile flag image
					// first find out if this country name is the list of country names
					if(in_array($country,$country_array))
					{
						$country_name = array_search($country,$country_array);
						// compile country flag image
						// if country is null or unknown, use generic image
						if(($country == '') OR ($country == '--'))
						{
							$country_img = './images/flags/none.png';
						}
						else
						{
							$country_img = './images/flags/' . strtolower($country) . '.png';	
						}
					}
					// this country is missing!
					else
					{
						$country_name = $country;
						$country_img = './images/flags/none.png';
					}
					// if player is 'loading in', the style is different
					if($this_team == 0)
					{
						echo '
						<tr>
						<td width="4%" class="count"><span class="information">' . $count . '</span></td>
						<td class="tablecontents" width="3%" style="text-align:left"><img src="' . $country_img . '" alt="' . $country_name . '"/></td>
						';
						// if this player has stats in this server, provide a link to their stats page
						if($PlayerID != null)
						{
							echo '<td class="tablecontents" width="93%" style="text-align:left"><a href="./index.php?sid=' . $ServerID . '&amp;pid=' . $PlayerID . '&amp;p=player">' . $player . '</a></td>';
						}
						// otherwise just display their name without a link
						else
						{
							echo '<td class="tablecontents" width="93%" style="text-align:left">' . $player . '</td>';
						}
					}
					else
					{
						echo '
						<tr>
						<td width="4%" class="count"><span class="information">' . $count . '</span></td>
						<td class="tablecontents" width="3%" style="text-align:left"><img src="' . $country_img . '" alt="' . $country_name . '"/></td>
						';
						// if this player has stats in this server, provide a link to their stats page
						if($PlayerID != null)
						{
							echo '<td class="tablecontents" width="37%" style="text-align:left"><a href="./index.php?sid=' . $ServerID . '&amp;pid=' . $PlayerID . '&amp;p=player">' . $player . '</a></td>';
						}
						// otherwise just display their name without a link
						else
						{
							echo '<td class="tablecontents" width="37%" style="text-align:left">' . $player . '</td>';
						}
					}
					// if player is loading in, don't show the score, kills, deaths, or squad name
					if($this_team != 0)
					{
						echo '
						<td class="tablecontents" width="13%" style="text-align:left">' . $score . '</td>
						<td class="tablecontents" width="13%" style="text-align:left">' . $kills . '</td>
						<td class="tablecontents" width="13%" style="text-align:left">' . $deaths . '</td>
						<td class="tablecontents" width="17%" style="text-align:left">' . $squad_name . '</td>
						';
					}
					$count++;
					echo '</tr>';
				}
			}
			// no players were found on this team!
			// some sort of database error must have occured
			// this is bad..
			// playing damage control
			else
			{
				// if player is 'loading in', the style is different
				if($this_team == 0)
				{
					echo '
					<tr>
					<td width="4%" class="count"><span class="information">&nbsp;</span></td>
					<td class="tablecontents" width="3%" style="text-align:left">&nbsp;</td>
					<td class="tablecontents" width="93%" style="text-align:left">An error occurred!</td>
					</tr>
					';
				}
				else
				{
					echo '
					<tr>
					<td width="4%" class="count"><span class="information">&nbsp;</span></td>
					<td class="tablecontents" width="3%" style="text-align:left">&nbsp;</td>
					<td class="tablecontents" width="37%" style="text-align:left">An error occured!</td>
					<td class="tablecontents" width="13%" style="text-align:left">&nbsp;</td>
					<td class="tablecontents" width="13%" style="text-align:left">&nbsp;</td>
					<td class="tablecontents" width="13%" style="text-align:left">&nbsp;</td>
					<td class="tablecontents" width="17%" style="text-align:left">&nbsp;</td>
					</tr>
					';
				}
			}
			echo '</table></td>';
			// the formatting between the "loading in" team and the other actual teams is different
			if($this_team == 0)
			{
				echo '</tr><tr>';
			}
		}
		// remember to track which team we just probed
		$last_team = $this_team;
	}
	// free up player ID query memory
	@mysqli_free_result($PlayerID_q);
	// free up team query memory
	@mysqli_free_result($Team_q);
	echo '
	</tr>
	</table>
	';
}
// free up score board query memory
@mysqli_free_result($Scoreboard_q);

?>
