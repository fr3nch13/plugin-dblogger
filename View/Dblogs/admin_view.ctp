<?php 
// File: app/View/Dblogs/admin_view.ctp

/*
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 20, 'key' => 'primary'),
		'model' => array('type' => 'string', 'null' => false, 'default' => null, 'key' => 'index', 'collate' => 'latin1_swedish_ci', 'charset' => 'latin1'),
		'model_id' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => 20, 'key' => 'index'),
		'new' => array('type' => 'boolean', 'null' => false, 'default' => '1', 'key' => 'index'),
		'changes' => array('type' => 'text', 'null' => true, 'default' => null, 'collate' => 'latin1_swedish_ci', 'charset' => 'latin1'),
		'readable' => array('type' => 'text', 'null' => true, 'default' => null, 'collate' => 'latin1_swedish_ci', 'charset' => 'latin1'),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => null),
*/
$details = array(
	array('name' => __('Object'), 'value' => $dblog['Dblog']['model']),
	array('name' => __('Object Id'), 'value' => $dblog['Dblog']['model_id']),
	array('name' => __('Added?'), 'value' => $this->Wrap->yesNo($dblog['Dblog']['new'])),
	array('name' => __('Created'), 'value' => $this->Wrap->niceTime($dblog['Dblog']['created'])),
);

$page_options = array(
);

$stats = array(
);

$tabs = array(
	array(
		'key' => 'readable',
		'title' => __('Changes Readable'),
		'content' => $this->Wrap->descView($dblog['Dblog']['readable']),
	),
	array(
		'key' => 'changespre',
		'title' => __('Changes Array'),
		'content' => '<pre>'. print_r(unserialize($dblog['Dblog']['changes']), true). '</pre>',
	),
	array(
		'key' => 'changesserialized',
		'title' => __('Changes Serialized'),
		'content' => '<pre>'. $dblog['Dblog']['changes']. '</pre>',
	),
	array(
		'key' => 'changesjson',
		'title' => __('Changes JSON'),
		'content' => '<pre>'. json_encode(unserialize($dblog['Dblog']['changes'])). '</pre>',
	),
);



echo $this->element('Utilities.page_view', array(
	'page_title' => __('Transaction Log Details'),
	'page_options' => $page_options,
	'details' => $details,
	'stats' => $stats,
	'tabs_id' => 'tabs',
	'tabs' => $tabs,
));

?>