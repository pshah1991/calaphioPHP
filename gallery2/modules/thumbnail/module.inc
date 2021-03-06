<?php
/*
 * $RCSfile: module.inc,v $
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
 * @version $Revision: 1.51 $ $Date: 2005/09/10 20:28:15 $
 * @package Thumbnail
 * @author Alan Harder <alan.harder@sun.com>
 */

/**
 * Thumbnail defaults
 *
 * This module provides default thumbnails for non-image mime types.
 *
 * @package Thumbnail
 */
class ThumbnailModule extends GalleryModule {

    function ThumbnailModule() {
	global $gallery;

	$this->setId('thumbnail');
	$this->setName($gallery->i18n('Thumbnail Manager'));
	$this->setDescription($gallery->i18n('Set default thumbnails for non-image items'));
	$this->setVersion('1.0.0'); /* Update upgrade() function below too */
	$this->setGroup('display', $this->translate('Display'));
	$this->setCallbacks('getSiteAdminViews|registerEventListeners');
	$this->setRequiredCoreApi(array(6, 0));
	$this->setRequiredModuleApi(array(2, 0));
    }

    /**
     * @see GalleryModule::upgrade()
     */
    function upgrade($currentVersion) {
	global $gallery;

	$platform = $gallery->getPlatform();
	$slash = $platform->getDirectorySeparator();
	$thumbnailDir = $gallery->getConfig('data.gallery.plugins_data') . 'modules' .
	    $slash . 'thumbnail' . $slash;

	if (!isset($currentVersion)) {
	    $currentVersion = '0';
	}

	switch ($currentVersion) {
	case '0':
	    /* Initial install */
	    list ($success) = GalleryUtilities::guaranteeDirExists($thumbnailDir);
	    if (!$success) {
		return GalleryStatus::error(ERROR_PLATFORM_FAILURE, __FILE__, __LINE__,
					    "Unable to create directory: $thumbnailDir");
	    }
	    break;

	case '0.8.1':
	case '0.8.2':
	    /* Assign parent to existing ThumbnailImage entities */
	    list ($ret, $rootId) =
		GalleryCoreApi::getPluginParameter('module', 'core', 'id.rootAlbum');
	    if ($ret->isError()) {
		return $ret->wrap(__FILE__, __LINE__);
	    }
	    $storage =& $gallery->getStorage();
	    $query = '
	    INSERT INTO [GalleryChildEntity] SELECT [ThumbnailImage::id], ' . $rootId . '
	    FROM [ThumbnailImage]
	    ';
	    $ret = $storage->execute($query);
	    if ($ret->isError()) {
		return $ret->wrap(__FILE__, __LINE__);
	    }

	case '0.8.3':
	case '0.8.4':
	case '0.8.5':
	case '0.8.6':
	case '0.8.7':
	case '0.8.8':
	case '0.9.1':
	    /* Move thumbnail dir from g2data/ to g2data/plugins_data */
	    $oldDir = $gallery->getConfig('data.gallery.base') . 'thumbnail' . $slash;
	    if ($platform->is_dir($oldDir)) {
		if (!$platform->rename($oldDir, $thumbnailDir)) {
		    return GalleryStatus::error(ERROR_PLATFORM_FAILURE, __FILE__, __LINE__,
						"Unable to move $oldDir to $thumbnailDir");
		}
	    } else {
		list ($success) = GalleryUtilities::guaranteeDirExists($thumbnailDir);
		if (!$success) {
		    return GalleryStatus::error(ERROR_PLATFORM_FAILURE, __FILE__, __LINE__,
						"Unable to create $thumbnailDir");
		}
	    }

	case '0.9.2':
	case '0.9.3':
	    /* Now allow thumbnail for root album; remove parent for mime type thumbnails */
	    list ($ret, $rootId) =
		GalleryCoreApi::getPluginParameter('module', 'core', 'id.rootAlbum');
	    if ($ret->isError()) {
		return $ret->wrap(__FILE__, __LINE__);
	    }
	    $query = '
	    SELECT
	      [ThumbnailImage::id]
	    FROM
	      [ThumbnailImage], [GalleryChildEntity]
	    WHERE
	      [ThumbnailImage::id] = [GalleryChildEntity::id]
	      AND
	      [GalleryChildEntity::parentId] = ?
	    ';
	    list ($ret, $results) = $gallery->search($query, array($rootId));
	    if ($ret->isError()) {
		return $ret->wrap(__FILE__, __LINE__);
	    }
	    $ids = array();
	    while ($result = $results->nextResult()) {
		$ids[] = $result[0];
	    }

	    GalleryCoreApi::relativeRequireOnce('modules/thumbnail/classes/ThumbnailImage.class');
	    if (!empty($ids)) {
		list ($ret, $lockId) = GalleryCoreApi::acquireWriteLock($ids);
		if ($ret->isError()) {
		    return $ret->wrap(__FILE__, __LINE__);
		}

		list ($ret, $thumbs) = GalleryCoreApi::loadEntitiesById($ids);
		if ($ret->isError()) {
		    return $ret->wrap(__FILE__, __LINE__);
		}

		foreach ($thumbs as $thumb) {
		    $thumb->setParentId(0);
		    $ret = $thumb->save();
		    if ($ret->isError()) {
			GalleryCoreApi::releaseLocks($lockId);
			return $ret->wrap(__FILE__, __LINE__);
		    }
		}

		$ret = GalleryCoreApi::releaseLocks($lockId);
		if ($ret->isError()) {
		    return $ret->wrap(__FILE__, __LINE__);
		}
	    }

	case '0.9.4':
	case '0.9.5':
	case '0.9.6':
	case '0.9.7':
	case '0.9.8':

	case 'end of upgrade path':
	    /*
	     * Leave this bogus case at the end of the legitimate case statements so that we
	     * always properly terminate our upgrade path with a break.
	     */
	    break;

	default:
	    return GalleryStatus::error(ERROR_BAD_PLUGIN, __FILE__, __LINE__,
					sprintf('Unknown module version %s', $currentVersion));
	}

	return GalleryStatus::success();
    }

    /**
     * @see GalleryModule::performFactoryRegistrations()
     */
    function performFactoryRegistrations() {
	$ret = GalleryCoreApi::registerFactoryImplementation(
	    'GalleryEntity', 'ThumbnailImage', 'ThumbnailImage',
	    'modules/thumbnail/classes/ThumbnailImage.class', 'thumbnail', null);
	if ($ret->isError()) {
	    return $ret->wrap(__FILE__, __LINE__);
	}

	$ret = GalleryCoreApi::registerFactoryImplementation(
	    'GalleryToolkit', 'ThumbnailToolkit', 'Thumbnail',
	    'modules/thumbnail/classes/ThumbnailToolkit.class', 'thumbnail', null);
	if ($ret->isError()) {
	    return $ret->wrap(__FILE__, __LINE__);
	}

	$ret = GalleryCoreApi::registerFactoryImplementation(
	    'ItemEditOption', 'CustomThumbnailOption', 'CustomThumbnailOption',
	    'modules/thumbnail/CustomThumbnailOption.inc', 'thumbnail', array('ItemEditItem'));
	if ($ret->isError()) {
	    return $ret->wrap(__FILE__, __LINE__);
	}

	return GalleryStatus::success();
    }

    /**
     * @see GalleryModule::registerEventListeners()
     */
    function registerEventListeners() {
	GalleryCoreApi::registerEventListener('GalleryEntity::delete', new ThumbnailModule());
    }

    /**
     * @see GalleryModule::isRecommendedDuringInstall
     */
    function isRecommendedDuringInstall() {
	return false;
    }

    /**
     * @see GalleryModule::autoConfigure
     */
    function autoConfigure() {
	/* We don't require any special configuration */
	return array(GalleryStatus::success(), true);
    }

    /**
     * @see GalleryModule::activate()
     */
    function activate($postActivationEvent=true) {
	GalleryCoreApi::relativeRequireOnce('modules/thumbnail/classes/ThumbnailHelper.class');
	global $gallery;

	/* Register toolkit operation for any mime type settings already in the database */
	list ($ret, $mimeTypeMap) = ThumbnailHelper::fetchMimeTypeMap();
	if ($ret->isError()) {
	    return array($ret->wrap(__FILE__, __LINE__), null);
	}
	if (!empty($mimeTypeMap)) {
	    $ret = GalleryCoreApi::registerToolkitOperation('Thumbnail',
				   array_keys($mimeTypeMap), 'convert-to-image/jpeg', array(),
				   $gallery->i18n('Convert to a JPEG'), 'image/jpeg', 50);
	    if ($ret->isError()) {
		return array($ret->wrap(__FILE__, __LINE__), null);
	    }
	}

	/* Set some defaults if not already supported */
	list ($ret, $toolkitMime) = ThumbnailHelper::fetchToolkitSupport();
	if ($ret->isError()) {
	    return array($ret->wrap(__FILE__, __LINE__), null);
	}
	$itemId = array();
	GalleryCoreApi::relativeRequireOnce('modules/thumbnail/classes/ThumbnailImage.class');
	foreach (array(
	    'G2audio.jpg' => array('audio/mpeg', 'audio/x-wav', 'audio/x-aiff',
				   'audio/midi', 'audio/basic', 'audio/x-ms-wma'),
	    'G2video.jpg' => array('video/mpeg', 'video/quicktime', 'video/x-msvideo',
				   'video/x-ms-asf', 'video/x-ms-wmv')
	    ) as $mimeImage => $mimeList) {
	    foreach ($mimeList as $mime) {
		if (!isset($toolkitMime[$mime])) {
		    if (!isset($itemId[$mimeImage])) {
			list ($ret, $itemId[$mimeImage]) = ThumbnailHelper::addItem($mime, null,
			    $mimeImage, dirname(__FILE__) . '/images/' . $mimeImage, 'image/jpeg',
			    array('width' => 400, 'height' => 352),
			    new ThumbnailImage());
			if ($ret->isError()) {
			    return array($ret->wrap(__FILE__, __LINE__), null);
			}
		    } else {
			$ret = ThumbnailHelper::updateItem($itemId[$mimeImage], $mime);
			if ($ret->isError()) {
			    return array($ret->wrap(__FILE__, __LINE__), null);
			}
		    }
		}
	    }
	}

	list ($ret, $redirect) = parent::activate($postActivationEvent);
	if ($ret->isError()) {
	    return array($ret->wrap(__FILE__, __LINE__), null);
	}

	return array(GalleryStatus::success(), $redirect);
    }

    /**
     * @see GalleryModule::deactivate()
     */
    function deactivate($postDeactivationEvent=true) {
	global $gallery;

	list ($ret, $redirect) = parent::deactivate($postDeactivationEvent);
	if ($ret->isError()) {
	    return array($ret->wrap(__FILE__, __LINE__), null);
	}

	/* Unregister all of our properties and operations */
	$ret = GalleryCoreApi::unregisterToolkit('Thumbnail');
	if ($ret->isError()) {
	    return array($ret->wrap(__FILE__, __LINE__), null);
	}

	return array(GalleryStatus::success(), $redirect);
    }

    /**
     * @see GalleryModule::getSiteAdminViews()
     */
    function getSiteAdminViews() {
	return array(GalleryStatus::success(),
		     array(array('name' => $this->translate('Thumbnails'),
				 'view' => 'thumbnail.ThumbnailSiteAdmin')));
    }

    /**
     * Event handler for GalleryEntity::delete event.
     * Remove custom ThumbnailImage if the thumbnail itself is deleted.
     *
     * @see GalleryEventListener::handleEvent
     */
    function handleEvent($event) {
	$entity = $event->getEntity();
	if (GalleryUtilities::isA($entity, 'GalleryDerivativeImage') &&
		$entity->getDerivativeType() == DERIVATIVE_TYPE_IMAGE_THUMBNAIL &&
		$entity->getDerivativeSourceId() != $entity->getParentId()) {
	    list ($ret, $source) =
		GalleryCoreApi::loadEntitiesById($entity->getDerivativeSourceId());
	    if ($ret->isError()) {
		if ($ret->getErrorCode() & ERROR_MISSING_OBJECT) {
		    /* Already gone.. ok! */
		    return array(GalleryStatus::success(), null);
		}
		return array($ret->wrap(__FILE__, __LINE__), null);
	    }
	    if (GalleryUtilities::isA($source, 'ThumbnailImage')) {
		$ret = GalleryCoreApi::deleteEntityById($source->getId());
		if ($ret->isError()) {
		    return array($ret->wrap(__FILE__, __LINE__), null);
		}
	    }
	}
	return array(GalleryStatus::success(), null);
    }
}
?>
