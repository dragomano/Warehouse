<?php declare(strict_types=1);

/**
 * Thing.php
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

use Closure;

if (! defined('SMF'))
	die('No direct access...');

class Thing
{
	public function __toString(): string
	{
		return 'It\'s just a file - an image, a package or something else.';
	}

	public function add(int $box): int
	{
		global $sourcedir, $modSettings, $user_info, $smcFunc;

		require_once($sourcedir . '/Subs-Attachments.php');

		$items = $this->getSortedFiles($_FILES['things']);

		if (empty($items[0]['name']))
			return 0;

		$attachIDs = [];

		$num_things = 0;

		foreach ($items as $item) {
			$attachmentOptions = [
				'post'      => 0,
				'name'      => $item['name'],
				'tmp_name'  => $item['tmp_name'],
				'error'     => $item['error'],
				'size'      => $item['size'],
				'extension' => strtolower(pathinfo($item['name'], PATHINFO_EXTENSION)),
				'id_folder' => $modSettings['currentAttachmentUploadDir'],
				'mime_type' => empty($item['type']) ? get_mime_type($item['tmp_name'], true) : $item['type'],
				'approved'  => 1,
			];

			$this->checkAttachment($attachmentOptions);

			if (createAttachment($attachmentOptions)) {
				$attachIDs[] = $attachmentOptions['id'];
				$num_things++;

				if (! empty($attachmentOptions['thumb']))
					$attachIDs[] = $attachmentOptions['thumb'];
			} elseif (function_exists('dd')) {
				dd($attachmentOptions, $item);
			}
		}

		if (empty($attachIDs))
			return 0;

		$things = [];
		foreach ($attachIDs as $id) {
			$things[] = [
				'attach_id'  => $id,
				'box_id'     => $box,
				'owner_id'   => $user_info['id'],
				'created_at' => time()
			];
		}

		$things = array_chunk($things, 100);
		$count  = sizeof($things);

		for ($i = 0; $i < $count; $i++) {
			$smcFunc['db_insert']('',
				'{db_prefix}warehouse_things',
				[
					'attach_id'  => 'int',
					'box_id'     => 'int',
					'owner_id'   => 'int',
					'created_at' => 'int'
				],
				$things[$i],
				['id'],
				2
			);
		}

		return $num_things;
	}

	private function getSortedFiles(array $files): array
	{
		$sortedFiles = [];
		$numFiles = count($files['name']);
		$keys = array_keys($files);

		for ($i = 0; $i < $numFiles; $i++) {
			foreach ($keys as $key) {
				$sortedFiles[$i][$key] = $files[$key][$i];
			}
		}

		return $sortedFiles;
	}

	private function checkAttachment(array $attachment): void
	{
		global $txt;

		loadLanguage('Post');

		// Check common errors
		if ($attachment['error'] !== UPLOAD_ERR_OK || ! is_uploaded_file($attachment['tmp_name'])) {
			$error_messages = [
				UPLOAD_ERR_PARTIAL    => sprintf($txt['warehouse_upload_err_partial'], $attachment['name']),
				UPLOAD_ERR_INI_SIZE   => sprintf($txt['warehouse_upload_err_ini_size'], $attachment['name']),
				UPLOAD_ERR_CANT_WRITE => sprintf($txt['warehouse_upload_err_cant_write'], $attachment['name']),
				UPLOAD_ERR_FORM_SIZE  => sprintf($txt['warehouse_error_size'], WH_SIZE_LIMIT / 1024 / 1024),
				UPLOAD_ERR_NO_FILE    => $txt['warehouse_upload_err_no_file'],
				UPLOAD_ERR_EXTENSION  => $txt['warehouse_upload_err_extension'],
				UPLOAD_ERR_NO_TMP_DIR => $txt['warehouse_upload_err_no_tmp_dir'],
			];

			$output_message = $error_messages[$attachment['error']] ?? $txt['warehouse_unknown_error'];

			fatal_error($output_message, false);
		}

		// Check size
		if ($attachment['size'] > WH_SIZE_LIMIT)
			fatal_error(sprintf($txt['warehouse_error_size'], WH_SIZE_LIMIT / 1024 / 1024), false);

		// Check extension
		$accepted_types = explode(',', WH_ACCEPTED_FILE_TYPES);

		array_walk($accepted_types, $this->prepareAcceptedTypes());

		$allowed_types  = implode(',', array_unique($accepted_types));
		$accepted_types = explode(',', $allowed_types);

		if (! in_array($attachment['extension'], $accepted_types))
			fatal_error($txt['cant_upload_type'] . ': ' . $allowed_types, false);
	}

	private function prepareAcceptedTypes(): Closure
	{
		return function (&$v) {
			$supported_types = [
				'.zip'       => ['zip'],
				'.rar'       => ['rar'],
				'.tar'       => ['tar'],
				'.tgz'       => ['tgz'],
				'image/*'    => ['jpe', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'ico', 'svg', 'svgz', 'tif', 'tiff', 'ai', 'drw', 'pct', 'psp', 'psd', 'xcf', 'raw', 'webp'],
				'audio/*'    => ['mp3', 'm4a', 'oga', 'flac', 'aac', 'aif', 'iff', 'm4b', 'mid', 'midi', 'mpa', 'mpc', 'ogg', 'opus', 'ra', 'ram', 'snd', 'wav', 'wma'],
				'video/*'    => ['avi', 'divx', 'flv', 'm4v', 'mkv', 'mov', 'mp4', 'mpeg', 'mpg', 'ogm', 'ogv', 'ogx', 'rm', 'rmvb', 'smil', 'wbm', 'wmv', 'xvid'],
				'text/plain' => ['text', 'txt']
			];

			$v = trim($v);

			if (isset($supported_types[$v])) {
				$v = implode(',', $supported_types[$v]);
			}
		};
	}
}
