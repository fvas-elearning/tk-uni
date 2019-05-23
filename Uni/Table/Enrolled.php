<?php
namespace Uni\Table;

use Tk\Form\Field;
use Tk\Table\Cell;

/**
 * @author Mick Mifsud
 * @created 2018-07-24
 * @link http://tropotek.com.au/
 * @license Copyright 2018 Tropotek
 */
class Enrolled extends \Uni\TableIface
{

    /**
     * @var \Uni\Ui\Dialog\AjaxSelect
     */
    protected $findSubjectDialog = null;

    protected $ajaxDialogParams = null;

    /**
     * @param null|array $ajaxDialogParams
     * @return Enrolled
     */
    public function setAjaxDialogParams($ajaxDialogParams)
    {
        $this->ajaxDialogParams = $ajaxDialogParams;
        return $this;
    }

    /**
     * @return \$this
     * @throws \Exception
     */
    public function init()
    {
        $this->findSubjectDialog = new \Uni\Ui\Dialog\AjaxSelect('Migrate Student', \Tk\Uri::create('/ajax/subject/findFiltered.html'));
        //$params = array('ignoreUser' => '1', 'subjectId' => $this->getConfig()->getSubject()->getId());
        $params = array('subjectId' => $this->getConfig()->getSubject()->getId());
        if ($this->ajaxDialogParams)
            $params = $this->ajaxDialogParams;
        $this->findSubjectDialog->setAjaxParams($params);
        $this->findSubjectDialog->setNotes('Select the subject to migrate the student to...');
        $this->findSubjectDialog->setOnSelect(function ($data) {
            $config = \Uni\Config::getInstance();
            $dispatcher = $config->getEventDispatcher();

            // Migrate the user to the new subject
            $event = new \Tk\Event\Event();
            $event->set('subjectFromId', $config->getSubject()->getId());
            $event->set('subjectToId', $data['selectedId']);
            $event->set('userId', $data['userId']);
            $dispatcher->dispatch(\Uni\UniEvents::SUBJECT_MIGRATE_USER, $event);

            if (!$event->isPropagationStopped()) {
                /** @var \Uni\Db\User $user */
                $user = $config->getUserMapper()->find($event->get('userId'));
                if ($user) {
                    if ($config->getSubjectMapper()->hasUser($event->get('subjectFromId'), $user->getId())) {
                        $config->getSubjectMapper()->removeUser($event->get('subjectFromId'), $user->getId());
                        // delete user from the pre-enrolment list if exists
                        $config->getSubjectMapper()->removePreEnrollment($event->get('subjectFromId'), $user->email);
                    }
                    if (!$config->getSubjectMapper()->hasUser($event->get('subjectToId'), $user->getId())) {
                        $config->getSubjectMapper()->addUser($event->get('subjectToId'), $user->getId());
                    }
                }
            }
            return \Tk\Uri::create()->reset()->set('subjectId', $config->getSubject()->getId());
        });
        $this->findSubjectDialog->execute(\Uni\Config::getInstance()->getRequest());
        $template = $this->getRenderer()->getTemplate();
        $template->appendBodyTemplate($this->findSubjectDialog->show());


        $this->addCss('tk-enrolled-users');


        $this->appendCell(new \Tk\Table\Cell\Checkbox('id'));
        $actionsCell = new \Tk\Table\Cell\Actions();
        $btn = $actionsCell->addButton(\Tk\Table\Cell\ActionButton::create('Migrate', null, 'fa fa-exchange'));
        $btn->setAttr('data-target','#' . $this->findSubjectDialog->getId());
        $btn->setAttr('data-toggle','modal');
        $btn->setOnShow(function ($cell, $obj, $btn) use ($params) {
            /** @var \Tk\Table\Cell\Actions $cell */
            /** @var \Uni\Db\User $obj */
            /** @var \Tk\Table\Cell\ActionButton $btn */
            if ($btn->getTitle() != 'Migrate') return;

            $config = \Uni\Config::getInstance();
            if ($btn) {
                $params['exclude'] = $config->getSubject()->getId();
                $list = $config->getSubjectMapper()->findFiltered($params);
                if (count($list) && $obj->isStudent()) {
                    $btn->setAttr('data-user-id', $obj->getId());
                } else {
                    $btn->setVisible(false);
                }
            }

        });
        $this->appendCell($actionsCell);
        $this->appendCell(new \Tk\Table\Cell\Text('name'))->addCss('key');
        $this->appendCell(new \Tk\Table\Cell\Text('username'));
        $this->appendCell(new \Tk\Table\Cell\Email('email'));
        $this->appendCell(new \Tk\Table\Cell\Text('uid'));
        $this->appendCell(new \Tk\Table\Cell\Text('roleId'))->setOnPropertyValue(function ($cell, $obj, $value) {
            /** @var \Uni\Db\User $obj */
            if ($obj->getRole())
                $value = $obj->getRole()->getName();
            return $value;
        });
        $this->appendCell(new \Tk\Table\Cell\Boolean('active'));
        $this->appendCell(new \Tk\Table\Cell\Date('created'));

        // Actions
        $this->appendAction(\Tk\Table\Action\Delete::create('delete')->setOnDelete(function (\Tk\Table\Action\Delete $action, $obj) {
            /** @var \Uni\Db\User $obj */
            $config = \Uni\Config::getInstance();
            $subject = $config->getSubject();
            $subjectMap = $config->getSubjectMapper();
            $subjectMap->removePreEnrollment($subject->getId(), $obj->getEmail());
            $subjectMap->removeUser($subject->getId(), $obj->getId());
            return false;
        }));
        $this->appendAction(\Tk\Table\Action\Csv::create());


        return $this;
    }

    /**
     * @param array $filter
     * @param null|\Tk\Db\Tool $tool
     * @return \Tk\Db\Map\ArrayObject|\Uni\Db\UserIface[]
     * @throws \Exception
     */
    public function findList($filter = array(), $tool = null)
    {
        if (!$tool) $tool = $this->getTool('name');
        $filter = array_merge($this->getFilterValues(), $filter);
        $list = $this->getConfig()->getUserMapper()->findFiltered($filter, $tool);
        return $list;
    }

}