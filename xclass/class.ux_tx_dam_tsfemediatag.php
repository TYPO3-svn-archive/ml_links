<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Rene Fritz <r.fritz@colorcube.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Plugin 'media' for the 'dam' extension.
 *
 * @author	Rene Fritz <r.fritz@colorcube.de>
 * @package	TYPO3
 * @subpackage	tx_dam
 */
class ux_tx_dam_tsfemediatag extends tx_dam_tsfemediatag {

	/**
	 * Implements the "typolink" property of stdWrap (and others)
	 * Basically the input string, $linktext, is (typically) wrapped in a <a>-tag linking to some page, email address, file or URL based on a parameter defined by the configuration array $conf.
	 * This function is best used from internal functions as is. There are some API functions defined after this function which is more suited for general usage in external applications.
	 * Generally the concept "typolink" should be used in your own applications as an API for making links to pages with parameters and more. The reason for this is that you will then automatically make links compatible with all the centralized functions for URL simulation and manipulation of parameters into hashes and more.
	 * For many more details on the parameters and how they are intepreted, please see the link to TSref below.
	 *
	 * @param string $linktxt The string (text) to link
	 * @param array $conf TypoScript configuration (see link below)
	 * @param tslib_cObj $cObj
	 * @return	string		A link-wrapped string.
	 * @see stdWrap(), tslib_pibase::pi_linkTP()
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=321&cHash=59bd727a5e
	 */
	function typoLink($linktxt, $conf, $cObj = NULL) {
		$finalTagParts = array();

		// If called from media link handler, use the reference of the passed cObj
		if (!is_object($this->cObj)) {
			$this->cObj = $cObj;
		}

		$link_param = trim($this->cObj->stdWrap($conf['parameter'], $conf['parameter.']));

		$initP = '?id=' . $GLOBALS['TSFE']->id . '&type=' . $GLOBALS['TSFE']->type;
		$this->cObj->lastTypoLinkUrl = '';
		$this->cObj->lastTypoLinkTarget = '';
		if ($link_param) {
			$link_paramA = t3lib_div::unQuoteFilenames($link_param, TRUE);

			$link_param = trim($link_paramA[0]);	// Link parameter value
			$linkClass = trim($link_paramA[2]);		// Link class
			if ($linkClass === '-') $linkClass = '';	// The '-' character means 'no class'. Necessary in order to specify a title as fourth parameter without setting the target or class!
			$forceTarget = trim($link_paramA[1]);	// Target value
			$forceTitle = trim($link_paramA[3]);	// Title value
			if ($forceTarget === '-') $forceTarget = '';	// The '-' character means 'no target'. Necessary in order to specify a class as third parameter without setting the target!
			// Check, if the target is coded as a JS open window link:
			$JSwindowParts = array();
			$JSwindowParams = '';
			$onClick = '';
			if ($forceTarget && preg_match('/^([0-9]+)x([0-9]+)(:(.*)|.*)$/', $forceTarget, $JSwindowParts)) {
					// Take all pre-configured and inserted parameters and compile parameter list, including width+height:
				$JSwindow_tempParamsArr = t3lib_div::trimExplode(',', strtolower($conf['JSwindow_params'] . ',' . $JSwindowParts[4]), TRUE);
				$JSwindow_paramsArr = array();
				foreach ($JSwindow_tempParamsArr as $JSv) {
					list($JSp, $JSv) = explode('=', $JSv);
					$JSwindow_paramsArr[$JSp] = $JSp . '=' . $JSv;
				}
				// Add width/height:
				$JSwindow_paramsArr['width'] = 'width=' . $JSwindowParts[1];
				$JSwindow_paramsArr['height'] = 'height=' . $JSwindowParts[2];
				// Imploding into string:
				$JSwindowParams = implode(',', $JSwindow_paramsArr);
				$forceTarget = '';	// Resetting the target since we will use onClick.
			}

			// Internal target:
			$target = isset($conf['target']) ? $conf['target'] : $GLOBALS['TSFE']->intTarget;
			if ($conf['target.']) {
				$target = $this->cObj->stdWrap($target, $conf['target.']);
			}

			// Checking if the id-parameter is an alias.
			if (version_compare(TYPO3_version, '4.6.0', '>=')) {
				$isInteger = t3lib_utility_Math::canBeInterpretedAsInteger($link_param);
			} else {
				$isInteger = t3lib_div::testInt($link_param);
			}
			if (!$isInteger) {
				$GLOBALS['TT']->setTSlogMessage("tx_dam_tsfemediatag->typolink(): File id '" . $link_param . "' is not an integer, so '" . $linktxt . "' was not linked.",1);
				return $linktxt;
			}

			if (!is_object($media = tx_dam::media_getByUid($link_param))) {
				$GLOBALS['TT']->setTSlogMessage("tx_dam_tsfemediatag->typolink(): File id '" . $link_param . "' was not found, so '" . $linktxt . "' was not linked.", 1);
				return $linktxt;
			}

			if (!$media->isAvailable) {
				$GLOBALS['TT']->setTSlogMessage("tx_dam_tsfemediatag->typolink(): File '" . $media->getPathForSite() . "' (" . $link_param . ") did not exist, so '" . $linktxt . "' was not linked.", 1);
				return $linktxt;
			}


			$meta = $media->getMetaArray();
			if (is_array($this->conf['procFields.'])) {
				foreach ($this->conf['procFields.'] as $field => $fieldConf) {

					if (substr($field, -1, 1) === '.') {
						$fN = substr($field, 0, -1);
					} else {
						$fN = $field;
						$fieldConf = array();
					}

					$meta[$fN] = $media->getContent ($fN, $fieldConf);
				}
			}


			$this->addMetaToData ($meta);


			// Title tag
			$title = $conf['title'];

			if ($conf['title.']) {
				$title = $this->cObj->stdWrap($title, $conf['title.']);
			}

			// Setting title if blank value to link:
			if ($linktxt === '') $linktxt = $media->getContent('title');

			if ($GLOBALS['TSFE']->config['config']['jumpurl_enable']) {
				$this->cObj->lastTypoLinkUrl = $GLOBALS['TSFE']->absRefPrefix.$GLOBALS['TSFE']->config['mainScript'].$initP.'&jumpurl='.rawurlencode($media->getPathForSite()) . $GLOBALS['TSFE']->getMethodUrlIdToken;
			} else {
				$this->cObj->lastTypoLinkUrl = $media->getURL();
			}
			if ($forceTarget) {
				$target = $forceTarget;
			}
			$this->cObj->lastTypoLinkTarget = $target;

			$finalTagParts['url'] = $this->cObj->lastTypoLinkUrl;
			$finalTagParts['targetParams'] = $target ? ' target="' . $target . '"' : '';
			$finalTagParts['aTagParams'] = $this->cObj->getATagParams($conf);
			$finalTagParts['TYPE'] = 'file';

			if ($forceTitle) {
				$title = $forceTitle;
			}

			if ($JSwindowParams) {

				// Create TARGET-attribute only if the right doctype is used
				if (!t3lib_div::inList('xhtml_strict,xhtml_11,xhtml_2', $GLOBALS['TSFE']->xhtmlDoctype)) {
					$target = ' target="FEopenLink"';
				} else {
					$target = '';
				}

				$onClick = "vHWin=window.open('" . $GLOBALS['TSFE']->baseUrlWrap($finalTagParts['url']) . "','FEopenLink','" . $JSwindowParams . "');vHWin.focus();return false;";
				$res = '<a href="' . htmlspecialchars($finalTagParts['url']) . '"' . $target . ' onclick="' . htmlspecialchars($onClick) . '"'
					. ($title ? ' title="' . $title . '"' : '')
					. ($linkClass ? ' class="' . $linkClass . '"' : '')
					. $finalTagParts['aTagParams'] . '>';
			} else {
				$res = '<a href="' . htmlspecialchars($finalTagParts['url']) . '"'
					. ($title ? ' title="' . $title . '"' : '')
					. $finalTagParts['targetParams']
					. ($linkClass ? ' class="' . $linkClass . '"' : '')
					. $finalTagParts['aTagParams'] . '>';
			}

			// Call userFunc
			if ($conf['userFunc']) {
				$finalTagParts['TAG'] = $res;
				$res = $this->cObj->callUserFunction($conf['userFunc'], $conf['userFunc.'], $finalTagParts);
			}

			// If flag "returnLastTypoLinkUrl" set, then just return the latest URL made:
			if ($conf['returnLast']) {
				switch ($conf['returnLast']) {
					case 'url':
						return $this->cObj->lastTypoLinkUrl;
						break;
					case 'target':
						return $this->cObj->lastTypoLinkTarget;
						break;
				}
			}

			if ($conf['postUserFunc']) {
				$linktxt = $this->cObj->callUserFunction($conf['postUserFunc'], $conf['postUserFunc.'], $linktxt);
			}

			if ($conf['ATagBeforeWrap']) {
				return $res . $this->cObj->wrap($linktxt, $conf['wrap']) . '</a>';
			} else {
				return $this->cObj->wrap($res . $linktxt . '</a>', $conf['wrap']);
			}
		} else {
			return $linktxt;
		}
	}
}
