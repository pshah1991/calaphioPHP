<?php
/**
 * UserMailer.php
 *  Copyright (C) 2004 Thomas Gries <mail@tgries.de>
 * http://www.mediawiki.org/
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @author <brion@pobox.com>
 * @author <mail@tgries.de>
 *
 * @package MediaWiki
 */

require_once( 'WikiError.php' );

/**
 * Converts a string into a valid RFC 822 "phrase", such as is used for the sender name
 */
function wfRFC822Phrase( $phrase ) {
	$phrase = strtr( $phrase, array( "\r" => '', "\n" => '', '"' => '' ) );
	return '"' . $phrase . '"';
}

class MailAddress {
	/**
	 * @param mixed $address String with an email address, or a User object
	 * @param string $name Human-readable name if a string address is given
	 */
	function MailAddress( $address, $name=null ) {
		if( is_object( $address ) && is_a( $address, 'User' ) ) {
			$this->address = $address->getEmail();
			$this->name = $address->getName();
		} else {
			$this->address = strval( $address );
			$this->name = strval( $name );
		}
	}

	/**
	 * Return formatted and quoted address to insert into SMTP headers
	 * @return string
	 */
	function toString() {
		if( $this->name != '' ) {
			return wfQuotedPrintable( $this->name ) . " <" . $this->address . ">";
		} else {
			return $this->address;
		}
	}
}

/**
 * This function will perform a direct (authenticated) login to
 * a SMTP Server to use for mail relaying if 'wgSMTP' specifies an
 * array of parameters. It requires PEAR:Mail to do that.
 * Otherwise it just uses the standard PHP 'mail' function.
 *
 * @param MailAddress $to recipient's email
 * @param MailAddress $from sender's email
 * @param string $subject email's subject
 * @param string $body email's text
 * @param string $replyto optional reply-to email (default : false)
 */
function userMailer( $to, $from, $subject, $body, $replyto=false ) {
	global $wgUser, $wgSMTP, $wgOutputEncoding, $wgErrorString;

	if (is_array( $wgSMTP )) {
		require_once( 'Mail.php' );

		$timestamp = time();
		$dest = $to->toString();

		$headers['From'] = $from->toString();
		$headers['To'] = $dest;
		if ( $replyto ) {
			$headers['Reply-To'] = $replyto;
		}
		$headers['Subject'] = wfQuotedPrintable( $subject );
		$headers['Date'] = date( 'r' );
		$headers['MIME-Version'] = '1.0';
		$headers['Content-type'] = 'text/plain; charset='.$wgOutputEncoding;
		$headers['Content-transfer-encoding'] = '8bit';
		$headers['Message-ID'] = "<{$timestamp}" . $wgUser->getName() . '@' . $wgSMTP['IDHost'] . '>'; // FIXME
		$headers['X-Mailer'] = 'MediaWiki mailer';

		// Create the mail object using the Mail::factory method
		$mail_object =& Mail::factory('smtp', $wgSMTP);
		wfDebug( "Sending mail via PEAR::Mail to $dest" );
		$mailResult =& $mail_object->send($dest, $headers, $body);

		# Based on the result return an error string,
		if ($mailResult === true) {
			return '';
		} elseif (is_object($mailResult)) {
			wfDebug( "PEAR::Mail failed: " . $mailResult->getMessage() . "\n" );
			return $mailResult->getMessage();
		} else {
			wfDebug( "PEAR::Mail failed, unknown error result\n" );
			return 'Mail object return unknown error.';
		}
	} else	{
		# In the following $headers = expression we removed "Reply-To: {$from}\r\n" , because it is treated differently
		# (fifth parameter of the PHP mail function, see some lines below)
		$headers =
			"MIME-Version: 1.0\n" .
			"Content-type: text/plain; charset={$wgOutputEncoding}\n" .
			"Content-Transfer-Encoding: 8bit\n" .
			"X-Mailer: MediaWiki mailer\n".
			'From: ' . $from->toString() . "\n";
		if ($replyto) {
			$headers .= "Reply-To: $replyto\n";
		}

		$dest = $to->toString();

		$wgErrorString = '';
		set_error_handler( 'mailErrorHandler' );
		wfDebug( "Sending mail via internal mail() function to $dest\n" );
		mail( $dest, wfQuotedPrintable( $subject ), $body, $headers );
		restore_error_handler();

		if ( $wgErrorString ) {
			wfDebug( "Error sending mail: $wgErrorString\n" );
		}
		return $wgErrorString;
	}
}

/**
 * Get the mail error message in global $wgErrorString
 *
 * @parameter $code error number
 * @parameter $string error message
 */
function mailErrorHandler( $code, $string ) {
	global $wgErrorString;
	$wgErrorString = preg_replace( "/^mail\(\): /", '', $string );
}


/**
 * This module processes the email notifications when the current page is
 * changed. It looks up the table watchlist to find out which users are watching
 * that page.
 *
 * The current implementation sends independent emails to each watching user for
 * the following reason:
 *
 * - Each watching user will be notified about the page edit time expressed in
 * his/her local time (UTC is shown additionally). To achieve this, we need to
 * find the individual timeoffset of each watching user from the preferences..
 *
 * Suggested improvement to slack down the number of sent emails: We could think
 * of sending out bulk mails (bcc:user1,user2...) for all these users having the
 * same timeoffset in their preferences.
 *
 * Visit the documentation pages under http://meta.wikipedia.com/Enotif
 *
 * @package MediaWiki
 *
 */
class EmailNotification {
	/**#@+
	 * @access private
	 */
	var $to, $subject, $body, $replyto, $from;
	var $user, $title, $timestamp, $summary, $minorEdit, $oldid;

	/**#@-*/

	/**
	 * @todo document
	 * @param $currentPage
	 * @param $currentNs
	 * @param $timestamp
	 * @param $currentSummary
	 * @param $currentMinorEdit
	 * @param $oldid (default: false)
	 */
	function notifyOnPageChange(&$title, $timestamp, $summary, $minorEdit, $oldid=false) {

		# we use $wgEmergencyContact as sender's address
		global $wgUser, $wgEnotifWatchlist;
		global $wgEnotifMinorEdits, $wgEnotifUserTalk, $wgShowUpdatedMarker;

		$fname = 'UserMailer::notifyOnPageChange';
		wfProfileIn( $fname );

		# The following code is only run, if several conditions are met:
		# 1. EmailNotification for pages (other than user_talk pages) must be enabled
		# 2. minor edits (changes) are only regarded if the global flag indicates so

		$isUserTalkPage = ($title->getNamespace() == NS_USER_TALK);
		$enotifusertalkpage = ($isUserTalkPage && $wgEnotifUserTalk);
		$enotifwatchlistpage = $wgEnotifWatchlist;

		if ( (!$minorEdit || $wgEnotifMinorEdits) ) {
			if( $wgEnotifWatchlist ) {
				// Send updates to watchers other than the current editor
				$userCondition = 'wl_user <> ' . intval( $wgUser->getId() );
			} elseif( $wgEnotifUserTalk && $title->getNamespace() == NS_USER_TALK ) {
				$targetUser = User::newFromName( $title->getText() );
				if( is_null( $targetUser ) ) {
					wfDebug( "$fname: user-talk-only mode; no such user\n" );
					$userCondition = false;
				} elseif( $targetUser->getId() == $wgUser->getId() ) {
					wfDebug( "$fname: user-talk-only mode; editor is target user\n" );
					$userCondition = false;
				} else {
					// Don't notify anyone other than the owner of the talk page
					$userCondition = 'wl_user = ' . intval( $targetUser->getId() );
				}
			} else {
				// Notifications disabled
				$userCondition = false;
			}
			if( $userCondition ) {
				$dbr =& wfGetDB( DB_MASTER );
				extract( $dbr->tableNames( 'watchlist' ) );

				$res = $dbr->select( 'watchlist', array( 'wl_user' ),
					array(
						'wl_title' => $title->getDBkey(),
						'wl_namespace' => $title->getNamespace(),
						$userCondition,
						'wl_notificationtimestamp IS NULL',
					), $fname );

				# if anyone is watching ... set up the email message text which is
				# common for all receipients ...
				if ( $dbr->numRows( $res ) > 0 ) {
					$this->title =& $title;
					$this->timestamp = $timestamp;
					$this->summary = $summary;
					$this->minorEdit = $minorEdit;
					$this->oldid = $oldid;

					$this->composeCommonMailtext();
					$watchingUser = new User();

					# ... now do for all watching users ... if the options fit
					for ($i = 1; $i <= $dbr->numRows( $res ); $i++) {

						$wuser = $dbr->fetchObject( $res );
						$watchingUser->setID($wuser->wl_user);
						if ( ( $enotifwatchlistpage && $watchingUser->getOption('enotifwatchlistpages') ) ||
							( $enotifusertalkpage && $watchingUser->getOption('enotifusertalkpages') )
						&& (!$minorEdit || ($wgEnotifMinorEdits && $watchingUser->getOption('enotifminoredits') ) )
						&& ($watchingUser->isEmailConfirmed() ) ) {
							# ... adjust remaining text and page edit time placeholders
							# which needs to be personalized for each user
							$this->composeAndSendPersonalisedMail( $watchingUser );

						} # if the watching user has an email address in the preferences
					}
				}
			} # if anyone is watching
		} # if $wgEnotifWatchlist = true

		if ( $wgShowUpdatedMarker || $wgEnotifWatchlist ) {
			# mark the changed watch-listed page with a timestamp, so that the page is
			# listed with an "updated since your last visit" icon in the watch list, ...
			$dbw =& wfGetDB( DB_MASTER );
			$success = $dbw->update( 'watchlist',
				array( /* SET */
					'wl_notificationtimestamp' => $dbw->timestamp($timestamp)
				), array( /* WHERE */
					'wl_title' => $title->getDBkey(),
					'wl_namespace' => $title->getNamespace(),
				), 'UserMailer::NotifyOnChange'
			);
			# FIXME what do we do on failure ?
		}

	} # function NotifyOnChange

	/**
	 * @access private
	 */
	function composeCommonMailtext() {
		global $wgUser, $wgEmergencyContact, $wgNoReplyAddress;
		global $wgEnotifFromEditor, $wgEnotifRevealEditorAddress;

		$summary = ($this->summary == '') ? ' - ' : $this->summary;
		$medit   = ($this->minorEdit) ? wfMsg( 'minoredit' ) : '';

		# You as the WikiAdmin and Sysops can make use of plenty of
		# named variables when composing your notification emails while
		# simply editing the Meta pages

		$subject = wfMsgForContent( 'enotif_subject' );
		$body    = wfMsgForContent( 'enotif_body' );
		$from    = ''; /* fail safe */
		$replyto = ''; /* fail safe */
		$keys    = array();

		# regarding the use of oldid as an indicator for the last visited version, see also
		# http://bugzilla.wikipeda.org/show_bug.cgi?id=603 "Delete + undelete cycle doesn't preserve old_id"
		# However, in the case of a new page which is already watched, we have no previous version to compare
		if( $this->oldid ) {
			$difflink = $this->title->getFullUrl( 'diff=0&oldid=' . $this->oldid );
			$keys['$NEWPAGE'] = wfMsgForContent( 'enotif_lastvisited', $difflink );
			$keys['$OLDID']   = $this->oldid;
			$keys['$CHANGEDORCREATED'] = wfMsgForContent( 'changed' );
		} else {
			$keys['$NEWPAGE'] = wfMsgForContent( 'enotif_newpagetext' );
			# clear $OLDID placeholder in the message template
			$keys['$OLDID']   = '';
			$keys['$CHANGEDORCREATED'] = wfMsgForContent( 'created' );
		}

		$body = strtr( $body, $keys );
		$pagetitle = $this->title->getPrefixedText();
		$keys['$PAGETITLE']          = $pagetitle;
		$keys['$PAGETITLE_URL']      = $this->title->getFullUrl();

		$keys['$PAGEMINOREDIT']      = $medit;
		$keys['$PAGESUMMARY']        = $summary;

		$subject = strtr( $subject, $keys );

		# Reveal the page editor's address as REPLY-TO address only if
		# the user has not opted-out and the option is enabled at the
		# global configuration level.
		$name    = $wgUser->getName();
		$adminAddress = new MailAddress( $wgEmergencyContact, 'WikiAdmin' );
		$editorAddress = new MailAddress( $wgUser );
		if( $wgEnotifRevealEditorAddress
		    && ( $wgUser->getEmail() != '' )
		    && $wgUser->getOption( 'enotifrevealaddr' ) ) {
			if( $wgEnotifFromEditor ) {
				$from    = $editorAddress;
			} else {
				$from    = $adminAddress;
				$replyto = $editorAddress;
			}
		} else {
			$from    = $adminAddress;
			$replyto = $wgNoReplyAddress;
		}

		if( $wgUser->isIP( $name ) ) {
			#real anon (user:xxx.xxx.xxx.xxx)
			$subject = str_replace('$PAGEEDITOR', 'anonymous user '. $name, $subject);
			$keys['$PAGEEDITOR']       = 'anonymous user ' . $name;
			$keys['$PAGEEDITOR_EMAIL'] = wfMsgForContent( 'noemailtitle' );
		} else {
			$subject = str_replace('$PAGEEDITOR', $name, $subject);
			$keys['$PAGEEDITOR']          = $name;
			$emailPage = Title::makeTitle( NS_SPECIAL, 'Emailuser/' . $name );
			$keys['$PAGEEDITOR_EMAIL'] = $emailPage->getFullUrl();
		}
		$userPage = $wgUser->getUserPage();
		$keys['$PAGEEDITOR_WIKI'] = $userPage->getFullUrl();
		$body = strtr( $body, $keys );
		$body = wordwrap( $body, 72 );

		# now save this as the constant user-independent part of the message
		$this->from    = $from;
		$this->replyto = $replyto;
		$this->subject = $subject;
		$this->body    = $body;
	}



	/**
	 * Does the per-user customizations to a notification e-mail (name,
	 * timestamp in proper timezone, etc) and sends it out.
	 * Returns true if the mail was sent successfully.
	 *
	 * @param User $watchingUser
	 * @param object $mail
	 * @return bool
	 * @access private
	 */
	function composeAndSendPersonalisedMail( $watchingUser ) {
		global $wgLang;
		// From the PHP manual:
		//     Note:  The to parameter cannot be an address in the form of "Something <someone@example.com>".
		//     The mail command will not parse this properly while talking with the MTA.
		$to = new MailAddress( $watchingUser );
		$body = str_replace( '$WATCHINGUSERNAME', $watchingUser->getName() , $this->body );

		$timecorrection = $watchingUser->getOption( 'timecorrection' );

		# $PAGEEDITDATE is the time and date of the page change
		# expressed in terms of individual local time of the notification
		# recipient, i.e. watching user
		$body = str_replace('$PAGEEDITDATE',
			$wgLang->timeanddate( $this->timestamp, true, false, $timecorrection ),
			$body);

		$error = userMailer( $to, $this->from, $this->subject, $body, $this->replyto );
		return ($error == '');
	}

} # end of class EmailNotification
?>
