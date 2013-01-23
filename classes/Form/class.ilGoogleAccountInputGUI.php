<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Form/classes/class.ilEMailInputGUI.php';

/**
 *
 */
class ilGoogleAccountInputGUI extends ilEMailInputGUI
{
	/**
	 * @var string
	 */
	protected $retype_value = '';

	/**
	 * @var string
	 */
	protected $retype_txt = '';

	/**
	 * @var string
	 */
	protected $retype_mismatch_error_text = '';

	/**
	 * @param array $a_values
	 */
	public function setValueByArray(array $a_values)
	{
		$this->setValue($a_values[$this->getPostVar()]);
		$this->setRetypeValue($a_values[$this->getPostVar() . '_retype']);
	}

	/**
	 * @return bool
	 */
	public function checkInput()
	{
		/**
		 * @var $lng ilLanguage
		 */
		global $lng;

		$_POST[$this->getPostVar()]             = ilUtil::stripSlashes($_POST[$this->getPostVar()]);
		$_POST[$this->getPostVar() . '_retype'] = ilUtil::stripSlashes($_POST[$this->getPostVar() . '_retype']);
		if($this->getRequired() && trim($_POST[$this->getPostVar()]) == '')
		{
			$this->setAlert($lng->txt('msg_input_is_required'));

			return false;
		}

		if($_POST[$this->getPostVar()] != $_POST[$this->getPostVar() . '_retype'])
		{
			$this->setAlert(ilGoogleDocsPlugin::getInstance()->txt('google_account_not_match'));
			return false;
		}

		if(!ilUtil::is_email($_POST[$this->getPostVar()]) &&
			trim($_POST[$this->getPostVar()]) != ''
		)
		{
			$this->setAlert(ilGoogleDocsPlugin::getInstance()->txt('google_account_not_valid'));
			return false;
		}

		return true;
	}

	/**
	 * @param ilTemplate $tpl
	 */
	public function insert(&$tpl)
	{
		/**
		 * @var $lng ilLanguage
		 */
		global $lng;

		$rtpl = ilGoogleDocsPlugin::getInstance()->getTemplate('tpl.prop_google_account_repetition.html');

		$rtpl->setVariable('SIZE', $this->getSize());
		$rtpl->setVariable('ID', $this->getFieldId());
		$rtpl->setVariable('MAXLENGTH', $this->getMaxLength());
		$rtpl->setVariable('POST_VAR', $this->getPostVar());
		$rtpl->setVariable('TXT_RETYPE', ilGoogleDocsPlugin::getInstance()->txt('form_retype_google_account'));
		$rtpl->setVariable('PROPERTY_VALUE', ilUtil::prepareFormOutput($this->getValue()));
		$rtpl->setVariable('PROPERTY_RETYPE_VALUE', ilUtil::prepareFormOutput($this->getRetypeValue()));

		if($this->getDisabled())
		{
			$rtpl->setVariable('DISABLED', ' disabled="disabled"');
		}

		$tpl->setCurrentBlock('prop_generic');
		$tpl->setVariable('PROP_GENERIC', $rtpl->get());
		$tpl->parseCurrentBlock();
	}

	/**
	 * @param string $retype_value
	 */
	public function setRetypeValue($retype_value)
	{
		$this->retype_value = $retype_value;
	}

	/**
	 * @return string
	 */
	public function getRetypeValue()
	{
		return $this->retype_value;
	}

	/**
	 * @param int $max_length
	 */
	public function setMaxLength($max_length)
	{
		$this->max_length = $max_length;
	}

	/**
	 * @return int
	 */
	public function getMaxLength()
	{
		return $this->max_length;
	}

	/**
	 * @param int $size
	 */
	public function setSize($size)
	{
		$this->size = $size;
	}

	/**
	 * @return int
	 */
	public function getSize()
	{
		return $this->size;
	}
}
