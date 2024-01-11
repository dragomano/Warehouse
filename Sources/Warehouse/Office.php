<?php declare(strict_types=1);

/**
 * Office.php
 *
 * @package Warehouse
 * @link https://github.com/dragomano/Warehouse
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2023-2024 Bugo
 * @license https://opensource.org/licenses/MIT The MIT License
 *
 * @version 0.1
 */

namespace Bugo\Warehouse;

if (! defined('SMF'))
	die('No direct access...');

class Office
{
	public function settings(): void
	{
		global $context, $txt, $scripturl;

		$context['page_title'] = $context['settings_title'] = $txt['warehouse_title'];
		$context['post_url'] = $scripturl . '?action=admin;area=modsettings;save;sa=warehouse';
		$context[$context['admin_menu_name']]['tab_data']['description'] = sprintf($txt['warehouse_description'], WH_NAME);

		$this->addDefaultSettings([
			'warehouse_menu_item_' . $context['user']['language'] => $txt['warehouse_title'],
			'warehouse_action' => WH_ACTION,
			'warehouse_max_filesize' => 10,
			'warehouse_accepted_types' => WH_ACCEPTED_FILE_TYPES,
			'warehouse_boxes_per_rack' => 6,
			'warehouse_boxes_per_page' => 20,
		]);

		$this->prepareForumLanguages();

		$config_vars = [];

		foreach ($context['languages'] as $lang) {
			$txt['warehouse_menu_item_' . $lang['filename']] = $txt['warehouse_menu_item'] . (count($context['languages']) > 1 ? ' [<strong>' . $lang['filename'] . '</strong>]' : '');
			$config_vars[] = [
				'text',
				'warehouse_menu_item_' . $lang['filename']
			];
		}

		$extra_vars = [
			'',
			['text', 'warehouse_action', 'subtext' => $txt['warehouse_action_subtext']],
			'',
			['int', 'warehouse_max_filesize', 'postinput' => $txt['megabyte']],
			['text', 'warehouse_accepted_types', '" style="width:100%" placeholder="' . WH_ACCEPTED_FILE_TYPES],
			'',
			['int', 'warehouse_boxes_per_rack'],
			['int', 'warehouse_boxes_per_page'],
			'',
			['check', 'warehouse_enable_activity_chart'],
			[
				'check',
				'warehouse_log_actions',
				'subtext' => sprintf($txt['warehouse_log_actions_subtext'], $scripturl . '?action=admin;area=logs;sa=modlog'),
			],
			['title', 'edit_permissions'],
			['permissions', 'warehouse_view'],
			['permissions', 'warehouse_manage_boxes_own'],
			['permissions', 'warehouse_manage_boxes_any'],
		];

		$config_vars = array_merge($config_vars, $extra_vars);

		// Saving?
		if (isset($_GET['save'])) {
			checkSession();
			saveDBSettings($config_vars);
			redirectexit('action=admin;area=modsettings;sa=warehouse');
		}

		prepareDBSettingContext($config_vars);
	}

	private function addDefaultSettings(array $settings): void
	{
		global $modSettings;

		$addSettings = [];

		foreach ($settings as $key => $value) {
			if (empty($value)) continue;

			if (! isset($modSettings[$key])) {
				$addSettings[$key] = $value;
			}
		}

		updateSettings($addSettings);
	}

	private function prepareForumLanguages(): void
	{
		global $modSettings, $context, $language;

		getLanguages();

		if (empty($modSettings['userLanguage'])) {
			$default_lang = $context['languages'][$language];
			$context['languages'] = [];
			$context['languages'][$language] = $default_lang;
		}
	}
}