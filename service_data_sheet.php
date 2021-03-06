<?php
require("include/includes.php");
require("include/Template.class.php");
Template::print_head(array('admin_view_requirements.css'));
Template::print_body_header('Home', 'ADMIN');

function query_attending_services($start, $end){
	$select_expression = sprintf("SELECT %scalendar_event.event_id, %scalendar_event.title, %scalendar_event.date, %scalendar_event.type_service_country, %scalendar_event.type_service_community, %scalendar_event.type_service_campus, %scalendar_event.type_service_chapter, %scalendar_event.deleted, %scalendar_event.auto_deleted, count(%scalendar_attend.event_id) AS total", TABLE_PREFIX, TABLE_PREFIX, TABLE_PREFIX, TABLE_PREFIX, TABLE_PREFIX, TABLE_PREFIX, TABLE_PREFIX, TABLE_PREFIX, TABLE_PREFIX,TABLE_PREFIX);
			
	new Query(sprintf("CREATE TEMPORARY TABLE table_attend AS(%s FROM %scalendar_event LEFT JOIN %scalendar_attend ON %scalendar_event.event_id=%scalendar_attend.event_id WHERE date >='%s' AND date <='%s' AND (%scalendar_event.type_service_country=TRUE OR %scalendar_event.type_service_chapter=TRUE OR %scalendar_event.type_service_campus OR %scalendar_event.type_service_community) GROUP BY %scalendar_event.event_id)",$select_expression, TABLE_PREFIX, TABLE_PREFIX, TABLE_PREFIX, TABLE_PREFIX, $start, $end, TABLE_PREFIX, TABLE_PREFIX, TABLE_PREFIX, TABLE_PREFIX, TABLE_PREFIX));
	return new Query(sprintf("SELECT * FROM table_attend"));
}

$start = '2011-8-30';
$end = '2011-11-29';
$query = new Query("
		SELECT sum(hours) AS hours FROM apo_calendar_event
			JOIN apo_calendar_attend USING (event_id)
			WHERE (type_service_chapter=TRUE OR type_service_campus=TRUE OR type_service_community=TRUE OR type_service_country=TRUE OR type_fundraiser=TRUE)
			AND flaked=FALSE  AND deleted=FALSE AND date BETWEEN '$start' AND '$end'");
$row = $query->fetch_row();
$fall11 = $row['hours'];
$fall11_dates = date("M d, Y", strtotime($start)) . " - " . date("M d, Y", strtotime($end));


$query = new Query("SELECT user_id, count(*) AS count FROM apo_actives_fa11");
$row = $query->fetch_row();
$fall11_actives = $row['count'];

$query = new Query("SELECT user_id, count(*) AS count FROM apo_pledges_fa11");
$row = $query->fetch_row();
$fall11_pledges = $row['count'];

$query = new Query("
		SELECT count(*) AS count FROM apo_calendar_event
			WHERE (type_service_chapter=TRUE OR type_service_campus=TRUE OR type_service_community=TRUE OR type_service_country=TRUE OR type_fundraiser=TRUE)
			 AND deleted=FALSE AND date BETWEEN '$start' AND '$end'");
$row = $query->fetch_row();
$fall11_projects = $row['count'];

$query = new Query("
		SELECT count(*) AS count FROM apo_calendar_event
			WHERE (type_fellowship=TRUE)
			 AND deleted=FALSE AND date BETWEEN '$start' AND '$end'");
$row = $query->fetch_row();
$fall11_fellowships = $row['count'];

$start = '2012-1-17';
$end = '2012-4-24';
$query = new Query("
		SELECT sum(hours) AS hours FROM apo_calendar_event
			JOIN apo_calendar_attend USING (event_id)
			WHERE (type_service_chapter=TRUE OR type_service_campus=TRUE OR type_service_community=TRUE OR type_service_country=TRUE OR type_fundraiser=TRUE)
			AND flaked=FALSE  AND deleted=FALSE AND date BETWEEN '$start' AND '$end'");
$row = $query->fetch_row();
$spring12 = $row['hours'];
$spring12_dates = date("M d, Y", strtotime($start)) . " - " . date("M d, Y", strtotime($end));


$query = new Query("SELECT user_id, count(*) AS count FROM apo_actives_sp12");
$row = $query->fetch_row();
$spring12_actives = $row['count'];

$query = new Query("SELECT user_id, count(*) AS count FROM apo_pledges_sp12");
$row = $query->fetch_row();
$spring12_pledges = $row['count'];

$query = new Query("
		SELECT count(*) AS count FROM apo_calendar_event
			WHERE (type_service_chapter=TRUE OR type_service_campus=TRUE OR type_service_community=TRUE OR type_service_country=TRUE OR type_fundraiser=TRUE)
			 AND deleted=FALSE AND date BETWEEN '$start' AND '$end'");
$row = $query->fetch_row();
$spring12_projects = $row['count'];

$query = new Query("
		SELECT count(*) AS count FROM apo_calendar_event
			WHERE (type_fellowship=TRUE)
			 AND deleted=FALSE AND date BETWEEN '$start' AND '$end'");
$row = $query->fetch_row();
$spring12_fellowships = $row['count'];

$start = '2012-8-28';
$end = '2012-11-27';
$query = new Query("
		SELECT sum(hours) AS hours FROM apo_calendar_event
			JOIN apo_calendar_attend USING (event_id)
			WHERE (type_service_chapter=TRUE OR type_service_campus=TRUE OR type_service_community=TRUE OR type_service_country=TRUE OR type_fundraiser=TRUE)
			AND flaked=FALSE  AND deleted=FALSE AND date BETWEEN '$start' AND '$end'");
$row = $query->fetch_row();
$fall12 = $row['hours'];
$fall12_dates = date("M d, Y", strtotime($start)) . " - " . date("M d, Y", strtotime($end));


$query = new Query("SELECT user_id, count(*) AS count FROM apo_actives_fa12");
$row = $query->fetch_row();
$fall12_actives = $row['count'];

$query = new Query("SELECT user_id, count(*) AS count FROM apo_pledges_fa12");
$row = $query->fetch_row();
$fall12_pledges = $row['count'];

$query = new Query("
		SELECT count(*) AS count FROM apo_calendar_event
			WHERE (type_service_chapter=TRUE OR type_service_campus=TRUE OR type_service_community=TRUE OR type_service_country=TRUE OR type_fundraiser=TRUE)
			 AND deleted=FALSE AND date BETWEEN '$start' AND '$end'");
$row = $query->fetch_row();
$fall12_projects = $row['count'];

$query = new Query("
		SELECT count(*) AS count FROM apo_calendar_event
			WHERE (type_fellowship=TRUE)
			 AND deleted=FALSE AND date BETWEEN '$start' AND '$end'");
$row = $query->fetch_row();
$fall12_fellowships = $row['count'];

$start = '2013-1-22';
$end = '2013-4-30';
$query = new Query("
		SELECT sum(hours) AS hours FROM apo_calendar_event
			JOIN apo_calendar_attend USING (event_id)
			WHERE (type_service_chapter=TRUE OR type_service_campus=TRUE OR type_service_community=TRUE OR type_service_country=TRUE OR type_fundraiser=TRUE)
			AND flaked=FALSE  AND deleted=FALSE AND date BETWEEN '$start' AND '$end'");
$row = $query->fetch_row();
$spring13 = $row['hours'];
$spring13_dates = date("M d, Y", strtotime($start)) . " - " . date("M d, Y", strtotime($end));

$query = new Query("SELECT user_id, count(*) AS count FROM apo_actives_sp13");
$row = $query->fetch_row();
$spring13_actives = $row['count'];

$query = new Query("SELECT user_id, count(*) AS count FROM apo_pledges_sp13");
$row = $query->fetch_row();
$spring13_pledges = $row['count'];

$query = new Query("
		SELECT count(*) AS count FROM apo_calendar_event
			WHERE (type_service_chapter=TRUE OR type_service_campus=TRUE OR type_service_community=TRUE OR type_service_country=TRUE OR type_fundraiser=TRUE)
			 AND deleted=FALSE AND date BETWEEN '$start' AND '$end'");
$row = $query->fetch_row();
$spring13_projects = $row['count'];

$query = new Query("
		SELECT count(*) AS count FROM apo_calendar_event
			WHERE (type_fellowship=TRUE)
			 AND deleted=FALSE AND date BETWEEN '$start' AND '$end'");
$row = $query->fetch_row();
$spring13_fellowships = $row['count'];

$start = '2013-9-3';
$end = '2013-12-3';
$query = new Query("
		SELECT sum(hours) AS hours FROM apo_calendar_event
			JOIN apo_calendar_attend USING (event_id)
			WHERE (type_service_chapter=TRUE OR type_service_campus=TRUE OR type_service_community=TRUE OR type_service_country=TRUE OR type_fundraiser=TRUE)
			AND flaked=FALSE  AND deleted=FALSE AND date BETWEEN '$start' AND '$end'");
$row = $query->fetch_row();
$fall13 = $row['hours'];
$fall13_dates = date("M d, Y", strtotime($start)) . " - " . date("M d, Y", strtotime($end));

$query = new Query("SELECT user_id, count(*) AS count FROM apo_actives_fa13");
$row = $query->fetch_row();
$fall13_actives = $row['count'];

$query = new Query("SELECT user_id, count(*) AS count FROM apo_pledges_fa13");
$row = $query->fetch_row();
$fall13_pledges = $row['count'];

$query = new Query("
		SELECT count(*) AS count FROM apo_calendar_event
			WHERE (type_service_chapter=TRUE OR type_service_campus=TRUE OR type_service_community=TRUE OR type_service_country=TRUE OR type_fundraiser=TRUE)
			 AND deleted=FALSE AND date BETWEEN '$start' AND '$end'");
$row = $query->fetch_row();
$fall13_projects = $row['count'];

$query = new Query("
		SELECT count(*) AS count FROM apo_calendar_event
			WHERE (type_fellowship=TRUE)
			 AND deleted=FALSE AND date BETWEEN '$start' AND '$end'");
$row = $query->fetch_row();
$fall13_fellowships = $row['count'];

$start = '2014-1-21';
$end = '2014-4-29';
$query = new Query("
		SELECT sum(hours) AS hours FROM apo_calendar_event
			JOIN apo_calendar_attend USING (event_id)
			WHERE (type_service_chapter=TRUE OR type_service_campus=TRUE OR type_service_community=TRUE OR type_service_country=TRUE OR type_fundraiser=TRUE)
			AND flaked=FALSE  AND deleted=FALSE AND date BETWEEN '$start' AND '$end'");
$row = $query->fetch_row();
$spring14 = $row['hours'];
$spring14_dates = date("M d, Y", strtotime($start)) . " - " . date("M d, Y", strtotime($end));

$query = new Query("SELECT user_id, count(*) AS count FROM apo_actives");
$row = $query->fetch_row();
$spring14_actives = $row['count'];

$query = new Query("SELECT user_id, count(*) AS count FROM apo_pledges");
$row = $query->fetch_row();
$spring14_pledges = $row['count'];

$query = new Query("
		SELECT count(*) AS count FROM apo_calendar_event
			WHERE (type_service_chapter=TRUE OR type_service_campus=TRUE OR type_service_community=TRUE OR type_service_country=TRUE OR type_fundraiser=TRUE)
			 AND deleted=FALSE AND date BETWEEN '$start' AND '$end'");
$row = $query->fetch_row();
$spring14_projects = $row['count'];

$query = new Query("
		SELECT count(*) AS count FROM apo_calendar_event
			WHERE (type_fellowship=TRUE)
			 AND deleted=FALSE AND date BETWEEN '$start' AND '$end'");
$row = $query->fetch_row();
$spring14_fellowships = $row['count'];


$query_attending = query_attending_services('2011-8-30', '2011-11-29');
$row_attending = $query_attending->fetch_row();
$popular_services = "<h2>Popular Service Events FOR CPZ Semester!</h2>";
while($row_attending)
{
	if(intval($row_attending['total']) >= 10){
		$event_title = $row_attending['title'];
		$num_attendees = intval($row_attending['total']);
		$event_date = $row_attending['date'];
		$popular_services .= "<br/><strong><FONT COLOR='GREEN'>$event_title</FONT></strong> on <strong><FONT COLOR='ORANGE'>$event_date</FONT></strong>: Has <strong><em><FONT COLOR='BLUE'>$num_attendees</FONT></em></strong> attending it!<br/>";
	}
	$row_attending = $query_attending->fetch_row();
}
new Query("DROP TABLE table_attend");
$query_attending = query_attending_services('2012-1-17', '2012-4-24');
$row_attending = $query_attending->fetch_row();
$popular_services .= "<h2>Popular Service Events FOR JS Semester!</h2>";
while($row_attending)
{
	if(intval($row_attending['total']) >= 10){
		$event_title = $row_attending['title'];
		$num_attendees = intval($row_attending['total']);
		$event_date = $row_attending['date'];
		$popular_services .= "<br/><strong><FONT COLOR='GREEN'>$event_title</FONT></strong> on <strong><FONT COLOR='ORANGE'>$event_date</FONT></strong>: Has <strong><em><FONT COLOR='BLUE'>$num_attendees</FONT></em></strong> attending it!<br/>";
	}
	$row_attending = $query_attending->fetch_row();
}
new Query("DROP TABLE table_attend");
$query_attending = query_attending_services('2012-8-28', '2012-11-27');
$row_attending = $query_attending->fetch_row();
$popular_services .= "<h2>Popular Service Events FOR MH Semester!</h2>";
while($row_attending)
{
	if(intval($row_attending['total']) >= 10){
		$event_title = $row_attending['title'];
		$num_attendees = intval($row_attending['total']);
		$event_date = $row_attending['date'];
		$popular_services .= "<br/><strong><FONT COLOR='GREEN'>$event_title</FONT></strong> on <strong><FONT COLOR='ORANGE'>$event_date</FONT></strong>: Has <strong><em><FONT COLOR='BLUE'>$num_attendees</FONT></em></strong> attending it!<br/>";
	}
	$row_attending = $query_attending->fetch_row();
}
new Query("DROP TABLE table_attend");
$query_attending = query_attending_services('2013-1-22', '2013-4-30');
$row_attending = $query_attending->fetch_row();
$popular_services .= "<h2>Popular Service Events FOR KK Semester!</h2>";
while($row_attending)
{
	if(intval($row_attending['total']) >= 10){
		$event_title = $row_attending['title'];
		$num_attendees = intval($row_attending['total']);
		$event_date = $row_attending['date'];
		$popular_services .= "<br/><strong><FONT COLOR='GREEN'>$event_title</FONT></strong> on <strong><FONT COLOR='ORANGE'>$event_date</FONT></strong>: Has <strong><em><FONT COLOR='BLUE'>$num_attendees</FONT></em></strong> attending it!<br/>";
	}
	$row_attending = $query_attending->fetch_row();
}
new Query("DROP TABLE table_attend");
$query_attending = query_attending_services('2013-9-3', '2013-12-3');
$row_attending = $query_attending->fetch_row();
$popular_services .= "<h2>Popular Service Events FOR DE Semester!</h2>";
while($row_attending)
{
	if(intval($row_attending['total']) >= 10){
		$event_title = $row_attending['title'];
		$num_attendees = intval($row_attending['total']);
		$event_date = $row_attending['date'];
		$popular_services .= "<br/><strong><FONT COLOR='GREEN'>$event_title</FONT></strong> on <strong><FONT COLOR='ORANGE'>$event_date</FONT></strong>: Has <strong><em><FONT COLOR='BLUE'>$num_attendees</FONT></em></strong> attending it!<br/>";
	}
	$row_attending = $query_attending->fetch_row();
}
new Query("DROP TABLE table_attend");
$query_attending = query_attending_services('2014-1-21', '2014-4-29');
$row_attending = $query_attending->fetch_row();
$popular_services .= "<h2>Popular Service Events FOR CM Semester!</h2>";
while($row_attending)
{
	if(intval($row_attending['total']) >= 10){
		$event_title = $row_attending['title'];
		$num_attendees = intval($row_attending['total']);
		$event_date = $row_attending['date'];
		$popular_services .= "<br/><strong><FONT COLOR='GREEN'>$event_title</FONT></strong> on <strong><FONT COLOR='ORANGE'>$event_date</FONT></strong>: Has <strong><em><FONT COLOR='BLUE'>$num_attendees</FONT></em></strong> attending it!<br/>";
	}
	$row_attending = $query_attending->fetch_row();
}

	echo <<<HEREDOC
<h1>View Total Service Hours</h1>
<p style="padding: 1em 0px">This is a service report on the calendar showing as much data on the progress and trends of service events!</p>
<table style="width: auto;">
<caption></caption>

<tr><th axis="semester" style="width: 300px; font-weight: bold; padding: 0px 2px">Semester</th><th axis="hours" style="width: 70px; font-weight: bold; padding: 0px 2px">Service Hours</th><th axis="projects" style="width: 70px; font-weight: bold; padding: 0px 2px">Service Events</th><th axis="fellowships" style="width: 100px; font-weight: bold; padding: 0px 2px">Fellowships</th><th axis="Comments" style="font-weight: bold; padding: 0px 2px">Comments</th></tr>

<tr><td axis="semester">$fall11_dates (Fall 2011) CPZ Semester</td><td axis="hours">$fall11</td><td axis="hours">$fall11_projects</td><td axis="hours">$fall11_fellowships</td><td axis="comments">Number of Pledges: $fall11_pledges , Number of Actives: $fall11_actives</td></tr>

<tr><td axis="semester">$spring12_dates (Spring 2012) JS Semester</td><td axis="hours">$spring12</td><td axis="hours">$spring12_projects</td><td axis="hours">$spring12_fellowships</td><td axis="comments">Number of Pledges: $spring12_pledges , Number of Actives: $spring12_actives</td></tr>

<tr><td axis="semester">$fall12_dates (Fall 2012) MH Semester</td><td axis="hours">$fall12</td><td axis="hours">$fall12_projects</td><td axis="hours">$fall12_fellowships</td><td axis="comments">Number of Pledges: $fall12_pledges , Number of Actives: $fall12_actives</td></tr>

<tr><td axis="semester">$spring13_dates (Spring 2013) KK Semester</td><td axis="hours">$spring13</td><td axis="hours">$spring13_projects</td><td axis="hours">$spring13_fellowships</td><td axis="comments">Number of Pledges: $spring13_pledges, Number of Actives: $spring13_actives</td></tr>

<tr><td axis="semester">$fall13_dates (Fall 2013) DE Semester</td><td axis="hours">$fall13</td><td axis="hours">$fall13_projects</td><td axis="hours">$fall13_fellowships</td><td axis="comments">Number of Pledges: $fall13_pledges, Number of Actives: $fall13_actives</td></tr>

<tr><td axis="semester">$spring14_dates (Spring 2014) CM Semester</td><td axis="hours">$spring14</td><td axis="hours">$spring14_projects</td><td axis="hours">$spring14_fellowships</td><td axis="comments">Number of Pledges: $spring14_pledges, Number of Actives: $spring14_actives </td></tr>

</table>
<p>This is a print out of all service events that have 10 or more people attending!</p>
<p>$popular_services</p>

HEREDOC;

Template::print_body_footer();
Template::print_disclaimer();
?>