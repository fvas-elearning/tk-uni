<?php
namespace Uni\Db;

use Tk\Date;
use Tk\Db\Tool;
use Tk\Db\Map\ArrayObject;
use Tk\DataMap\Db;
use Tk\DataMap\Form;
use Bs\Db\Mapper;
use Tk\Db\Filter;

/**
 * @author Mick Mifsud
 * @created 2019-12-05
 * @link http://tropotek.com.au/
 * @license Copyright 2019 Tropotek
 */
class CourseMap extends Mapper
{

    /**
     * @return \Tk\DataMap\DataMap
     */
    public function getDbMap()
    {
        if (!$this->dbMap) {
            $this->dbMap = new \Tk\DataMap\DataMap();
            $this->dbMap->addPropertyMap(new Db\Integer('id'), 'key');
            $this->dbMap->addPropertyMap(new Db\Integer('institutionId', 'institution_id'));
            $this->dbMap->addPropertyMap(new Db\Integer('coordinatorId', 'coordinator_id'));
            $this->dbMap->addPropertyMap(new Db\Text('code'));
            $this->dbMap->addPropertyMap(new Db\Text('name'));
            $this->dbMap->addPropertyMap(new Db\Text('email'));
            $this->dbMap->addPropertyMap(new Db\Text('emailSignature', 'email_signature'));
            $this->dbMap->addPropertyMap(new Db\Text('description'));
            $this->dbMap->addPropertyMap(new Db\Date('modified'));
            $this->dbMap->addPropertyMap(new Db\Date('created'));

        }
        return $this->dbMap;
    }

    /**
     * @return \Tk\DataMap\DataMap
     */
    public function getFormMap()
    {
        if (!$this->formMap) {
            $this->formMap = new \Tk\DataMap\DataMap();
            $this->formMap->addPropertyMap(new Form\Integer('id'), 'key');
            $this->formMap->addPropertyMap(new Form\Integer('institutionId'));
            $this->formMap->addPropertyMap(new Form\Integer('coordinatorId'));
            $this->formMap->addPropertyMap(new Form\Text('code'));
            $this->formMap->addPropertyMap(new Form\Text('name'));
            $this->formMap->addPropertyMap(new Form\Text('email'));
            $this->formMap->addPropertyMap(new Form\Text('emailSignature'));
            $this->formMap->addPropertyMap(new Form\Text('description'));
            $this->formMap->addPropertyMap(new Form\Date('modified'));
            $this->formMap->addPropertyMap(new Form\Date('created'));

        }
        return $this->formMap;
    }


    /**
     * @param string $code
     * @param int $institutionId
     * @return Course|\Tk\Db\ModelInterface
     * @throws \Exception
     */
    public function findByCode($code, $institutionId)
    {
        return $this->findFiltered(array('code' => $code, 'institutionId' => $institutionId))->current();
    }

    /**
     * @param int $userId
     * @param int $institutionId
     * @param Tool $tool
     * @return ArrayObject|Course[]
     * @throws \Exception
     */
    public function findByUserId($userId, $institutionId = 0, $tool = null)
    {
        $from = sprintf('%s a, course_has_user b', $this->getDb()->quoteParameter($this->getTable()));
        $where = sprintf('a.id = b.course_id AND b.user_id = %d', (int)$userId);
        if ($institutionId) {
            $where .= sprintf(' AND a.institution_id = %d', (int)$institutionId);
        }
        $arr = $this->selectFrom($from, $where, $tool);
        return $arr;
    }

    /**
     * @param array|Filter $filter
     * @param Tool $tool
     * @return ArrayObject|Course[]
     * @throws \Exception
     */
    public function findFiltered($filter, $tool = null)
    {
        return $this->selectFromFilter($this->makeQuery(\Tk\Db\Filter::create($filter)), $tool);
    }

    /**
     * @param Filter $filter
     * @return Filter
     */
    public function makeQuery(Filter $filter)
    {
        $filter->appendFrom('%s a', $this->quoteParameter($this->getTable()));

        if (!empty($filter['keywords'])) {
            $kw = '%' . $this->escapeString($filter['keywords']) . '%';
            $w = '';
            //$w .= sprintf('a.name LIKE %s OR ', $this->quote($kw));
            if (is_numeric($filter['keywords'])) {
                $id = (int)$filter['keywords'];
                $w .= sprintf('a.id = %d OR ', $id);
            }
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['id'])) {
            $w = $this->makeMultiQuery($filter['id'], 'a.id');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['institutionId'])) {
            $filter->appendWhere('a.institution_id = %s AND ', (int)$filter['institutionId']);
        }
        if (!empty($filter['coordinatorId'])) {
            $filter->appendWhere('a.coordinator_id = %s AND ', (int)$filter['coordinatorId']);
        }
        if (!empty($filter['code'])) {
            $filter->appendWhere('a.code = %s AND ', $this->quote($filter['code']));
        }
        if (!empty($filter['name'])) {
            $filter->appendWhere('a.name = %s AND ', $this->quote($filter['name']));
        }
        if (!empty($filter['email'])) {
            $filter->appendWhere('a.email = %s AND ', $this->quote($filter['email']));
        }
        if (!empty($filter['subjectId']) || !empty($filter['active'])) {
            $filter->appendFrom(' ,%s c', $this->quoteTable('subject'));
            $filter->appendWhere('a.id = c.course_id AND ');

            if (!empty($filter['subjectId'])) {
                $filter->appendWhere('c.id = %s AND ', (int)$filter['subjectId']);
            }
            if (!empty($filter['active'])) {        // Only with active subjects???? (see SubjectMap)
                $active = null;
                if (isset($filter['active']) && $filter['active'] !== null && $filter['active'] !== '') $active = (int)$filter['active'];
                $now = Date::create()->format(Date::FORMAT_ISO_DATETIME);
                if ($active) {
                    $filter->appendWhere('c.date_start <= %s AND c.date_end >= %s AND ', $this->quote($now), $this->quote($now));
                } else {
                    $filter->appendWhere('c.date_start > %s OR c.date_end < %s AND ', $this->quote($now), $this->quote($now));
                }
            }
        }
        if (!empty($filter['userId'])) {
            $filter['staffId'] = $filter['userId'];
        }
        if (!empty($filter['staffId'])) {
            $filter->appendFrom(' ,%s b', $this->quoteTable('course_has_user'));
            $filter->appendWhere('a.id = b.course_id AND b.user_id = %s AND ', (int)$filter['staffId']);
        }

        if (!empty($filter['exclude'])) {
            $w = $this->makeMultiQuery($filter['exclude'], 'a.id', 'AND', '!=');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        return $filter;
    }


    // course_has_user holds the staff users who instruct the course

    /**
     * @param int $courseId
     * @return array
     * @throws \Exception
     */
    public function findUsers($courseId)
    {
        $stm = $this->getDb()->prepare('SELECT user_id as \'id\' FROM course_has_user WHERE course_id = ?');
        $stm->execute(array($courseId));
        return $stm->fetchAll(\PDO::FETCH_COLUMN, 0);
    }

    /**
     * @param int $courseId
     * @param int $userId
     * @return boolean
     * @throws \Exception
     */
    public function hasUser($courseId, $userId)
    {
        $stm = $this->getDb()->prepare('SELECT * FROM course_has_user WHERE course_id = ? AND user_id = ?');
        $stm->execute(array($courseId, $userId));
        return ($stm->rowCount() > 0);
    }

    /**
     * @param int $courseId
     * @param int $userId
     * @throws \Exception
     */
    public function addUser($courseId, $userId)
    {
        if ($this->hasUser($courseId, $userId)) return;
        $stm = $this->getDb()->prepare('INSERT INTO course_has_user (course_id, user_id)  VALUES (?, ?)');
        $stm->execute(array($courseId, $userId));
    }

    /**
     * depending on the combination of parameters:
     *  o remove a user from a course
     *  o remove all users from a course
     *  o remove all courses from a user
     *
     * @param int $courseId
     * @param int $userId
     * @throws \Exception
     */
    public function removeUser($courseId = null, $userId = null)
    {
        if ($courseId && $userId) {
            $stm = $this->getDb()->prepare('DELETE FROM course_has_user WHERE course_id = ? AND user_id = ?');
            $stm->execute(array($courseId, $userId));
        } else if(!$courseId && $userId) {
            $stm = $this->getDb()->prepare('DELETE FROM course_has_user WHERE user_id = ?');
            $stm->execute(array($userId));
        } else if ($courseId && !$userId) {
            $stm = $this->getDb()->prepare('DELETE FROM course_has_user WHERE course_id = ?');
            $stm->execute(array($courseId));
        }
    }
}
