<?php
namespace Uni\Controller;

use Tk\Form;
use Tk\Form\Field;
use Tk\Form\Event;
use Tk\Request;
use Tk\Auth\AuthEvents;
use Bs\Controller\Iface;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Recover extends Iface
{

    /**
     * @var \Tk\Form
     */
    protected $form = null;



    /**
     * Login constructor.
     */
    public function __construct()
    {
        $this->setPageTitle('Recover Password');
    }


    /**
     * @param Request $request
     * @throws \Exception
     * @throws Form\Exception
     * @throws Form\Exception
     */
    public function doDefault(Request $request)
    {
        $this->form = $this->getConfig()->createForm('recover-account');
        $this->form->setRenderer($this->getConfig()->createFormRenderer($this->form));

        $this->form->addField(new Field\Input('account'));
        $this->form->addField(new Event\Submit('recover', array($this, 'doRecover')))->addCss('btn btn-primary btn-ss');
        $this->form->addField(new Event\Link('login', \Tk\Uri::create('/login.html'), ''))
            ->removeCss('btn btn-sm btn-default btn-once');

        $this->form->execute();
    }

    /**
     * @param Form $form
     * @param \Tk\Form\Event\Iface $event
     * @throws \Tk\Exception
     */
    public function doRecover($form, $event)
    {
        if (!$form->getFieldValue('account')) {
            $form->addFieldError('account', 'Please enter a valid username or email');
        }

        if ($form->hasErrors()) {
            return;
        }

        $account = $form->getFieldValue('account');
        /** @var \Uni\Db\User $user */
        $user = null;
        if (filter_var($account, FILTER_VALIDATE_EMAIL)) {
            $user = $this->getConfig()->getUserMapper()->findByEmail($account);
        } else {
            $user = $this->getConfig()->getUserMapper()->findByUsername($account);
        }
        if (!$user) {
            $form->addFieldError('account', 'Please enter a valid username or email');
            return;
        }

        $newPass = \Tk\Config::createPassword(10);
        $user->password = $this->getConfig()->hashPassword($newPass, $user);
        $user->save();

        // Fire the login event to allow developing of misc auth plugins
        $e = new \Tk\Event\Event();
        $e->set('form', $form);
        $e->set('user', $user);
        $e->set('password', $newPass);
        $this->getConfig()->getEventDispatcher()->dispatch(AuthEvents::RECOVER, $e);

        \Tk\Alert::addSuccess('You new access details have been sent to your email address.');
        $event->setRedirect(\Tk\Uri::create());
        
    }


    public function show()
    {
        $template = parent::show();

        // Render the form
        $template->appendTemplate('form', $this->form->getRenderer()->show());

        if ($this->getConfig()->get('site.client.registration')) {
            $template->setChoice('register');
        }

        return $template;
    }


    /**
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="tk-login-panel tk-recover">

  <div var="form"></div>
  <div class="not-member" choice="register">
    <p>Not a member? <a href="/register.html">Register here</a></p>
  </div>

</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }
}