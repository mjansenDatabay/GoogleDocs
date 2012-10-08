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
	