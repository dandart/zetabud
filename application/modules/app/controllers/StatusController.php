<?php
class App_StatusController extends ZB_Controller_Action_App
{
    private $_site_id;
    private $_site;
    private $_config;

    public function preDispatch()
    {
        parent::preDispatch();

        $this->createDefaults();
        $this->_session = new Zend_Session_Namespace('oauth');

        $site_id = $this->getRequest()->getQuery('site');
        if(is_null($site_id) && !is_null($this->_session->site_id))
        {
            $site_id = $this->_session->site_id;
        }
        if(intval($site_id) > 0)
        {
            $this->_site = OStatus_SitePeer::retrieveByPK($site_id);
            $this->_config = $this->_site->getConfig(); 
        }
       
    }

    public function indexAction()
    {
        $this->requireLogin();
        $this->view->assign('apptitle', 'Social Status');

        $sites = OStatus_SitePeer::retrieveAll();
        $users = OStatus_UserPeer::retrieveMine();

        $this->view->assign('sites', $sites);
        $this->view->assign('users', $users);

        $status_form = $this->_getStatusForm();
        $this->view->assign('status_form', $status_form);

        if($this->getRequest()->isPost())
        {
            $data = $this->getRequest()->getPost();
            if($status_form->isValid($data))
            {
                $status = $data['status'];

                foreach($users as $user)
                {
                    $token = unserialize($user->getAccessToken());
                    $client = $token->getHttpClient($user->getSite()->getConfig());
                    $client->setUri($user->getSite()->getUpdateUrl());
                    $client->setMethod(Zend_Http_Client::POST);
                    $client->setParameterPost('status', $status);
                    $response = $client->request();

                    $data = Zend_Json::decode($response->getBody());
                    $result = $response->getBody();
                    if (isset($data['text']))
                    {
                        $this->setMessage(array(
                            'text' => 'Status Posted',
                            'class' => 'info'
                        ));
                    }
                    elseif(isset($data->error))
                    {
                        $this->setMessage(array(
                            'text' => $data->error,
                            'class' => 'error'
                        ));
                    }
                    else
                    {
                        $this->setMessage(array(
                            'text' => $data,
                            'class' => 'error'
                        ));
                    }
                }
            }
        }
    }

    public function requestAction()
    {
        if(!is_int($this->_site))
        {
            $this->_redirect('/app/status');
        }

        $consumer = new Zend_Oauth_Consumer($this->_config);
        $token = $consumer->getRequestToken();
        $this->_session->request_token = serialize($token);
        $this->_session->site_id = $this->_site->getId();
        $this->_redirect($consumer->getRedirectUrl());
    }

    public function callbackAction()
    {

        $consumer = new Zend_Oauth_Consumer($this->_config);
        if (!empty($_GET) && isset($this->_session->request_token))
        {
             $token = $consumer->getAccessToken( $_GET, unserialize($this->_session->request_token));
             $this->_session->access_token = serialize($token);
             $this->_session->request_token = null;
             $user = new OStatus_User();
             $user->setUser(User::getIdentity());
             $user->setSiteId($this->_session->site_id);
             $user->setAccessToken(serialize($token));
             $user->save();
             $this->_redirect('/app/status');
        }
        else
        {
            die('Oops. Malformed request.');
        }

    }

    private function createDefaults()
    {
        $sites = OStatus_SitePeer::retrieveAll();
        if(count($sites) > 0)
        {
            return null; // No need to add some - we have some already.
        }

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/config/ostatus_sites.ini', 'general');
        foreach($config->site->toArray() as $config_site)
        {
            $site = new OStatus_Site();
            $site->fromArray($config_site); // Doesn't work????

            $site->setFullname($config_site['fullname']);
            $site->setShortname($config_site['shortname']);
            $site->setConsumerKey($config_site['consumer_key']);
            $site->setConsumerSecret($config_site['consumer_secret']);
            $site->setSiteUrl($config_site['site_url']);
            $site->setRequestTokenUrl($config_site['request_token_url']);
            $site->setAccessTokenUrl($config_site['access_token_url']);
            $site->setAuthorizeUrl($config_site['authorize_url']);
            $site->setUpdateUrl($config_site['update_url']);
            $site->setUpdateParam($config_site['update_param']);
            $site->save();
        }
    }

    private function _getStatusForm()
    {
        $form = new Zend_Form();
        
        $form->setAction('');
        $form->setMethod('post');

        $status = new Zend_Form_Element_Textarea('status');
        $status->setOptions(array('style' => 'width: 300px; height: 150px;'));
        $status->setLabel('Status');

        $submit = new Zend_Form_Element_Submit('Submit');

        $form->addElements(array($status, $submit));

        return $form;
    }

}
