<?php
namespace Uni\Table;



use Uni\Db\Permission;

/**
 * @author Mick Mifsud
 * @created 2018-07-24
 * @link http://tropotek.com.au/
 * @license Copyright 2018 Tropotek
 */
class User extends \Bs\Table\User
{

    /**
     * @var \Tk\Ui\Dialog\AjaxSelect
     */
    protected $findSubjectDialog = null;

    /**
     * @var null|array
     */
    protected $ajaxDialogParams = null;

    /**
     * @param null|array $ajaxDialogParams
     * @return User
     */
    public function setAjaxDialogParams($ajaxDialogParams)
    {
        $this->ajaxDialogParams = $ajaxDialogParams;
        return $this;
    }

    public function init()
    {
        parent::init();
        if (!$this->getAuthUser()->isAdmin() && !$this->getAuthUser()->isClient() && !$this->getAuthUser()->hasPermission(Permission::MANAGE_STAFF))
            $this->removeAction('delete');

        if ($this->getTargetType() == \Uni\Db\User::TYPE_STUDENT) {
            if ($this->getAuthUser()->isStaff()) {
                $this->appendCell(new \Tk\Table\Cell\Text('barcode'), 'uid')
                    ->addOnPropertyValue(function (\Tk\Table\Cell\Iface $cell, $obj, $value) {
                        /** @var $obj \Uni\Db\User */
                        $value = '';
                        if ($obj->getData()->has('barcode')) {
                            $value .= $obj->getData()->get('barcode');
                        }
                        return $value;
                    });

            }
            $this->appendCell(\Tk\Table\Cell\Text::create('mentor'))->setOrderProperty('')
                ->addOnCellHtml(function (\Tk\Table\Cell\Iface $cell, $obj, $html) {
                    /** @var $obj \Uni\Db\User */
                    $html = '';
                    $idList = $this->getConfig()->getUserMapper()->findMentor($obj->getId());
                    if (count($idList)) {
                        $mentors = $this->getConfig()->getUserMapper()->findFiltered(array(
                            'id' => $idList
                        ), \Tk\Db\Tool::create('name_first', 5));
                        foreach ($mentors as $mentor) {
                            $html .= sprintf('<small>%s</small><br/>', htmlspecialchars($mentor->getName()));
                        }
                        $html = '<span>' . preg_replace('/<br\\s*?\\/?>\\s*$/', '', $html) . '</span>';
                    }
                    return $html;
                });

            $this->appendCell(\Tk\Table\Cell\Text::create('subjects'))->setOrderProperty('')
                ->setLabel('Subject Entries')
                ->addOnCellHtml(function (\Tk\Table\Cell\Iface $cell, $obj, $html) {
                    /** @var $obj \Uni\Db\User */
                    $subjectList = $obj->getConfig()->getSubjectMapper()->findFiltered(array(
                        'studentId' => $obj->getId(),
                        'institutionId' => $obj->getInstitutionId()
                    ), \Tk\Db\Tool::create('dateStart DESC'));
                    $html = array();
                    foreach ($subjectList as $subject) {
                        $html[] = sprintf('<small>%s</small><br/>', htmlspecialchars($subject->getName())
                        );
                    }
                    $html = '<span>'. implode("<br/>\n", $html) . '</span>';
                    return $html;
                });

        } else if ($this->getTargetType() == \Uni\Db\User::TYPE_STAFF) {
            $list = array(
                'Coordinator' => Permission::IS_COORDINATOR,
                'Lecturer' => Permission::IS_LECTURER,
                'Mentor' => Permission::IS_MENTOR
            );
            $this->appendFilter(new \Tk\Form\Field\CheckboxSelect('permission', $list));

            $this->appendCell(new \Tk\Table\Cell\Text('role'), 'uid')
                ->addOnPropertyValue(function (\Tk\Table\Cell\Iface $cell, $obj, $value) {
                    /** @var $obj \Uni\Db\User */
                    $value ='';
                    if ($obj->isCoordinator()) {
                        $value .= 'Coordinator, ';
                    } else if ($obj->isLecturer()) {
                        $value .= 'Lecturer, ';
                    }
                    if ($obj->isMentor()) {
                        $value .= 'Mentor, ';
                    }
                    if (!$value) {
                        $value = 'Staff';
                    }
                    return trim($value, ', ');
                });
        }

        $arr = array('modified', 'created');
        if ($this->getAuthUser()->isStaff()) {
            $arr[] = 'username';
            $arr[] = 'barcode';
            $arr[] = 'active';
            $arr[] = 'mentor';
            $arr[] = 'subjects';
        }
        $this->findAction('columns')->setUnselected($arr);


        // For subject urls only
        if (!$this->getConfig()->isSubjectUrl()) return $this;

        $this->findSubjectDialog = new \Tk\Ui\Dialog\AjaxSelect('Migrate Student', \Tk\Uri::create('/ajax/subject/findFiltered.html'));
        //$params = array('ignoreUser' => '1', 'subjectId' => $this->getConfig()->getSubject()->getId());
        $params = array('institutionId'=> $this->getConfig()->getInstitutionId(), 'subjectId' => $this->getConfig()->getSubject()->getId());
        if ($this->ajaxDialogParams)
            $params = $this->ajaxDialogParams;
        $this->findSubjectDialog->setAjaxParams($params);
        $this->findSubjectDialog->setNotes('Select the subject to migrate the student to...');
        $this->findSubjectDialog->addOnSelect(function ($dialog) {
            $config = \Uni\Config::getInstance();
            $dispatcher = $config->getEventDispatcher();
            $data = $config->getRequest()->all();

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
                        $config->getSubjectMapper()->removePreEnrollment($event->get('subjectFromId'), $user->getEmail());
                    }
                    if (!$config->getSubjectMapper()->hasUser($event->get('subjectToId'), $user->getId())) {
                        $config->getSubjectMapper()->addUser($event->get('subjectToId'), $user->getId());
                    }
                    /** @var \Uni\Db\Subject $subjectTo */
                    $subjectTo = $config->getSubjectMapper()->find($event->get('subjectToId'));
                    $sn = '';
                    if ($subjectTo)
                        $sn = ' To ' . $subjectTo->getName();
                    \Tk\Alert::addSuccess($user->getName() . ' Successfully Migrated'.$sn);
                }
            }
            return \Tk\Uri::create()->reset()->set('subjectId', $config->getSubject()->getId());
        });
        $this->findSubjectDialog->execute();
        $template = $this->getRenderer()->getTemplate();
        $template->appendBodyTemplate($this->findSubjectDialog->show());

        $btn = $this->getActionCell()->addButton(\Tk\Table\Cell\ActionButton::create('Migrate', null, 'fa fa-exchange'));
        $btn->setAttr('data-target','#' . $this->findSubjectDialog->getId());
        $btn->setAttr('data-toggle','modal');
        $btn->addOnShow(function ($cell, $obj, $btn) use ($params) {
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
        $eUrl = \Uni\Uri::createSubjectUrl('/')->toString();
        $html = <<<HTML
<p><small><em>NOTE: To remove users, un-enroll the user from the subject from the <a href="$eUrl">Enrollment Manager</a>.</em></small></p>
HTML;
        $this->getRenderer()->getTemplate()->prependHtml('table', $html);

        return $this;
    }

}
