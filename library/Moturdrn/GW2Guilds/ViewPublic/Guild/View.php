<?php

class Moturdrn_GW2Guilds_ViewPublic_Guild_View extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		XenForo_Application::set('view', $this);

		$bbCodeParser = XenForo_BbCode_Parser::create(XenForo_BbCode_Formatter_Base::create('Base', array('view' => $this)));



		$this->_params['guild']['recruitmentHtml'] = new XenForo_BbCode_TextWrapper($this->_params['guild']['guildrecruitment'], $bbCodeParser);

	}
}