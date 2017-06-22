<?php

class Moturdrn_GW2Guilds_ViewPublic_Guild_Add extends XenForo_ViewPublic_Base
{
    public function renderHtml()
    {
        $guildrecruitment = (isset($this->_params['guild']['guildrecruitment']) ? $this->_params['guild']['guildrecruitment'] : '');

        $this->_params['editorTemplate'] = XenForo_ViewPublic_Helper_Editor::getEditorTemplate(
            $this, 'guildrecruitment', $guildrecruitment,
            array(
                'extraClass' => 'NoAutoComplete'
            )
        );
    }
}