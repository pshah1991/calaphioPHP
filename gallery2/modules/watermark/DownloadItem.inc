<?php
/*
 * $RCSfile: DownloadItem.inc,v $
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
 * @version $Revision: 1.3 $ $Date: 2005/08/23 03:49:57 $
 * @package Watermark
 * @subpackage UserInterface
 * @author Alan Harder <alan.harder@sun.com>
 */

/**
 * Applies watermark on the fly and sends binary item
 *
 * @package Watermark
 * @subpackage UserInterface
 *
 */
class DownloadItemView extends GalleryView {

    /**
     * @see GalleryView::isImmediate
     */
    function isImmediate() {
	return true;
    }

    /**
     * @see GalleryView::renderImmediate
     */
    function renderImmediate($status, $error) {
	$itemId = GalleryUtilities::getRequestVariables('itemId');
	if (empty($itemId)) {
	    return GalleryStatus::error(ERROR_BAD_PARAMETER, __FILE__, __LINE__);
	}

	/* Load the item */
	list ($ret, $item) = GalleryCoreApi::loadEntitiesById($itemId);
	if ($ret->isError()) {
	    return $ret->wrap(__FILE__, __LINE__);
	}

	/* Figure out the filename */
	list ($ret, $pseudoFileName) = GalleryUtilities::getPseudoFileName($item);
	if ($ret->isError()) {
	    return $ret->wrap(__FILE__, __LINE__);
	}

	/* Get the path to the file */
	list ($ret, $path) = $item->fetchPath();
	if ($ret->isError()) {
	    return $ret->wrap(__FILE__, __LINE__);
	}

	/* Rebuild derivative cache, if necessary */
	if (GalleryUtilities::isA($item, 'GalleryDerivative')) {
	    list ($ret, $item) = GalleryCoreApi::rebuildDerivativeCacheIfNotCurrent($item->getId());
	    if ($ret->isError()) {
		return $ret->wrap(__FILE__, __LINE__);
	    }
	    $itemForPermission = $item->getParentId();
	    $derivativeType = $item->getDerivativeType();
	} else {
	    $itemForPermission = $item->getId();
	    $derivativeType = null;
	}
	$mimeType = $item->getMimeType();

	/* Apply Watermark -- skip for thumbnails */
	if ($derivativeType != DERIVATIVE_TYPE_IMAGE_THUMBNAIL) {
	    list ($ret, $toolkit) = GalleryCoreApi::getToolkitByOperation($mimeType, 'composite');
	    if ($ret->isError()) {
		return $ret->wrap(__FILE__, __LINE__);
	    }
	    if (!isset($toolkit)) {
		/*
		 * Allow items that can't be watermarked (movies, audio, etc) to be
		 * downloaded.  Uncomment the line below to block these downloads.
		 */
		/* return GalleryStatus::error(ERROR_PERMISSION_DENIED, __FILE__, __LINE__); */
	    } else {
		list ($ret, $hotlinkWatermarkId) =
		    GalleryCoreApi::getPluginParameter('module', 'watermark', 'hotlinkWatermarkId');
		if ($ret->isError()) {
		    return $ret->wrap(__FILE__, __LINE__);
		}
		if (!empty($hotlinkWatermarkId)) {
		    list ($ret, $watermark) = GalleryCoreApi::loadEntitiesById($hotlinkWatermarkId);
		    if ($ret->isError() && !($ret->getErrorCode() & ERROR_MISSING_OBJECT)) {
			/* Ignore error if this watermark has been deleted */
			return $ret->wrap(__FILE__, __LINE__);
		    }
		}
	    }
	    if (isset($watermark) && GalleryUtilities::isA($watermark, 'WatermarkImage')) {
		global $gallery;
		$platform = $gallery->getPlatform();
		$tmpFile = $platform->tempnam($gallery->getConfig('data.gallery.tmp'), 'wmk_');
		list ($ret, $mimeType) = $toolkit->performOperation(
		    $mimeType, 'composite', $path, $tmpFile,
		    array('plugins_data/modules/watermark/' . $watermark->getFileName(),
			  $watermark->getMimeType(),
			  $watermark->getWidth(), $watermark->getHeight(),
			  'manual', $watermark->getXPercentage(), $watermark->getYPercentage()));
		if ($ret->isError()) {
		    return $ret->wrap(__FILE__, __LINE__);
		}
		$path = $tmpFile;
	    }
	}

	$ret = $this->_sendFile(array('derivativePath' => $path,
				      'derivativeType' => $derivativeType,
				      'mimeType' => $mimeType,
				      'pseudoFileName' => $pseudoFileName,
				      'parentId' => $itemForPermission));
	if ($ret->isError()) {
	    return $ret->wrap(__FILE__, __LINE__);
	}

	if (isset($tmpFile)) {
	    @$platform->unlink($tmpFile);
	}

	return GalleryStatus::success();
    }

    function _sendFile($data) {
	global $gallery;
	$platform = $gallery->getPlatform();

	/* Make sure we have permission */
	$permission = 'core.viewSource';
	switch($data['derivativeType']) {
	    case DERIVATIVE_TYPE_IMAGE_THUMBNAIL:
		$permission = 'core.view';
		break;

	    case DERIVATIVE_TYPE_IMAGE_RESIZE:
		$permission = 'core.viewResizes';
		break;

	    /* DERIVATIVE_TYPE_IMAGE_PREFERRED uses core.viewSource */
	}
	$ret = GalleryCoreApi::assertHasItemPermission($data['parentId'], $permission);
	if ($ret->isError()) {
	    return $ret->wrap(__FILE__, __LINE__);
	}

	header('Content-type: ' . $data['mimeType']);
	header('Content-Disposition: inline; filename="' . $data['pseudoFileName'] . '"');
	$stats = $platform->stat($data['derivativePath']);
	if ($stats[7] > 0) {
	    header('Content-length: ' . $stats[7]);
	}
	header('Last-Modified: ' . GalleryUtilities::getHttpDate($stats[9]));
	header('Expires: Sun, 17 Jan 2038 12:00:00 GMT');

	/*
	 * Don't use readfile() because it buffers the entire file in memory
	 * Profiling shows that this approach is as efficient as fpassthru()
	 * but we get to call guaranteeTimeLimit which prevents it from failing on
	 * very large files.
	 */
	if ($fd = $platform->fopen($data['derivativePath'], 'rb')) {
	    while (true) {
		$bits = $platform->fread($fd, 65535);
		if (strlen($bits) == 0) {
		    break;
		}
		print $bits;
		$gallery->guaranteeTimeLimit(30);
	    }
	    $platform->fclose($fd);
	}

	return GalleryStatus::success();
    }
}
?>
