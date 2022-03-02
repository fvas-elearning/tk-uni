<?php
namespace Uni\Form;

use Tk\Db\Tool;
use Tk\Form\Field;
use Tk\Form\Event;
use Tk\Form;
use Uni\Db\Permission;

/**
 * Example:
 * <code>
 *   $form = new User::create();
 *   $form->setModel($obj);
 *   $formTemplate = $form->getRenderer()->show();
 *   $template->appendTemplate('form', $formTemplate);
 * </code>
 *
 * @author Mick Mifsud
 * @created 2018-11-19
 * @link http://tropotek.com.au/
 * @license Copyright 2018 Tropotek
 */
class User extends \Bs\Form\User
{

    protected $isNew = false;


    /**
     * @throws \Exception
     */
    public function init()
    {
        $this->isNew = ($this->getUser()->getId() == 0);
        parent::init();
//        $this->getField('update')->appendCallback(array($this, 'doSubjectUpdate'));
//        $this->getField('save')->appendCallback(array($this, 'doSubjectUpdate'));

        $tab = 'Details';
        if (!$this->getConfig()->canChangePassword()) {
            $this->removeField('newPassword');
            $this->removeField('confPassword');
        }

        $f = $this->appendField(new Field\Input('uid'), 'username')->setLabel('UID')->setTabGroup($tab)
            ->setNotes('The student or staff number assigned by the institution (if Applicable).');
        if ($this->getUser()->getId()) {
            $f->addCss('tk-input-lock');
        }

        if ($this->getUser()->getId() == $this->getConfig()->getAuthUser()->getId()) {
            $this->removeField('active');
        }
        // TODO: This needs to be made into a searchable system as once there are many subjects it will be unmanageable
        // TODO: This needs to be replaced with a dialog box and search feature so it works for a large number of subjects
        // TODO: done it twice so it is becoming something that needs to be looked at soon..... ;-)
        if ($this->getUser()->getId()) {
            if ($this->getUser()->isStaff()) {
                $tab = 'Course';
                $list = \Tk\Form\Field\Option\ArrayObjectIterator::create($this->getConfig()->getCourseMapper()
                    ->findFiltered(array('institutionId' => $this->getConfig()->getInstitutionId()), Tool::create('created DESC')));
                if ($list->count()) {
//                    $this->appendField(new Field\Select('selCourse[]', $list), 'active')->setLabel('Course Selection')
//                        ->setNotes('Select the courses this staff member is allowed to access.')
//                        ->setTabGroup($tab)->addCss('tk-dualSelect')->setAttr('data-title', 'Courses');
                    $this->appendField(new Field\CheckboxGroup('selCourse[]', $list), 'active')->setLabel('Course Selection')
                        ->setNotes('Select the courses this staff member is allowed to access.')
                        ->setTabGroup($tab);
                    $arr = $this->getConfig()->getCourseMapper()->findByUserId($this->getUser()->getId())->toArray('id');
                    $this->setFieldValue('selCourse', $arr);
                }
            } else if ($this->getUser()->isStudent()) {
                $tab = 'Subject';
                $list = \Tk\Form\Field\Option\ArrayObjectIterator::create($this->getConfig()->getSubjectMapper()
                    ->findFiltered(array('institutionId' => $this->getConfig()->getInstitutionId()), Tool::create('created DESC')));
                if ($list->count()) {
//                    $this->appendField(new Field\Select('selSubject[]', $list), 'active')->setLabel('Subject Selection')
//                        ->setNotes('This list only shows active and enrolled subjects. Use the enrollment form in the edit subject page if your subject is not visible.')
//                        ->setTabGroup($tab)->addCss('tk-dualSelect')->setAttr('data-title', 'Subjects');
                    $this->appendField(new Field\CheckboxGroup('selSubject[]', $list), 'active')->setLabel('Subject Selection')
                        ->setNotes('This list only shows active and enrolled subjects. Use the enrollment form in the edit subject page if your subject is not visible.')
                        ->setTabGroup($tab);
                    $arr = $this->getConfig()->getSubjectMapper()->findByUserId($this->getUser()->getId())->toArray('id');
                    $this->setFieldValue('selSubject', $arr);
                }
            }
        }



    }

    /**
     * @param Form $form
     * @param Event\Iface $event
     * @throws \Exception
     */
    //public function doSubjectUpdate($form, $event)
    public function doSubmit($form, $event)
    {
        parent::doSubmit($form, $event);

        if ($form->hasErrors()) return;

        if ($form->getField('selCourse')) {
            // Add user to Courses
            $selected = $form->getFieldValue('selCourse');
            if ($this->getUser()->getId() && is_array($selected)) {
                $this->getConfig()->getCourseMapper()->removeUser(null, $this->getUser()->getId());
                foreach ($selected as $courseId) {
                    $this->getConfig()->getCourseMapper()->addUser($courseId, $this->getUser()->getId());
                }
            }
        }

        if ($form->getField('selSubject')) {
            // Add user to subjects
            $selected = $form->getFieldValue('selSubject');

            //if ($this->getUser()->getId() && is_array($selected)) {
            if ($this->getUser()->getId()) {
                // Get existing subjects and remove pre-enrollment
                $enrolled = $this->getConfig()->getSubjectMapper()->findByUserId($this->getUser()->getId());
                foreach ($enrolled as $subject) {
                    if (is_array($selected) && in_array($subject->getId(), $selected)) continue;
                    $this->getConfig()->getSubjectMapper()->removePreEnrollment($subject->getId(), $this->getUser()->getEmail(), $this->getUser()->getUid(), $this->getUser()->getUsername());
                }

                $this->getConfig()->getSubjectMapper()->removeUser(null, $this->getUser()->getId());
                if (is_array($selected)) {
                    foreach ($selected as $subjectId) {
                        $this->getConfig()->getSubjectMapper()->addUser($subjectId, $this->getUser()->getId());
                    }
                }
            }
        }

        $this->getUser()->save();

        // Add the user to the subject/course
        if ($this->isNew && $this->getConfig()->getSubjectId()) {
            if ($this->getUser()->isStudent()) {
                $this->getConfig()->getSubjectMapper()->addUser($this->getConfig()->getSubjectId(), $this->getUser()->getVolatileId());
            } else if ($this->getUser()->isStaff()) {
                $this->getConfig()->getCourseMapper()->addUser($this->getConfig()->getCourseId(), $this->getUser()->getVolatileId());
            }
        }
    }

    /**
     * @return \Tk\Db\ModelInterface|\Uni\Db\User
     */
    public function getUser()
    {
        return $this->getModel();
    }

    /**
     * @param \Uni\Db\User $user
     * @return $this
     */
    public function setUser($user)
    {
        return $this->setModel($user);
    }


}
