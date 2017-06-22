<?php

class Moturdrn_GW2Guilds_DataWriter_Pending extends XenForo_DataWriter
{
    protected function _getFields()
    {
        return array(
            'xf_moturdrn_gw2guilds_pending' => array(
                'pending_id' => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
                'guild_id' => array('type' => self::TYPE_UINT, 'required' => true),
                'user_id' => array('type' => self::TYPE_UINT, 'required' => true),
                'pending_type' => array('type' => self::TYPE_STRING, 'required' => true)
            )
        );
    }

    protected function _getExistingData($data)
    {
        if (!is_array($data))
        {
            return false;
        }

        if (isset($data['pending_id']))
        {
            $pendingInfo = $this->_getPendingModel()->getPendingRequestById($data['pending_id']);
        }
        else if(isset($data['guild_id']) && isset($data['pending_type']) && ($data['pending_type'] == 'NewGuild' || $data['pending_type'] == 'ChangeGuild'))
        {
            $pendingInfo = $this->_getPendingModel()->getPendingRequestsByGuildId($data['guild_id']);
        }
        else if (isset($data['user_id']) && isset($data['guild_id']))
        {
            $pendingInfo = $this->_getPendingModel()->getPendingRequestByUserGuild($data['guild_id'], $data['user_id']);
        }
        else
        {
            return false;
        }

        if(!$pendingInfo)
            return false;

        return $this->getTablesDataFromArray($pendingInfo);
    }

    /**
     * Gets SQL condition to update the existing record.
     *
     * @return string
     */
    protected function _getUpdateCondition($tableName)
    {
        return  'pending_id = ' . $this->_db->quote($this->getExisting('pending_id'));
    }

    /**
     *	Gets the current value of the team ID for this team.
     *
     *	@return integer
     */
    public function getGuildId()
    {
        return $this->get('pending_id');
    }

    /**
     * @return Moturdrn_GW2Guilds_Model_Guild
     */
    protected function _getGuildModel()
    {
        /** @var Moturdrn_GW2Guilds_Model_Guild $model */
        $model = $this->getModelFromCache('Moturdrn_GW2Guilds_Model_Guild');
        return $model;
    }

    /**
     * @return Moturdrn_GW2Guilds_Model_Pending
     */
    protected function _getPendingModel()
    {
        /** @var Moturdrn_GW2Guilds_Model_Pending $model */
        $model = $this->getModelFromCache('Moturdrn_GW2Guilds_Model_Pending');
        return $model;
    }

    protected function _preSave()
    {
        $existingPending = $this->_getPendingModel()->getPendingRequestByUserGuild($this->get('guild_id'), $this->get('user_id'));

        if($existingPending && $existingPending['pending_type'] == 'JoinReq')
        {
            $this->error('You already have a pending request to join this guild');
            return false;
        }

        $existingPendingGuildReq = $this->_getPendingModel()->getPendingRequestActivateByGuildId($this->get('guild_id'));

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

    protected function _postDelete()
    {
        $this->_db->query('
			DELETE FROM xf_user_alert
			WHERE user_id = ?
				AND content_type = ?
				AND content_id = ?
		', array($this->get('user_id'), 'moturdrn_gw2guilds', $this->get('pending_id')));
    }

    protected function _alertUser()
    {
        $guildId = $this->get('guild_id');

        if ($this->isInsert())
        {
            $fromUser = $this->_getUserModel()->getUserById($this->get('user_id'));
            $guild = $this->_getGuildModel()->getGuildById($guildId);
            $guildAlerters = explode(",", $guild['guildofficer_userids']);
            $guildAlerters[] = $guild['guildleader_userid'];

            $extraData = array(
                'guild' => $guild,
            );

            if($this->get('pending_type') == 'NewGuild')
            {
                $toUserIds = $this->_getUserGroupModel()->getUserIdsInUserGroup(3);
                foreach($toUserIds as $toUserId => $isPrimary)
                {
                    XenForo_Model_Alert::alert($toUserId, $fromUser['user_id'], $fromUser['username'], 'moturdrn_gw2guilds', $this->get('pending_id'), 'newguild', $extraData);
                }
            }
            elseif($this->get('pending_type') == 'ChangeGuild')
            {
                $toUserIds = $this->_getUserGroupModel()->getUserIdsInUserGroup(3);
                foreach($toUserIds as $toUserId => $isPrimary)
                {
                    XenForo_Model_Alert::alert($toUserId, $fromUser['user_id'], $fromUser['username'], 'moturdrn_gw2guilds', $this->get('pending_id'), 'changeguild', $extraData);
                }
            }
            elseif($this->get('pending_type') == 'JoinReq')
            {
                foreach($guildAlerters as $toUserId)
                {
                    if(!is_null($toUserId) && $toUserId > 0)
                        XenForo_Model_Alert::alert($toUserId, $fromUser['user_id'], $fromUser['username'], 'moturdrn_gw2guilds', $this->get('pending_id'), 'joinreq', $extraData);
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
        /** @var XenForo_Model_UserGroup $model */
        $model = $this->getModelFromCache('XenForo_Model_UserGroup');
        return $model;
    }
}