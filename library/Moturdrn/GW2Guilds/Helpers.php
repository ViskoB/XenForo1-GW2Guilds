<?php
class Moturdrn_GW2Guilds_Helpers
{
    public static function helperGuildEmblem(array $guild)
    {
        $hash = uniqid();
        $src = XenForo_Application::$externalDataUrl . "/Moturdrn/GW2Guilds/$guild[guildid].png?$hash";
        
        $guildname = htmlspecialchars($guild['guildname']);
        
        $src = "https://guilds.gw2w2w.com/guilds/{$guildname}/32.svg";

        $image = "<img src=\"{$src}\" alt=\"{$guildname}\" class=\"GuildIcon\" />";

        return $image;
    }

    public static function helperRibbon(array $user)
    {
        $guildModel = XenForo_Model::create('Moturdrn_GW2Guilds_Model_Guild');
        $guildMemberModel = XenForo_Model::create('Moturdrn_GW2Guilds_Model_Member');
        $guilds = $guildModel->getActiveGuildsOfUser($user['user_id']);
        $baseSpan = "";
        foreach($guilds as $guild)
        {
            $guildMember = $guildMemberModel->getMemberState($user['user_id'], $guild['guildid']);
            if($guildMember['state'] == 'accepted') {
                $viewMiniLink = XenForo_Link::buildPublicLink('canonical:guilds/ViewMini', $guild);
                $baseSpan .= "\n<em class=\"userBanner guild wrapped\" itemprop=\"title\"><span class=\"before\"></span><strong><a data-href=\"$viewMiniLink\" href=\"$viewLink\" class=\"OverlayTrigger\">" . $guild['guildname'] . " [" . $guild['guildtag'] . "]</a></strong><span class=\"after\"></span></em>";
            }
        }

        return $baseSpan;
    }

    public static function helperUserBanner($user, $extraClass = '', $disableStacking = false)
    {
        echo $user['guild_ids'];
        $response = call_user_func_array($GLOBALS['XenForoHelperUserBanner'], func_get_args());

        $response .= "\n" . static::helperRibbon($user);

        return $response;
    }
}