<?php

class Moturdrn_GW2Guilds_DataWriter_Member extends XenForo_DataWriter
{
    protected function _getFields()
    {
        return array(
            'xf_moturdrn_gw2guilds_member' => array(
                'guild_id' => array('type' => self::TYPE_UINT, 'required' => true),
                'user_id' => array('type' => self::TYPE_UINT, 'required' => true),
                'state' => array('type' => self::TYPE_BINARY, 'allowedValues' => array('pending','accepted'), 'default' => 'pending'),
                'join_date' => array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time)
            )
        );
    }

    protected function _getExistingData($data)
    {
        if (!is_array($data))
        {
            return false;
        }

        $userId = false;
        $guildId = false;

        if (isset($data['user_id']) && isset($data['guild_id']))
        {
            $userId = $data['user_id'];
            $guildId = $data['guild_id'];
        }
        else if (isset($data[0]) && isset($data[1]))
        {
            $guildId = $data[0];
            $userId = $data[1];
        }
        else
        {
            return false;
        }

        $memberInfo = $this->_getMemberModel()->getGuildMember($guildId, $userId);

        return $this->getTablesDataFromArray($memberInfo);
    }

    /**
     * Gets SQL condition to update the existing record.
     *
     * @return string
     */
    protected function _getUpdateCondition($tableName)
    {
        $conditions = array();

        foreach (array('guild_id', 'user_id') as $field)
        {
            $conditions[] = $field . ' = ' . $this->_db->quote($this->getExisting($field));
        }

        return implode(' AND ', $conditions);
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

    protected function _getGuild()
    {
        if($this->get('guild_id'))
        {
            return $this->_getGuildModel()->getGuildById($this->get('guild_id'));
        }
    }

    protected function _getGuildModel()
    {
        return $this->getModelFromCache('Moturdrn_GW2Guilds_Model_Guild');
    }

    /**
     * @return Moturdrn_GW2Guilds_Model_Member
     */
    protected function _getMemberModel()
    {
        /** @var Moturdrn_GW2Guilds_Model_Member $model */
        $model = $this->getModelFromCache('Moturdrn_GW2Guilds_Model_Member');
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
        if(!$this->_getGuild())
        {
            $this->error('Guild Not Found!');
            return false;
        }

        if($this->get('user_id'))
        {
            $user = $this->_getUserModel()->getUserById($this->get('user_id'));

            if(!$user)
            {
                $this->set('user_id', 0);
            }
        }
    }

    protected function _postSave()
    {
        $state = $this->get('state');
        if($this->isInsert() && $state == 'pending')
        {
            $pendingDw = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Pending');
            $pendingDw->set('guild_id', $this->get('guild_id'));
            $pendingDw->set('user_id', $this->get('user_id'));
            $pendingDw->set('pending_type', 'JoinReq');
            $pendingDw->save();
        }else if($state == 'accepted')
        {
            /** @var Moturdrn_GW2Guilds_DataWriter_Pending $pendingDw */
            $pendingDw = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Pending');
            if($pendingDw->setExistingData(["guild_id" => $this->get('guild_id'), "user_id" => $this->get('user_id'), "pending_type" => "JoinReq"]))
                $pendingDw->delete();
        }
        $this->_getMemberModel()->accessAddOrRemove($this->get('user_id'));
    }

    protected function _postDelete()
    {
        /** @var Moturdrn_GW2Guilds_DataWriter_Pending $pendingDw */
        $pendingDw = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Pending');
        if($pendingDw->setExistingData(["guild_id" => $this->get('guild_id'), "user_id" => $this->get('user_id'), "pending_type" => "JoinReq"]))
            $pendingDw->delete();
        $this->_getMemberModel()->accessAddOrRemove($this->get('user_id'));
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

            if($this->get('state') != 'accepted')
            {
                foreach($guildAlerters as $toUserId)
                {
                    XenForo_Model_Alert::alert($toUserId, $fromUser['user_id'], $fromUser['username'], 'moturdrn_gw2guildsmember', $this->get('guild_id'), 'join', $extraData);
                }
            }
        }else{
            if($this->get('state') == 'accepted')
            {
                $this->_db->query('
                    DELETE FROM xf_user_alert
                    WHERE user_id = ?
                        AND content_type = ?
                        AND content_id = ?
                ', array($this->get('user_id'), 'moturdrn_gw2guildsmember', $this->get('guild_id')));
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