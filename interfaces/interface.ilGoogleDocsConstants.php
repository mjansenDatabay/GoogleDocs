<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 *
 */
interface ilGoogleDocsConstants
{
	/**
	 * @var int
	 */
	const CREATION_TYPE_NEW = 1;

	/**
	 * @var int
	 */
	const CREATION_TYPE_UPLOAD = 2;

	/**
	 * @var int
	 */
	const DOC_TYPE_DOCUMENT = 1;

	/**
	 * @var int
	 */
	const DOC_TYPE_SPREADSHEET = 2;

	/**
	 * @var int
	 */
	const DOC_TYPE_PRESENTATION = 3;

	/**
	 * @var int
	 */
	const GDOC_READER = 1;

	/**
	 * @var int
	 */
	const GDOC_WRITER = 2;

	/**
	 * @var string
	 */
	const CREATION_ERROR_INCOMPLETE = 'err_cr_incomplete';

	/**
	 * @var string
	 */
	const CREATION_ERROR_TYPE_MISMATCH = 'err_cr_type_mismatch';

	/**
	 * @var string
	 */
	const CREATION_ERROR_TYPE_UPLOAD = 'err_cr_upload';

	/**
	 * @var string
	 */
	const ERROR_ACCOUNT_DATA = 'err_account_data';
}