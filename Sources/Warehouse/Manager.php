<?php declare(strict_types=1);

/**
 * Manager.php
 *
 * @package Warehouse
 * @link https://github.com/dragomano/Warehouse
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2023-2025 Bugo
 * @license https://opensource.org/licenses/MIT The MIT License
 *
 * @version 0.3
 */

namespace Bugo\Warehouse;

if (! defined('SMF'))
	die('No direct access...');

class Manager
{
	public function __toString(): string
	{
		return 'Hey, dude. I work for you. Can I help you?';
	}

	public function tasks(): void
	{
		add_integration_function('integrate_autoload', __CLASS__ . '::autoload#', false, __FILE__);
		add_integration_function('integrate_user_info', __CLASS__ . '::userInfo#', false, __FILE__);
		add_integration_function('integrate_load_theme', __CLASS__ . '::loadTheme#', false, __FILE__);
		add_integration_function('integrate_load_illegal_guest_permissions', __CLASS__ . '::loadIllegalGuestPermissions#', false, __FILE__);
		add_integration_function('integrate_load_permissions', __CLASS__ . '::loadPermissions#', false, __FILE__);
		add_integration_function('integrate_actions', __CLASS__ . '::actions#', false, __FILE__);
		add_integration_function('integrate_menu_buttons', __CLASS__ . '::menuButtons#', false, __FILE__);
		add_integration_function('integrate_admin_areas', __CLASS__ . '::adminAreas#', false, __FILE__);
		add_integration_function('integrate_modify_modifications', __CLASS__ . '::modifications#', false, __FILE__);
		add_integration_function('integrate_download_request', __CLASS__ . '::downloadRequest#', false, __FILE__);
		add_integration_function('integrate_attachments_browse', __CLASS__ . '::attachmentsBrowse#', false, __FILE__);
		add_integration_function('integrate_attachment_remove', __CLASS__ . '::attachmentRemove#', false, __FILE__);
		add_integration_function('integrate_remove_attachments', __CLASS__ . '::removeAttachments#', false, __FILE__);
		add_integration_function('integrate_repair_attachments_nomsg', __CLASS__ . '::repairAttachmentsNomsg#', false, __FILE__);
		add_integration_function('integrate_weekly_maintenance', __NAMESPACE__ . '\Cleaner::cleanStorage#', false, '$sourcedir/Warehouse/Cleaner.php');
		add_integration_function('integrate_whos_online', __CLASS__ . '::whosOnline#', false, __FILE__);
		add_integration_function('integrate_credits', __CLASS__ . '::credits#', false, __FILE__);
	}

	/**
	 * @hook integrate_autoload
	 */
	public function autoload(array &$classMap): void
	{
		$classMap['Bugo\\Warehouse\\'] = 'Warehouse/';
	}

	/**
	 * @hook integrate_user_info
	 */
	public function userInfo(): void
	{
		global $modSettings, $scripturl;

		defined('WH_NAME') || define('WH_NAME', 'Warehouse');
		defined('WH_ACTION') || define('WH_ACTION', $modSettings['warehouse_action'] ?? 'warehouse');
		defined('WH_BASE_URL') || define('WH_BASE_URL', $scripturl . '?action=' . WH_ACTION);
		defined('WH_SIZE_LIMIT') || define('WH_SIZE_LIMIT', (int) ($modSettings['warehouse_max_filesize'] ?? 10) * 1024 * 1024); // 10 MB, in bytes
		defined('WH_ITEMS_PER_PAGE') || define('WH_ITEMS_PER_PAGE', (int) ($modSettings['warehouse_boxes_per_page'] ?? 20));
		defined('WH_ACCEPTED_FILE_TYPES') || define('WH_ACCEPTED_FILE_TYPES', $modSettings['warehouse_accepted_types'] ?? '.zip,.rar,.tar,.tgz,image/*,video/*,audio/*,text/plain');
	}

	/**
	 * @hook integrate_load_theme
	 */
	public function loadTheme(): void
	{
		global $context;

		loadLanguage('Warehouse/');

		$context['allow_warehouse_view']             = allowedTo('warehouse_view');
		$context['allow_warehouse_manage_boxes_own'] = allowedTo('warehouse_manage_boxes_own');
		$context['allow_warehouse_manage_boxes_any'] = allowedTo('warehouse_manage_boxes_any');
	}

	/**
	 * @hook integrate_load_illegal_guest_permissions
	 */
	public function loadIllegalGuestPermissions(): void
	{
		global $context;

		$context['non_guest_permissions'] = array_merge(
			$context['non_guest_permissions'],
			[
				'warehouse_manage_boxes',
				'warehouse_manage_boxes_own',
				'warehouse_manage_boxes_any',
			]
		);
	}

	/**
	 * @hook integrate_load_permissions
	 */
	public function loadPermissions(array &$permissionGroups, array &$permissionList): void
	{
		$permissionList['membergroup']['warehouse_view']         = [false, 'warehouse'];
		$permissionList['membergroup']['warehouse_manage_boxes'] = [true, 'warehouse'];

		$permissionGroups['membergroup'][] = 'warehouse';
	}

	/**
	 * @hook integrate_actions
	 */
	public function actions(array &$actions): void
	{
		$actions[WH_ACTION] = ['Warehouse/Storage.php', [new Storage(), 'init']];
	}

	/**
	 * @hook integrate_menu_buttons
	 */
	public function menuButtons(array &$buttons): void
	{
		global $modSettings, $context, $txt;

		if (! isset($txt['warehouse_title']))
			return;

		$counter = 0;
		foreach (array_keys($buttons) as $button) {
			$counter++;

			if ($button === 'search')
				break;
		}

		$title = empty($modSettings['warehouse_menu_item_' . $context['user']['language']]) ? $txt['warehouse_title'] : $modSettings['warehouse_menu_item_' . $context['user']['language']];

		$buttons = array_merge(
			array_slice($buttons, 0, $counter, true),
			[
				WH_ACTION => [
					'title' => $title,
					'href'  => WH_BASE_URL,
					'icon'  => 'packages',
					'show'  => true,
				],
			],
			array_slice($buttons, $counter, null, true)
		);
	}

	/**
	 * @hook integrate_admin_areas
	 */
	public function adminAreas(array &$admin_areas): void
	{
		global $txt;

		$admin_areas['config']['areas']['modsettings']['subsections']['warehouse'] = [$txt['warehouse_title']];
	}

	/**
	 * @hook integrate_modify_modifications
	 */
	public function modifications(array &$subActions): void
	{
		$subActions['warehouse'] = [new Office(), 'settings'];
	}

	/**
	 * @hook integrate_download_request
	 */
	public function downloadRequest(&$attachRequest): void
	{
		global $smcFunc;

		if ((! empty($attachRequest) && is_resource($attachRequest)) || empty($_REQUEST['box']))
			return;

		$attach = (int) $_REQUEST['attach'];
		$box    = (int) $_REQUEST['box'];

		$attachRequest = $smcFunc['db_query']('', '
			SELECT {string:source} AS source,
				a.id_folder, a.filename, a.file_hash, a.fileext, a.id_attach, a.id_thumb, a.attachment_type, a.mime_type, a.approved, a.id_msg
			FROM {db_prefix}attachments AS a
				INNER JOIN {db_prefix}warehouse_things AS wt ON (a.id_attach = wt.attach_id)
			WHERE a.id_attach = {int:attach}
				AND wt.box_id = {int:box}
			LIMIT 1',
			[
				'source' => WH_NAME,
				'attach' => $attach,
				'box'    => $box,
			]
		);

		if (empty($smcFunc['db_num_rows']($attachRequest)))
			return;

		$smcFunc['db_query']('', '
			UPDATE {db_prefix}warehouse_things
			SET requested_at = {int:current_time}
			WHERE attach_id = {int:attach}
				AND box_id = {int:box}',
			[
				'current_time' => time(),
				'attach'       => $attach,
				'box'          => $box,
			]
		);
	}

	/**
	 * @hook integrate_attachments_browse
	 */
	public function attachmentsBrowse(array &$listOptions, array &$titles): void
	{
		global $context, $txt;

		if (isset($_REQUEST['wh_attach'])) {
			$context['browse_type'] = 'wh_attach';
		}

		$titles['wh_attach'] = ['?action=admin;area=manageattachments;sa=browse;wh_attach', $txt['attachment_manager_attachments'] . ' ' . WH_NAME];

		if (isset($_REQUEST['wh_attach'])) {
			$listOptions = (new Storekeeper())->getListOptions();
		}
	}

	/**
	 * @hook integrate_attachment_remove
	 */
	public function attachmentRemove(bool &$filesRemoved, array $attachments): void
	{
		if ($_REQUEST['type'] === 'wh_attach' && ! empty($attachments)) {
			removeAttachments(['id_attach' => $attachments]);
		}

		$filesRemoved = true;
	}

	/**
	 * Update our tables when SMF wants to remove attachments
	 *
	 * @hook integrate_remove_attachments
	 */
	public function removeAttachments(array $items): void
	{
		(new Cleaner())->removeThings($items);
	}

	/**
	 * Prepare attachment ids that should not be deleted on maintenance
	 *
	 * @hook integrate_repair_attachments_nomsg
	 */
	public function repairAttachmentsNomsg(array &$ignore_ids): void
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('', /** @lang text */ '
			SELECT a.id_attach
			FROM {db_prefix}attachments AS a
				INNER JOIN {db_prefix}warehouse_things AS wt ON (a.id_attach = wt.attach_id)',
			[]
		);

		while ($row = $smcFunc['db_fetch_assoc']($request)) {
			$ignore_ids[] = $row['id_attach'];
		}

		$smcFunc['db_free_result']($request);
	}

	/**
	 * @hook integrate_whos_online
	 */
	public function whosOnline(array $actions): string
	{
		global $txt;

		if (empty($actions['action']) || $actions['action'] !== WH_ACTION)
			return '';

		$result = sprintf($txt['warehouse_who_viewing_index'], WH_BASE_URL);

		if (isset($actions['rack'])) {
			$result = sprintf($txt['warehouse_who_viewing_rack'], WH_BASE_URL . ';rack=' . $actions['rack'], $actions['rack']);
		}

		if (isset($actions['box'])) {
			$result = sprintf($txt['warehouse_who_viewing_box'], WH_BASE_URL . ';box=' . $actions['box'], $actions['box']);
		}

		return $result;
	}

	/**
	 * @hook integrate_credits
	 */
	public function credits(): void
	{
		global $context;

		$context['copyrights']['mods'][] = '<a href="https://github.com/dragomano/Warehouse" target="_blank" rel="noopener">' . WH_NAME . '</a> &copy; 2023&ndash;' . date('Y') . ', Bugo';
	}
}
