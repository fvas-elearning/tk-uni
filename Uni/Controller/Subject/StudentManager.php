<?php
namespace Uni\Controller\Subject;

use Dom\Template;
use Tk\Form\Field;
use Tk\Log;
use Tk\Request;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 * @deprecated I think, not sure if it is used anywhere (24-01-2022)
 */
class StudentManager extends \Uni\Controller\AdminIface
{

    /**
     * @var \Tk\Table
     */
    protected $table = null;


    /**
     * StudentManager constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        Log::alert('This is a deprecated class I think, remove this message and note where is it used in the code if not.');
        $this->setPageTitle('My Subjects');
        $this->getConfig()->unsetSubject();
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {

        $this->table = \Uni\Config::getInstance()->createTable('student-manager');
        $this->table->setRenderer(\Uni\Config::getInstance()->createTableRenderer($this->table));

        $this->table->appendCell(new \Tk\Table\Cell\Text('name'))->addCss('key')->setUrl(\Uni\Uri::createSubjectUrl('/index.html'))
            ->addOnPropertyValue(function ($cell, $obj, $value) {
                /** @var \Tk\Table\Cell\Text $cell */
                $cell->setUrl(\Uni\Uri::createSubjectUrl('/index.html', $obj));
                return $value;
            });
        $this->table->appendCell(new \Tk\Table\Cell\Text('code'));
        $this->table->appendCell(new \Tk\Table\Cell\Email('email'));
        $this->table->appendCell(new \Tk\Table\Cell\Date('dateStart'));
        $this->table->appendCell(new \Tk\Table\Cell\Date('dateEnd'));

        // Filters
        $this->table->appendFilter(new Field\Input('keywords'))->setLabel('')->setAttr('placeholder', 'Keywords');

        // Actions
        $this->table->appendAction(\Tk\Table\Action\Csv::create());

        $filter = $this->table->getFilterValues();
        $filter['institutionId'] = $this->getConfig()->getInstitutionId();
        if (!empty($filter['userId'])) {
            $filter['userId'] = $this->getAuthUser()->id;
        }
        
        $users = $this->getConfig()->getSubjectMapper()->findFiltered($filter, $this->table->getTool());
        $this->table->setList($users);

    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        $template->appendTemplate('table', $this->table->getRenderer()->show());

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

  <div class="tk-panel" data-panel-title="Subject" data-panel-icon="fa fa-graduation-cap" var="table"></div>
    
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }


}