<#1>
<?php
$fields = array(
	'obj_id' => array(
		'type'    => 'integer',
		'length'  => 4,
		'notnull' => true,
		'default' => 0
	)
);

$ilDB->createTable('rep_robj_xgdo_data', $fields);
$ilDB->addPrimaryKey('rep_robj_xgdo_data', array('obj_id'));
?>