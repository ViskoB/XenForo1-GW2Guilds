<?php

class Moturdrn_GW2Guilds_DataWriter_Guild extends XenForo_DataWriter
{
    protected function _getFields()
    {
        return array(
            'xf_moturdrn_gw2guilds_guild' => array(
                'guild_id' => array('type' => self::TYPE_UINT, 'autoIncrement' => true),

                'guild_name' => array('type' => self::TYPE_STRING, 'maxLength' => 50, 'required' => true,
					'verification' => array('$this', '_verifyGuildName'),),
                'guild_tag' => array('type' => self::TYPE_STRING, 'maxLength' => 4, 'required' => true),

                'guildleader_userid' => array('type' => self::TYPE_UINT, 'required' => true),
                'guildofficer_userids' => array('type' => self::TYPE_STRING, 'maxLength' => 250, 'default' => ''),

                'members' => array('type' => self::TYPE_STRING, 'maxLength' => 20, 'default' => '1-50'),
                'WvW' => array('type' => self::TYPE_STRING, 'default' => 'N'),
                'PvE' => array('type' => self::TYPE_STRING, 'default' => 'N'),
                'PvP' => array('type' => self::TYPE_STRING, 'default' => 'N'),
                'Casual' => array('type' => self::TYPE_STRING, 'default' => 'N'),
                'guild_recruitment' => array('type' => self::TYPE_STRING, 'maxLength' => 6777215,
                    'default' => 'No Guild Information Entered'),
                'guild_website' => array('type' => self::TYPE_STRING, 'maxLength' => 500, 'default' => ''),
                'status' => array('type' => self::TYPE_STRING, 'maxLength' => 20, 'default' => 'Pending (New)'),
                'last_modified' => array('type' => self::TYPE_UINT)
            )
        );
    }

    protected function _getExistingData($data)
    {
        if (!$guildID = $this->_getExistingPrimaryKey($data, 'guild_id'))
        {
            return false;
        }

        if (!$guildInfo = $this->_getGuildModel()->getGuildById($guildID))
        {
            return false;
        }

        return $this->getTablesDataFromArray($guildInfo);
    }

    protected function _postDelete()
    {
        $guildId = $this->get('guild_id');
        $leaderId = $this->get('guildleader_userid');
        if($pendingModels = $this->_getPendingModel()->getPendingRequestsByGuildId($guildId)){
            foreach($pendingModels as $pendingModel){
                $pendingDw = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Pending');
                if($pendingDw->setExistingData($pendingModel))
                    $pendingDw->delete();
            }
        }

        if($memberModels = $this->_getMemberModel()->getGuildMembers($guildId))
        {
            foreach($memberModels as $memberModel){
                $memberDw = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Member');
                if($memberDw->setExistingData(['guild_id' => $guildId, 'user_id' => $memberModel['user_id']]))
                    $memberDw->delete();
            }
        }
        $this->_getMemberModel()->leaderAddOrRemove($leaderId);
    }

    protected function _postSave()
    {
        $guildId = $this->get('guild_id');
        $leaderId = $this->get('guildleader_userid');
        $guildStatus = $this->get('status');

        if($this->isInsert()) {
            $pendingDw = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Pending');
            $pendingDw->set('guild_id', $guildId);
            $pendingDw->set('user_id', $leaderId);
            $pendingDw->set('pending_type', 'NewGuild');
            $pendingDw->preSave();
            $pendingDw->save();
        }else if($guildStatus != 'Active' && $guildStatus != 'Inactive'){
            $pendingDw = XenForo_DataWriter::create('Moturdrn_GW2Guilds_Datawriter_Pending');
            if($pendingDw->setExistingData(["guild_id" => $guildId, "pending_type" => "NewGuild"]))
                $pendingDw->delete();

            $pendingDw = XenForo_DataWriter::create('Moturdrn_GW2Guilds_Datawriter_Pending');
            if(!$pendingDw->setExistingData(["guild_id" => $guildId, "pending_type" => "ChangeGuild"]))
            {
                $pendingDw->set('guild_id', $guildId);
                $pendingDw->set('user_id', $leaderId);
                $pendingDw->set('pending_type', 'ChangeGuild');
                $pendingDw->preSave();
                $pendingDw->save();
            }
        }else if($guildStatus == 'Active' || $guildStatus == 'Inactive'){
            $pendingModels = $this->_getPendingModel()->getPendingRequestsByGuildId($guildId);
            if($pendingModels)
            {
                foreach($pendingModels as $pendingModel)
                {
                    $pendingDw = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Pending');
                    if($pendingDw->setExistingData($pendingModel))
                        $pendingDw->delete();
                }
            }

            //Add/Remove current members to group
            $memberModels = $this->_getMemberModel()->getGuildMembers($guildId);
            if($memberModels)
            {
                foreach($memberModels as $memberModel)
                {
                    $this->_getMemberModel()->accessAddOrRemove($memberModel['user_id']);
                }
            }

            //Add/Remove Guild Leader to group
            $this->_getMemberModel()->leaderAddOrRemove($leaderId);

            if($guildStatus == 'Inactive'){
                //Remove pending users
                if($pendingModels = $this->_getPendingModel()->getPendingJoinRequestsByGuildId($guildId)){
                    if($pendingModels)
                    {
                        foreach($pendingModels as $pendingModel)
                        {
                            $pendingDw = XenForo_DataWriter::create('Moturdrn_GW2Guilds_Datawriter_Pending');
                            if($pendingDw->setExistingData($pendingModel))
                                $pendingDw->delete();
                        }
                    }
                }
            }
        }
    }

    /**
     * Gets SQL condition to update the existing record.
     *
     * @return string
     */
    protected function _getUpdateCondition($tableName)
    {
        return  'guild_id = ' . $this->_db->quote($this->getExisting('guild_id'));
    }

    /**
     *	Gets the current value of the team ID for this team.
     *
     *	@return integer
     */
    public function getGuildId()
    {
        return $this->get('guild_id');
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

    /**
     * @return Moturdrn_GW2Guilds_Model_Member
     */
    protected function _getMemberModel()
    {
        /** @var Moturdrn_GW2Guilds_Model_Member $model */
        $model = $this->getModelFromCache('Moturdrn_GW2Guilds_Model_Member');
        return $model;
    }

    protected function _verifyGuildName(&$data)
    {
        if (!$data)
        {
            $data = null;
            return true;
        }

        if ($this->getExisting('guild_name') === $this->get('guild_name'))
        {
            return true;
        }

        return true;
    }

    protected function _preSave()
    {
        if ($this->get('guild_name') && $this->isChanged('guild_name'))
        {
            $conflict = $this->_getGuildModel()->getGuildByName($this->get('guild_name'));
            if ($conflict)
            {
                $this->error('Guild Exists: Names must be unique');
                return false;
            }
        }
    }
}