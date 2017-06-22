<?php

class Moturdrn_GW2Guilds_AlertHandler_Member extends XenForo_AlertHandler_Abstract
{
    public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
    {
        $guildModel = $model->getModelFromCache('Moturdrn_GW2Guilds_Model_Guild');

        return $guildModel->getGuildsByIds($contentIds);
    }

    public function canViewAlert(array $alert, $content, array $viewingUser)
    {
        $guild = XenForo_Model::create('Moturdrn_GW2Guilds_Model_Guild')->getGuildById($content['guildid']);
        return XenForo_Model::create('Moturdrn_GW2Guilds_Model_Guild')->canEditGuild(
            $guild, $null, $viewingUser
        );
    }


}