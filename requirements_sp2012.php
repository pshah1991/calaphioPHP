<?php
/**
 * This file is intended as a kluge to keep the chapter running until I have
 * time to create a real dynamic requirements tracker. -Geoffrey Lee
 */
require("include/includes.php");
require("include/Calendar.class.php");
require("include/Template.class.php");
Template::print_head(array("requirements.css"));
Template::print_body_header('Calendar', 'REQUIREMENTS');
 ca

?>

<script language="javascript" type="text/javascript" src="popup.js"></script>
<div id="requirements">

<?php

/**
 *
 */
function process_attendance($attended, $flaked, $chair)
{
	if (!$attended && !$flaked && $chair) {
		return "Chairing";
	} else if (!$attended && !$flaked && !$chair) {
		return "Signed Up";
	} else if ($flaked) {
		return "Flaked!";
	} else if ($attended && $chair) {
		return "Chaired";
	} else if ($attended) {
		return "Attended";
	} else {
		trigger_error("Woops, something happened behind the scenes that wasn't expected. Please contact the webmaster!", E_USER_ERROR);
		return "";
	}
}

/**
 *
 */
function event_link($event_id, $title)
{
	$popup_width = CALENDAR_POPUP_WIDTH;
	$popup_height = CALENDAR_POPUP_HEIGHT;
	$session_id = session_id(); // JavaScript popups in IE tend to block cookies, so need to explicitly set session id
	return "<a href=\"event.php?id=$event_id&sid=$session_id\" onclick=\"return popup('event.php?id=$event_id&sid=$session_id', $popup_width, $popup_height)\">$title</a>";
}

if ($g_user->is_logged_in()) {
	// Find out if user is a pledge
	$query = new Query(sprintf("SELECT user_id FROM %spledges_sp12 WHERE user_id=%d LIMIT 1", TABLE_PREFIX, $g_user->data['user_id']));
	if ($row = $query->fetch_row()) {
		$is_pledge = true;
	} else {
		$is_pledge = false;
	}
	
	$is_active = !$is_pledge;
	$start_date = strtotime("2011-11-30");
	$end_date = strtotime("2012-4-24");
	$sql_start_date = date("Y-m-d", $start_date);
	$sql_end_date = date("Y-m-d", $end_date);
	$user_id = $g_user->data['user_id'];
	
	if ($is_active) {
		// Retrieve IC events
		$ic_events = "";
		$ic_events_count = 0;
		$query = new Query(sprintf("SELECT %scalendar_event.event_id, title, date, attended, flaked, chair FROM %scalendar_event
			JOIN %scalendar_attend USING (event_id)
			WHERE type_interchapter=TRUE AND deleted=FALSE AND date BETWEEN '%s' AND '%s' AND user_id=%d ORDER BY date ASC",
			TABLE_PREFIX, TABLE_PREFIX,
			TABLE_PREFIX,
			$sql_start_date, $sql_end_date, $user_id));
		while ($row = $query->fetch_row()) {
			$date = date("M d", strtotime($row['date']));
			$attendance = process_attendance($row['attended'], $row['flaked'], $row['chair']);
			$title_link = event_link($row['event_id'], $row['title']);
			$ic_events .= "<tr><td class=\"date\" axis=\"date\">$date</td><td axis=\"title\">$title_link</td><td class=\"attendance\" axis=\"attendance\">$attendance</td><td class=\"hours\" axis=\"hours\"></td></tr>\r\n";
			if ($row['attended']) {
				$ic_events_count++;
			}
		}
		
		// Retrieve Service events
		$service_events = "";
		$service_hours = 0;
		$query = new Query(sprintf("SELECT %scalendar_event.event_id, title, date, attended, flaked, chair, hours, driver FROM %scalendar_event
			JOIN %scalendar_attend USING (event_id)
			WHERE (type_service_chapter=TRUE OR type_service_campus=TRUE OR type_service_community=TRUE OR type_service_country=TRUE OR type_fundraiser=TRUE) AND deleted=FALSE AND date BETWEEN '%s' AND '%s' AND user_id=%d ORDER BY date ASC",
			TABLE_PREFIX, TABLE_PREFIX,
			TABLE_PREFIX,
			$sql_start_date, $sql_end_date, $user_id));
		while ($row = $query->fetch_row()) {
			$date = date("M d", strtotime($row['date']));
			//if ($row['driver']) {
			//	$row['hours'] += 1; // 1 service hour for driving
			//}
			$hours = $row['hours'] ? $row['hours'] . ' hrs' : '';
			if ($row['flaked']) {
				$hours = "-" . $hours;
			}
			$attendance = process_attendance($row['attended'], $row['flaked'], $row['chair']);
			$title_link = event_link($row['event_id'], $row['title']);
			$service_events .= "<tr><td class=\"date\" axis=\"date\">$date</td><td axis=\"title\">$title_link</td><td class=\"attendance\" axis=\"attendance\">$attendance</td><td class=\"hours\" axis=\"hours\">$hours</td></tr>\r\n";
			if ($row['attended'] && is_numeric($row['hours'])) {
				$service_hours += $row['hours'];
			} else if ($row['flaked'] && is_numeric($row['hours'])) {
				$service_hours -= $row['hours'];
			}
		}
		
		// Retrieve Service type Chapter
		$service_type_chapter = "";
		$service_type_chapter_hours = 0;
		$query = new Query(sprintf("SELECT %scalendar_event.event_id, title, date, attended, flaked, chair, hours FROM %scalendar_event
			JOIN %scalendar_attend USING (event_id)
			WHERE type_service_chapter=TRUE AND deleted=FALSE AND date BETWEEN '%s' AND '%s' AND user_id=%d ORDER BY date ASC",
			TABLE_PREFIX, TABLE_PREFIX,
			TABLE_PREFIX,
			$sql_start_date, $sql_end_date, $user_id));
		while ($row = $query->fetch_row()) {
			$date = date("M d", strtotime($row['date']));
			//if ($row['driver']) {
			//	$row['hours'] += 1; // 1 service hour for driving
			//}
			$hours = $row['hours'] ? $row['hours'] . ' hrs' : '';
			if ($row['flaked']) {
				$hours = "-" . $hours;
			}
			$attendance = process_attendance($row['attended'], $row['flaked'], $row['chair']);
			$title_link = event_link($row['event_id'], $row['title']);
			$service_type_chapter .= "<tr><td class=\"date\" axis=\"date\">$date</td><td axis=\"title\">$title_link</td><td class=\"attendance\" axis=\"attendance\">$attendance</td><td class=\"hours\" axis=\"hours\">$hours</td></tr>\r\n";
			if ($row['attended'] && is_numeric($row['hours'])) {
				$service_type_chapter_hours += $row['hours'];
			} else if ($row['flaked'] && is_numeric($row['hours'])) {
				$service_type_chapter_hours -= $row['hours'];
			}
		}

		// Retrieve Service type Campus
		$service_type_campus = "";
		$service_type_campus_hours = 0;
		$query = new Query(sprintf("SELECT %scalendar_event.event_id, title, date, attended, flaked, chair, hours FROM %scalendar_event
			JOIN %scalendar_attend USING (event_id)
			WHERE type_service_campus=TRUE AND deleted=FALSE AND date BETWEEN '%s' AND '%s' AND user_id=%d ORDER BY date ASC",
			TABLE_PREFIX, TABLE_PREFIX,
			TABLE_PREFIX,
			$sql_start_date, $sql_end_date, $user_id));
		while ($row = $query->fetch_row()) {
			$date = date("M d", strtotime($row['date']));
			//if ($row['driver']) {
			//	$row['hours'] += 1; // 1 service hour for driving
			//}
			$hours = $row['hours'] ? $row['hours'] . ' hrs' : '';
			if ($row['flaked']) {
				$hours = "-" . $hours;
			}
			$attendance = process_attendance($row['attended'], $row['flaked'], $row['chair']);
			$title_link = event_link($row['event_id'], $row['title']);
			$service_type_campus .= "<tr><td class=\"date\" axis=\"date\">$date</td><td axis=\"title\">$title_link</td><td class=\"attendance\" axis=\"attendance\">$attendance</td><td class=\"hours\" axis=\"hours\">$hours</td></tr>\r\n";
			if ($row['attended'] && is_numeric($row['hours'])) {
				$service_type_campus_hours += $row['hours'];
			} else if ($row['flaked'] && is_numeric($row['hours'])) {
				$service_type_campus_hours -= $row['hours'];
			}
		}

		// Retrieve Service type Community
		$service_type_community = "";
		$service_type_community_hours = 0;
		$query = new Query(sprintf("SELECT %scalendar_event.event_id, title, date, attended, flaked, chair, hours FROM %scalendar_event
			JOIN %scalendar_attend USING (event_id)
			WHERE type_service_community=TRUE AND deleted=FALSE AND date BETWEEN '%s' AND '%s' AND user_id=%d ORDER BY date ASC",
			TABLE_PREFIX, TABLE_PREFIX,
			TABLE_PREFIX,
			$sql_start_date, $sql_end_date, $user_id));
		while ($row = $query->fetch_row()) {
			$date = date("M d", strtotime($row['date']));
			//if ($row['driver']) {
			//	$row['hours'] += 1; // 1 service hour for driving
			//}
			$hours = $row['hours'] ? $row['hours'] . ' hrs' : '';
			if ($row['flaked']) {
				$hours = "-" . $hours;
			}
			$attendance = process_attendance($row['attended'], $row['flaked'], $row['chair']);
			$title_link = event_link($row['event_id'], $row['title']);
			$service_type_community .= "<tr><td class=\"date\" axis=\"date\">$date</td><td axis=\"title\">$title_link</td><td class=\"attendance\" axis=\"attendance\">$attendance</td><td class=\"hours\" axis=\"hours\">$hours</td></tr>\r\n";
			if ($row['attended'] && is_numeric($row['hours'])) {
				$service_type_community_hours += $row['hours'];
			} else if ($row['flaked'] && is_numeric($row['hours'])) {
				$service_type_community_hours -= $row['hours'];
			}
		}

		// Retrieve Service type Country
		$service_type_country = "";
		$service_type_country_hours = 0;
		$query = new Query(sprintf("SELECT %scalendar_event.event_id, title, date, attended, flaked, chair, hours FROM %scalendar_event
			JOIN %scalendar_attend USING (event_id)
			WHERE type_service_country=TRUE AND deleted=FALSE AND date BETWEEN '%s' AND '%s' AND user_id=%d ORDER BY date ASC",
			TABLE_PREFIX, TABLE_PREFIX,
			TABLE_PREFIX,
			$sql_start_date, $sql_end_date, $user_id));
		while ($row = $query->fetch_row()) {
			$date = date("M d", strtotime($row['date']));
			//if ($row['driver']) {
			//	$row['hours'] += 1; // 1 service hour for driving
			//}
			$hours = $row['hours'] ? $row['hours'] . ' hrs' : '';
			if ($row['flaked']) {
				$hours = "-" . $hours;
			}
			$attendance = process_attendance($row['attended'], $row['flaked'], $row['chair']);
			$title_link = event_link($row['event_id'], $row['title']);
			$service_type_country .= "<tr><td class=\"date\" axis=\"date\">$date</td><td axis=\"title\">$title_link</td><td class=\"attendance\" axis=\"attendance\">$attendance</td><td class=\"hours\" axis=\"hours\">$hours</td></tr>\r\n";
			if ($row['attended'] && is_numeric($row['hours'])) {
				$service_type_country_hours += $row['hours'];
			} else if ($row['flaked'] && is_numeric($row['hours'])) {
				$service_type_country_hours -= $row['hours'];
			}
		}

		// Retrieve Service type count
		$service_type_count = 0;
		if ($service_type_chapter_hours > 0) {
			$service_type_count++;
		}
		if ($service_type_campus_hours > 0) {
			$service_type_count++;
		}
		if ($service_type_community_hours > 0) {
			$service_type_count++;
		}
		if ($service_type_country_hours > 0) {
			$service_type_count++;
		}
		
		// Retrieve Fellowship events
		$fellowship_events = "";
		$fellowship_events_count = 0;
		$query = new Query(sprintf("SELECT %scalendar_event.event_id, title, date, attended, flaked, chair FROM %scalendar_event
			JOIN %scalendar_attend USING (event_id)
			WHERE type_fellowship=TRUE AND deleted=FALSE AND date BETWEEN '%s' AND '%s' AND user_id=%d ORDER BY date ASC",
			TABLE_PREFIX, TABLE_PREFIX,
			TABLE_PREFIX,
			$sql_start_date, $sql_end_date, $user_id));
		while ($row = $query->fetch_row()) {
			$date = date("M d", strtotime($row['date']));
			$attendance = process_attendance($row['attended'], $row['flaked'], $row['chair']);
			$title_link = event_link($row['event_id'], $row['title']);
			$fellowship_events .= "<tr><td class=\"date\" axis=\"date\">$date</td><td axis=\"title\">$title_link</td><td class=\"attendance\" axis=\"attendance\">$attendance</td><td class=\"hours\" axis=\"hours\"></td></tr>\r\n";
			if ($row['attended']) {
				$fellowship_events_count++;
			} else if ($row['flaked']) {
				$fellowship_events_count--;
			}
		}
		
		// Retrieve Fundraiser events
		$fundraiser_events = "";
		$fundraiser_events_count = 0;
		$query = new Query(sprintf("SELECT %scalendar_event.event_id, title, date, attended, flaked, chair FROM %scalendar_event
			JOIN %scalendar_attend USING (event_id)
			WHERE type_fundraiser=TRUE AND deleted=FALSE AND date BETWEEN '%s' AND '%s' AND user_id=%d ORDER BY date ASC",
			TABLE_PREFIX, TABLE_PREFIX,
			TABLE_PREFIX,
			$sql_start_date, $sql_end_date, $user_id));
		while ($row = $query->fetch_row()) {
			$date = date("M d", strtotime($row['date']));
			$attendance = process_attendance($row['attended'], $row['flaked'], $row['chair']);
			$title_link = event_link($row['event_id'], $row['title']);
			$fundraiser_events .= "<tr><td class=\"date\" axis=\"date\">$date</td><td axis=\"title\">$title_link</td><td class=\"attendance\" axis=\"attendance\">$attendance</td><td class=\"hours\" axis=\"hours\"></td></tr>\r\n";
			if ($row['attended']) {
				$fundraiser_events_count++;
			}
		}
		
		// Retrieve Election events
		$election_events = "";
		$election_events_count = 0;
		$query = new Query(sprintf("SELECT %scalendar_event.event_id, title, date, attended, flaked, chair FROM %scalendar_event
			JOIN %scalendar_attend USING (event_id)
			JOIN %scalendar_event_type_custom ON (type_id=type_custom AND type_name='Elections')
			WHERE deleted=FALSE AND date BETWEEN '%s' AND '%s' AND user_id=%d ORDER BY date ASC",
			TABLE_PREFIX, TABLE_PREFIX,
			TABLE_PREFIX,
			TABLE_PREFIX,
			$sql_start_date, $sql_end_date, $user_id));
		while ($row = $query->fetch_row()) {
			$date = date("M d", strtotime($row['date']));
			$attendance = process_attendance($row['attended'], $row['flaked'], $row['chair']);
			$title_link = event_link($row['event_id'], $row['title']);
			$election_events .= "<tr><td class=\"date\" axis=\"date\">$date</td><td axis=\"title\">$title_link</td><td class=\"attendance\" axis=\"attendance\">$attendance</td><td class=\"hours\" axis=\"hours\"></td></tr>\r\n";
			if ($row['attended']) {
				$election_events_count++;
			}
		}
		
		// Retrieve Tabling hours
		$tabling_events = "";
		$tabling_hours = 0;
		$query = new Query(sprintf("SELECT %scalendar_event.event_id, title, date, attended, flaked, chair, hours FROM %scalendar_event
			JOIN %scalendar_attend USING (event_id)
			JOIN %scalendar_event_type_custom ON (type_id=type_custom AND type_name='Tabling')
			WHERE deleted=FALSE AND date BETWEEN '%s' AND '%s' AND user_id=%d ORDER BY date ASC",
			TABLE_PREFIX, TABLE_PREFIX,
			TABLE_PREFIX,
			TABLE_PREFIX,
			$sql_start_date, $sql_end_date, $user_id));
		while ($row = $query->fetch_row()) {
			$date = date("M d", strtotime($row['date']));
			$hours = $row['hours'] ? $row['hours'] . ' hrs' : '';
			$attendance = process_attendance($row['attended'], $row['flaked'], $row['chair']);
			$title_link = event_link($row['event_id'], $row['title']);
			$tabling_events .= "<tr><td class=\"date\" axis=\"date\">$date</td><td axis=\"title\">$title_link</td><td class=\"attendance\" axis=\"attendance\">$attendance</td><td class=\"hours\" axis=\"hours\">$hours</td></tr>\r\n";
			if ($row['attended']) {
				$tabling_hours += $row['hours'];
			} else if ($row['flaked']) {
				$tabling_hours -= $row['hours'];
			}
		}
		
		// Retrieve Rush events
		$rush_events = "";
		$rush_events_count = 0;
		$query = new Query(sprintf("SELECT %scalendar_event.event_id, title, date, attended, flaked, chair FROM %scalendar_event
			JOIN %scalendar_attend USING (event_id)
			WHERE type_rush=TRUE AND deleted=FALSE AND date BETWEEN '%s' AND '%s' AND user_id=%d ORDER BY date ASC",
			TABLE_PREFIX, TABLE_PREFIX,
			TABLE_PREFIX,
			$sql_start_date, $sql_end_date, $user_id));
		while ($row = $query->fetch_row()) {
			$date = date("M d", strtotime($row['date']));
			$attendance = process_attendance($row['attended'], $row['flaked'], $row['chair']);
			$title_link = event_link($row['event_id'], $row['title']);
			$rush_events .= "<tr><td class=\"date\" axis=\"date\">$date</td><td axis=\"title\">$title_link</td><td class=\"attendance\" axis=\"attendance\">$attendance</td><td class=\"hours\" axis=\"hours\"></td></tr>\r\n";
			if ($row['attended']) {
				$rush_events_count++;
			}
		}
		
		// Retrieve Chapter events
		$chapter_events = "";
		$chapter_events_count = 0;
		$query = new Query(sprintf("SELECT %scalendar_event.event_id, title, date, attended, flaked, chair FROM %scalendar_event
			JOIN %scalendar_attend USING (event_id)
			JOIN %scalendar_event_type_custom ON (type_id=type_custom AND type_name IN ('Ritual', 'Activation', 'Chapter Forum'))
			WHERE deleted=FALSE AND date BETWEEN '%s' AND '%s' AND user_id=%d ORDER BY date ASC",
			TABLE_PREFIX, TABLE_PREFIX,
			TABLE_PREFIX,
			TABLE_PREFIX,
			date("Y-m-d", strtotime("2011-12-3")), $sql_end_date, $user_id));
		while ($row = $query->fetch_row()) {
			$date = date("M d", strtotime($row['date']));
			$attendance = process_attendance($row['attended'], $row['flaked'], $row['chair']);
			$title_link = event_link($row['event_id'], $row['title']);
			$chapter_events .= "<tr><td class=\"date\" axis=\"date\">$date</td><td axis=\"title\">$title_link</td><td class=\"attendance\" axis=\"attendance\">$attendance</td><td class=\"hours\" axis=\"hours\"></td></tr>\r\n";
			if ($row['attended']) {
				$chapter_events_count++;
			}
		}
		
		// Retrieve Chapter Meeting events
		$chaptermeeting_events = "";
		$chaptermeeting_events_count = 0;
		$query = new Query(sprintf("SELECT %scalendar_event.event_id, title, date, attended, flaked, chair FROM %scalendar_event
			JOIN %scalendar_attend USING (event_id)
			WHERE type_active_meeting=TRUE AND deleted=FALSE AND date BETWEEN '%s' AND '%s' AND user_id=%d ORDER BY date ASC",
			TABLE_PREFIX, TABLE_PREFIX,
			TABLE_PREFIX,
			$sql_start_date, $sql_end_date, $user_id));
		while ($row = $query->fetch_row()) {
			$date = date("M d", strtotime($row['date']));
			$attendance = process_attendance($row['attended'], $row['flaked'], $row['chair']);
			$title_link = event_link($row['event_id'], $row['title']);
			$chaptermeeting_events .= "<tr><td class=\"date\" axis=\"date\">$date</td><td axis=\"title\">$title_link</td><td class=\"attendance\" axis=\"attendance\">$attendance</td><td class=\"hours\" axis=\"hours\"></td></tr>\r\n";
			if ($row['attended']) {
				$chaptermeeting_events_count++;
			}
		}
		
		// Retrieve Active events
		$active_events = "";
		$active_events_count = 0;
		$query = new Query(sprintf("SELECT %scalendar_event.event_id, title, date, attended, flaked, chair FROM %scalendar_event
			JOIN %scalendar_attend USING (event_id)
			WHERE type_custom=12 AND deleted=FALSE AND date BETWEEN '%s' AND '%s' AND user_id=%d ORDER BY date ASC",
			TABLE_PREFIX, TABLE_PREFIX,
			TABLE_PREFIX,
			$sql_start_date, $sql_end_date, $user_id));
		while ($row = $query->fetch_row()) {
			$date = date("M d", strtotime($row['date']));
			$attendance = process_attendance($row['attended'], $row['flaked'], $row['chair']);
			$title_link = event_link($row['event_id'], $row['title']);
			$active_events .= "<tr><td class=\"date\" axis=\"date\">$date</td><td axis=\"title\">$title_link</td><td class=\"attendance\" axis=\"attendance\">$attendance</td><td class=\"hours\" axis=\"hours\"></td></tr>\r\n";
			if ($row['attended']) {
				$active_events_count++;
			} else if ($row['flaked']) {
				$active_events_count--;
			}
		}
		
		/*// Retrieve Driver Miles
		$driving_events = "";
		$miles = 0;
		$query = new Query(sprintf("SELECT %scalendar_event.event_id, title, date, attended, flaked, chair, hours, driver, miles FROM %scalendar_event
					JOIN %scalendar_attend USING (event_id)
					WHERE deleted=FALSE AND date BETWEEN '%s' AND '%s' AND miles<>0 AND user_id=%d ORDER BY date ASC",
		TABLE_PREFIX, TABLE_PREFIX,
		TABLE_PREFIX,
		$sql_start_date, $sql_end_date, $user_id));
		while ($row = $query->fetch_row()) {
			$date = date("M d", strtotime($row['date']));
			$miles = $row['miles'] . ' miles';
			$attendance = process_attendance($row['attended'], $row['flaked'], $row['chair']);
			$title_link = event_link($row['event_id'], $row['title']);
			$driving_events .= "<tr><td class=\"date\" axis=\"date\">$date</td><td axis=\"title\">$title_link</td><td class=\"attendance\" axis=\"attendance\">$attendance</td><td class=\"hours\" axis=\"hours\">$miles</td></tr>\r\n";
		}*/
		
		echo <<<DOCHERE
<table>
<caption>Attend 1 IC event - You have completed $ic_events_count</caption>
$ic_events
</table>
<table>
<caption>Attend 3 out of 5 rush events - You have completed $rush_events_count</caption>
$rush_events
</table>
<table>
<caption>Complete 3 hours tabling - You have completed $tabling_hours hours</caption>
$tabling_events
</table>
<table>
<caption>Attend 1 fundraiser - You have completed $fundraiser_events_count</caption>
$fundraiser_events
</table>
<table>
<caption>Attend 2/4 chapter events (Ritual, Activation, 2 Forums) - You have completed $chapter_events_count</caption>
$chapter_events
</table>
<table>
<caption>Attend 5/8 chapter meetings - You have completed $chaptermeeting_events_count</caption>
$chaptermeeting_events
</table>
<table>
<caption>Attend both elections - You have completed $election_events_count</caption>
$election_events
</table>
<table>
<caption>Complete 25 service hours - You have completed $service_hours hours<br>
Complete the 4 C's of service - You have completed $service_type_count/4 service types<br>
</caption>
</table>
<table>
<caption style="background-color: #FFFFFF;border: none;text-decoration: underline;">Service to the Chapter</caption>
$service_type_chapter
</table>
<table>
<caption style="background-color: #FFFFFF;border: none;text-decoration: underline;">Service to the Campus</caption>
$service_type_campus
</table>
<table>
<caption style="background-color: #FFFFFF;border: none;text-decoration: underline;">Service to the Community</caption>
$service_type_community
</table>
<table>
<caption style="background-color: #FFFFFF;border: none;text-decoration: underline;">Service to the Country</caption>
$service_type_country
</table>
<table>
<caption>Attend 5 fellowships - You have completed $fellowship_events_count</caption>
$fellowship_events
</table>
<table>
<caption>Attend 1 active event - You have completed $active_events_count</caption>
$active_events
</table>
<table>
<caption>Pay your \$45 active dues (\$55 at CM3, \$65 at CM5)</caption>
</table>


<table width="100%">
<caption>Complete 3 leadership credits - You can get credit by doing any of the following:</caption>
<tr><td axis="name">Serving on a committee - <a href="js_chairs.php">Search for one here</a></td></tr>
<tr><td axis="name">Chairing an event</td></tr>
<tr><td axis="name">Attending a LEADS workshop</td></tr>
<tr><td axis="name">Driving to 3 events </td></tr>
<tr><td axis="name">Attending Nationals or Regionals</td></tr>
<tr><td axis="name">Participating in Leadership Workshop</td></tr>
<tr><td axis="name">Attending Mid-semester or End-of-semester forum (if not used as a chapter event)</td></tr>
<tr><td axis="name">Being a Big, Aunt, or Uncle (<b>MUST ATTEND</b> Campout, Broomball, and 2 out of 3 Interfams)</td></tr>
</table>

<a href="requirements_fa2011.php">Fall 2011 (CPZ) Requirements ></a>

DOCHERE;
	} else if ($is_pledge) {
		// Retrieve IC events
		$ic_events = "";
		$ic_events_count = 0;
		$query = new Query(sprintf("SELECT %scalendar_event.event_id, title, date, attended, flaked, chair FROM %scalendar_event
			JOIN %scalendar_attend USING (event_id)
			WHERE type_interchapter=TRUE AND deleted=FALSE AND date BETWEEN '%s' AND '%s' AND user_id=%d ORDER BY date ASC",
			TABLE_PREFIX, TABLE_PREFIX,
			TABLE_PREFIX,
			$sql_start_date, $sql_end_date, $user_id));
		while ($row = $query->fetch_row()) {
			$date = date("M d", strtotime($row['date']));
			$attendance = process_attendance($row['attended'], $row['flaked'], $row['chair']);
			$title_link = event_link($row['event_id'], $row['title']);
			$ic_events .= "<tr><td class=\"date\" axis=\"date\">$date</td><td axis=\"title\">$title_link</td><td class=\"attendance\" axis=\"attendance\">$attendance</td><td class=\"hours\" axis=\"hours\"></td></tr>\r\n";
			if ($row['attended']) {
				$ic_events_count++;
			}
		}
		
		// Retrieve Service events
		$service_events = "";
		$service_hours = 0;
		$query = new Query(sprintf("SELECT %scalendar_event.event_id, title, date, attended, flaked, chair, hours, driver FROM %scalendar_event
			JOIN %scalendar_attend USING (event_id)
			WHERE (type_service_chapter=TRUE OR type_service_campus=TRUE OR type_service_community=TRUE OR type_service_country=TRUE OR type_fundraiser=TRUE) AND deleted=FALSE AND date BETWEEN '%s' AND '%s' AND user_id=%d ORDER BY date ASC",
			TABLE_PREFIX, TABLE_PREFIX,
			TABLE_PREFIX,
			$sql_start_date, $sql_end_date, $user_id));
		while ($row = $query->fetch_row()) {
			$date = date("M d", strtotime($row['date']));
			//if ($row['driver']) {
			//	$row['hours'] += 1; // 1 service hour for driving
			//}
			$hours = $row['hours'] ? $row['hours'] . ' hrs' : '';
			if ($row['flaked']) {
				$hours = "-" . $hours;
			}
			$attendance = process_attendance($row['attended'], $row['flaked'], $row['chair']);
			$title_link = event_link($row['event_id'], $row['title']);
			$service_events .= "<tr><td class=\"date\" axis=\"date\">$date</td><td axis=\"title\">$title_link</td><td class=\"attendance\" axis=\"attendance\">$attendance</td><td class=\"hours\" axis=\"hours\">$hours</td></tr>\r\n";
			if ($row['attended'] && is_numeric($row['hours'])) {
				$service_hours += $row['hours'];
			} else if ($row['flaked'] && is_numeric($row['hours'])) {
				$service_hours -= $row['hours'];
			}
		}
				// Retrieve Fellowship events
		$fellowship_events = "";
		$fellowship_events_count = 0;
		$query = new Query(sprintf("SELECT %scalendar_event.event_id, title, date, attended, flaked, chair FROM %scalendar_event
			JOIN %scalendar_attend USING (event_id)
			WHERE type_fellowship=TRUE AND deleted=FALSE AND date BETWEEN '%s' AND '%s' AND user_id=%d ORDER BY date ASC",
			TABLE_PREFIX, TABLE_PREFIX,
			TABLE_PREFIX,
			$sql_start_date, $sql_end_date, $user_id));
		while ($row = $query->fetch_row()) {
			$date = date("M d", strtotime($row['date']));
			$attendance = process_attendance($row['attended'], $row['flaked'], $row['chair']);
			$title_link = event_link($row['event_id'], $row['title']);
			$fellowship_events .= "<tr><td class=\"date\" axis=\"date\">$date</td><td axis=\"title\">$title_link</td><td class=\"attendance\" axis=\"attendance\">$attendance</td><td class=\"hours\" axis=\"hours\"></td></tr>\r\n";
			if ($row['attended']) {
				$fellowship_events_count++;
			}
		}
		
		// Retrieve Fundraiser events
		$fundraiser_events = "";
		$fundraiser_events_count = 0;
		$query = new Query(sprintf("SELECT %scalendar_event.event_id, title, date, attended, flaked, chair FROM %scalendar_event
			JOIN %scalendar_attend USING (event_id)
			WHERE type_fundraiser=TRUE AND deleted=FALSE AND date BETWEEN '%s' AND '%s' AND user_id=%d ORDER BY date ASC",
			TABLE_PREFIX, TABLE_PREFIX,
			TABLE_PREFIX,
			$sql_start_date, $sql_end_date, $user_id));
		while ($row = $query->fetch_row()) {
			$date = date("M d", strtotime($row['date']));
			$attendance = process_attendance($row['attended'], $row['flaked'], $row['chair']);
			$title_link = event_link($row['event_id'], $row['title']);
			$fundraiser_events .= "<tr><td class=\"date\" axis=\"date\">$date</td><td axis=\"title\">$title_link</td><td class=\"attendance\" axis=\"attendance\">$attendance</td><td class=\"hours\" axis=\"hours\"></td></tr>\r\n";
			if ($row['attended']) {
				$fundraiser_events_count++;
			}
		}
		
		// Retrieve Election events
		$election_events = "";
		$election_events_count = 0;
		$query = new Query(sprintf("SELECT %scalendar_event.event_id, title, date, attended, flaked, chair FROM %scalendar_event
			JOIN %scalendar_attend USING (event_id)
			JOIN %scalendar_event_type_custom ON (type_id=type_custom AND type_name='Elections')
			WHERE deleted=FALSE AND date BETWEEN '%s' AND '%s' AND user_id=%d ORDER BY date ASC",
			TABLE_PREFIX, TABLE_PREFIX,
			TABLE_PREFIX,
			TABLE_PREFIX,
			$sql_start_date, $sql_end_date, $user_id));
		while ($row = $query->fetch_row()) {
			$date = date("M d", strtotime($row['date']));
			$attendance = process_attendance($row['attended'], $row['flaked'], $row['chair']);
			$title_link = event_link($row['event_id'], $row['title']);
			$election_events .= "<tr><td class=\"date\" axis=\"date\">$date</td><td axis=\"title\">$title_link</td><td class=\"attendance\" axis=\"attendance\">$attendance</td><td class=\"hours\" axis=\"hours\"></td></tr>\r\n";
			if ($row['attended']) {
				$election_events_count++;
			}
		}
		
		// Retrieve Interfam events
		$interfam_events = "";
		$interfam_events_count = 0;
		$query = new Query(sprintf("SELECT %scalendar_event.event_id, title, date, attended, flaked, chair FROM %scalendar_event
			JOIN %scalendar_attend USING (event_id)
			JOIN %scalendar_event_type_custom ON (type_id=type_custom AND type_name='Interfam')
			WHERE deleted=FALSE AND date BETWEEN '%s' AND '%s' AND user_id=%d ORDER BY date ASC",
			TABLE_PREFIX, TABLE_PREFIX,
			TABLE_PREFIX,
			TABLE_PREFIX,
			$sql_start_date, $sql_end_date, $user_id));
		while ($row = $query->fetch_row()) {
			$date = date("M d", strtotime($row['date']));
			$attendance = process_attendance($row['attended'], $row['flaked'], $row['chair']);
			$title_link = event_link($row['event_id'], $row['title']);
			$interfam_events .= "<tr><td class=\"date\" axis=\"date\">$date</td><td axis=\"title\">$title_link</td><td class=\"attendance\" axis=\"attendance\">$attendance</td><td class=\"hours\" axis=\"hours\"></td></tr>\r\n";
			if ($row['attended']) {
				$interfam_events_count++;
			}
		}
		
		// Retrieve ExComm Meeting events
		$excomm_events = "";
		$excomm_events_count = 0;
		$query = new Query(sprintf("SELECT %scalendar_event.event_id, title, date, attended, flaked, chair FROM %scalendar_event
			JOIN %scalendar_attend USING (event_id)
			JOIN %scalendar_event_type_custom ON (type_id=type_custom AND type_name='ExComm Meeting')
			WHERE deleted=FALSE AND date BETWEEN '%s' AND '%s' AND user_id=%d ORDER BY date ASC",
			TABLE_PREFIX, TABLE_PREFIX,
			TABLE_PREFIX,
			TABLE_PREFIX,
			$sql_start_date, $sql_end_date, $user_id));
		while ($row = $query->fetch_row()) {
			$date = date("M d", strtotime($row['date']));
			$attendance = process_attendance($row['attended'], $row['flaked'], $row['chair']);
			$title_link = event_link($row['event_id'], $row['title']);
			$excomm_events .= "<tr><td class=\"date\" axis=\"date\">$date</td><td axis=\"title\">$title_link</td><td class=\"attendance\" axis=\"attendance\">$attendance</td><td class=\"hours\" axis=\"hours\"></td></tr>\r\n";
			if ($row['attended']) {
				$excomm_events_count++;
			}
		}
		
		// Retrieve Chapter Meeting events
		$chaptermeeting_events = "";
		$chaptermeeting_events_count = 0;
		$query = new Query(sprintf("SELECT %scalendar_event.event_id, title, date, attended, flaked, chair FROM %scalendar_event
			JOIN %scalendar_attend USING (event_id)
			WHERE type_active_meeting=TRUE AND deleted=FALSE AND date BETWEEN '%s' AND '%s' AND user_id=%d ORDER BY date ASC",
			TABLE_PREFIX, TABLE_PREFIX,
			TABLE_PREFIX,
			$sql_start_date, $sql_end_date, $user_id));
		while ($row = $query->fetch_row()) {
			$date = date("M d", strtotime($row['date']));
			$attendance = process_attendance($row['attended'], $row['flaked'], $row['chair']);
			$title_link = event_link($row['event_id'], $row['title']);
			$chaptermeeting_events .= "<tr><td class=\"date\" axis=\"date\">$date</td><td axis=\"title\">$title_link</td><td class=\"attendance\" axis=\"attendance\">$attendance</td><td class=\"hours\" axis=\"hours\"></td></tr>\r\n";
			if ($row['attended']) {
				$chaptermeeting_events_count++;
			}
		}
		
		echo <<<DOCHERE
<table>
<caption>Attend 1 IC event - You have completed $ic_events_count</caption>
$ic_events
</table>
<table>
<caption>Attend 1 fundraiser - You have completed $fundraiser_events_count</caption>
$fundraiser_events
</table>
<table>
<caption>Attend 4/5 chapter meetings - You have completed $chaptermeeting_events_count</caption>
$chaptermeeting_events
</table>
<table>
<caption>Attend both elections - You have completed $election_events_count</caption>
$election_events
</table>
<table>
<caption>Attend 1 ExComm meeting - You have completed $excomm_events_count</caption>
$excomm_events
</table>
<table>
<caption>Attend 2/3 Interfams - You have completed $interfam_events_count</caption>
$interfam_events
</table>
<table>
<caption>Complete 20 service hours - You have completed $service_hours hours</caption>
$service_events
</table>
<table>
<caption>Attend 5 fellowships - You have completed $fellowship_events_count</caption>
$fellowship_events
</table>
<table>
<caption>Other pledge requirements:</caption>
<tr><td axis="name">Attend Ritual</td></tr>
<tr><td axis="name">Join a Pledge committee</td></tr>
<tr><td axis="name">Join an ExComm committee - <a href="js_chairs.php">Search for one here</a></td></tr>
<tr><td axis="name">Complete committee requirements</td></tr>
<tr><td axis="name">Attend both pledge class service projects</td></tr>
<tr><td axis="name">Attend Sib Social</td></tr>
<tr><td axis="name">Attend Broomball</td></tr>
<tr><td axis="name">Attend Talent Show</td></tr>
<tr><td axis="name">Attend Campout</td></tr>
<tr><td axis="name">Attend all pledge reviews</td></tr>
<tr><td axis="name">Pass all pledge quizzes</td></tr>
<tr><td axis="name">Fill out 40 interviews, take a picture with each interviewee, and upload onto photobucket</td></tr>
<tr><td axis="name">Write 4 reflections (2 page, double spaced)</td></tr>
<tr><td axis="name">Complete signature page - <a href="/documents/sp12/Pexcomm OH and Signatures.pdf">Click here for office hours/requirements</a></td></tr>
<tr><td axis="name">Serving on a committee
<tr><td axis="name">Pass pledge test</td></tr>
<tr><td axis="name">Attend activation</td></tr>
</table>

DOCHERE;
	} else {
		// User is neither an active or a pledge
	}
} else {
	trigger_error("You must be logged in to view your requirements.", E_USER_ERROR);
}
?>

</div>

<?php
Template::print_body_footer();
Template::print_disclaimer();
?>