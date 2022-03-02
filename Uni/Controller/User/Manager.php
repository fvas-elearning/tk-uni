<?php
namespace Uni\Controller\User;


use Tk\Ui\Link;
use Uni\Db\Permission;
use Uni\Db\User;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Manager extends \Uni\Controller\AdminManagerIface
{

    /**
     * @var null|\Tk\Uri
     */
    protected $editUrl = null;

    /**
     * Setup the controller to work with users of this type
     * @var string
     */
    protected $targetType = '';

    /**
     * @var \Uni\Ui\Dialog\ImportStudents
     */
    protected $importDialog = null;


    /**
     * Manager constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->setPageTitle('User Manager');

        if ($this->getAuthUser()->isClient()) {
            $this->getConfig()->resetCrumbs();
        }
    }

    /**
     * @return string
     */
    public function getTargetType(): string
    {
        return $this->targetType;
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
        switch($this->getTargetType()) {
            case \Uni\Db\User::TYPE_ADMIN:
                $this->setPageTitle('Admin Users');
                break;
            case \Uni\Db\User::TYPE_STAFF:
                $this->setPageTitle('Staff Manager');
                break;
            case \Uni\Db\User::TYPE_STUDENT:
                $this->setPageTitle('Student Manager');
                break;
        }

        if (!$this->editUrl) {
            $this->editUrl = \Uni\Uri::createHomeUrl('/'.$this->getTargetType().'UserEdit.html');
            if ($this->getConfig()->isSubjectUrl()) {
                $this->editUrl = \Uni\Uri::createSubjectUrl('/'.$this->getTargetType().'UserEdit.html');
            }
        }


        // Setup import students dialog
        if ($this->getConfig()->isSubjectUrl() && ($this->getTargetType() == User::TYPE_STUDENT && $this->getConfig()->getAuthUser()->hasPermission(\Uni\Db\Permission::MANAGE_SUBJECT))) {
            $this->importDialog = new \Uni\Ui\Dialog\ImportStudents('Import Users To this Subject');
            $this->importDialog->execute();
        }


        $this->setTable($this->createTable());
        if (!$this->getAuthUser()->isStudent())
            $this->getTable()->getActionCell()->removeButton($this->getTable()->getActionCell()->findButtonByName('Masquerade'));

        $this->initTable();
        $this->getTable()->init();
        $this->postInitTable();


        $filter = array();
        if ($this->getAuthUser()->getInstitutionId()) {
            $filter['institutionId'] = $this->getAuthUser()->getInstitutionId();
        } else if ($this->getAuthUser()->isClient()) {
            $filter['institutionId'] = $this->getConfig()->getInstitutionId();
        }
        if (empty($filter['type'])) {
            $filter['type'] = $this->getTargetType();
        }
        if (($this->getConfig()->isSubjectUrl() || $request->has('subjectId')) && $this->getConfig()->getSubjectId()) {
            if ($this->getTargetType() == User::TYPE_STUDENT)
                $filter['subjectId'] = $this->getConfig()->getSubjectId();
            else if ($this->getTargetType() == User::TYPE_STAFF)
                $filter['courseId'] = $this->getConfig()->getCourseId();
        }
        $this->getTable()->setList($this->getTable()->findList($filter));


    }

    /**
     * @return \Bs\TableIface|\Tk\Table|\Uni\Table\User
     */
    public function createTable()
    {
        return \Uni\Table\User::create()->setEditUrl($this->editUrl)->setTargetType($this->getTargetType());
    }


    public function initTable()
    {
        // Do any override inits here
    }


    public function postInitTable()
    {
        // Do any override inits here
    }

    /**
     *
     */
    public function initActionPanel()
    {
        if (
            ($this->getTargetType() == User::TYPE_STAFF && $this->getConfig()->getAuthUser()->hasPermission(\Uni\Db\Permission::MANAGE_STAFF)) ||
            ($this->getTargetType() == User::TYPE_STUDENT && $this->getConfig()->getAuthUser()->isCoordinator()) ||
            $this->getConfig()->getAuthUser()->isClient() || $this->getConfig()->getAuthUser()->isAdmin()) {
            $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('Create ' . ucfirst($this->getTargetType()), $this->getTable()->getEditUrl(), 'fa fa-user-plus'));
        }
        if (!$this->getConfig()->isSubjectUrl() && ($this->getTargetType() == User::TYPE_STAFF && $this->getConfig()->getAuthUser()->hasPermission(\Uni\Db\Permission::MANAGE_STAFF))) {
            $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('Import Mentor List', \Uni\Uri::createHomeUrl('/mentorImport.html'), 'fa fa-users'));
        }
        if ($this->importDialog) {
            $this->getActionPanel()->append(Link::createBtn('Import Students','#', 'fa fa-user-plus'))
                ->setAttr('data-toggle', 'modal')->setAttr('data-target', '#'.$this->importDialog->getId())
                ->setAttr('title', 'Create student accounts and enroll into this subject');
        }
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $this->initActionPanel();
        $template = parent::show();

        $template->appendTemplate('table', $this->table->show());

        if ($this->importDialog)
            $template->appendBodyTemplate($this->importDialog->show());

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
<div class="tk-panel" data-panel-icon="fa fa-users" var="table"></div>
HTML;

        return \Dom\Loader::load($xhtml);
    }


}
