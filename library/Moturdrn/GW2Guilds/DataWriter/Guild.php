<?php

class Moturdrn_GW2Guilds_DataWriter_Guild extends XenForo_DataWriter
{
    protected function _getFields()
    {
        return array(
            'xf_moturdrn_gw2guilds_guilds' => array(
                'guildid' => array('type' => self::TYPE_UINT, 'autoIncrement' => true),

                'guildname' => array('type' => self::TYPE_STRING, 'maxLength' => 50, 'required' => true,
					'verification' => array('$this', '_verifyGuildName'),),
                'guildtag' => array('type' => self::TYPE_STRING, 'maxLength' => 4, 'required' => true),

                'guildleader_userid' => array('type' => self::TYPE_UINT, 'required' => true),
                'guildofficer_userids' => array('type' => self::TYPE_STRING, 'maxLength' => 250, 'default' => ''),

                'members' => array('type' => self::TYPE_STRING, 'maxLength' => 20, 'default' => '1-50'),
                'WvW' => array('type' => self::TYPE_STRING, 'default' => 'N'),
                'PvE' => array('type' => self::TYPE_STRING, 'default' => 'N'),
                'PvP' => array('type' => self::TYPE_STRING, 'default' => 'N'),
                'Casual' => array('type' => self::TYPE_STRING, 'default' => 'N'),
                'guildrecruitment' => array('type' => self::TYPE_STRING, 'maxLength' => 6777215,
                    'default' => 'No Guild Information Entered'),
                'guildwebsite' => array('type' => self::TYPE_STRING, 'maxLength' => 500, 'default' => ''),
                'status' => array('type' => self::TYPE_STRING, 'maxLength' => 20, 'default' => 'Pending (New)'),
                'last_modified' => array('type' => self::TYPE_UINT)
            )
        );
    }

    protected function _getExistingData($data)
    {
        if (!$guildID = $this->_getExistingPrimaryKey($data, 'guildid'))
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
        $this->_db->query('
			DELETE FROM xf_user_alert
			WHERE content_type = ?
				AND content_id = ?
		', array('moturdrn_gw2guildsmember', $this->get('guildid')));
    }

    /**
     * Gets SQL condition to update the existing record.
     *
     * @return string
     */
    protected function _getUpdateCondition($tableName)
    {
        return  'guildid = ' . $this->_db->quote($this->getExisting('guildid'));
    }

    /**
     *	Gets the current value of the team ID for this team.
     *
     *	@return integer
     */
    public function getGuildId()
    {
        return $this->get('guildid');
    }

    protected function _getGuildModel()
    {
        return $this->getModelFromCache('Moturdrn_GW2Guilds_Model_Guild');
    }

    protected function _verifyGuildName(&$data)
    {
        if (!$data)
        {
            $data = null;
            return true;
        }

        if ($this->getExisting('guildname') === $this->get('guildname'))
        {
            return true;
        }

        return true;
    }

    protected function _preSave()
    {
        if ($this->get('guildname') && $this->isChanged('guildname'))
        {
            $conflict = $this->_getGuildModel()->getGuildByName($this->get('guildname'));
            if ($conflict)
            {
                $this->error('Guild Exists: Names must be unique');
                return false;
            }
        }
    }
}