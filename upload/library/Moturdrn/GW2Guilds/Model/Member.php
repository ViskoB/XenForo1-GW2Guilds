<?php

class Moturdrn_GW2Guilds_Model_Member extends XenForo_Model
{
    const SOURCE_GROUP_NO_PERMISSIONS = 2;
    const SOURCE_GROUP_WITH_PERMISSIONS = 306;
    const ACCESS_GROUP_ID = 354;
    const GUILD_LEADER_GROUP_ID = 352;

    public function getPendingRequestByUserGuild($guildId, $userId)
    {
        return $this->_getDb()->fetchRow('SELECT * FROM xf_moturdrn_gw2guilds_member WHERE guild_id = ? and user_id = ? and state = \'pending\'', array($guildId, $userId));
    }

    public function getPendingRequestActivateByGuildId($guildId)
    {
        return $this->_getDb()->fetchRow('SELECT * FROM xf_moturdrn_gw2guilds_pending WHERE guild_id = ? and pending_type in (\'NewGuild\',\'ChangeGuild\')', $guildId);
    }

    public function getPendingRequestsByGuildId($guildId)
    {
        return $this->_getDb()->fetchAll($this->limitQueryResults(
            'SELECT * FROM xf_moturdrn_gw2guilds_member WHERE state = \'pending\' and guild_id = ' . $this->_getDb()->quote($guildId),0));
    }

    public function getPendingRequestsByUser($userId)
    {
        return $this->_getDb()->fetchAll($this->limitQueryResults(
            'SELECT * FROM xf_moturdrn_gw2guilds_member WHERE state = \'pending\' AND user_id = ' . $this->_getDb()->quote($userId),0));
    }

    public function getGuildMember($guildId, $userId)
    {
        return $this->_getDb()->fetchRow('SELECT * FROM xf_moturdrn_gw2guilds_member WHERE guild_id = ' . $this->_getDb()->quote($guildId) . ' AND user_id = ' . $this->_getDb()->quote($userId));
    }

    public function getActiveGuildMember($guildId, $userId)
    {
        return $this->_getDb()->fetchRow('SELECT * FROM xf_moturdrn_gw2guilds_member WHERE state = \'accepted\' AND guild_id = ' . $this->_getDb()->quote($guildId) . ' AND user_id = ' . $this->_getDb()->quote($userId));
    }

    public function getGuildMembers($guildId)
    {
        return $this->_getDb()->fetchAll($this->limitQueryResults(
            'SELECT * FROM xf_moturdrn_gw2guilds_member WHERE state = \'accepted\' AND guild_id = ' . $this->_getDb()->quote($guildId),0));
    }

    public function getMemberState($userId, $guildId)
    {
        return $this->_getDb()->fetchRow('SELECT * FROM xf_moturdrn_gw2guilds_member WHERE user_id = ' . $this->_getDb()->quote($userId) . ' AND guild_id = ' . $this->_getDb()->quote($guildId));
    }

    public function scrubPendingRequests($userId)
    {
        $requests = $this->getPendingRequestsByUser($userId);

        XenForo_Db::beginTransaction();

        foreach ($requests as $request)
        {
            $dw = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Member');
            $dw->setExistingData($request, true);
            $dw->delete();
        }

        XenForo_Db::commit();

        return count($requests);
    }

    public function leaderAddOrRemove($userId)
    {
        $userModel = $this->_getUserModel();
        $user = $userModel->getUserById($userId);

        if($user)
        {
            $guildModel = $this->_getGuildModel();
            $activeGuilds = $guildModel->getActiveGuildsWhereLeader($userId);

            if($activeGuilds)
            {
                $this->addSecondaryGroup($userId, self::GUILD_LEADER_GROUP_ID);
            }
            else
            {
                $this->removeSecondaryGroup($userId, self::GUILD_LEADER_GROUP_ID);
            }
        }
    }

    public function accessAddOrRemove($userId)
    {
        $userModel = $this->_getUserModel();
        $user = $userModel->getUserById($userId);

        if($user)
        {
            $guildModel = $this->_getGuildModel();
            $activeGuilds = $guildModel->getActiveGuildsOfUser($userId);

            if($activeGuilds)
            {
                $this->addSecondaryGroup($userId, self::ACCESS_GROUP_ID);
            }
            else
            {
                $this->removeSecondaryGroup($userId, self::ACCESS_GROUP_ID);
            }
        }
    }

    private function addSecondaryGroup($userId, $groupId)
    {
        $userModel = $this->_getUserModel();
        $userGroupModel = $this->_getUserGroupModel();

        $user = $userModel->getUserById($userId);
        $userGroup = $userGroupModel->getUserGroupById($groupId);

        if($user && $userGroup)
        {
            if(!$userModel->isMemberOfUserGroup($user, $groupId))
            {
                $secondaryGroups = explode(",", $user['secondary_group_ids']);
                $secondaryGroups[] = $groupId;
                $writer = XenForo_DataWriter::create('XenForo_DataWriter_User');
                $writer->setExistingData($user['user_id']);
                $writer->setSecondaryGroups($secondaryGroups);
                $writer->save();
            }
        }
    }

    private function removeSecondaryGroup($userId, $groupId)
    {
        $userModel = $this->_getUserModel();
        $userGroupModel = $this->_getUserGroupModel();

        $user = $userModel->getUserById($userId);
        $userGroup = $userGroupModel->getUserGroupById($groupId);

        if($user && $userGroup)
        {
            if($userModel->isMemberOfUserGroup($user, $groupId))
            {
                $secondaryGroups = explode(",", $user['secondary_group_ids']);
                if(($key = array_search($groupId, $secondaryGroups)) !== false)
                {
                    unset($secondaryGroups[$key]);
                }
                $writer = XenForo_DataWriter::create('XenForo_DataWriter_User');
                $writer->setExistingData($user['user_id']);
                $writer->setSecondaryGroups($secondaryGroups);
                $writer->save();
            }
        }
    }

    /**
     * @return Moturdrn_GW2Guilds_Model_Guild
     */
    protected function _getGuildModel()
    {
        /** @var Moturdrn_GW2Guilds_Model_Guild $model */
        $model = XenForo_Model::create('Moturdrn_GW2Guilds_Model_Guild');
        return $model;
    }

    /**
     * @return XenForo_Model_User
     */
    protected function _getUserModel()
    {
        /** @var XenForo_Model_User $model */
        $model = XenForo_Model::create('XenForo_Model_User');
        return $model;
    }

    /**
     * @return XenForo_Model_UserGroup
     */
    protected function _getUserGroupModel()
    {
        /** @var XenForo_Model_UserGroup $model */
        $model = XenForo_Model::create('XenForo_Model_UserGroup');
        return $model;
    }
}