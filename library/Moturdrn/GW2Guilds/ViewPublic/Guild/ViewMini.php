<?php

class Moturdrn_GW2Guilds_ViewPublic_Guild_ViewMini extends XenForo_ViewPublic_Base
{
    public function renderHtml()
    {
        XenForo_Application::set('view', $this);

        $bbCodeParser = XenForo_BbCode_Parser::create(XenForo_BbCode_Formatter_Base::create('Base', array('view' => $this)));

        $this->_params['guild']['recruitmentShort'] = XenForo_Helper_String::bbCodeStrip($this->_params['guild']['guild_recruitment']);
        $this->_params['guild']['recruitmentShort'] = XenForo_Helper_String::wholeWordTrim($this->_params['guild']['recruitmentShort'], 300);

    }
}