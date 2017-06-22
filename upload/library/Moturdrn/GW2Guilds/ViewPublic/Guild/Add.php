<?php

class Moturdrn_GW2Guilds_ViewPublic_Guild_Add extends XenForo_ViewPublic_Base
{
    public function renderHtml()
    {
        $guildrecruitment = (isset($this->_params['guild']['guild_recruitment']) ? $this->_params['guild']['guild_recruitment'] : '');

        $this->_params['editorTemplate'] = XenForo_ViewPublic_Helper_Editor::getEditorTemplate(
            $this, 'guild_recruitment', $guildrecruitment,
            array(
                'extraClass' => 'NoAutoComplete'
            )
        );
    }
}