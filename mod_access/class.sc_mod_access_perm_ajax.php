<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007 - 2008 mehrwert <typo3@mehrwert.de>
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
 * This class extends the commerce module in the TYPO3 Backend to provide
 * convenient methods of editing of category permissions (including category ownership
 * (user and group)) via new TYPO3AJAX facility
 */
class SC_mod_access_perm_ajax {
	/**
	 * The local configuration array
	 *
	 * @var array
	 */
	protected $conf = array();

	/**
	 * TYPO3 Back Path ###CALCULATE THIS###
	 *
	 * @var string
	 */
	protected $backPath = '../../../../typo3/';

	/**
	 * The constructor of this class
	 *
	 * @return self
	 */
	public function __construct() {
			// Configuration, variable assignment
			// Page is actually the current category UID
		$this->conf['page']          = t3lib_div::_POST('page');
		$this->conf['who']           = t3lib_div::_POST('who');
		$this->conf['mode']          = t3lib_div::_POST('mode');
		$this->conf['bits']          = intval(t3lib_div::_POST('bits'));
		$this->conf['permissions']   = intval(t3lib_div::_POST('permissions'));
		$this->conf['action']	     = t3lib_div::_POST('action');
		$this->conf['ownerUid']      = intval(t3lib_div::_POST('ownerUid'));
		$this->conf['username']      = t3lib_div::_POST('username');
		$this->conf['groupUid']      = intval(t3lib_div::_POST('groupUid'));
		$this->conf['groupname']     = t3lib_div::_POST('groupname');
		$this->conf['editLockState'] = intval(t3lib_div::_POST('editLockState'));

			// User: Replace some parts of the posted values
		$this->conf['owner_data']	      = urldecode(t3lib_div::_POST('owner_data'));
		$this->conf['owner_data']         = str_replace('new_page_owner=', '', $this->conf['owner_data']);
		$this->conf['owner_data']         = str_replace('%3B', ';', $this->conf['owner_data']);
		$temp_owner_data                  = explode(';', $this->conf['owner_data']);
		$this->conf['new_owner_uid']      = intval($temp_owner_data[0]);
		$this->conf['new_owner_username'] = htmlspecialchars($temp_owner_data[1]);

			// Group: Replace some parts of the posted values
		$this->conf['group_data']         = urldecode(t3lib_div::_POST('group_data'));
		$this->conf['group_data']         = str_replace('new_page_group=', '', $this->conf['group_data']);
		$this->conf['group_data']         = str_replace('%3B', ';', $this->conf['group_data']);
		$temp_group_data                  = explode(';', $this->conf['group_data']);
		$this->conf['new_group_uid']      = intval($temp_group_data[0]);
		$this->conf['new_group_username'] = htmlspecialchars($temp_group_data[1]);

	}

	/**
	 * The main dispatcher function. Collect data and prepare HTML output.
	 *
	 * @param array $params: array of parameters from the AJAX interface, currently unused
	 * @param TYPO3AJAX $ajaxObj: object of type TYPO3AJAX
	 * @return Void
	 */
	public function dispatch($params = array(), TYPO3AJAX &$ajaxObj = NULL) {
		$content = '';

			// Basic test for required value
		if ($this->conf['page'] > 0) {

				// Init TCE for execution of update
			/** @var t3lib_TCEmain $tce */
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			$tce->stripslashes_values = 1;

				// Determine the scripts to execute
			switch ($this->conf['action']) {

					// Return the select to change the owner (BE user) of the page
				case 'show_change_owner_selector':
					$content = $this->renderUserSelector($this->conf['page'], $this->conf['ownerUid'], $this->conf['username']);
					break;

					// Change the owner and return the new owner HTML snippet
				case 'change_owner':
					if (is_int($this->conf['new_owner_uid'])) {

							// Prepare data to change
						$data = array('perms_userid' => $this->conf['new_owner_uid']);

							// Initialize the TCE
						$tce->start($data, array());

							// Execute TCE Update
							// Check rights
						$table = 'tx_commerce_categories';
						$id = $this->conf['page'];

						if ($tce->checkModifyAccessList($table) && $tce->checkRecordUpdateAccess($table, $id) && $tce->BE_USER->recordEditAccessInternals($table, $id)) {
							$tce->updateDB($table, $id, $data);
							$tce->placeholderShadowing($table, $id);
						}

						$content = $this->renderOwnername($this->conf['page'], $this->conf['new_owner_uid'], $this->conf['new_owner_username']);
					} else {
						$ajaxObj->setError('An error occured: No page owner uid specified.');
					}
					break;

					// Return the select to change the group (BE group) of the page
				case 'show_change_group_selector':
					$content = $this->renderGroupSelector($this->conf['page'], $this->conf['groupUid'], $this->conf['groupname']);
					break;

					// Change the group and return the new group HTML snippet
				case 'change_group':
					if (is_int($this->conf['new_group_uid'])) {

							// Prepare data to change
						$data = array('perms_groupid' => $this->conf['new_group_uid']);

							// Initialize the TCE
						$tce->start($data, array());

							// Execute TCE Update
							// Check rights
						$table = 'tx_commerce_categories';
						$id = $this->conf['page'];

						if ($tce->checkModifyAccessList($table) && $tce->checkRecordUpdateAccess($table, $id) && $tce->BE_USER->recordEditAccessInternals($table, $id)) {
							$tce->updateDB($table, $id, $data);
							$tce->placeholderShadowing($table, $id);
						}

						$content = $this->renderGroupname($this->conf['page'], $this->conf['new_group_uid'], $this->conf['new_group_username']);
					} else {
						$ajaxObj->setError('An error occured: No page group uid specified.');
					}
					break;

					// Change the group and return the new group HTML snippet
				case 'toggle_edit_lock':
						// Toggle
					$this->conf['editLockState'] = ($this->conf['editLockState'] === 1 ? 0 : 1);

						// Prepare data to change
					$data = array('editlock' => $this->conf['editLockState']);

						// Initialize the TCE
					$tce->start($data, array());

						// Execute TCE Update
						// Check rights
					$table = 'tx_commerce_categories';
					$id = $this->conf['page'];

					if ($tce->checkModifyAccessList($table) && $tce->checkRecordUpdateAccess($table, $id) && $tce->BE_USER->recordEditAccessInternals($table, $id)) {
						$tce->updateDB($table, $id, $data);
						$tce->placeholderShadowing($table, $id);
					}

					$content = $this->renderToggleEditLock($this->conf['page'], $this->conf['editLockState']);
					break;

					// The script defaults to change permissions
				default:
					if ($this->conf['mode'] == 'delete') {
						$this->conf['permissions'] = intval($this->conf['permissions'] - $this->conf['bits']);
					} else {
						$this->conf['permissions'] = intval($this->conf['permissions'] + $this->conf['bits']);
					}

						// Prepare data to change
					$data = array('perms_' . $this->conf['who'] => $this->conf['permissions']);

						// Initialize the TCE
					$tce->start($data, array());

						// Execute TCE Update
						// Check rights
					$table = 'tx_commerce_categories';
					$id = $this->conf['page'];

					if ($tce->checkModifyAccessList($table) && $tce->checkRecordUpdateAccess($table, $id) && $tce->BE_USER->recordEditAccessInternals($table, $id)) {
						$tce->updateDB($table, $id, $data);
						$tce->placeholderShadowing($table, $id);
					}

					$content = $this->renderPermissions($this->conf['permissions'], $this->conf['page'], $this->conf['who']);
			}
		} else {
			$ajaxObj->setError('This script cannot be called directly.');
		}
		$ajaxObj->addContent($this->conf['page'] . '_' . $this->conf['who'], $content);
	}

	/********************************************
	 *
	 * Helpers for this script
	 *
	 ********************************************/

	/**
	 * Generate the user selector element
	 *
	 * @param	Integer		$page: The page id to change the user for
	 * @param	Integer		$ownerUid: The page owner uid
	 * @param	String		$username: The username to display
	 * @return	String		The html select element
	 */
	protected function renderUserSelector($page, $ownerUid, $username = '') {
			// Get usernames
		$beUsers = t3lib_BEfunc::getUserNames();

			// Init groupArray
		$groups = array();

		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];
		if (!$backendUser->isAdmin()) {
			$beUsers = t3lib_BEfunc::blindUserNames($beUsers, $groups, 1);
		}

			// Owner selector:
		$options = '';

			// Loop through the users
		foreach ($beUsers as $uid => $row) {
			$selected = ($uid == $ownerUid	? ' selected="selected"' : '');
			$options .= '<option value="' . $uid . ';' . htmlspecialchars($row['username']) . '"' . $selected . '>' . htmlspecialchars($row['username']) . '</option>';
		}

		$elementId = 'o_' . $page;
		$options = '<option value="0"></option>' . $options;
		$selector = '<select name="new_page_owner" id="new_page_owner">' . $options . '</select>';
		$saveButton = '<a onclick="WebPermissions.changeOwner(' . $page . ', ' . $ownerUid . ', \'' . $elementId .
			'\');"><img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/savedok.gif', 'width="21" height="16"') .
			' border="0" title="Change owner" align="top" alt="" /></a>';
		$cancelButton = '<a onclick="WebPermissions.restoreOwner(' . $page . ', ' . $ownerUid . ', \'' .
			($username == '' ? '<span class=not_set>[not set]</span>' : htmlspecialchars($username)) . '\', \'' .
			$elementId . '\');"><img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/closedok.gif', 'width="21" height="16"') .
			' border="0" title="Cancel" align="top" alt="" /></a>';
		$ret = $selector . $saveButton . $cancelButton;
		return $ret;
	}

	/**
	 * Generate the group selector element
	 *
	 * @param integer $page : The page id to change the user for
	 * @param integer $groupUid : The page group uid
	 * @param string $groupname
	 * @return String The html select element
	 */
	protected function renderGroupSelector($page, $groupUid, $groupname = '') {
			// Get usernames
		$beGroups = t3lib_BEfunc::getListGroupNames('title,uid');
		$beGroupKeys = array_keys($beGroups);
		$beGroupsO = $beGroups = t3lib_BEfunc::getGroupNames();

		/** @var t3lib_beUserAuth $backendUser */
		$backendUser = $GLOBALS['BE_USER'];
		if (!$backendUser->isAdmin()) {
			$beGroups = t3lib_BEfunc::blindGroupNames($beGroupsO, $beGroupKeys, 1);
		}

			// Group selector:
		$options = '';

			// flag: is set if the page-groupid equals one from the group-list
		$userset = 0;

			// Loop through the groups
		foreach ($beGroups as $uid => $row) {
			if ($uid == $groupUid) {
				$userset = 1;
				$selected = ' selected="selected"';
			} else {
				$selected = '';
			}
			$options .= '<option value="' . $uid . ';' . htmlspecialchars($row['title']) . '"' . $selected . '>' . htmlspecialchars($row['title']) . '</option>';
		}

			// If the group was not set AND there is a group for the page
		if (!$userset && $groupUid) {
			$options = '<option value="' . $groupUid . '" selected="selected">' . htmlspecialchars($beGroupsO[$groupUid]['title']) . '</option>' . $options;
		}

		$elementId = 'g_' . $page;
		$options = '<option value="0"></option>' . $options;
		$selector = '<select name="new_page_group" id="new_page_group">' . $options . '</select>';
		$saveButton = '<a onclick="WebPermissions.changeGroup(' . $page . ', ' . $groupUid . ', \'' . $elementId .
			'\');"><img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/savedok.gif', 'width="21" height="16"') .
			' border="0" title="Change group" align="top" alt="" /></a>';
		$cancelButton = '<a onclick="WebPermissions.restoreGroup(' . $page . ', ' . $groupUid . ', \'' .
			($groupname == '' ? '<span class=not_set>[not set]</span>' : htmlspecialchars($groupname)) . '\', \'' . $elementId .
			'\');"><img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/closedok.gif', 'width="21" height="16"') .
			' border="0" title="Cancel" align="top" alt="" /></a>';
		$ret = $selector . $saveButton . $cancelButton;
		return $ret;
	}

	/**
	 * Print the string with the new owner of a page record
	 *
	 * @param	Integer		$page: The TYPO3 page id
	 * @param	Integer		$ownerUid: The new page user uid
	 * @param	String		$username: The TYPO3 BE username (used to display in the element)
	 * @return	String		The new group wrapped in HTML
	 */
	public static function renderOwnername($page, $ownerUid, $username) {
		$elementId = 'o_' . $page;
		$ret = '<span id="' . $elementId . '"><a class="ug_selector" onclick="WebPermissions.showChangeOwnerSelector(' . $page .
			', ' . $ownerUid . ', \'' . $elementId . '\', \'' . htmlspecialchars($username) . '\');">' .
			($username == '' ? '<span class=not_set>[not set]</span>' : htmlspecialchars(t3lib_div::fixed_lgd_cs($username, 20))) .
			'</a></span>';
		return $ret;
	}

	/**
	 * Print the string with the new group of a page record
	 *
	 * @param	Integer		$page: The TYPO3 page id
	 * @param	Integer		$groupUid: The new page group uid
	 * @param	String		$groupname: The TYPO3 BE groupname (used to display in the element)
	 * @return	String		The new group wrapped in HTML
	 */
	public static function renderGroupname($page, $groupUid, $groupname) {
		$elementId = 'g_' . $page;
		$ret = '<span id="' . $elementId . '"><a class="ug_selector" onclick="WebPermissions.showChangeGroupSelector(' . $page .
			', ' . $groupUid . ', \'' . $elementId . '\', \'' . htmlspecialchars($groupname) . '\');">' .
			($groupname == '' ? '<span class=not_set>[not set]</span>' : htmlspecialchars(t3lib_div::fixed_lgd_cs($groupname, 20))) .
			'</a></span>';
		return $ret;
	}

	/**
	 * Print the string with the new edit lock state of a page record
	 *
	 * @param integer $page : The TYPO3 page id
	 * @param string $editLockState : The state of the TYPO3 page (locked, unlocked)
	 * @return string The new edit lock string wrapped in HTML
	 */
	protected function renderToggleEditLock($page, $editLockState) {
		if ($editLockState === 1) {
			$ret = '<a class="editlock" onclick="WebPermissions.toggleEditLock(' . $page . ', 1);"><img' .
				t3lib_iconWorks::skinImg($this->backPath, 'gfx/recordlock_warning2.gif', 'width="22" height="16"') .
				' title="The page and all content is locked for editing by all non-Admin users." alt="" /></a>';
		} else {
			$ret = '<a class="editlock" onclick="WebPermissions.toggleEditLock(' . $page . ', 0);" title="Enable the &raquo;Admin-only&laquo; edit lock for this page">[+]</a>';
		}
		return $ret;
	}

	/**
	 * Print a set of permissions. Also used in index.php
	 *
	 * @param integer $int
	 * @param integer $pageId
	 * @param string $who : The scope (user, group or everybody)
	 * @return string HTML marked up x/* indications.
	 */
	public static function renderPermissions($int, $pageId = 0, $who = 'user') {
		/** @var language $language */
		$language = $GLOBALS['LANG'];
		$str = '';

		$permissions = array(1, 16, 2, 4, 8);
		foreach ($permissions as $permission) {
			if ($int&$permission) {
				$str .= '<span class="perm-allowed"><a title="' .
					$language->getLL($permission, 1) . '" class="perm-allowed" onclick="WebPermissions.setPermissions(' . $pageId .
					', ' . $permission . ', \'delete\', \'' . $who . '\', ' . $int . ');">*</a></span>';
			} else {
				$str .= '<span class="perm-denied"><a title="' .
					$language->getLL($permission, 1) . '" class="perm-denied" onclick="WebPermissions.setPermissions(' . $pageId .
					', ' . $permission . ', \'add\', \'' . $who . '\', ' . $int . ');">x</a></span>';
			}
		}

		return '<span id="' . $pageId . '_' . $who . '">' . $str . '</span>';
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_access/class.sc_mod_access_perm_ajax.php']) {
	/** @noinspection PhpIncludeInspection */
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/commerce/mod_access/class.sc_mod_access_perm_ajax.php']);
}

?>