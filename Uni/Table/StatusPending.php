<?php
namespace Uni\Table;

use Bs\Db\StatusMap;

/**
 * Example:
 * <code>
 *   $table = new Status::create();
 *   $table->init();
 *   $list = ObjectMap::getObjectListing();
 *   $table->setList($list);
 *   $tableTemplate = $table->show();
 *   $template->appendTemplate($tableTemplate);
 * </code>
 * 
 * @author Mick Mifsud
 * @created 2019-05-23
 * @link http://tropotek.com.au/
 * @license Copyright 2019 Tropotek
 */
class StatusPending extends \Bs\Table\StatusPending
{

    protected function getSelectList()
    {
        $filter = array();
        $filter['courseId'] = $this->getConfig()->getCourseId();
        return StatusMap::create()->findKeys($filter);
    }
}