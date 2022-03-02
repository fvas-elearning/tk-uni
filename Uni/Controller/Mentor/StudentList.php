<?php
namespace Uni\Controller\Mentor;

use Bs\Db\UserIface;
use Dom\Loader;
use Exception;
use Tk\Request;
use Dom\Template;
use Tk\Ui\Dialog\AjaxSelect;
use Uni\Controller\AdminIface;
use Uni\Table\User;
use Uni\Uri;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class StudentList extends AdminIface
{

    /**
     * @var User
     */
    protected $userTable = null;

    /**
     * @var AjaxSelect
     */
    protected $userSelect = null;

    /**
     * @var \Uni\Db\User
     */
    protected $user = null;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->setPageTitle('Mentor Student List');
        $this->getConfig()->unsetSubject();

    }

    /**
     * @param Request $request
     * @throws Exception
     */
    public function doDefault(Request $request)
    {
        $user = $this->user = $this->getConfig()->getUserMapper()->find($request->get('userId'));


        if ($this->getAuthUser()->isCoordinator() || $this->getAuthUser()->isClient()) {
            $this->userSelect = AjaxSelect::create('Select User');
        }

        $this->userTable = User::create()->setEditUrl(Uri::createHomeUrl('/mentor/studentView.html'));
        $this->userTable->setTargetType(\Uni\Db\User::TYPE_STUDENT);
        $this->userTable->init();
        $this->userTable->removeAction('delete');

        if ($this->getAuthUser()->isCoordinator() || $this->getAuthUser()->isClient()) {
            $btn = $this->userTable->appendAction(\Tk\Table\Action\Link::createLink('Add Student', '#', 'fa fa-user-plus'));
            $btn->setAttr('data-target', '#' . $this->userSelect->getId());
            $btn->setAttr('data-toggle', 'modal');

            $this->userTable->appendAction(\Tk\Table\Action\Delete::create('Remove')->setIcon('fa fa-trash')
                ->addOnDelete(function (\Tk\Table\Action\Delete $action, $obj) use ($user) {
                    /** @var $obj \Uni\Db\User */
                    $obj->getConfig()->getUserMapper()->removeMentor($user->getId(), $obj->getId());
                    return false;
                })->setAttr('title', 'Remove Student Confirmation.')->setConfirmStr('Are you sure you want to remove the selected student(s) from your mentor list?'));
        }

        //$this->userTable->removeCell('id');
        $this->userTable->removeCell('actions');
        $this->userTable->removeCell('username');
        $this->userTable->removeCell('active');
        $this->userTable->removeCell('lastLogin');
        $this->userTable->findCell('nameFirst')->addOnPropertyValue(function ($cell, $obj, $value) {
            /** @var UserIface $obj */
            return $obj->getName();
        });

        $filter = array();
        $filter['mentorId'] = $this->user->getId();
        $filter['active'] = true;
        $list = $this->userTable->findList($filter);
        $this->userTable->setList($list);
        $this->userTable->execute();


        if ($this->userSelect) {
            $this->userSelect->addOnAjax(function (AjaxSelect $dialog) use ($list) {
                $arr = array();
                $filter = array(
                    'institutionId' => $dialog->getConfig()->getInstitutionId(),
                    'active' => true,
                    'type' => \Uni\Db\User::TYPE_STUDENT,
                    'exclude' => $list->toArray('id')
                );
                if ($dialog->getRequest()->get('keywords')) {
                    $filter['keywords'] = $dialog->getRequest()->get('keywords');
                }
                $studentList = $dialog->getConfig()->getUserMapper()->findFiltered($filter, \Tk\Db\Tool::create('name_first', 25));
                foreach ($studentList as $user) {
                    $arr[] = array('id' => $user->getId(), 'name' => $user->getName());
                }
                return $arr;
            });
            $this->userSelect->addOnSelect(function (AjaxSelect $dialog) use ($user) {
                if ($dialog->getSelectedId())
                    $dialog->getConfig()->getUserMapper()->addMentor($user->getId(), $dialog->getSelectedId());
            });
            $this->userSelect->execute();
        }

    }

    public function show()
    {
        $template = parent::show();

        $template->appendTemplate('panel', $this->userTable->getRenderer()->show());
        $template->setAttr('panel', 'data-panel-title', $this->user->getName() . '`s Student List');
        if ($this->userSelect) {
            $template->appendBodyTemplate($this->userSelect->show());
        }

        return $template;
    }

    /**
     * DomTemplate magic method
     *
     * @return Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="">

  <div class="tk-panel" data-panel-title="Mentor Student List" data-panel-icon="fa fa-users" var="panel"></div>

</div>
HTML;

        return Loader::load($xhtml);
    }


}