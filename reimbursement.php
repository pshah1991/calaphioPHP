<?php
require("include/includes.php");
require("include/Calendar.class.php");
require("include/Template.class.php");
Template::print_head(array());
Template::print_body_header('Calendar', 'REIMBURSE');
?>
<h2><b>Reimbursements</b></h2><br/>
<p> In order to get reimbursed for an expense, you need the receipt.<br>
Write down the information below on the back or on an additional piece of paper<br/>
and give it to either the Finance VP or one of the Reimbursement Chairs: <br/><br/>
<b>Name: <br/>
SID: <br/>
E-Mail: <br/>
Address: <br/>
Phone Number: <br/>
Reason for Reimbursement/Name of Service Project:<br/><br/>

<h2><b>Service Project Reimbursements</h2></b>
<li>You can get your public transportation or mileage fees reimbursed too! </li> <br>
<li>Get reimbursed for BART tickets (pay by Credit Card), Bridge Toll and Parking if you get the receipt!</li><br />
<li>Drivers: If you cross any toll bridge be sure to ask for a receipt and you'll get reimbursed full </li> <br />
<li>Be sure to fill out the <a href"/documents/TransportReimbForm.pdf"> Transportation Reimbursement Form </a>' upon submission!<br>
<li>Fill out all applicable fields, attach receipts, and turn into Finance VP or reimbursement chair.</li><br>

<?php
Template::print_body_footer();
Template::print_disclaimer();
?>
