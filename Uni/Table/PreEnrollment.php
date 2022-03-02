<?php
namespace Uni\Table;


/**
 * @author Mick Mifsud
 * @created 2018-07-24
 * @link http://tropotek.com.au/
 * @license Copyright 2018 Tropotek
 */
class PreEnrollment extends \Uni\TableIface
{


    /**
     * @return \$this
     * @throws \Exception
     */
    public function init()
    {
        $this->addCss('tk-pending-users');

        $this->appendCell(new \Tk\Table\Cell\Checkbox('email'));
        $this->appendCell(new \Tk\Table\Cell\Text('uid'))->setLabel('UID')->addCss('key');
        $this->appendCell(new \Tk\Table\Cell\Text('username'));
        $this->appendCell(new \Tk\Table\Cell\Text('email'));
        $this->appendCell(new \Tk\Table\Cell\Boolean('enrolled'))->setOrderProperty('IF(c.subject_id IS NULL,0,1)')
            ->setLabel('X')->setHeadTitle('Is Enrolled')->addOnCellHtml(function ($cell, $obj, $html) {
            /** @var $cell \Tk\Table\Cell\Boolean */
            /** @var $obj \StdClass */
            $config = \Uni\Config::getInstance();
            if ($obj->hash)
                $cell->getRow()->setAttr('data-user-id', $obj->hash);

            if (!empty($obj->enrolled)) {
                $cell->getRow()->addCss('enrolled');
                $cell->setAttr('title', 'User Enrolled');
                $cell->setAttr('data-toggle', 'tooltip');
                $cell->setAttr('data-placement', 'left');
                $cell->addCss('text-center');
                return sprintf('<a href="#" class=""><i class="fa fa-check text-success"></i></a>');
            }
            return '';
        });

        // Actions
        $this->appendAction(\Tk\Table\Action\Delete::create('delete', 'email')->addOnDelete(function (\Tk\Table\Action\Delete $action, $obj) {
            $config = \Uni\Config::getInstance();
            $subjectMap = $config->getSubjectMapper();
            $subjectMap->removePreEnrollment($obj->subject_id, $obj->email, $obj->uid, $obj->username);
            return false;
        }));
        $this->appendAction(\Tk\Table\Action\Csv::create());

        return $this;
    }

    /**
     * @param array $filter
     * @param null|\Tk\Db\Tool $tool
     * @return array
     * @throws \Exception
     */
    public function findList($filter = array(), $tool = null)
    {
        if (!$tool) $tool = $this->getTool('IF(c.subject_id IS NULL,0,1) DESC');
        $filter = array_merge($this->getFilterValues(), $filter);
        $list = $this->getConfig()->getSubjectMapper()->findPreEnrollments($filter, $tool);
        return $list;
    }

}