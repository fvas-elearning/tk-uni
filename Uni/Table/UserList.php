<?php
namespace Uni\Table;



use Uni\Db\Permission;
use Uni\Uri;

/**
 * @author Mick Mifsud
 * @created 2018-07-24
 * @link http://tropotek.com.au/
 * @license Copyright 2018 Tropotek
 */
class UserList extends User
{
    /**
     * @var null|\Tk\Ui\Dialog\AjaxSelect
     */
    protected $userDialog = null;

    protected $onSelect = null;

    protected $ajaxParams = array();

    protected $userType = '';


    /**
     * @param string $tableId
     */
    public function __construct($tableId = '')
    {
        parent::__construct($tableId);
    }

    /**
     * @return string
     */
    public function getUserType(): string
    {
        return $this->userType;
    }

    /**
     * @param string $userType
     * @return UserList
     */
    public function setUserType(string $userType): UserList
    {
        $this->userType = $userType;
        return $this;
    }

    /**
     * @return null
     */
    public function getOnSelect()
    {
        return $this->onSelect;
    }

    /**
     * @param null|callable $onSelect
     * @return UserList
     */
    public function setOnSelect($onSelect)
    {
        $this->onSelect = $onSelect;
        return $this;
    }

    /**
     * @return array
     */
    public function getAjaxParams()
    {
        return $this->ajaxParams;
    }

    /**
     * @param array $ajaxParams
     * @return UserList
     */
    public function setAjaxParams($ajaxParams)
    {
        $this->ajaxParams = $ajaxParams;
        return $this;
    }

    public function init()
    {
        parent::init();
        $this->removeFilter('keywords');
        $this->removeCell('actions');
        $this->removeCell('phone');
        $this->removeCell('uid');
        $this->removeCell('type');
        $this->removeCell('active');
        $this->removeCell('lastLogin');
        $this->removeCell('created');
        $this->removeAction('delete');
        $this->removeAction('csv');

        if ($this->getAuthUser()->hasPermission(Permission::MANAGE_SUBJECT) || $this->getAuthUser()->hasPermission(Permission::MANAGE_STAFF)) {
            if ($this->getUserType() == \Uni\Db\User::TYPE_STAFF) {
                $this->appendAction(\Tk\Table\Action\Delete::create()->setLabel('Remove')
                    ->setConfirmStr('Are you sure you want to remove the user`s access from this course.')
                    ->addOnDelete(function (\Tk\Table\Action\Delete $action, $obj) {
                        $config = \Uni\Config::getInstance();
                        /** @var $obj \Uni\Db\User */
                        $course = $config->getCourseMapper()->find(\Uni\Config::getInstance()->getRequest()->get('courseId'));
                        if (!$course) {
                            \Tk\Alert::addError('Cannot locate course object.');
                        }
                        $config->getCourseMapper()->removeUser($course->getId(), $obj->getId());
                        return false;
                    }));
            } else if ($this->getUserType() == \Uni\Db\User::TYPE_STUDENT) {
                $this->appendAction(\Tk\Table\Action\Delete::create()->setLabel('Un-Enroll')
                    ->setConfirmStr('Are you sure you want to remove the user`s access from this subject.')
                    ->addOnDelete(function (\Tk\Table\Action\Delete $action, $obj) {
                        $config = \Uni\Config::getInstance();
                        /** @var $obj \Uni\Db\User */
                        $subject = $config->getSubjectMapper()->find(\Uni\Config::getInstance()->getRequest()->get('subjectId'));
                        if (!$subject) {
                            \Tk\Alert::addError('Cannot locate subject.');
                        }
                        $config->getSubjectMapper()->removeUser($subject->getId(), $obj->getId());
                        return false;
                    }));
            }

            $template = $this->getRenderer()->getTemplate();
            $this->userDialog = \Tk\Ui\Dialog\AjaxSelect::create('Add User');
            $this->userDialog->setAjaxUrl(Uri::create('/ajax/user/findFiltered.html'));
            $this->userDialog->setAjaxParams($this->getAjaxParams());
            $this->userDialog->addOnSelect($this->getOnSelect());
            $this->userDialog->execute();
            $template->appendBodyTemplate($this->userDialog->show());

            $this->appendAction(\Tk\Table\Action\Link::createLink('Add User', '#', 'fa fa-plus'))->setAttr('data-toggle', 'modal')
                ->setAttr('data-target', '#' . $this->userDialog->getId());
            $this->appendAction(\Tk\Table\Action\Csv::create());
        } else {

            $this->setEditUrl(null);

        }
        return $this;
    }

}