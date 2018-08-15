<?php
namespace Uni\Controller;

use Tk\Request;
use Tk\Form;
use Tk\Form\Field;
use Tk\Form\Event;
use Tk\Auth\AuthEvents;
use Tk\Event\AuthEvent;
use Bs\Controller\Iface;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Login extends Iface
{

    /**
     * @var Form
     */
    protected $form = null;

    /**
     * @var \Uni\Db\Institution
     */
    protected $institution = null;

    /**
     * @var string
     */
    protected $isInstLogin = true;


    /**
     * Login constructor.
     */
    public function __construct()
    {
        $this->setPageTitle('Login');
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->isInstLogin = false;
        $this->institution = $this->getConfig()->getInstitutionMapper()->findByDomain($request->getUri()->getHost());
        if ($this->institution) {
            $this->doInsLogin($request, $this->institution->getHash());
        } else {
            $this->init();
            $this->form->execute();
        }
    }

    /**
     * @param Request $request
     * @param string $instHash
     * @throws \Exception
     */
    public function doInsLogin(Request $request, $instHash)
    {
        if (!$this->institution) {
            $this->institution = $this->getConfig()->getInstitutionMapper()->findByHash($instHash);
        }
        if (!$this->institution || !$this->institution->active ) {
            \Tk\Alert::addWarning('Invalid or inactive Institution.');
            \Uni\Uri::create('/index.html');
        }
        $this->init();
        $this->form->appendField(new Field\Hidden('instHash', $instHash));
        $this->form->execute();
    }

    /**
     * @throws \Exception
     */
    private function init()
    {
        if (!$this->form) {
            $this->form = $this->getConfig()->createForm('login-form');
            $this->form->setRenderer($this->getConfig()->createFormRenderer($this->form));
            $this->form->addCss('form-horizontal');
        }

        $this->form->appendField(new Field\Input('username'));
        $this->form->appendField(new Field\Password('password'));
        $this->form->appendField(new Event\Submit('login', array($this, 'doLogin')))->addCss('btn btn-lg btn-primary btn-ss');
        if (!$this->isInstLogin) {
            $this->form->appendField(new Event\Link('forgotPassword', \Tk\Uri::create('/recover.html'), ''))
                ->removeCss('btn btn-sm btn-default btn-once');
        }
    }


    /**
     * doLogin()
     *
     * @param \Tk\Form $form
     * @param \Tk\Form\Event\Iface $event
     */
    public function doLogin($form, $event)
    {
        if (!$form->getFieldValue('username')) {
            $form->appendFieldError('username', 'Please enter a valid username');
        }
        if (!$form->getFieldValue('password')) {
            $form->appendFieldError('password', 'Please enter a valid password');
        }
        if ($form->hasErrors()) {
            return;
        }

        try {
            // Fire the login event to allow developing of misc auth plugins
            $e = new AuthEvent();
            $e->replace($form->getValues());
            $this->getConfig()->getEventDispatcher()->dispatch(AuthEvents::LOGIN, $e);
            $result = $e->getResult();
            if (!$result) {
                $form->addError('Invalid username or password');
                return;
            }
            if (!$result->isValid()) {
                $form->addError( implode("<br/>\n", $result->getMessages()) );
                return;
            }

            // Copy the event to avoid propagation issues
            $e2 = new AuthEvent($e->getAdapter());
            $e2->replace($e->all());
            $e2->setResult($e->getResult());
            $e2->setRedirect($e->getRedirect());
            $this->getConfig()->getEventDispatcher()->dispatch(AuthEvents::LOGIN_SUCCESS, $e2);
            if ($e2->getRedirect())
                $e2->getRedirect()->redirect();

        } catch (\Exception $e) {
            \Tk\Log::error($e->__toString());
            $form->addError('Login Error: ' . $e->getMessage());
        }
    }

    /**
     * show()
     *
     * @return \Dom\Template
     * @throws \Exception
     */
    public function show()
    {
        $template = parent::show();

        // Render the form
        $template->appendTemplate('form', $this->form->getRenderer()->show());

        if ($this->institution) {
            if ($this->institution->getLogoUrl()) {
                $template->setChoice('instLogo');
                $template->setAttr('instLogo', 'src', $this->institution->getLogoUrl()->toString());
            }
            $template->insertText('instName', $this->institution->name);
            $template->setChoice('inst');
        } else {
            $template->setChoice('noinst');
            $template->setChoice('recover');
        }
        if ($this->getConfig()->get('site.client.registration') && !$this->institution) {
            $template->setChoice('register');
        }


        $js = <<<JS
jQuery(function ($) {
  
  $('#login-form').on("keypress", function (e) {
    if (e.which === 13) {
      $(this).find('#login-form_login').trigger('click');
    }
  });
  
});
JS;
        $template->appendJs($js);

        return $template;
    }


    /**
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="tk-login-panel tk-login">

  <div var="form"></div>
  <div class="not-member" choice="register">
    <p>Not a member? <a href="/register.html">Register here</a></p>
  </div>

</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }
}