<?php
namespace Uni\Form;

use Tk\Form\Field;
use Tk\Form\Event;
use Tk\Form;
use Tk\Str;

/**
 * Example:
 * <code>
 *   $form = new Subject::create();
 *   $form->setModel($obj);
 *   $formTemplate = $form->getRenderer()->show();
 *   $template->appendTemplate('form', $formTemplate);
 * </code>
 * 
 * @author Mick Mifsud
 * @created 2019-06-04
 * @link http://tropotek.com.au/
 * @license Copyright 2019 Tropotek
 */
class Subject extends \Uni\FormIface
{

    /**
     * @throws \Exception
     */
    public function init()
    {
        $layout = $this->getRenderer()->getLayout();
        $layout->addRow('name', 'col-md-6');
        $layout->removeRow('code', 'col-md-6');
        $layout->addRow('publish', 'col-md-6');
        $layout->removeRow('notify', 'col-md-6');

        $tab = 'Details';
        $this->appendField(new Field\Input('name'))->setTabGroup($tab)->setRequired(true);
        $this->appendField(new Field\Input('code'))->setTabGroup($tab)->setRequired(true);
        $list = $this->getConfig()->getCourseMapper()->findFiltered(array('institutionId' => $this->getConfig()->getInstitutionId()), \Tk\Db\Tool::create('name'));
        $this->appendField(new Field\Select('courseId', $list))->prependOption('-- Select --', '')
            ->setTabGroup($tab)->setRequired(true); //->setNotes('Select a course group. <a href="/staff/courseEdit.html">Click here to create a new Course.</a>');
        $this->appendField(new Field\Input('email'))->setTabGroup($tab)->setRequired(true);
        $this->appendField(new Field\DateRange('date'))->setTabGroup($tab)->setRequired(true)->setLabel('Dates')
            ->setNotes('The start and end dates of the subject. Students will not have access to subject after end date');

        $this->appendField(new Field\Checkbox('publish'))->setTabGroup($tab)
            ->setCheckboxLabel('Allow students access to this subject and its data.');
        $this->appendField(new Field\Checkbox('notify'))->setTabGroup($tab)
            ->setCheckboxLabel('Enable all email notifications for this subject.');

        $this->appendField(new Field\Textarea('description'))->setTabGroup($tab)
            ->addCss('mce-med')->setAttr('data-elfinder-path', $this->getSubject()->getDataPath().'/media');


        if ($this->getSubject()->getId())
            $this->appendField(new Event\Submit('update', array($this, 'doSubmit')));
        $this->appendField(new Event\Submit('save', array($this, 'doSubmit')));
        $this->appendField(new Event\Link('cancel', $this->getBackUrl()));

    }

    /**
     * @param \Tk\Request $request
     * @throws \Exception
     */
    public function execute($request = null)
    {
        $this->load($this->getConfig()->getSubjectMapper()->unmapForm($this->getSubject()));
        parent::execute($request);
    }

    /**
     * @param Form $form
     * @param Event\Iface $event
     * @throws \Exception
     */
    public function doSubmit($form, $event)
    {
        // Load the object with form data
        $this->getConfig()->getSubjectMapper()->mapForm($form->getValues(), $this->getSubject());

        // Do Custom Validations
        $form->addFieldErrors($this->getSubject()->validate());
        if ($form->hasErrors()) {
            return;
        }
        
        $isNew = !(bool)$this->getSubject()->getId();
        $this->getSubject()->setDescription(Str::stripStyles($this->getSubject()->getDescription()));
        $this->getSubject()->save();

        // If this is a staff member add them to the course
        if ($this->getAuthUser()->isStaff()) {
            $this->getConfig()->getCourseMapper()->addUser($this->getSubject()->getCourseId(), $this->getAuthUser()->getId());
        }

        \Tk\Alert::addSuccess('Record saved!');
        $event->setRedirect($this->getBackUrl());
        if ($form->getTriggeredEvent()->getName() == 'save') {
            $url = \Tk\Uri::create()->set('subjectId', $this->getSubject()->getId());
            if ($this->getConfig()->isSubjectUrl())
                $url = \Uni\Uri::create();
            $event->setRedirect($url);
        }
    }

    /**
     * @return \Tk\Db\ModelInterface|\Uni\Db\Subject
     */
    public function getSubject()
    {
        return $this->getModel();
    }

    /**
     * @param \Uni\Db\Subject $subject
     * @return $this
     */
    public function setSubject($subject)
    {
        return $this->setModel($subject);
    }
    
}