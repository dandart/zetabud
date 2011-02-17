<?php
class ZB_Controller_Action_Ajax extends ZB_Controller_Action
{
    public function preDispatch()
    {
        parent::preDispatch();

        $this->view->layout()->disableLayout();

        $renderer = $this->getHelper('ViewRenderer');
        $renderer->setNoRender(true);
    }
}
