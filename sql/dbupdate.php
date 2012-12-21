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
<#2>
<?php
	$fields = array(
		'keyword' => array(
			'type' => 'text',
			'length' => 50,
			'notnull' => true,
		),
		'value' => array(
			'type' => 'text',
			'length' => 4000,
			"notnull" => false,
			"default" => null
		));

	$ilDB->createTable("rep_robj_xgdo_settings", $fields);
	$ilDB->addPrimaryKey('rep_robj_xgdo_settings', array('keyword'));
?>
<#3>
<?php
 if(!$ilDB->tableColumnExists('rep_robj_xgdo_data','doc_type'))
 {
	 $ilDB->addTableColumn('rep_robj_xgdo_data','doc_type',
		 array('type' => 'integer',
			   'length' => 1,
			   'notnull' => false,
			   'default'=> 1 ));
 }
?>
<#4>
<?php
	if(!$ilDB->tableColumnExists('rep_robj_xgdo_data','doc_url'))
	{
		$ilDB->addTableColumn('rep_robj_xgdo_data','doc_url',
			array('type' => 'text',
				  'length' => 1000,
				  'notnull' => true
				  ));
	}
?>
<#5>
<?php
	if(!$ilDB->tableColumnExists('rep_robj_xgdo_data','edit_doc_url'))
	{
		$ilDB->addTableColumn('rep_robj_xgdo_data','edit_doc_url',
			array('type' => 'text',
				  'length' => 1000,
				  'notnull' => true
			));
	}
?>
<#6>
<?php
$query = 'SELECT ops_id FROM rbac_operations WHERE ' . $ilDB->in(
	'operation',
	array(
		'visible',
		'read',
		'write',
		'delete',
		'edit_permission'
	),
	false,
	'text'
);
$res   = $ilDB->query($query);
$ops   = array();
while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
{
	$ops[$row->ops_id] = $operation;
}

include_once 'Services/AccessControl/classes/class.ilObjRoleTemplate.php';
$roleObj = new ilObjRoleTemplate();
$roleObj->setTitle('il_xgdo_reader');
$roleObj->setDescription('Reader template for google docs objects');
$roleObj->create();

$GLOBALS['rbacadmin']->assignRoleToFolder($roleObj->getId(), ROLE_FOLDER_ID, 'n');

$GLOBALS['rbacadmin']->setRolePermission(
	$roleObj->getId(),
	'xgdo',
	array_keys($ops),
	ROLE_FOLDER_ID
);
?>
<#7>
<?php
$query = 'SELECT ops_id FROM rbac_operations WHERE ' . $ilDB->in(
	'operation',
	array(
		'visible',
		'read',
		'write',
		'delete',
		'edit_permission'
	),
	false,
	'text'
);
$res   = $ilDB->query($query);
$ops   = array();
while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
{
	$ops[$row->ops_id] = $operation;
}

include_once 'Services/AccessControl/classes/class.ilObjRoleTemplate.php';
$roleObj = new ilObjRoleTemplate();
$roleObj->setTitle('il_xgdo_writer');
$roleObj->setDescription('Writer template for google docs objects');
$roleObj->create();

$GLOBALS['rbacadmin']->assignRoleToFolder($roleObj->getId(), ROLE_FOLDER_ID, 'n');

$GLOBALS['rbacadmin']->setRolePermission(
	$roleObj->getId(),
	'xgdo',
	array_keys($ops),
	ROLE_FOLDER_ID
);
?>