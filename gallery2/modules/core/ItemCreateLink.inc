<?php
/*
 * $RCSfile: ItemCreateLink.inc,v $
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
 * @version $Revision: 1.16 $ $Date: 2005/08/23 03:49:02 $
 * @package GalleryCore
 * @subpackage UserInterface
 * @author Bharat Mediratta <bharat@menalto.com>
 */

/**
 * This controller will handle linking one or more items from one album to
 * another.
 *
 * @package GalleryCore
 * @subpackage UserInterface
 *
 */
class ItemCreateLinkController extends GalleryController {

    /**
     * Link the selected items into the destination album, if all the
     * permissions are set correctly.
     */
    function handleRequest($form) {
	global $gallery;

	$itemId = GalleryUtilities::getRequestVariables('itemId');

	$status = array();
	$error = array();
	if (isset($form['action']['link'])) {
	    if (empty($form['destination'])) {
		$error[] = 'form[error][destination][empty]';
	    }
        if (empty($form['selectedIds'])) {
            $error[] = 'form[error][sources][empty]';
        }

	    if (empty($error)) {
		$destinationId = $form['destination'];
		$selectedIds = array_keys($form['selectedIds']);

		/* Make sure we can write to the destination */
		list ($ret, $permissions) = GalleryCoreApi::getPermissions($destinationId);
		if ($ret->isError()) {
		    return array($ret->wrap(__FILE__, __LINE__), null);
		}

		if (!isset($permissions['core.addDataItem'])) {
		    return array(GalleryStatus::error(ERROR_PERMISSION_DENIED, __FILE__, __LINE__),
				 null);
		}

		/* Load the selected items */
		list ($ret, $selectedItems) = GalleryCoreApi::loadEntitiesById($selectedIds);
		if ($ret->isError()) {
		    return array($ret->wrap(__FILE__, __LINE__), null);
		}

		list ($ret, $permissions) = GalleryCoreApi::fetchPermissionsForItems($selectedIds);
		if ($ret->isError()) {
		    return array($ret->wrap(__FILE__, __LINE__), null);
		}

		/* Look out for monkey business */
		foreach ($selectedItems as $selectedItem) {
		    if (!isset($permissions[$selectedItem->getId()]['core.viewSource'])) {
			return array(GalleryStatus::error(ERROR_PERMISSION_DENIED, __FILE__, __LINE__),
				     null);
		    }

		    if ($selectedItem->getParentId() != $itemId) {
			return array(GalleryStatus::error(ERROR_PERMISSION_DENIED, __FILE__, __LINE__),
				     null);
		    }

		    if (!$selectedItem->getIsLinkable()) {
			return array(GalleryStatus::error(ERROR_PERMISSION_DENIED, __FILE__, __LINE__),
				     null);
		    }

		}

		/*
		 * Ok we've got linkable items and a legal destination album.
		 * Lock everything up and start linkin'.  We need to read lock
		 * the source ids, source hierarchy and destination hierarchy.
		 */
		$ids = array_merge($selectedIds, array($itemId, $destinationId));
		list ($ret, $locks[]) = GalleryCoreApi::acquireReadLock($ids);
		if ($ret->isError()) {
		    return array($ret->wrap(__FILE__, __LINE__), null);
		}

		list ($ret, $locks[]) = GalleryCoreApi::acquireReadLockParents($itemId);
		if ($ret->isError()) {
		    GalleryCoreApi::releaseLocks($locks);
		    return array($ret->wrap(__FILE__, __LINE__), null);
		}

		list ($ret, $locks[]) = GalleryCoreApi::acquireReadLockParents($destinationId);
		if ($ret->isError()) {
		    GalleryCoreApi::releaseLocks($locks);
		    return array($ret->wrap(__FILE__, __LINE__), null);
		}

		/* Create all our links */
		$status['linked']['count'] = 0;
		foreach ($selectedItems as $selectedItem) {

		    $classType = get_class($selectedItem);
		    $linkedItem = new $classType;

		    /* If we're linking to an item that's already a link, then link to its source instead */
		    if ($selectedItem->isLinked()) {
			$linkedEntity = $selectedItem->getLinkedEntity();
			$ret = $linkedItem->createLink($linkedEntity, $destinationId);
		    } else {
			$ret = $linkedItem->createLink($selectedItem, $destinationId);
		    }
		    if ($ret->isError()) {
			GalleryCoreApi::releaseLocks($locks);
			return array($ret->wrap(__FILE__, __LINE__), null);
		    }

		    $ret = $linkedItem->save();
		    if ($ret->isError()) {
			GalleryCoreApi::releaseLocks($locks);
			return array($ret->wrap(__FILE__, __LINE__), null);
		    }

		    $ret = GalleryCoreApi::addExistingItemToAlbum($linkedItem, $destinationId);
		    if ($ret->isError()) {
			GalleryCoreApi::releaseLocks($locks);
			return array($ret->wrap(__FILE__, __LINE__), null);
		    }

		    $status['linked']['count']++;
		}

		/* Release the locks */
		$ret = GalleryCoreApi::releaseLocks($locks);
		if ($ret->isError()) {
		    return array($ret->wrap(__FILE__, __LINE__), null);
		}

		/* Figure out where to redirect upon success */
		$redirect['view'] = 'core.ItemAdmin';
		$redirect['subView'] = 'core.ItemCreateLink';
		$redirect['itemId'] = $itemId;
	    }
	} else if (isset($form['action']['next'])) {
	    $page = GalleryUtilities::getRequestVariables('page');
	    list($ret, $peerIds) =
		GalleryCoreApi::fetchLinkableChildItemIdsWithPermission($itemId, 'core.view');
	    if ($ret->isError()) {
		return array($ret->wrap(__FILE__, __LINE__), null);
	    }

	    $numPages = ceil(sizeof($peerIds) / $form['numPerPage']);

	    $results['delegate']['itemId'] = $itemId;
	    $results['delegate']['page'] = min($page + 1, $numPages);
	} else if (isset($form['action']['previous'])) {
	    $page = GalleryUtilities::getRequestVariables('page');
	    $results['delegate']['itemId'] = $itemId;
	    $results['delegate']['page'] = max($page - 1, 1);
	} else if (isset($form['action']['cancel'])) {
	    $results['return'] = 1;
	}

	if (!empty($redirect)) {
	    $results['redirect'] = $redirect;
	} else {
	    if (empty($results['return'])) {
		$results['delegate']['view'] = 'core.ItemAdmin';
		$results['delegate']['subView'] = 'core.ItemCreateLink';
	    }
	}
	$results['status'] = $status;
	$results['error'] = $error;

	return array(GalleryStatus::success(), $results);
    }
}

/**
 * This view lets you choose where you want to put the new link
 *
 * @package GalleryCore
 * @subpackage UserInterface
 *
 */
class ItemCreateLinkView extends GalleryView {

    /**
     * @see GalleryView::loadTemplate
     */
    function loadTemplate(&$template, &$form) {
	global $gallery;

	list ($itemId, $selectedId, $page) = GalleryUtilities::getRequestVariables('itemId', 'selectedId', 'page');
        if ($form['formName'] != 'ItemCreateLink') {
            /* First time around, load the form with item data */
            if ($selectedId) {
                $form['selectedIds'][$selectedId] = true;
            }
            $form['destination'] = '';
	    $form['formName'] = 'ItemCreateLink';
	    $form['numPerPage'] = 15;
        }

	list ($ret, $item) = GalleryCoreApi::loadEntitiesById($itemId);
	if ($ret->isError()) {
	    return array($ret->wrap(__FILE__, __LINE__), null);
	}

	/* Get all peers that we can link */
	list ($ret, $peerIds) =
	    GalleryCoreApi::fetchLinkableChildItemIdsWithPermission($item->getId(), 'core.viewSource');
        if ($ret->isError()) {
            return array($ret->wrap(__FILE__, __LINE__), null);
        }

	$peers = array();
	$peerTypes = array();
	$peerDescendentCounts = array();
	$numPages = 1;
	$selectedIds = array();
	$albums = array();
	if (!empty($peerIds)) {
	    $numPages = ceil(sizeof($peerIds) / $form['numPerPage']);
	    if (empty($page)) {
		/* determine which page we're on */
		$page = 1;
		for ($i = 0; $i < sizeof($peerIds); $i++) {
		    if ($peerIds[$i] == $selectedId) {
			$page = ceil(($i + 1) / $form['numPerPage']);
		    }
		}
	    }

	    $start = $form['numPerPage'] * ($page - 1);
	    $peerIds = array_slice($peerIds, $start, $form['numPerPage']);
	    if (isset($form['selectedIds'])) {
		$selectedIds = $form['selectedIds'];
		foreach ($peerIds as $peerId) {
		    if (isset($selectedIds[$peerId])) {
			unset($selectedIds[$peerId]);
		    }
		}
	    }

	    /* Load all the peers */
	    list ($ret, $peerItems) = GalleryCoreApi::loadEntitiesById($peerIds);
	    if ($ret->isError()) {
		return array($ret->wrap(__FILE__, __LINE__), null);
	    }

	    /* get peer thumbnails */
	    list ($ret, $thumbnails) = GalleryCoreApi::fetchThumbnailsByItemIds($peerIds);
	    if ($ret->isError()) {
		return array($ret->wrap(__FILE__, __LINE__), null);
	    }

	    /* Build our peers table */
	    $peers = array();
	    foreach ($peerItems as $peerItem) {
		$peers[$peerItem->getId()] = $peerItem->getMemberData();
		$peers[$peerItem->getId()]['selected'] =
		    isset($form['selectedIds'][$peerItem->getId()]);

		/* While we're at it, attach the thumbnails */
		if (isset($thumbnails[$peerItem->getId()])) {
		    $thumbnail = $thumbnails[$peerItem->getId()];
		    list ($ret, $thumbnail) =
			GalleryCoreApi::rebuildDerivativeCacheIfNotCurrent($thumbnail->getId());
		    $peers[$peerItem->getId()]['thumbnail'] = $thumbnail->getMemberData();
		}
	    }

	    /* Find all the possible locations where this item can be linked. */
	    list ($ret, $ids) = GalleryCoreApi::fetchAllItemIds('GalleryAlbumItem', 'core.addDataItem');
	    if ($ret->isError()) {
		return array($ret->wrap(__FILE__, __LINE__), null);
	    }

	    /* Load all the album entities */
	    list ($ret, $albums) = GalleryCoreApi::loadEntitiesById($ids);
	    if ($ret->isError()) {
		return array($ret->wrap(__FILE__, __LINE__), null);
	    }
	}

	$urlGenerator =& $gallery->getUrlGenerator();

	$ItemCreateLink = array();
	$ItemCreateLink['canCancel'] = $urlGenerator->isNavigationBackPossible();
	$ItemCreateLink['peers'] = $peers;
	$ItemCreateLink['albumTree'] = GalleryUtilities::createAlbumTree($albums);
	$ItemCreateLink['page'] = $page;
	$ItemCreateLink['numPages'] = $numPages;
	$ItemCreateLink['numPerPage'] = $form['numPerPage'];
	$ItemCreateLink['selectedIds'] = array_keys($selectedIds);

	$template->setVariable('ItemCreateLink', $ItemCreateLink);
        $template->setVariable('controller', 'core.ItemCreateLink');
	return array(GalleryStatus::success(),
		     array('body' => 'modules/core/templates/ItemCreateLink.tpl'));
    }

    /**
     * @see GalleryView::getViewDescription()
     */
    function getViewDescription() {
	list ($ret, $core) = GalleryCoreApi::loadPlugin('module', 'core');
	if ($ret->isError()) {
	    return array($ret->wrap(__FILE__, __LINE__), null);
	}

	return array(GalleryStatus::success(), $core->translate('create link'));
    }
}
?>
