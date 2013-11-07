<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once dirname(__FILE__) . '/../interfaces/interface.ilGoogleDocsConstants.php';
require_once 'Services/Export/classes/class.ilXmlExporter.php';

/**
 *
 */
class ilGoogleDocsExporter extends ilXmlExporter implements ilGoogleDocsConstants
{
	/**
	 * Get xml representation
	 * @param    string        entity
	 * @param    string        schema version
	 * @param    string        id
	 * @return    string        xml string
	 */
	public function getXmlRepresentation($a_entity, $a_schema_version, $a_id)
	{
		throw new RuntimeException('XML export currently not supported');
	}

	/**
	 * @param $a_entity
	 * @return array|void
	 */
	public function getValidSchemaVersions($a_entity)
	{
		throw new RuntimeException('XML export currently not supported');
	}

	/**
	 *
	 */
	public function init()
	{
	}
}
