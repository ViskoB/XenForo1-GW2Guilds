<?php

class Moturdrn_GW2Guilds_XenForo_ControllerPublic_Member extends XFCP_Moturdrn_GW2Guilds_XenForo_ControllerPublic_Member
{
    public function actionMember()
    {
        $response = parent::actionMember();
        if ($response instanceof XenForo_ControllerResponse_View AND !empty($response->params))
        {
            $params =& $response->params;
            if(empty($params['user']))
            {
                return $response;
            }

            $memberModel = XenForo_Model::create('Moturdrn_GW2Guilds_Model_Member');
            $hasJoinAny = $memberModel->countMembers(array(
                'member_state' => 'accept',
                'user_id' => $response->params['user']['user_id']
            ));

            $params['user']['groupList'] = $hasJoinAny;
        }

        return $response;
    }

    public function actionCard()
    {
        return parent::actionCard();
    }

}
