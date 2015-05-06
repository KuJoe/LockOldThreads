<?php
/*
	LockOldThreads (The anti-thread necro plugin.) by KuJoe <www.jmd.cc>
*/

// Don't allow direct initialization.
if (! defined('IN_MYBB')) {
	die('Nope.');
}

// The info for this plugin.
function lot_info() {
	return array(
		'name'			=> 'LockOldThreads',
		'description'	=> 'Automatically locks old threads after X days based on the last reply.',
		'website'		=> 'http://www.jmd.cc',
		'author'		=> 'KuJoe',
		'authorsite'	=> 'http://www.jmd.cc',
		'version'		=> '1.0',
		'compatibility'	=> '18*',
		'codename'		=> 'lot'
	);
}

function lot_activate() {
	global $db;
	$me = lot_info();

	$lot_group = array(
		'name'			=> 'lot',
		'title'			=> 'LockOldThreads',
		'description'	=> 'LockOldThreads Settings.',
		'disporder'		=> '99',
		'isdefault'		=> 0
	);

	$db->insert_query('settinggroups', $lot_group);
	$gid = $db->insert_id();

	$lot_setting_1 = array(
		'name'			=> 'lot_onoff',
		'title'			=> 'LockOldThreads Status',
		'description'	=> 'Turn LockOldThreads On or Off.',
		"optionscode"	=> "onoff",
		"value"		=> '0',
		'disporder'		=> 1,
		'gid'			=> intval($gid)
	);
	$lot_setting_2 = array(
		'name'			=> 'lot_age',
		'title'			=> 'Age',
		'description'	=> 'Enter how many days old threads must be to be locked. (Based on last reply.)',
		'optionscode'	=> 'text',
		'value'		=> '30',
		'disporder'		=> 3,
		'gid'			=> intval($gid)
	);

	$lot_setting_3 = array(
		'name'			=> 'lot_forums',
		'title'			=> 'Forums',
		'description'	=> 'Enter which forums to lock old threads. (Comma seperated, 0 for all.)',
		'optionscode'	=> 'text',
		'value'		=> '1,2,3,4,5',
		'disporder'		=> 2,
		'gid'			=> intval($gid)
	);
	$db->insert_query('settings', $lot_setting_1);
	$db->insert_query('settings', $lot_setting_2);
	$db->insert_query('settings', $lot_setting_3);
	
	rebuild_settings();
	
	global $message;

	// Add task if task file exists, warn otherwise.
	if (! file_exists(MYBB_ROOT . "inc/tasks/{$me['codename']}.php")) {
		$message = "The {$me['name']} task file (<code>inc/tasks/{$me['codename']}.php</code>) does not exist. Install this first!";
	}
	else {
		lot_add_task();
	}
}
// Action to take to deactivate the plugin.
function lot_deactivate() {
	global $db, $mybb;

	$me = lot_info();

	// Remove task.

	// Switch modules and actions.
	$prev_module = $mybb->input['module'];
	$prev_action = $mybb->input['action'];
	$mybb->input['module'] = 'tools/tasks';
	$mybb->input['action'] = 'delete';

	// Fetch ID and title.
	$result = $db->simple_select('tasks', 'tid, title', "file = '{$me['codename']}'");
	while ($task = $db->fetch_array($result)) {
		// Log.
		log_admin_action($task['tid'], $task['title']);
	}

	// Delete.
	# or should I just disable the task here and not remove it until _deactivate()?
	$result = $db->delete_query('tasks', "file = '{$me['codename']}'");

	//Remove settings.
	$db->delete_query("settings","name='lot_onoff'");
	$db->delete_query("settings","name='lot_age'");
	$db->delete_query("settings","name='lot_forums'");
	$db->delete_query("settinggroups","name='lot'");
	
	// Reset module.
	$mybb->input['module'] = $prev_module;

	// Reset action.
	$mybb->input['action'] = $prev_action;

	// Log.
	log_admin_action($me['name']);
	
	rebuild_settings();

}

// Function to add task to task system.
function lot_add_task() {
	global $db, $mybb;

	require_once MYBB_ROOT . 'inc/functions_task.php';

	$me = lot_info();

	$result = $db->simple_select('tasks', 'count(tid) as count', "file = '{$me['codename']}'");
	if (! $db->fetch_field($result, 'count')) {
		// Switch modules and actions.
		$prev_module = $mybb->input['module'];
		$prev_action = $mybb->input['action'];
		$mybb->input['module'] = 'tools/tasks';
		$mybb->input['action'] = 'add';

		// Create task. Have it run every 15 minutes by default.
		$insert_array = array(
			'title'			=> $me['name'],
			'description'	=> "Locks threads based on the date of the last reply.",
			'file'			=> $me['codename'],
			'minute'		=> '1',
			'hour'			=> '0',
			'day'			=> '*',
			'month'			=> '*',
			'weekday'		=> '*',
			'lastrun'		=> 0,
			'enabled'		=> 1,
			'logging'		=> 1,
			'locked'		=> 0,
		);
		$insert_array['nextrun'] = fetch_next_run($insert_array);
		$result = $db->insert_query('tasks', $insert_array);
		$tid = $db->insert_id();

		log_admin_action($tid, $me['name']);

		// Reset module and action.
		$mybb->input['module'] = $prev_module;
		$mybb->input['action'] = $prev_action;

		return TRUE;
	}
	return FALSE;
}
?>
