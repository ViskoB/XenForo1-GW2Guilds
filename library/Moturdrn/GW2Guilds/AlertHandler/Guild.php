<?php

class Moturdrn_GW2Guilds_AlertHandler_Guild extends XenForo_AlertHandler_Abstract
{
    public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
    {
        $guildModel = $model->getModelFromCache('Moturdrn_GW2Guilds_Model_Guild');

        return $guildModel->getGuildsPendingByIds($contentIds);
    }

    public function canViewAlert(array $alert, $content, array $viewingUser)
    {
        return XenForo_Model::create('Moturdrn_GW2Guilds_Model_Guild')->canEditGuild(
            $content, $null, $viewingUser
        );
    }


}