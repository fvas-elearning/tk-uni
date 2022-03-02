<?php
namespace Uni\Controller\Institution;


use Uni\Db\Permission;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Edit extends \Uni\Controller\AdminEditIface
{

    /**
     * @var \Uni\Db\Institution
     */
    protected $institution = null;


    /**
     * @param \Tk\Request $request
     * @return \Tk\Db\Map\Model|\Tk\Db\ModelInterface|\Uni\Db\Institution|\Uni\Db\InstitutionIface|null
     * @throws \Exception
     */
    protected function findInstitution(\Tk\Request $request)
    {
        if (!$this->institution) {
            if ($this->getAuthUser()->isAdmin())
                $this->institution = $this->getConfig()->createInstitution();
            if ($request->get('institutionId')) {
                $this->institution = $this->getConfig()->getInstitutionMapper()->find($request->get('institutionId'));
            }
            if ($this->getAuthUser()->isClient()) {
                $this->institution = $this->getConfig()->getInstitutionMapper()->findByUserId($this->getAuthUser()->getId());
            }
            if ($this->getAuthUser()->isStaff()) {
                $this->setPageTitle('Settings');
                $this->institution = $this->getAuthUser()->getInstitution();
            }
        }
        return $this->institution;
    }


    /**
     * @param \Tk\Request $request
     * @throws \Exception
     */
    public function doDefault(\Tk\Request $request)
    {
        $this->setPageTitle('Institution Edit');
        $this->findInstitution($request);

        $this->setForm(\Uni\Form\Institution::create()->setModel($this->institution));
        $this->initForm($request);
        $this->getForm()->execute();

    }

    /**
     * @throws \Exception
     */
    public function initActionPanel()
    {
        if ($this->getConfig()->getMasqueradeHandler()->canMasqueradeAs($this->getAuthUser(), $this->getInstitution()->getUser())) {
            $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('Masquerade',
                \Uni\Uri::create()->reset()->set(\Uni\Listener\MasqueradeHandler::MSQ, $this->getInstitution()->getUser()->getHash()),
                'fa fa-user-secret'))->addCss('tk-masquerade')->setAttr('data-confirm', 'You are about to masquerade as the selected user?');
        }

        if ($this->getAuthUser()->isClient() || $this->getAuthUser()->isStaff()) {
            if ($this->getAuthUser()->hasPermission(Permission::MANAGE_PLUGINS) || $this->getAuthUser()->isClient()) {
                $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('Plugins',
                    \Uni\Uri::createHomeUrl('/institution/' . $this->getInstitution()->getId() . '/plugins.html'), 'fa fa-plug'));
            }
            $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('Staff',
                \Uni\Uri::createHomeUrl('/staffUserManager.html'), 'fa fa-users'));

            if ($this->getAuthUser()->hasPermission(Permission::MANAGE_SUBJECT) || $this->getAuthUser()->isClient()) {
                $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('Courses',
                    \Uni\Uri::createHomeUrl('/courseManager.html'), 'fa fa-book'));
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
<div>

  <div class="tk-panel" data-panel-icon="fa fa-university" var="panel"></div>
  
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

    /**
     * @return \Tk\Db\ModelInterface|\Uni\Db\Institution
     */
    public function getInstitution()
    {
        return $this->institution;
    }

}