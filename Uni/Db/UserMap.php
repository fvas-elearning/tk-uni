<?php
namespace Uni\Db;

use Tk\Db\Map\Model;
use Tk\Db\Map\ArrayObject;
use Tk\DataMap\Db;
use Tk\DataMap\Form;
use Tk\Db\Tool;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class UserMap extends \Bs\Db\UserMap
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
            $this->dbMap->addPropertyMap(new Db\Text('uid'));
            $this->dbMap->addPropertyMap(new Db\Text('type'));
            $this->dbMap->addPropertyMap(new Db\Text('username'));
            $this->dbMap->addPropertyMap(new Db\Text('title'));
            $this->dbMap->addPropertyMap(new Db\Text('nameFirst', 'name_first'));
            $this->dbMap->addPropertyMap(new Db\Text('nameLast', 'name_last'));
            $this->dbMap->addPropertyMap(new Db\Text('email'));
            $this->dbMap->addPropertyMap(new Db\Text('phone'));
            $this->dbMap->addPropertyMap(new Db\Text('credentials'));
            $this->dbMap->addPropertyMap(new Db\Text('position'));
            $this->dbMap->addPropertyMap(new Db\Text('image'));
            $this->dbMap->addPropertyMap(new Db\Text('notes'));
            $this->dbMap->addPropertyMap(new Db\Boolean('active'));
            $this->dbMap->addPropertyMap(new Db\Date('lastLogin', 'last_login'));
            $this->dbMap->addPropertyMap(new Db\Text('sessionId', 'session_id'));
            $this->dbMap->addPropertyMap(new Db\Text('password'));
            $this->dbMap->addPropertyMap(new Db\Text('hash'));
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
            $this->formMap->addPropertyMap(new Form\Text('uid'));
            $this->formMap->addPropertyMap(new Form\Text('type'));
            $this->formMap->addPropertyMap(new Form\Text('username'));
            $this->formMap->addPropertyMap(new Form\Text('title'));
            $this->formMap->addPropertyMap(new Form\Text('nameFirst'));
            $this->formMap->addPropertyMap(new Form\Text('nameLast'));
            $this->formMap->addPropertyMap(new Form\Text('email'));
            $this->formMap->addPropertyMap(new Form\Text('phone'));
            $this->formMap->addPropertyMap(new Form\Text('credentials'));
            $this->formMap->addPropertyMap(new Form\Text('position'));
            $this->formMap->addPropertyMap(new Form\Text('image'));
            $this->formMap->addPropertyMap(new Form\Text('notes'));
            $this->formMap->addPropertyMap(new Form\Text('password'));
            $this->formMap->addPropertyMap(new Form\Boolean('active'));
        }
        return $this->formMap;
    }

    /**
     * @param string|int $identity
     * @return \Tk\Db\Map\Model|User
     * @throws \Exception
     */
    public function findByAuthIdentity($identity)
    {
        return $this->find($identity);
    }

    /**
     * @param $username
     * @param int $institutionId
     * @param string|array $type
     * @return null|Model|User
     * @throws \Exception
     */
    public function findByUsername($username, $institutionId = 0, $type = null)
    {
        return $this->findFiltered(array(
            'institutionId' => $institutionId,
            'username' => $username,
            'type' => $type
        ))->current();
    }

    /**
     * @param string $email
     * @param int $institutionId
     * @param string|array $type
     * @return null|Model|User
     * @throws \Exception
     */
    public function findByEmail($email, $institutionId = null, $type = null)
    {
        // TODO: I think we have a logic issue here?????
        if (!$this->getConfig()->get('system.auth.email.unique') && !$this->getConfig()->get('system.auth.email.require')) {
            throw new \Tk\Exception('User email is not a unique key. Change $config[\'system.auth.email.unique\'] = false;');
        }
        return $this->findFiltered(array(
            'institutionId' => $institutionId,
            'email' => $email,
            'type' => $type
        ))->current();
    }

    /**
     * @param string $uid
     * @param int $institutionId
     * @param string|array $type
     * @return null|Model|User
     * @throws \Exception
     */
    public function findByUid($uid, $institutionId = null, $type = null)
    {
        return $this->findFiltered(array(
            'institutionId' => $institutionId,
            'uid' => $uid,
            'type' => $type
        ))->current();
    }

    /**
     * @param $hash
     * @param int $institutionId (default 0 = admin and institution users)
     * @param string|array $type
     * @return null|Model|User
     * @throws \Exception
     */
    public function findByHash($hash, $institutionId = 0, $type = null)
    {
        $filter = array(
            'institutionId' => $institutionId,
            'hash' => $hash,
            'type' => $type
        );
        $r = $this->findFiltered($filter)->current();
        return $r;
    }

    /**
     * @param int $institutionId
     * @param string|array $type
     * @param \Tk\Db\Tool|null $tool
     * @return ArrayObject|User[]
     * @throws \Exception
     */
    public function findByInstitutionId($institutionId, $type = null, $tool = null)
    {
        return $this->findFiltered(array(
            'institutionId' => $institutionId,
            'type' => $type
        ));
    }

    /**
     * @param \Tk\Db\Filter $filter
     * @return \Tk\Db\Filter
     */
    public function makeQuery(\Tk\Db\Filter $filter)
    {
        parent::makeQuery($filter);
        if (isset($filter['institutionId'])) {
            if ($filter['institutionId'] > 0)
                $filter->appendWhere('a.institution_id = %d AND ', (int)$filter['institutionId']);
            else
                $filter->appendWhere('(a.institution_id IS NULL OR a.institution_id = 0) AND ');
        }

        if (!empty($filter['courseId'])) {
            $filter->appendFrom(', %s f', $this->quoteTable('course_has_user'));
            $filter->appendWhere('a.id = f.user_id AND ');
            $w = $this->makeMultiQuery($filter['courseId'], 'f.course_id');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['subjectId'])) {
            $filter->appendFrom(', subject_has_user c');
            $filter->appendWhere('a.id = c.user_id AND ');
            $w = $this->makeMultiQuery($filter['subjectId'], 'c.subject_id');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['mentorId'])) {
            $filter->appendFrom(', user_mentor n');
            $filter->appendWhere('a.id = n.user_id AND ');
            $w = $this->makeMultiQuery($filter['mentorId'], 'n.mentor_id');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        return $filter;
    }



    // Mentor functions

    /**
     * @param int $mentorId
     * @return array
     * @throws \Exception
     */
    public function findByMentorId($mentorId)
    {
        $stm = $this->getDb()->prepare('SELECT user_id FROM user_mentor WHERE mentor_id = ?');
        $stm->execute(array($mentorId));
        return $stm->fetchAll(\PDO::FETCH_COLUMN, 0);
    }

    /**
     * @param int $studentId
     * @return array
     * @throws \Exception
     */
    public function findMentor($studentId)
    {
        $stm = $this->getDb()->prepare('SELECT mentor_id FROM user_mentor WHERE user_id = ?');
        $stm->execute(array($studentId));
        return $stm->fetchAll(\PDO::FETCH_COLUMN, 0);
    }

    /**
     * @param int $mentorId
     * @param int $userId
     * @return boolean
     * @throws \Exception
     */
    public function hasMentor($mentorId, $userId)
    {
        $stm = $this->getDb()->prepare('SELECT * FROM user_mentor WHERE mentor_id = ? AND user_id = ?');
        $stm->execute(array($mentorId, $userId));
        return ($stm->rowCount() > 0);
    }

    /**
     * @param int $mentorId
     * @param int $userId
     * @throws \Exception
     */
    public function addMentor($mentorId, $userId)
    {
        if ($this->hasMentor($mentorId, $userId)) return;
        $stm = $this->getDb()->prepare('INSERT INTO user_mentor (mentor_id, user_id)  VALUES (?, ?)');
        $stm->execute(array($mentorId, $userId));
    }

    /**
     * depending on the combination of parameters:
     *  o remove a user from a mentor
     *  o remove all users from a mentor
     *  o remove all mentors from a user
     *
     * @param int $mentorId
     * @param int $userId
     * @throws \Exception
     */
    public function removeMentor($mentorId = null, $userId = null)
    {
        if ($mentorId && $userId) {
            $stm = $this->getDb()->prepare('DELETE FROM user_mentor WHERE mentor_id = ? AND user_id = ?');
            $stm->execute(array($mentorId, $userId));
        } else if(!$mentorId && $userId) {
            $stm = $this->getDb()->prepare('DELETE FROM user_mentor WHERE user_id = ?');
            $stm->execute(array($userId));
        } else if ($mentorId && !$userId) {
            $stm = $this->getDb()->prepare('DELETE FROM user_mentor WHERE mentor_id = ?');
            $stm->execute(array($mentorId));
        }
    }
}