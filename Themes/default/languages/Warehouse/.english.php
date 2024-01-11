<?php

/**
 * @package Warehouse
*/

$txt['warehouse_title'] = $txt['permissiongroup_warehouse'] = 'Warehouse';
$txt['warehouse_description'] = 'You are not limited in naming - in the language files you can rename "boxes" and "racks" to "items" and "categories", or "books" and "shelves", etc. Whether you have a warehouse, a media library, just a library or something else is up to you!';
$txt['warehouse_example_description'] = 'Welcome to our warehouse! This is where boxes that have been added by members are stored.';

// Settings
$txt['warehouse_menu_item'] = 'Menu item name';
$txt['warehouse_action'] = 'Action (end of address)';
$txt['warehouse_action_subtext'] = 'Default value: <strong>warehouse</strong>';
$txt['warehouse_max_filesize'] = 'Maximum size of uploaded files';
$txt['warehouse_accepted_types'] = 'Accepted file types for uploading';
$txt['warehouse_boxes_per_rack'] = 'Maximum of the last open boxes on each rack (on the main rack)';
$txt['warehouse_boxes_per_page'] = 'Number of boxes per page (for pagination)';
$txt['warehouse_enable_activity_chart'] = 'Display activity graph';
$txt['warehouse_log_actions'] = 'Log user actions in the warehouse';
$txt['warehouse_log_actions_subtext'] = 'To view the activity, see the <a href="%1$s">Moderation Log</a>.';

$txt['groups_warehouse_view'] = 'Who can visit the warehouse';
$txt['groups_warehouse_manage_boxes_own'] = 'Who can manage their own boxes';
$txt['groups_warehouse_manage_boxes_any'] = 'Who can manage any boxes';

// Main
$txt['warehouse_racks'] = 'Racks';
$txt['warehouse_recently_opened_boxes'] = 'Racks with recently added boxes, showing the last %1$d boxes from each rack';

$txt['warehouse_activity_chart'] = 'Activity graph';
$txt['warehouse_boxes'] = 'Boxes';
$txt['warehouse_recent_boxes'] = 'Recently added boxes';
$txt['warehouse_new_box'] = 'Adding a box';
$txt['warehouse_box_not_yet_approved'] = 'The box has not yet been approved!';
$txt['warehouse_rack_not_found'] = 'This rack is out of stock!';
$txt['warehouse_box_not_found'] = 'This box is out of stock!';
$txt['warehouse_see_other_racks'] = 'View other racks';
$txt['warehouse_see_other_boxes'] = 'View other boxes';

$txt['warehouse_week_top'] = 'Box of the Week';
$txt['warehouse_buttons'] = ['Reception', 'My boxes', 'Sorting area'];
$txt['warehouse_actions'] = 'Actions';
$txt['warehouse_back_to_storage'] = 'Return to reception';
$txt['warehouse_are_you_sure'] = 'Are you sure?';
$txt['warehouse_search_whole_storage'] = 'Search the entire warehouse';
$txt['warehouse_top_boxes'] = 'Popular boxes';

// Modlog actions
$txt['modlog_ac_add_rack'] = '{name} adds rack "{rack}".';
$txt['modlog_ac_edit_rack'] = '{name} updates rack "{rack}".';
$txt['modlog_ac_remove_rack'] = '{name} removes rack #{rack}.';
$txt['modlog_ac_add_box'] = '{name} adds the box "{box}".';
$txt['modlog_ac_edit_box'] = '{name} updates the box "{box}".';
$txt['modlog_ac_remove_box'] = '{name} deletes box #{box}.';

// Boxes and racks
$txt['warehouse_box_owner'] = 'Owner';
$txt['warehouse_filesize'] = 'File size';
$txt['warehouse_num_views'] = 'Views';
$txt['warehouse_downloads'] = 'Downloads';
$txt['warehouse_created_at'] = 'Created';
$txt['warehouse_uploaded_at'] = 'Uploaded';
$txt['warehouse_updated_at'] = 'Updated';
$txt['warehouse_requested_at'] = 'Requested';

$txt['warehouse_box_description'] = 'Description';
$txt['warehouse_box_content'] = 'Contents';
$txt['warehouse_rack_content'] = 'Rack contents';
$txt['warehouse_no_items'] = 'There\'s nothing here.';
$txt['warehouse_is_empty'] = 'The warehouse is still empty.';
$txt['warehouse_add_box_button'] = 'Add a box';
$txt['warehouse_add_rack_button'] = 'Add a rack';
$txt['warehouse_required_racks'] = 'First, we need to set up racks!';

$txt['warehouse_add_box'] = 'Box placement';
$txt['warehouse_edit_box'] = 'Changing the box';
$txt['warehouse_rack'] = 'Rack';
$txt['warehouse_box'] = 'Box';
$txt['warehouse_postfix'] = ' «%1$s»';
$txt['warehouse_box_title'] = 'Box name';
$txt['warehouse_box_date'] = 'Posting date';
$txt['warehouse_box_things'] = 'Items';

$txt['warehouse_add_rack'] = 'Rack placement';
$txt['warehouse_edit_rack'] = 'Changing the rack';
$txt['warehouse_rack_title'] = 'Rack name';

$txt['warehouse_upload_err_ini_size'] = 'The file size "%s" exceeded the upload_max_filesize value in the PHP configuration.';
$txt['warehouse_upload_err_partial'] = 'The downloadable file "%s" was only partially received.';
$txt['warehouse_upload_err_no_file'] = 'The file has not been uploaded.';
$txt['warehouse_upload_err_no_tmp_dir'] = 'Temporary folder is missing.';
$txt['warehouse_upload_err_cant_write'] = 'Failed to write file "%s" to disk.';
$txt['warehouse_upload_err_extension'] = 'The PHP extension stopped loading the file.';
$txt['warehouse_unknown_error'] = 'An unknown error occurred while downloading the file.';
$txt['warehouse_error_size'] = 'The image size must not exceed %01.2f Mbytes.';

$txt['warehouse_empty_data'] = 'Fill in all required fields!';

// Who
$txt['warehouse_who_viewing_index'] = 'Viewing <a href="%1$s">the warehouse</a>.';
$txt['warehouse_who_viewing_rack'] = 'Viewing <a href="%1$s">the rack #%2$s</a> in the warehouse.';
$txt['warehouse_who_viewing_box'] = 'Viewing <a href="%1$s">the box #%2$s</a> in the warehouse.';

// Permissions
$txt['permissionname_warehouse_view'] = $txt['group_perms_name_warehouse_view'] = 'Viewing the warehouse, racks, and boxes';
$txt['permissionhelp_warehouse_view'] = 'Allows to visit the warehouse and view the boxes on the racks.';
$txt['permissionname_warehouse_manage_boxes'] = $txt['group_perms_name_warehouse_manage_boxes'] = 'Managing boxes in the warehouse';
$txt['permissionhelp_warehouse_manage_boxes'] = 'Allows to add and remove boxes in the warehouse';

$txt['group_perms_name_warehouse_manage_boxes_own'] = 'Manage own boxes';
$txt['group_perms_name_warehouse_manage_boxes_any'] = 'Manage any boxes';

$txt['permissionname_warehouse_manage_boxes_own'] = 'Own boxes';
$txt['permissionname_warehouse_manage_boxes_any'] = 'Any boxes';

$txt['cannot_warehouse_view'] = 'You are not allowed to view the warehouse!';
$txt['cannot_warehouse_manage_boxes'] = 'You are not allowed to manage boxes in the warehouse!';
