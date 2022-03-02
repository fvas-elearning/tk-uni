<?php
namespace Uni\Form;

use Tk\Form\Field;
use Tk\Form\Event;
use Tk\Form;
use Tk\Str;
use Uni\Db\Permission;

/**
 * Example:
 * <code>
 *   $form = new Course::create();
 *   $form->setModel($obj);
 *   $formTemplate = $form->getRenderer()->show();
 *   $template->appendTemplate('form', $formTemplate);
 * </code>
 * 
 * @author Mick Mifsud
 * @created 2019-12-05
 * @link http://tropotek.com.au/
 * @license Copyright 2019 Tropotek
 */
class Course extends \Uni\FormIface
{

    /**
     * @throws \Exception
     */
    public function init()
    {
        $layout = $this->getRenderer()->getLayout();

        $layout->removeRow('code', 'col');

        $tab = 'Details';
        $this->appendField(new Field\Input('name'))->setTabGroup($tab);
        $this->appendField(new Field\Input('code'))->setTabGroup($tab);
        $filter = array('institutionId' => $this->getConfig()->getInstitutionId(), 'permission' => Permission::IS_COORDINATOR);
        $list = $this->getConfig()->getUserMapper()->findFiltered($filter, \Tk\Db\Tool::create('name_first'));
        $this->appendField(new Field\Select('coordinatorId', $list))->setTabGroup($tab)->prependOption('-- Select --', '');
        $this->appendField(new Field\Input('email'))->setTabGroup($tab);
        $this->appendField(new Field\Textarea('emailSignature'))->setTabGroup($tab)
            ->addCss('mce-min')->setAttr('data-elfinder-path', $this->getCourse()->getDataPath().'/media');
        $tab = 'Description';
        $this->appendField(new Field\Textarea('description'))->setTabGroup($tab)
            ->addCss('mce')->setAttr('data-elfinder-path', $this->getCourse()->getDataPath().'/media');



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
        $this->load($this->getConfig()->getCourseMapper()->unmapForm($this->getCourse()));
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
        $this->getConfig()->getCourseMapper()->mapForm($form->getValues(), $this->getCourse());

        // Do Custom Validations

        $form->addFieldErrors($this->getCourse()->validate());
        if ($form->hasErrors()) {
            return;
        }
        
        $isNew = (bool)$this->getCourse()->getId();
        $this->getCourse()->setDescription(Str::stripStyles($this->getCourse()->getDescription()));
        $this->getCourse()->save();

        // Do Custom data saving

        \Tk\Alert::addSuccess('Record saved!');
        $event->setRedirect($this->getBackUrl());
        if ($form->getTriggeredEvent()->getName() == 'save') {
            $event->setRedirect(\Tk\Uri::create()->set('courseId', $this->getCourse()->getId()));
        }
    }

    /**
     * @return \Tk\Db\ModelInterface|\Uni\Db\Course
     */
    public function getCourse()
    {
        return $this->getModel();
    }

    /**
     * @param \Uni\Db\Course $course
     * @return $this
     */
    public function setCourse($course)
    {
        return $this->setModel($course);
    }
    
}