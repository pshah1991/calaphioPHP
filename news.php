<?php
require("include/includes.php");
require("include/Calendar.class.php");
require("include/Template.class.php");
require("include/Shoutbox.class.php");
require("include/EvalNag.class.php");
require("include/GGManiacNag.class.php");
Template::print_head(array("site.css", "calendar.css", "excel.css"));
Template::print_body_header('Home', 'NEWS');

$evalnag = new EvalNag();
echo $evalnag->display("2007-01-01");

$gg_maniac_nag = new GGManiacNag();
echo $gg_maniac_nag->display();

$shoutbox = new Shoutbox();
$shoutbox->process();
echo $shoutbox->display();

Calendar::print_upcoming_events(5);

$g_user->process_mailer(false);
$g_user->print_mailer(false);
$g_user->print_personal_messages();

if (!$g_user->is_logged_in()) {
	echo '<img style="float: right" src="images/lfs_banner.png" alt="LFS" />';
}

?>

<?php if ($g_user->is_logged_in()): ?>
    <div class="newsItem">
        <h2>Notes From CM 1</h2>
        <p class="date">January 22, 2015</p>
        <p style="margin-bottom: 1em">Here are the documents from CM 1!<br>
            Excomm Powerpoint Slides:<a href="https://docs.google.com/a/calaphio.com/presentation/d/1CZIzn8gb5EC64YtW_tWEOVxyhaQv1IpL0DQzCOesMOo/edit#slide=id.g5824adeec_193"> CM 1 Slides</a><br>
            And here are the <a href="https://docs.google.com/a/calaphio.com/document/d/1xAiRGk0aOvFXGC_OcKugTsRJdTuHq_rhr9Qr55UDcUY/edit">Active Requirements</a> & 
            <a href="https://docs.google.com/a/calaphio.com/spreadsheets/d/1ASILS5dhWOf0F78D3LBrydRED-b7_42c6_GJb_oA5-s/edit#gid=0">Budget.</a></p>
            <iframe style="margin-bottom: 1em" width="480" height="360" src="//www.youtube.com/embed/NtV0Yso1gqI" frameborder="0" allowfullscreen></iframe>
        <p>-<a href="profile.php?user_id=2055">Jason Lee (CM)</a></p>
    </div>
<?php endif ?>


<?php if ($g_user->is_logged_in()): ?>
    <div class="newsItem">
        <br/>
        <h2>Congratulations to the Spring 2015 Pledge Committee!</h2>
        <p class="date">December 9th, 2014</p>
        <div class="collage-container">
            <div class="collage-pictures">
                <div class="person-picture">
                    <img src="documents/sp15/pcomm/justin_fang.jpg"></img>
                    <p class="center"><strong>Leadership Trainer</strong>: <a href="profile.php?user_id=2452">Justin Fang</a></p>
                </div>

                <div class="person-picture">
                    <img src="documents/sp15/pcomm/joseph_gapuz.jpg"></img>
                    <p class="center"><strong>Leadership Trainer</strong>: <a href="profile.php?user_id=2175">Joseph Gapuz</a></p>
                </div>

                <div class="person-picture">
                    <img src="documents/sp15/pcomm/amanda_thai.jpg"></img>
                    <p class="center"><strong>Fellowship Trainer</strong>: <a href="profile.php?user_id=2164">Amanda Thai</a></p>
                </div>

                <div class="person-picture">
                    <img src="documents/sp15/pcomm/irene_yu.jpg"></img>
                    <p class="center"><strong>Fellowship Trainer</strong>: <a href="profile.php?user_id=2447">Irene Yu</a></p>
                </div>

                <div class="person-picture">
                    <img src="documents/sp15/pcomm/scottie_wan.jpg"></img>
                    <p class="center"><strong>Service Trainer</strong>: <a href="profile.php?user_id=2461">Scottie Wan</a></p>
                </div>

                <div class="person-picture">
                    <img src="documents/sp15/pcomm/aimee_chan.jpg"></img>
                    <p class="center"><strong>Service Trainer</strong>: <a href="profile.php?user_id=2137">Aimee Chan</a></p>
                </div>

                <div class="person-picture">
                    <img src="documents/sp15/pcomm/alex_quan.jpg"></img>
                    <p class="center"><strong>Finance Trainer</strong>: <a href="profile.php?user_id=2432">Alex Quan</a></p>
                </div>

                <div class="person-picture">
                    <img src="documents/sp15/pcomm/vivian_rubio.jpg"></img>
                    <p class="center"><strong>Finance Trainer</strong>: <a href="profile.php?user_id=2730">Vivian Rubio</a></p>
                </div>

                <div class="person-picture">
                    <img src="documents/sp15/pcomm/debbie_phuong.jpg"></img>
                    <p class="center"><strong>Historian Trainer</strong>: <a href="profile.php?user_id=2170">Debbie Phuong</a></p>
                </div>
                
            </div>
            <div style="clear: left;"></div>
        </div>

        <p>-<a href="profile.php?user_id=2055">Kelsey Chan (KK)</a></p>
    </div>
<?php endif ?>

<div class="newsItem">
    <h2>Congratulations to the Spring 2015 Rush Chairs!</h2>
    <p class="date">December 9, 2014</p>
    <div class="collage-container">
        <div class="collage-pictures">
            <div class="person-picture">
                <img src="documents/sp15/rush/bella_tsay.jpg"></img>
                <p class="center"><a href="profile.php?user_id=2073">Bella Tsay</a></p>
                
            </div>
            <div class="person-picture">
                <img src="documents/sp15/rush/joanna_choi.jpg"></img>
                <p class="center"><a href="profile.php?user_id=2855">Joanna Choi</a></p>
                
            </div>
            <div class="person-picture">
                <img src="documents/sp15/rush/antony_nguyen.jpg"></img>
                <p class="center"><a href="profile.php?user_id=2869">Antony Nguyen</a></p>
                
            </div>
            <div class="person-picture">
                <img src="documents/sp15/rush/nicki_bartak.jpg"></img>
                <p class="center"><a href="profile.php?user_id=2851">Nicki Bartak</a></p>
                
            </div>
        </div>
        <div style="clear: left;"></div>
    </div>
</p>
<p>-<a href="profile.php?user_id=2055">Kelsey Chan (KK)</a></p>
</div>

<a href="news_fa14.php">Older News ></a>
<?php
Template::print_body_footer();
Template::print_disclaimer();
?>
