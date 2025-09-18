<?php

class IndexController extends pm_Controller_Action
{
    public function init()
    {
        parent::init();
        if (!\pm_Session::getClient()->isAdmin()) {
            throw new \pm_Exception('Permission denied');
        }
        $this->view->pageTitle = 'INWX DNS Settings';
    }

    public function indexAction()
    {
        $form = new pm_Form_Simple();

        $form->addElement('text', 'inwx_username', [
            'label' => 'INWX Username',
            'value' => pm_Settings::get('inwx_username'),
            'required' => true,
        ]);

        $form->addElement('password', 'inwx_password', [
            'label' => 'INWX Password',
            'value' => pm_Settings::get('inwx_password'),
            'required' => true,
        ]);

        $form->addElement('text', 'inwx_2fa_secret', [
            'label' => '2FA Secret (optional)',
            'value' => pm_Settings::get('inwx_2fa_secret'),
            'required' => false,
            'description' => 'TOTP shared secret for API 2FA if enabled on your account.',
        ]);

        $currentEnv = pm_Settings::get('inwx_live') === '1' ? 'live' : 'ote';
        $form->addElement('radio', 'environment', [
            'label' => 'Environment',
            'multiOptions' => [
                'live' => 'Live (Production)',
                'ote'  => 'OTE (Sandbox)',
            ],
            'value' => $currentEnv,
            'required' => true,
        ]);

        $form->addControlButtons([
            'sendTitle' => 'Save',
            'cancelLink' => pm_Context::getModulesListUrl(),
        ]);

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {
                pm_Settings::set('inwx_username', $form->getValue('inwx_username'));
                pm_Settings::set('inwx_password', $form->getValue('inwx_password'));
                pm_Settings::set('inwx_2fa_secret', $form->getValue('inwx_2fa_secret'));
                $env = $form->getValue('environment');
                pm_Settings::set('inwx_live', $env === 'live' ? '1' : '0');

                $this->_status->addMessage('info', 'Settings were saved.');
                $this->_helper->json(['redirect' => pm_Context::getBaseUrl()]);
                return;
            } else {
                $this->_status->addMessage('error', 'Please correct the errors in the form.');
            }
        }

        $this->view->form = $form;
    }
}
