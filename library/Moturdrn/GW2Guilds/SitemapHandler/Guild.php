<?php

class Moturdrn_GW2Guilds_SitemapHandler_Guild extends XenForo_SitemapHandler_Abstract
{
    protected $_guildModel;

    public function getPhraseKey($key)
    {
        return 'moturdrn_gw2guilds_guild';
    }

    public function getRecords($previousLast, $limit, array $viewingUser)
    {
        $guildModel = $this->_getGuildModel();

        $ids = $guildModel->getGuildIdsInRange($previousLast, $limit);

        $guilds = $guildModel->getGuildsByIds($ids);

        ksort($guilds);

        return $guilds;
    }

    public function isIncluded(array $entry, array $viewingUser)
    {
        return true;
    }

    public function getData(array $entry)
    {
        $entry['title'] = XenForo_Helper_String::censorString($entry['guild_name']);

        return array(
            'loc' => XenForo_Link::buildPublicLink('canonical:guilds', $entry),
            'lastmod' => $entry['last_modified']
        );
    }

    public function isInterruptable()
    {
        return true;
    }

    protected function _getGuildModel()
    {
        if (!$this->_guildModel)
        {
            $this->_guildModel = XenForo_Model::create('Moturdrn_GW2Guilds_Model_Guild');
        }

        return $this->_guildModel;
    }
}