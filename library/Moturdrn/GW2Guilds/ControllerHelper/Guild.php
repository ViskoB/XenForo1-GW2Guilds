<?php

class Moturdrn_GW2Guilds_ControllerHelper_Guild extends XenForo_ControllerHelper_Abstract
{
	/**
	 * The current browsing user.
	 * 
	 * @var XenForo_Visitor
	 */
	protected $_visitor;
	
	/**
	 * Additional contructor setup behaviour.
	 */
	protected function _constructSetup()
	{
		$this->_visitor = XenForo_Visitor::getInstance();
	}
	
	public function assertGuildValid($guildIdOrName = null)
	{
		$guild = $this->getGuildOrError($guildIdOrName);
		
		$guildModel = $this->_controller->getModelFromCache('Moturdrn_GW2Guilds_Model_Guild');

		$guild = $guildModel->prepareGuild($guild);

		return $guild;
	}
	
	public function getGuildOrError($guildIdOrName = null)
	{
		$guildId = $this->_controller->getInput()->filterSingle('guild_id', XenForo_Input::UINT);
		$guildId_Legacy = $this->_controller->getInput()->filterSingle('id', XenForo_Input::UINT);
		$guildName = $this->_controller->getInput()->filterSingle('guild_name', XenForo_Input::STRING);
		
		if($guildIdOrName === null)
		{
			$guildIdOrName_Compat = ($guildId) ? $guildId : $guildId_Legacy;
			$guildIdOrName = ($guildIdOrName_Compat) ? $guildIdOrName_Compat : $guildName;
		}
		
		if(is_int($guildIdOrName) || $guildIdOrName === strval(intval($guildIdOrName)))
		{
			$guild = $this->_controller->getModelFromCache('Moturdrn_GW2Guilds_Model_Guild')->getGuildById(
				$guildIdOrName
			);
		}
		else
		{
			$guild = $this->_controller->getModelFromCache('Moturdrn_GW2Guilds_Model_Guild')->getGuildByName(
				$guildIdOrName
			);
		}
		
		if(!$guild)
		{
			throw $this->_controller->responseException($this->_controller->responseError("Guild {$guildIdOrName} Not Found", 404));
		}

		return $guild;
		
	}
}