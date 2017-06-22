<?php

class Moturdrn_GW2Guilds_DataWriter_Pending extends XenForo_DataWriter
{
    protected function _getFields()
    {
        return array(
            'xf_moturdrn_gw2guilds_pending' => array(
                'pendingid' => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
                'guildid' => array('type' => self::TYPE_UINT, 'required' => true),
                'user_id' => array('type' => self::TYPE_UINT, 'required' => true),
                'pendingtype' => array('type' => self::TYPE_STRING, 'required' => true)
            )
        );
    }

    protected function _getExistingData($data)
    {
        if (!$pendingID = $this->_getExistingPrimaryKey($data, 'pendingid'))
        {
            return false;
        }

        if (!$pendingInfo = $this->_getPendingModel()->getPendingRequestById($pendingID))
        {
            return false;
        }

        return $this->getTablesDataFromArray($pendingInfo);
    }

    /**
     * Gets SQL condition to update the existing record.
     *
     * @return string
     */
    protected function _getUpdateCondition($tableName)
    {
        return  'pendingid = ' . $this->_db->quote($this->getExisting('pendingid'));
    }

    /**
     *	Gets the current value of the team ID for this team.
     *
     *	@return integer
     */
    public function getGuildId()
    {
        return $this->get('pendingid');
    }

    protected function _getGuildModel()
    {
        return $this->getModelFromCache('Moturdrn_GW2Guilds_Model_Guild');
    }

    protected function _getPendingModel()
    {
        return $this->getModelFromCache('Moturdrn_GW2Guilds_Model_Pending');
    }

    protected function _preSave()
    {
        $existingPending = $this->_getPendingModel()->getPendingRequestByUserGuild($this->get('guildid'), $this->get('user_id'));

        if($existingPending && $existingPending['pendingtype'] == 'JoinReq')
        {
            $this->error('You already have a pending request to join this guild');
            return false;
        }

        $existingPendingGuildReq = $this->_getPendingModel()->getPendingRequestActivateByGuildId($this->get('guildid'));

        if($existingPendingGuildReq)
        {
            $this->error('There is already a pending request to activate this guild');
            return false;
        }
    }

    protected function _postSave()
    {
        $this->_alertUser();
    }

    protected function _alertUser()
    {
        $guildId = $this->get('guildid');

        if ($this->isInsert())
        {
            $fromUser = $this->_getUserModel()->getUserById($this->get('user_id'));
            $guild = $this->_getGuildModel()->getGuildById($guildId);
            $guildAlerters = explode(",", $guild['guildofficer_userids']);
            $guildAlerters[] = $guild['guildleader_userid'];

            $extraData = array(
                'guild' => $guild,
            );

            if($this->get('pendingtype') == 'NewGuild')
            {
                $toUserIds = $this->_getUserGroupModel()->getUserIdsInUserGroup(3);
                foreach($toUserIds as $toUserId => $isPrimary)
                {
                    XenForo_Model_Alert::alert($toUserId, $fromUser['user_id'], $fromUser['username'], 'moturdrn_gw2guilds', $this->get('pendingid'), 'new', $extraData);
                }
            }
            elseif($this->get('pendingtype') == 'ChangeGuild')
            {
                $toUserIds = $this->_getUserGroupModel()->getUserIdsInUserGroup(3);
                foreach($toUserIds as $toUserId => $isPrimary)
                {
                    XenForo_Model_Alert::alert($toUserId, $fromUser['user_id'], $fromUser['username'], 'moturdrn_gw2guilds', $this->get('pendingid'), 'change', $extraData);
                }
            }
        }
    }

    /**
     * Gets the user group model.
     *
     * @return XenForo_Model_UserGroup
     */
    protected function _getUserGroupModel()
    {
        return $this->getModelFromCache('XenForo_Model_UserGroup');
    }
}