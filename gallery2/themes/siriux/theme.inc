<?php
/*
 * $RCSfile: theme.inc,v $
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
 * @version $Revision: 1.5 $ $Date: 2005/09/10 20:28:16 $
 * @package Gallery
 * @author Nico Kaiser <nico@siriux.net>
 */

/**
 * This implements the standard gallery theme
 *
 * @package GalleryTheme
 * @subpackage Theme
 */
class SiriuxTheme extends GalleryTheme {

    /**
     * Constructor
     */
    function SiriuxTheme() {
	global $gallery;

	$this->setId('siriux');
	$this->setName($gallery->i18n('Siriux'));
	$this->setDescription($gallery->i18n('Siriux.net Gallery theme'));
	$this->setVersion('1.0.0');
	$this->setRequiredCoreApi(array(6, 5));
	$this->setRequiredThemeApi(array(2, 0));
	$this->setStandardSettings(
	    array('showImageOwner' => 0,
		  'perPage' => 9,
		  'contentWidth' => 700,
		  'albumBlocks' => serialize(array(
			array('comment.ViewComments', array()))),
		  'photoBlocks' => serialize(array(
			array('exif.ExifInfo', array()),
			array('comment.ViewComments', array())))));
    }

    /**
     * @see GalleryTheme::showAlbumPage
     */
    function showAlbumPage(&$template, $item, $params, $childIds) {
	$ret = $this->loadCommonTemplateData(
	    $template, $item, $params,
	    array('owner', 'viewCount', 'childCount', 'descendentCount', 'parents',
		  'systemLinks', 'itemLinks', 'itemSummaries', 'permissions',
		  'thumbnails', 'pageNavigator', 'jumpRange'),
	     $childIds);
	if ($ret->isError()) {
	    return array($ret->wrap(__FILE__, __LINE__), null);
	}

	/* Override some navigator settings */
	$theme =& $template->getVariableByReference('theme');
	if (isset($theme['navigator']['first'])) {
	    unset($theme['navigator']['first']);
	}
	if (isset($theme['navigator']['last'])) {
	    unset($theme['navigator']['last']);
	}
	$theme['params']['groupByYear'] = !$item->getParentId();

	/* Find the thumbnail size for this album */
	$theme['params']['thumbnailSize'] = 150;
	list ($ret, $preferences) =
	    GalleryCoreApi::fetchDerivativePreferencesForItem($item->getId());
	if ($ret->isError()) {
	    return array($ret->wrap(__FILE__, __LINE__), null);
	}
	foreach ($preferences as $preference) {
	    if ($preference['derivativeType'] == DERIVATIVE_TYPE_IMAGE_THUMBNAIL &&
		  preg_match('/thumbnail\|(\d+)/', $preference['derivativeOperations'], $matches)) {
		$theme['params']['thumbnailSize'] =  $matches[1];
		break;
	    }
	}

	return array(GalleryStatus::success(), 'theme.tpl');
    }

    /**
     * @see GalleryTheme::showPhotoPage
     */
    function showPhotoPage(&$template, $item, $params) {
	$ret = $this->loadCommonTemplateData($template, $item, $params,
	    array('owner', 'parents', 'systemLinks', 'itemLinks', 'permissions',
		  'itemLinksDetailed', 'itemNavigator', 'imageViews'));
	if ($ret->isError()) {
	    return array($ret->wrap(__FILE__, __LINE__), null);
	}

	/* Override some navigator settings */
	$theme =& $template->getVariableByReference('theme');
	if (isset($theme['navigator']['first'])) {
	    unset($theme['navigator']['first']);
	}
	if (isset($theme['navigator']['last'])) {
	    unset($theme['navigator']['last']);
	}

	return array(GalleryStatus::success(), 'theme.tpl');
    }

    /**
     * @see GalleryTheme::showModulePage
     */
    function showModulePage(&$template, $item, $params, $templateFile) {
	$ret = $this->loadCommonTemplateData(
	    $template, $item, $params, array('parents', 'systemLinks'));
	if ($ret->isError()) {
	    return array($ret->wrap(__FILE__, __LINE__), null);
	}

	/* Add in our extra stuff */
	$theme =& $template->getVariableByReference('theme');
	$theme['moduleTemplate'] = $templateFile;

	/* Add our header and styles */
	return array(GalleryStatus::success(), 'theme.tpl');
    }

    /**
     * @see GalleryTheme::showAdminPage
     */
    function showAdminPage(&$template, $item, $params, $templateFile) {
	$ret = $this->loadCommonTemplateData(
	    $template, $item, $params, array('parents', 'systemLinks'));
	if ($ret->isError()) {
	    return array($ret->wrap(__FILE__, __LINE__), null);
	}

	/* Add in our extra stuff */
	$theme =& $template->getVariableByReference('theme');
	$theme['adminTemplate'] = $templateFile;

	return array(GalleryStatus::success(), 'theme.tpl');
    }

    /**
     * @see GalleryTheme::showErrorPage
     */
    function showErrorPage(&$template) {
	return array(GalleryStatus::success(), 'error.tpl');
    }

    /**
     * @see GalleryTheme::showProgressBarPage
     */
    function showProgressBarPage(&$template, $item, $params) {
	$ret = $this->loadCommonTemplateData(
	    $template, $item, $params, array('parents', 'systemLinks'));
	if ($ret->isError()) {
	    return array($ret->wrap(__FILE__, __LINE__), null);
	}

	return array(GalleryStatus::success(), 'theme.tpl');
    }

    /**
     * @see GalleryTheme::getSettings()
     */
    function getSettings($itemId=null) {
	list ($ret, $settings) = parent::getSettings($itemId);
	if ($ret->isError()) {
	    return array($ret->wrap(__FILE__, __LINE__), null);
	}

	list ($ret, $params) = $this->fetchParameters($itemId);
	if ($ret->isError()) {
	    return array($ret->wrap(__FILE__, __LINE__), null);
	}

	/* Add in our custom settings */
	$params = array_merge(array('contentWidth' => 700), $params);
	$settings[] = array('key' => 'contentWidth',
	                    'name' => $this->translate('Width of page content in pixels'),
	                    'type' => 'text-field',
	                    'typeParams' => array('size' => 2),
	                    'value' => $params['contentWidth']);

	return array(GalleryStatus::success(), $settings);
    }

    /**
     * @see GalleryTheme::validateSettings
     */
    function validateSettings($settings) {
	$error = parent::validateSettings($settings);
	if (!is_numeric($settings['contentWidth'])) {
	    $error['contentWidth'] = $this->translate('You must enter a number');
	}
	return $error;
    }
}
?>
