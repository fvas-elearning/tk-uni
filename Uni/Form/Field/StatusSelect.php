<?php
namespace Uni\Form\Field;

use Tk\Form\Exception;

/**
 * This field is a select with a checkbox.
 * The checkbox state is not saved, and is reset to the default value
 * on each page load. it is meant to be used as a trigger element.
 *
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class StatusSelect extends \Tk\Form\Field\Select
{

    /**
     * @var boolean
     */
    protected $checkboxValue = true;

    /**
     * @var string
     */
    protected $checkboxName = '';

    /**
     * @var string
     */
    protected $notesName = '';

    /**
     * @var string
     */
    protected $notesValue = '';


    /**
     * @param string $name
     * @param \Tk\Form\Field\Option\ArrayIterator|array $optionIterator
     * @internal param string $checkboxName
     */
    public function __construct($name, $optionIterator = null)
    {
        parent::__construct($name, $optionIterator);
        $this->checkboxName = $name . '_notify';
        $this->notesName = $name . '_notes';
    }

    /**
     * @return string
     */
    public function getNotesName()
    {
        return $this->notesName;
    }

    /**
     * @return string
     */
    public function getNotesValue()
    {
        return $this->notesValue;
    }

    /**
     * @return string
     */
    public function getCheckboxName()
    {
        return $this->checkboxName;
    }

    /**
     * @return bool
     */
    public function isChecked()
    {
        return $this->checkboxValue;
    }

    /**
     * @param $b
     * @return $this
     */
    public function setChecked($b)
    {
        $this->checkboxValue = $b;
        return $this;
    }

    /**
     * @param array|\ArrayObject $values
     * @return $this|\Tk\Form\Field\Select
     */
    public function load($values)
    {
        parent::load($values);

        if ($this->getForm() && $this->getForm()->isSubmitted()) {
            $this->checkboxValue = false;
            if (isset($values[$this->getCheckboxName()]) && $values[$this->getCheckboxName()] == $this->getCheckboxName()) {
                $this->checkboxValue = true;
            }
            if (isset($values[$this->getNotesName()]) && $values[$this->getNotesName()]) {
                $this->notesValue = $values[$this->getNotesName()];
            }
        }
        return $this;
    }

    /**
     * Get the element HTML
     *
     * @return string|\Dom\Template
     */
    public function show()
    {
        $t = parent::show();
        $t->appendJsUrl(\Uni\Uri::create('/vendor/ttek/tk-uni/Uni/Form/Field/jquery.statusSelect.js'));
        $js = <<<JS
jQuery(function ($) {
    //$('.form-group.tk-statusselect').statusSelect();
    $('.tk-status-select').statusSelect();
});
JS;
        $t->appendJs($js);

        $t->setAttr('checkbox', 'name', $this->getCheckboxName());
        $t->setAttr('checkbox', 'value', $this->getCheckboxName());
        $t->setAttr('checkbox', 'aria-label', $this->getCheckboxName());
        if ($this->isChecked()) {
            $t->setAttr('checkbox', 'checked', 'checked');
        }
        $t->setAttr('notes', 'name', $this->getNotesName());
        return $t;
    }

    /**
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="tk-status-select">
  <div class="input-group">
    <div class="input-group-prepend">
      <div class="input-group-text">
        <input type="checkbox" var="checkbox" title="Send Status Change Notification Email" />
      </div>
    </div>
    <select var="element" type="text" aria-label="Status" class="form-control"><option repeat="option" var="option"></option></select>
  </div>
  <div class="" style="margin-top: -2px;position:relative;">
    <textarea name="statusNotes" class="form-control" style="min-height: 80px;" placeholder="Status Update Message." var="notes"></textarea>
  </div>
</div>
HTML;
        
        return \Dom\Loader::load($xhtml);
    }
}