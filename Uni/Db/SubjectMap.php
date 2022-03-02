<?php
namespace Uni\Db;


use Tk\Date;
use Tk\Db\Filter;
use Tk\Db\Tool;
use Tk\Db\Map\ArrayObject;
use Tk\DataMap\Db;
use Tk\DataMap\Form;
use \Bs\Db\Mapper;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class SubjectMap extends Mapper
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
            $this->dbMap->addPropertyMap(new Db\Integer('courseId', 'course_id'));
            $this->dbMap->addPropertyMap(new Db\Text('name'));
            $this->dbMap->addPropertyMap(new Db\Text('code'));
            $this->dbMap->addPropertyMap(new Db\Text('email'));
            $this->dbMap->addPropertyMap(new Db\Text('description'));
            $this->dbMap->addPropertyMap(new Db\Date('dateStart', 'date_start'));
            $this->dbMap->addPropertyMap(new Db\Date('dateEnd', 'date_end'));
            $this->dbMap->addPropertyMap(new Db\Boolean('notify'));
            $this->dbMap->addPropertyMap(new Db\Boolean('publish'));
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
            $this->formMap->addPropertyMap(new Form\Integer('courseId'));
            $this->formMap->addPropertyMap(new Form\Text('name'));
            $this->formMap->addPropertyMap(new Form\Text('code'));
            $this->formMap->addPropertyMap(new Form\Text('email'));
            $this->formMap->addPropertyMap(new Form\Text('description'));
            $this->formMap->addPropertyMap(new Form\Date('dateStart'));
            $this->formMap->addPropertyMap(new Form\Date('dateEnd'));
            $this->formMap->addPropertyMap(new Form\Boolean('notify'));
            $this->formMap->addPropertyMap(new Form\Boolean('publish'));
        }
        return $this->formMap;
    }


    /**
     * @param string $code
     * @param int $institutionId
     * @return Subject|\Tk\Db\ModelInterface
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
     * @return ArrayObject|Subject[]
     * @throws \Exception
     */
    public function findByUserId($userId, $institutionId = 0, $tool = null)
    {
        $from = sprintf('%s a, subject_has_user b', $this->getDb()->quoteParameter($this->getTable()));
        $where = sprintf('a.id = b.subject_id AND b.user_id = %d', (int)$userId);
        if ($institutionId) {
            $where .= sprintf(' AND a.institution_id = %d', (int)$institutionId);
        }
        $arr = $this->selectFrom($from, $where, $tool);
        return $arr;
    }

    /**
     * @param int $institutionId
     * @param Tool $tool
     * @return ArrayObject|Subject[]
     * @throws \Exception
     */
    public function findActive($institutionId = 0, $tool = null)
    {
        $now = \Tk\Date::create()->format(\Tk\Date::FORMAT_ISO_DATE);
        // `now >= start && now <= finish`          =>      active
        $where = sprintf('%s >= date_start AND %s <= date_end ', $this->getDb()->quote($now), $this->getDb()->quote($now));
        if ($institutionId) {
            $where .= sprintf(' AND a.institution_id = %d', (int)$institutionId);
        }
        return $this->select($where, $tool);
    }

    /**
     * @param array|Filter $filter
     * @param Tool $tool
     * @return ArrayObject|Subject[]|\Uni\Db\Subject[]
     * @throws \Exception
     */
    public function findFiltered($filter, $tool = null)
    {
        $list = $this->selectFromFilter($this->makeQuery(\Tk\Db\Filter::create($filter)), $tool);
        return $list;
    }

    /**
     * @param \Tk\Db\Filter $filter
     * @return Filter
     */
    public function makeQuery(\Tk\Db\Filter $filter)
    {
        $filter->appendFrom('%s a', $this->quoteParameter($this->getTable()));

        if (!empty($filter['keywords'])) {
            $kw = '%' . $this->getDb()->escapeString($filter['keywords']) . '%';
            $w = '';
            $w .= sprintf('a.name LIKE %s OR ', $this->getDb()->quote($kw));
            $w .= sprintf('a.code LIKE %s OR ', $this->getDb()->quote($kw));
            $w .= sprintf('a.email LIKE %s OR ', $this->getDb()->quote($kw));
            $w .= sprintf('a.description LIKE %s OR ', $this->getDb()->quote($kw));
            if (is_numeric($filter['keywords'])) {
                $id = (int)$filter['keywords'];
                $w .= sprintf('a.id = %d OR ', $id);
            }
            if ($w) {
                $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
            }
        }

        if (!empty($filter['id'])) {
            $w = $this->makeMultiQuery($filter['id'], 'a.id');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['code'])) {
            $filter->appendWhere('a.code = %s AND ', $this->getDb()->quote($filter['code']));
        }

        if (!empty($filter['email'])) {
            $filter->appendWhere('a.email = %s AND ', $this->getDb()->quote($filter['email']));
        }

        if (!empty($filter['institutionId'])) {
            $filter->appendWhere('a.institution_id = %s AND ', (int)$filter['institutionId']);
        }

        if (!empty($filter['courseId'])) {
            $w = $this->makeMultiQuery($filter['courseId'], 'a.course_id');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['excludeCourseId'])) {
            $filter->appendWhere('a.course_id != %s AND ', (int)$filter['excludeCourseId']);
        }

        if (!empty($filter['userId'])) {
            $filter['studentId'] = $filter['userId'];
        }
        if (!empty($filter['studentId'])) {
            $filter->appendFrom(', subject_has_user k');
            $filter->appendWhere('a.id = k.subject_id AND ');
            $w = $this->makeMultiQuery($filter['studentId'], 'k.user_id');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['staffId'])) {
            $filter->appendFrom(', course_has_user l');
            $filter->appendWhere('a.course_id = l.course_id AND ');
            $w = $this->makeMultiQuery($filter['staffId'], 'l.user_id');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['mentorId'])) {
            $filter->appendFrom(', subject_has_user m, user_mentor n');
            $filter->appendWhere('a.id = m.subject_id AND m.user_id = n.user_id AND ');
            $w = $this->makeMultiQuery($filter['mentorId'], 'n.mentor_id');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (isset($filter['publish']) && $filter['publish'] !== '' && $filter['publish'] !== null) {
            $filter->appendWhere('a.publish = %s AND ', (int)$filter['publish']);
        }

        $dates = array('dateStart', 'dateEnd');
        foreach ($dates as $name) {
            if (!empty($filter[$name]) && !$filter[$name] instanceof \DateTime) {
                $filter[$name] = \Tk\Date::createFormDate($filter[$name]);
            }
        }

        if (!empty($filter['dateStart']) && !empty($filter['dateEnd'])) {     // Contains
            /** @var \DateTime $dateStart */
            $dateStart = Date::floor($filter['dateStart']);
            /** @var \DateTime $dateEnd */
            $dateEnd = Date::floor($filter['dateEnd']);

            $filter->appendWhere('((a.date_start >= %s AND ', $this->quote($dateStart->format(Date::FORMAT_ISO_DATETIME)) );
            $filter->appendWhere('a.date_start <= %s) OR ', $this->quote($dateEnd->format(Date::FORMAT_ISO_DATETIME)) );

            $filter->appendWhere('(a.date_end <= %s AND ', $this->quote($dateStart->format(Date::FORMAT_ISO_DATETIME)) );
            $filter->appendWhere('a.date_end >= %s)) AND ', $this->quote($dateEnd->format(Date::FORMAT_ISO_DATETIME)) );
        } else if (!empty($filter['dateStart'])) {
            /** @var \DateTime $date */
            $date = Date::floor($filter['dateStart']);
            $filter->appendWhere('a.date_start >= %s AND ', $this->quote($date->format(Date::FORMAT_ISO_DATETIME)) );
        } else if (!empty($filter['dateEnd'])) {
            /** @var \DateTime $date */
            $date = Date::floor($filter['dateEnd']);
            $filter->appendWhere('a.date_end <= %s AND ', $this->quote($date->format(Date::FORMAT_ISO_DATETIME)) );
        }

        $active = null;
        if (isset($filter['active']) && $filter['active'] !== null && $filter['active'] !== '') $active = (int)$filter['active'];
        if (isset($filter['current']) && $filter['current'] !== null && $filter['current'] !== '') $active = (int)$filter['current'];
        if ($active !== null) {
            $now = Date::create()->format(Date::FORMAT_ISO_DATETIME);
            if ($active) {
                $filter->appendWhere('a.date_start <= %s AND a.date_end >= %s AND ', $this->quote($now), $this->quote($now));
            } else {
                $filter->appendWhere('a.date_start > %s OR a.date_end < %s AND ', $this->quote($now), $this->quote($now));
            }
        }

//        if (isset($filter['active']) && $filter['active'] !== null && $filter['active'] !== '') {
//            $now = \Tk\Date::create()->format(\Tk\Date::FORMAT_ISO_DATETIME);
//            $filter->appendWhere('a.date_start <= %s AND a.date_end >= %s AND ', $this->quote($now), $this->quote($now));
//        }

        if (!empty($filter['exclude'])) {
            $w = $this->makeMultiQuery($filter['exclude'], 'a.id', 'AND', '!=');
            if ($w) {
                $filter->appendWhere('(%s) AND ', $w);
            }
        }

        return $filter;
    }



    // Enrolment direct queries - subject_has_user holds the currently enrolled users


    /**
     * @param int $subjectId
     * @return array
     * @throws \Exception
     */
    public function findUsers($subjectId)
    {
        $stm = $this->getDb()->prepare('SELECT user_id FROM subject_has_user WHERE subject_id = ?');
        $stm->execute(array($subjectId));
        return $stm->fetchAll(\PDO::FETCH_COLUMN, 0);
    }

    /**
     * @param int $subjectId
     * @param int $userId
     * @return boolean
     * @throws \Exception
     */
    public function hasUser($subjectId, $userId)
    {
        $stm = $this->getDb()->prepare('SELECT * FROM subject_has_user WHERE subject_id = ? AND user_id = ?');
        $stm->execute(array($subjectId, $userId));
        return ($stm->rowCount() > 0);
    }

    /**
     * @param int $subjectId
     * @param int $userId
     * @throws \Exception
     */
    public function addUser($subjectId, $userId)
    {
        if ($this->hasUser($subjectId, $userId)) return;
        $stm = $this->getDb()->prepare('INSERT INTO subject_has_user (subject_id, user_id)  VALUES (?, ?)');
        $stm->execute(array($subjectId, $userId));
    }

    /**
     * depending on the combination of parameters:
     *  o remove a user from a subject
     *  o remove all users from a subject
     *  o remove all subjects from a user
     *
     * @param int $subjectId
     * @param int $userId
     * @throws \Exception
     */
    public function removeUser($subjectId = null, $userId = null)
    {
        if ($subjectId && $userId) {
            $stm = $this->getDb()->prepare('DELETE FROM subject_has_user WHERE subject_id = ? AND user_id = ?');
            $stm->execute(array($subjectId, $userId));
        } else if(!$subjectId && $userId) {
            $stm = $this->getDb()->prepare('DELETE FROM subject_has_user WHERE user_id = ?');
            $stm->execute(array($userId));
        } else if ($subjectId && !$userId) {
            $stm = $this->getDb()->prepare('DELETE FROM subject_has_user WHERE subject_id = ?');
            $stm->execute(array($subjectId));
        }
    }


    //  Enrolment Pending List Queries - The enrollment table holds emails of users that are to be enrolled on their next login.

    /**
     * find all subject that the user is pending enrolment
     *
     * @param $institutionId
     * @param $email
     * @param null|\Tk\Db\Tool $tool
     * @return ArrayObject|Subject[]
     * @throws \Exception
     */
    public function findPendingPreEnrollments($institutionId, $email, $tool = null)
    {
        $from = sprintf('%s a, %s b ',
            $this->quoteTable($this->getTable()), $this->quoteTable('subject_pre_enrollment'));
        $where = sprintf('a.id = b.subject_id AND a.institution_id = %d AND b.email = %s ', (int)$institutionId, $this->quote($email));

        $ret = $this->selectFrom($from, $where, $tool);
        return $ret;
    }

    /**
     * @param int $institutionId
     * @param string $uid
     * @param null|\Tk\Db\Tool $tool
     * @return ArrayObject|Subject[]
     * @throws \Exception
     */
    public function findPendingPreEnrollmentsByUid($institutionId, $uid, $tool = null)
    {
        $from = sprintf('%s a, %s b',
            $this->quoteTable($this->getTable()), $this->quoteTable('subject_pre_enrollment'));
        $where = sprintf('a.id = b.subject_id AND a.institution_id = %d AND b.uid = %s', (int)$institutionId, $this->quote($uid));
        $ret = $this->selectFrom($from, $where, $tool);
        return $ret;
    }

    /**
     * @param int $institutionId
     * @param string $username
     * @param null|\Tk\Db\Tool $tool
     * @return ArrayObject|Subject[]
     * @throws \Exception
     */
    public function findPendingPreEnrollmentsByUsername($institutionId, $username, $tool = null)
    {
        $from = sprintf('%s a, %s b',
            $this->quoteTable($this->getTable()), $this->quoteTable('subject_pre_enrollment'));
        $where = sprintf('a.id = b.subject_id AND a.institution_id = %d AND b.username = %s', (int)$institutionId, $this->quote($username));
        $ret = $this->selectFrom($from, $where, $tool);
        return $ret;
    }


    /**
     * Find all pre enrolments for a subject and return with an `enrolled` boolean field
     *
     * @param array $filter    array('subjectId' => $subjectId)
     * @param \Tk\Db\Tool $tool
     * @return array
     * @throws \Exception
     */
    public function findPreEnrollments($filter = array(), $tool = null)
    {
        $toolStr = '';
        if ($tool) {
            $tool->setLimit(0);
            //$toolStr = ' '.$tool->toSql('', $this->getDb());
            $toolStr = ' '.$this->getToolSql($tool);
        }

//        $stm = $this->getDb()->prepare('SELECT a.subject_id, a.uid, a.email, a.username, b.hash, b.id as \'user_id\', IF(c.subject_id IS NULL,0,1) as enrolled
//FROM  subject_pre_enrollment a
//  LEFT JOIN  user b ON (b.email != \'\' AND b.email IS NOT NULL AND b.email = a.email)
//  LEFT JOIN subject_has_user c ON (b.id = c.user_id AND c.subject_id = ?)
//WHERE a.subject_id = ? ' . $toolStr);
//        $stm->execute(array($filter['subjectId'], $filter['subjectId']));
//        $arr = $stm->fetchAll();

        $sql = sprintf('SELECT a.subject_id, a.uid, a.email, a.username, b.hash, b.id as \'user_id\', IF(c.subject_id IS NULL,0,1) as enrolled
FROM  subject_pre_enrollment a 
  LEFT JOIN  user b ON (b.email != \'\' AND b.email IS NOT NULL AND b.email = a.email)  
  LEFT JOIN subject_has_user c ON (b.id = c.user_id AND c.subject_id = %s)
WHERE a.subject_id = %s %s', (int)$filter['subjectId'], (int)$filter['subjectId'], $toolStr);
        $res = $this->getDb()->query($sql);
        $arr = $res->fetchAll();


        $tool->setFoundRows(count($arr));
        return $arr;
    }

    /**
     * Find all students on a subject pre-enrolment list
     *
     * @param $subjectId
     * @return array|\StdClass[]
     * @deprecated use findEnrolmentsBySubjectId($subjectId, $tool)
     * @throws \Exception
     */
    public function findAllPreEnrollments($subjectId)
    {
        $stm = $this->getDb()->prepare('SELECT * FROM user a LEFT JOIN subject_pre_enrollment b ON (a.email = b.email) WHERE b.subject_id = ?');
        $stm->execute(array($subjectId));
        return $stm->fetchAll();
    }

    /**
     * @param $subjectId
     * @param $email
     * @param string $uid
     * @param string $username
     * @return bool
     * @throws \Exception
     */
    public function hasPreEnrollment($subjectId, $email = '', $uid = '', $username = '')
    {
        $rc = 0;
        if ($email) {
            $stm = $this->getDb()->prepare('SELECT * FROM subject_pre_enrollment WHERE subject_id = ? AND email = ?');
            $stm->execute(array($subjectId, $email));
            $rc = $stm->rowCount();
        }
        if ($uid) {
            $stm = $this->getDb()->prepare('SELECT * FROM subject_pre_enrollment WHERE subject_id = ? AND uid = ?');
            $stm->execute(array($subjectId, $uid));
            $rc = $stm->rowCount();
        }
        if ($username) {
            $stm = $this->getDb()->prepare('SELECT * FROM subject_pre_enrollment WHERE subject_id = ? AND username = ?');
            $stm->execute(array($subjectId, $username));
            $rc = $stm->rowCount();
        }
        if ($rc) {
            return true;
        }
        return false;
    }

    /**
     * @param int $subjectId
     * @param string $email
     * @param string $uid
     * @param string $username
     * @throws \Exception
     */
    public function addPreEnrollment($subjectId, $email = '', $uid = '', $username = '')
    {
        if (($email || $uid || $username) && !$this->hasPreEnrollment($subjectId, $email, $uid, $username)) {
            $stm = $this->getDb()->prepare('INSERT INTO subject_pre_enrollment (subject_id, uid, email, username)  VALUES (?, ?, ?, ?)');
            $stm->execute(array($subjectId, $uid, $email, $username));
        }
        // Do not add the user to the subject_has_user table as this will be added automatically the next time the user logs in
        // This part should be implemented in a auth.onLogin listener
    }

    /**
     * @param int $subjectId
     * @param string $email
     * @param string $uid
     * @param string $username
     * @throws \Exception
     */
    public function removePreEnrollment($subjectId, $email = '', $uid = '', $username = '')
    {
        if ($this->hasPreEnrollment($subjectId, $email)) {
            $stm = $this->getDb()->prepare('DELETE FROM subject_pre_enrollment WHERE subject_id = ? AND email = ?');
            $stm->execute(array($subjectId, $email));
        } else if ($this->hasPreEnrollment($subjectId, '', $uid)) {
            $stm = $this->getDb()->prepare('DELETE FROM subject_pre_enrollment WHERE subject_id = ? AND uid = ?');
            $stm->execute(array($subjectId, $uid));
        } else if ($this->hasPreEnrollment($subjectId, '', '', $username)) {
            $stm = $this->getDb()->prepare('DELETE FROM subject_pre_enrollment WHERE subject_id = ? AND username = ?');
            $stm->execute(array($subjectId, $username));
        } else {
//            $stm = $this->getDb()->prepare('DELETE FROM subject_pre_enrollment WHERE subject_id = ? AND email = ? AND uid = ? AND username = ?');
//            $stm->execute(array($subjectId, $email, $uid, $username));
        }
    }


    /**
     * Check if a user is pre-enrolled in any subject for that institution
     *
     * @param $institutionId
     * @param array|string $email
     * @param string $uid
     * @param string $username
     * @return bool
     * @throws \Exception
     */
    public function isPreEnrolled($institutionId, $email = '', $uid = '', $username = '')
    {
        $found = false;
        if ($uid) {
            $subjectList = $this->findPendingPreEnrollmentsByUid($institutionId, $uid);
            $found = (count($subjectList) > 0);
        }
        if ($username) {
            $subjectList = $this->findPendingPreEnrollmentsByUsername($institutionId, $username);
            $found = (count($subjectList) > 0);
        }

        if (is_string($email)) $email = array($email);
        if (!$found && is_array($email)) {
            foreach ($email as $e) {
                if ($found) break;
                if (!filter_var($e, \FILTER_VALIDATE_EMAIL)) continue;
                $subjectList = $this->findPendingPreEnrollments($institutionId, $e);
                $found = (count($subjectList) > 0);
            }
        }
        return $found;
    }


}