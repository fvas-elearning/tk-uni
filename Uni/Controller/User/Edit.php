<?php
namespace Uni\Controller\User;


use Uni\Db\User;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Edit extends \Uni\Controller\AdminEditIface
{
    /**
     * Setup the controller to work with users of this type
     * @var string
     */
    protected $targetType = '';

    /**
     * @var \Uni\Db\User
     */
    protected $user = null;


    /**
     *
     */
    public function __construct()
    {
        $this->setPageTitle('User Edit');
    }

    /**
     * @param \Tk\Request $request
     * @param string $targetType
     * @throws \Exception
     */
    public function doDefaultType(\Tk\Request $request, $targetType)
    {
        $this->targetType = $targetType;
        $this->doDefault($request);
    }

    /**
     * @param \Tk\Request $request
     * @throws \Exception
     */
    public function doDefault(\Tk\Request $request)
    {
        switch($this->targetType) {
            case \Uni\Db\User::TYPE_ADMIN:
                $this->setPageTitle('Admin Edit');
                break;
            case \Uni\Db\User::TYPE_STAFF:
                $this->setPageTitle('Staff Edit');
                break;
            case \Uni\Db\User::TYPE_STUDENT:
                $this->setPageTitle('Student Edit');
                break;
        }

        $this->user = $this->getConfig()->createUser();
        if ($this->targetType != \Uni\Db\User::TYPE_ADMIN && $this->targetType != \Uni\Db\User::TYPE_CLIENT) {
            $this->user->setInstitutionId($this->getConfig()->getInstitutionId());
        }
        $this->user->setType($this->targetType);

        if ($request->has('userId')) {
            $this->user = $this->getConfig()->getUserMapper()->find($request->get('userId'));
            if (!$this->user)
                throw new \Tk\Exception('Invalid user account.');
            if ($this->getAuthUser()->isStaff() && $this->getAuthUser()->getInstitutionId() != $this->user->getInstitutionId())
                throw new \Tk\Exception('Invalid system details');
        }

        $this->setForm($this->createForm());
        $this->initForm($request);
        $this->getForm()->execute($request);

    }

    public function initForm(\Tk\Request $request)
    {

        if (!$this->getAuthUser()->getId() && ($this->getAuthUser()->getId() == 1 || !$this->getConfig()->getAuthUser()->hasPermission(\Uni\Db\Permission::MANAGE_SUBJECT))) {
            $this->getForm()->appendField(new \Tk\Form\Field\Html('username'))->setAttr('disabled')
                ->addCss('form-control disabled')->setTabGroup('Details');
        }

    }

    /**
     * @return \Bs\Form\User
     */
    protected function createForm()
    {
        return \Uni\Form\User::create()->setTargetType($this->targetType)->setModel($this->user);
    }

    /**
     * @throws \Exception
     */
    public function initActionPanel()
    {
        if ($this->user->getId()) {
            if ($this->getConfig()->getMasqueradeHandler()->canMasqueradeAs($this->getAuthUser(), $this->user)) {
                $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('Masquerade',
                    \Uni\Uri::create()->reset()->set(\Uni\Listener\MasqueradeHandler::MSQ, $this->user->getHash()), 'fa fa-user-secret'))
                    ->setAttr('data-confirm', 'You are about to masquerade as the selected user?')->addCss('tk-masquerade');
            }

            if ($this->user->isMentor() && $this->getAuthUser()->hasType(User::TYPE_STAFF, User::TYPE_CLIENT)) {
                $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('Mentee`s',
                    \Uni\Uri::createHomeUrl('/mentorList.html')->set('userId', $this->user->getId()), 'fa fa-users'));
            }
        }
    }

    /**
     * @return \Dom\Template
     * @throws \Exception
     */
    public function show()
    {
        $this->initActionPanel();
        $template = parent::show();

        // Render the form
        $template->appendTemplate('panel', $this->getForm()->show());
        
        if ($this->user->getId()) {
            $template->setAttr('panel', 'data-panel-title', $this->user->getName() . ' - [UID ' . $this->user->getId() . ']');
        } else {
            $template->setAttr('panel', 'data-panel-title', 'Create User');
        }
        return $template;
    }


    /**
     * DomTemplate magic method
     *
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {

        $xhtml = <<<HTML
<div class="tk-panel" data-panel-icon="fa fa-user" var="panel"></div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}