<?php
/*
	LockOldThreads (The anti-thread necro plugin.) by KuJoe <www.jmd.cc>
*/
function task_lot($task) {
    global $db,$mybb;
 
        $onoff = $mybb->settings['lot_onoff'];
	$age = $mybb->settings['lot_age'] * 86400;
	$forums = $mybb->settings['lot_forums'];
        $claction = array(
		'closed' => "1"
	);		

	if ($forums != 0) {
	        if ($age > 1) {
			$db->update_query('threads', $claction, "(" . TIME_NOW . " - " . ("lastpost") . ") > $age AND fid IN ($forums)");

        	}
	} else {
	        if ($age > 1) {
			$db->update_query('threads', $claction, "(" . TIME_NOW . " - " . ("lastpost") . ") > $age");

        	}
	}

    add_task_log($task, 'The LockOldThreads task successfully ran.');
}
?>