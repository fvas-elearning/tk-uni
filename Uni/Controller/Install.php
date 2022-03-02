<?php
namespace Uni\Controller;

use Tk\Form;
use Tk\Form\Field\Input;
use Tk\Request;
use Uni\Db\Permission;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Install extends Iface
{
    /**
     * @var null|\Tk\Form
     */
    protected $form = null;

    /**
     * @var null|\Tk\Db\Data
     */
    protected $data = null;

    /**
     * @var null|\Uni\Db\Institution
     */
    protected $institution = null;

    /**
     * @var null|\Uni\Db\User
     */
    protected $instUser = null;

    /**
     * @var null|\Uni\Db\User
     */
    protected $adminUser = null;



    /**
     * Install constructor.
     */
    public function __construct()
    {
        $this->setPageTitle('Install');
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->data = \Tk\Db\Data::create();

        $this->adminUser = $this->getConfig()->createUser();
        $this->adminUser->setType(\Uni\Db\User::TYPE_ADMIN);
        $this->adminUser->setName('Administrator');
        $this->adminUser->setEmail('info@unimelb.edu.au');
        $this->adminUser->setUsername('admin');


        $this->institution = $this->getConfig()->createInstitution();
        $this->institution->setName('The University Of Melbourne');
        $this->institution->setEmail('fvas-elearning@unimelb.edu.au');
        //$this->institution->setDescription('<p>The University Of Melbourne</p>');
        $this->institution->setStreet('250 Princes Highway');
        $this->institution->setCity('Werribee');
        $this->institution->setState('Victoria');
        $this->institution->setPostcode('3030');
        $this->institution->setCountry('Australia');
        $this->institution->setAddress('250 Princes Hwy, Werribee VIC 3030, Australia');
        $this->institution->setMapLat('-37.88916600');
        $this->institution->setMapLng('144.69314774');
        $this->institution->setMapZoom(18.00);


        $this->instUser = $this->getConfig()->createUser();
        $this->instUser->setType(\Uni\Db\User::TYPE_CLIENT);
        $this->instUser->setUsername('unimelb');
        $this->adminUser->setEmail('fvas-elearning@unimelb.edu.au');
        $this->instUser->setNameFirst('The University Of Melbourne');



        $this->form = $this->getConfig()->createForm('install');
        $this->form->setDispatcher($this->getConfig()->getEventDispatcher());
        $this->form->setRenderer($this->getConfig()->createFormRenderer($this->form));

        $layout = $this->form->getRenderer()->getLayout();


        $this->form->setFieldset('Site Details');
        $this->form->appendField(new Input('site.title'))->setLabel('Site Title')->setRequired(true);
        $this->form->appendField(new Input('site.short.title'))->setLabel('Short Title')->setRequired(true);
        $this->form->appendField(new Input('site.email'))->setLabel('Site Email')->setRequired(true);


        $xhtml = <<<HTML
<div class="inline" var="checkbox">
  <input type="hidden" var="hidden" value="" />
  <input type="checkbox" var="element" /> 
  <label var="checkbox-label">
    <span var="label" class="cb-label">&nbsp;</span>
  </label>
</div>
HTML;
        $this->form->appendField(new Form\Field\Checkbox('exampleData'))->setTemplate(\Dom\Loader::load($xhtml))->setRequired(true)
            ->setCheckboxLabel('Populate the database with sample staff/student and subject data.');

        $this->form->setFieldset('Admin Setup');
        $this->form->appendField(new Input('admin-username'))->setLabel('Admin Username')->setReadonly()->setDisabled();
        $this->form->appendField(new Form\Field\Password('admin-newPassword'))->setLabel('Admin Password')->setRequired(true);
        $this->form->appendField(new Form\Field\Password('admin-confPassword'))->setLabel('Admin Password Confirm')->setRequired(true);

        $this->form->setFieldset('Institution Setup');
        $this->form->appendField(new Input('ins-name'))->setLabel('Institution Name')->setRequired(true);
        $this->form->appendField(new Input('ins-username'))->setLabel('Institution Username')->setRequired(true);
        $this->form->appendField(new Form\Field\Password('ins-newPassword'))->setLabel('Institution Password')->setRequired(true);
        $this->form->appendField(new Form\Field\Password('ins-confPassword'))->setLabel('Institution Password Confirm')->setRequired(true);


        // Load form data
        $this->form->load(array(
            'ins-name' => $this->institution->getName(),
            'ins-username' => $this->instUser->getUsername(),
            'ins-email' => $this->institution->getEmail(),
            'site.email' => $this->institution->getEmail(),
            'admin-username' => $this->adminUser->getUsername()
        ));
        if ($this->getConfig()->isDebug()) {
            $this->form->load(array(
                'admin-newPassword' => 'password',
                'admin-confPassword' => 'password',
                'ins-newPassword' => 'password',
                'ins-confPassword' => 'password'
            ));
        }


        $this->form->load($this->data->all());

        $this->form->appendField(new Form\Event\Submit('save', array($this, 'doSubmit')));

        $this->form->execute($request);

    }

    /**
     * @param Form $form
     * @param Form\Event\Iface $event
     * @throws \Exception
     */
    public function doSubmit($form, $event)
    {

        // Load Admin
        $this->getConfig()->getUserMapper()->mapForm(array(
            //'email' => $form->getFieldValue('site.email'),
            'email' => 'fvas-elearning@unimelb.edu.au',
            'institutionId' => 0
        ), $this->adminUser);
        // load Institution
        $this->getConfig()->getInstitutionMapper()->mapForm(array(
            'email' => $form->getFieldValue('site.email'),
            'name' => $form->getFieldValue('ins-name'),
            'description' => sprintf('<p>%s</p>', $form->getFieldValue('ins-name'))
        ), $this->institution);
        // load Institution User
        $this->getConfig()->getUserMapper()->mapForm(array(
            'email' => $form->getFieldValue('site.email'),
            'nameFirst' => $form->getFieldValue('ins-name'),
            'institutionId' => 0,
            'username' => $form->getFieldValue('ins-username')
        ), $this->instUser);

        $this->data->replace( $form->getValues('/^site\./'));

        if (!$form->getFieldValue('site.title') || strlen($form->getFieldValue('site.title')) < 3) {
            $form->addFieldError('site.title', 'Please enter a valid site name');
        }
        if (!$form->getFieldValue('site.short.title') || strlen($form->getFieldValue('site.short.title')) < 1) {
            $form->addFieldError('site.short.title', 'Please enter short name for the site');
        }
        if (!$form->getFieldValue('site.email') || !filter_var($form->getFieldValue('site.email'), \FILTER_VALIDATE_EMAIL)) {
            $form->addFieldError('site.email', 'Please enter a valid site email address');
        }

        // Password validation needs to be here
        if ($form->getField('admin-newPassword')) {
            if ($form->getFieldValue('admin-newPassword')) {
                if ($form->getFieldValue('admin-newPassword') != $form->getFieldValue('admin-confPassword')) {
                    $form->addFieldError('admin-newPassword', 'Passwords do not match.');
                    $form->addFieldError('admin-confPassword');
                }
            } else {
                $form->addFieldError('admin-newPassword', 'Please enter a password for the administrator account.');
            }
        }
        if ($form->getField('ins-newPassword')) {
            if ($form->getFieldValue('ins-newPassword')) {
                if ($form->getFieldValue('ins-newPassword') != $form->getFieldValue('ins-confPassword')) {
                    $form->addFieldError('ins-newPassword', 'Passwords do not match.');
                    $form->addFieldError('ins-confPassword');
                }
            } else {
                $form->addFieldError('ins-newPassword', 'Please enter a password for the Institution account.');
            }
        }

        $form->addFieldErrors($this->adminUser->validate());
        $form->addFieldErrors($this->instUser->validate());
        $form->addFieldErrors($this->institution->validate());
        if ($form->hasErrors()) {
            return;
        }

        $this->data->save();

        if ($form->getFieldValue('admin-newPassword')) {
            $this->adminUser->setNewPassword($form->getFieldValue('admin-newPassword'));
        }
        $this->adminUser->save();

        $this->adminUser->addPermission($this->getConfig()->getPermission()->getDefaultUserPermissions($this->adminUser->getType()));
        //$this->adminUser->addPermission(Permission::getDefaultPermissionList($this->adminUser->getType()));


        if ($form->getFieldValue('ins-newPassword')) {
            $this->instUser->setNewPassword($form->getFieldValue('ins-newPassword'));
        }
        $this->instUser->save();

        $this->adminUser->addPermission($this->getConfig()->getPermission()->getDefaultUserPermissions($this->adminUser->getType()));
        //$this->instUser->addPermission(Permission::getDefaultPermissionList($this->instUser->getType()));

        $this->institution->setUserId($this->instUser->getVolatileId());
        $this->institution->save();


        \Tk\Alert::addSuccess('Site Setup Successfully!');
        $event->setRedirect($this->getRedirectUrl());

        // Override or add DB data as needed
        $this->doInstall($form, $event);
    }

    /**
     * @param Form $form
     * @param Form\Event\Iface $event
     * @throws \Exception
     */
    protected function doInstall($form, $event)
    {

//        if ($form->getFieldValue('exampleData') === true) {
//            // TODO: Implement the example data code
//            //\Tk\Alert::addWarning('TODO: Implement the example data code');
//
//            // See if we can run the command testData()
//
//        }

        // Do this in yor own sql not her or else add some SQL in the doInstall() function
        //$this->getConfig()->getDb()->exec('TRUNCATE course;');

    }


    /**
     * @return \Tk\Uri
     */
    public function getRedirectUrl()
    {
        return \Tk\Uri::create();
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();
        $css = <<<CSS
body {
  background-color: #FFF;
}
.page-header, header.image , .page-footer {
  display: none !important;
}
.page-inner {
    padding-top: 10px;
}
CSS;
        $template->appendCss($css);

        $js = <<<JS
jQuery(function ($) {
  
  
  
});
JS;
        $template->appendJs($js);

        $template->appendTemplate('form', $this->form->getRenderer()->show());
        
        return $template;
    }

    /**
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="container">
  
  <h1>Site Setup</h1>
  <div var="form"></div>
  
  <p>&nbsp;</p>
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }


}