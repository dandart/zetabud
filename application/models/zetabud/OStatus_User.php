<?php
class OStatus_User extends BaseOStatus_User
{
    public function getAccessTokenObject()
    {
        return unserialize($this->getAccessToken());
    }

    public function getScreenName()
    {
        $screen_name = $this->getAccessTokenObject()->getParam('screen_name');
        if(is_null($screen_name))
        {
            return 'Unknown user';
        }
        return $screen_name;
    }

} // OStatus_User
