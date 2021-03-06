<?php
/*
 * $RCSfile: AdminDeleteGroupQuota.inc,v $
 *
 * Gallery - a web based photo album viewer and editor
 * Copyright (C) 2000-2005 Bharat Mediratta
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street - Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * @version $Revision: 1.6 $ $Date: 2005/08/23 03:49:49 $
 * @package Quotas
 * @subpackage UserInterface
 * @author Robert Balousek <volksport@users.sf.net>
 */

/**
 * This controller will handle the deletion of a group quota
 *
 * @package Quotas
 * @subpackage UserInterface
 *
 */
class AdminDeleteGroupQuotaController extends GalleryController {

    /**
     * @see GalleryController::handleRequest
     */
    function handleRequest($form) {
	global $gallery;

	$ret = GalleryCoreApi::assertUserIsSiteAdministrator();
	if ($ret->isError()) {
	    return array($ret->wrap(__FILE__, __LINE__), null);
	}

	$results = array();
	$status = array();
	$error = array();
	$groupId = GalleryUtilities::getRequestVariables('groupId');
	list ($ret, $group) = GalleryCoreApi::loadEntitiesById($groupId);
	if ($ret->isError()) {
	    return array($ret->wrap(__FILE__, __LINE__), null);
	}
	if (isset($form['action']['cancel'])) {

	    /* Go back to the AdminGroups view */
	    $redirect['view'] = 'core.SiteAdmin';
	    $redirect['subView'] = 'quotas.AdminQuotas';

	} else if (isset($form['action']['delete'])) {

	    GalleryCoreApi::relativeRequireOnce('modules/quotas/classes/QuotasMap.class');
	    GalleryCoreApi::relativeRequireOnce('modules/quotas/classes/GalleryQuotasHelper.class');

	    list ($ret, $quotaExists, $groupQuota) =
	        GalleryQuotasHelper::fetchGroupQuota($group->getId());
	    if ($ret->isError()) {
	        return array($ret->wrap(__FILE__, __LINE__), null);
	    }

	    if ($quotaExists) {
		$ret = QuotasMap::removeMapEntry(array('userOrGroupId' => $group->getId()));
                if ($ret->isError()) {
                    return array($ret->wrap(__FILE__, __LINE__), null);
                }

		/* Request a redirect to the confirmation screen */
		$redirect['view'] = 'core.SiteAdmin';
		$redirect['subView'] = 'quotas.AdminQuotas';
		$status['deletedGroup'] = $group->getGroupName();
	    }
	}

	if (!empty($redirect)) {
	    $results['redirect'] = $redirect;
	} else {
	    $results['delegate']['view'] = 'core.SiteAdmin';
	    $results['delegate']['subView'] = 'quotas.AdminDeleteGroupQuota';
	}
	$results['status'] = $status;
	$results['error'] = $error;

	return array(GalleryStatus::success(), $results);
    }
}

/**
 * This view will prompt for confirmation to delete a group quota
 *
 * @package Quotas
 * @subpackage UserInterface
 */
class AdminDeleteGroupQuotaView extends GalleryView {

    /**
     * @see GalleryView::loadTemplate
     */
    function loadTemplate(&$template, &$form) {
	global $gallery;

	$ret = GalleryCoreApi::assertUserIsSiteAdministrator();
	if ($ret->isError()) {
	    return array($ret->wrap(__FILE__, __LINE__), null);
	}
	GalleryCoreApi::relativeRequireOnce('modules/quotas/classes/GalleryQuotasHelper.class');
	$groupId = GalleryUtilities::getRequestVariables('groupId');
	list ($ret, $group) = GalleryCoreApi::loadEntitiesById($groupId);
	if ($ret->isError()) {
	    return array($ret->wrap(__FILE__, __LINE__), null);
	}

	list ($ret, $quotaExists, $groupQuota) =
	    GalleryQuotasHelper::fetchGroupQuota($group->getId());
	if ($ret->isError()) {
	    return array($ret->wrap(__FILE__, __LINE__), null);
	}

        if ($form['formName'] != 'AdminDeleteGroupQuota') {
	    /* First time around initialize our form */
	    $form['groupName'] = $group->getGroupName();
	    $form['formName'] = 'AdminDeleteGroupQuota';
	}

	$AdminDeleteGroupQuota = array();
	list ($ret, $AdminDeleteGroupQuota['quotaSize'], $AdminDeleteGroupQuota['quotaUnit']) =
	    GalleryQuotasHelper::humanReadableFromKilobytes($groupQuota['quota']);
	if ($ret->isError()) {
	    return array($ret->wrap(__FILE__, __LINE__), null);
	}
	$AdminDeleteGroupQuota['group'] = $group->getMemberData();

	/* Render the HTML body */
	$template->setVariable('AdminDeleteGroupQuota', $AdminDeleteGroupQuota);
	$template->setVariable('controller', 'quotas.AdminDeleteGroupQuota');

	return array(GalleryStatus::success(),
		     array('body' => 'modules/quotas/templates/AdminDeleteGroupQuota.tpl'));
    }
}
?>
