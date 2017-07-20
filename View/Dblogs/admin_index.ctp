<?php 
// File: dblogger/View/Dblogs/admin_index.ctp

$page_options = array(
);

// content
$th = array(
	'Dblog.created' => array('content' => __('Timestamp'), 'options' => array('sort' => 'Dblog.created')),
	'Dblog.model' => array('content' => __('Object Type'), 'options' => array('sort' => 'Dblog.model')),
	'Dblog.model_id' => array('content' => __('Object Id'), 'options' => array('sort' => 'Dblog.model_id')),
	'Dblog.user_id' => array('content' => __('User Id'), 'options' => array('sort' => 'Dblog.user_id')),
	'Dblog.new' => array('content' => __('Added?'), 'options' => array('sort' => 'Dblog.new')),
	'Dblog.deleted' => array('content' => __('Deleted?'), 'options' => array('sort' => 'Dblog.deleted')),
	'actions' => array('content' => __('Actions'), 'options' => array('class' => 'actions')),
);

$td = array();
foreach ($dblogs as $i => $dblog)
{
	$link = false;
	
	$td[$i] = array(
		$this->Wrap->niceTime($dblog['Dblog']['created']),
		$dblog['Dblog']['model'],
		$dblog['Dblog']['model_id'],
		$dblog['Dblog']['user_id'],
		$this->Wrap->yesNo($dblog['Dblog']['new']),
		$this->Wrap->yesNo($dblog['Dblog']['deleted']),
		array(
			$this->Html->link(__('View'), array('action' => 'view', $dblog['Dblog']['id'])),
			array('class' => 'actions'),
		),
	);
}

echo $this->element('Utilities.page_index', array(
	'page_title' => __('Database Transaction Logs'),
	'page_options' => $page_options,
	'th' => $th,
	'td' => $td,
));
?>