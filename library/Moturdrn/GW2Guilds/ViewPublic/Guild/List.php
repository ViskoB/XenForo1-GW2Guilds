<?php

class Moturdrn_GW2Guilds_ViewPublic_Guild_List extends XenForo_ViewPublic_Base
{
    public function renderHtml()
    {
        XenForo_Application::set('view', $this);

        $bbCodeParser = XenForo_BbCode_Parser::create(XenForo_BbCode_Formatter_Base::create('Base', array('view' => $this)));

        foreach($this->_params['guilds'] as $key => $guild){
            $this->_params['guilds'][$key]['recruitmentShort'] = XenForo_Helper_String::bbCodeStrip($this->_params['guilds'][$key]['guildrecruitment']);
            $this->_params['guilds'][$key]['recruitmentShort'] = XenForo_Helper_String::wholeWordTrim($this->_params['guilds'][$key]['recruitmentShort'], 300);
        }
    }
}