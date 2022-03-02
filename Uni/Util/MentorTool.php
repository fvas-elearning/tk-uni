<?php
namespace Uni\Util;

use Tk\ConfigTrait;

/**
 *
 */
class MentorTool
{
    use ConfigTrait;

    /**
     * @var MentorTool
     */
    public static $instance = null;


    protected $success = [];
    protected $fail = [];
    protected $error = [];

    /**
     *
     */
    public function __constructor()
    {

    }

    /**
     * Get an instance of this object
     *
     * @return MentorTool|static
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * @param string $csv The CSV text (not a file path)
     * @return int
     * @throws \Tk\Exception
     */
    public function setCsv(string $csv)
    {
        if (!$csv) throw new \Tk\Exception('Empty CSV string');
        $csv = mb_convert_encoding(trim($csv), 'UTF-8');
        $rowId = -1;
        // clear import table and add new ones
        $this->remove();
        try {
            $temp = fopen('php://temp', 'r+');
            fputs($temp, $csv);
            rewind($temp);
            while ($csvRow = fgetcsv($temp)) {
                // HAC FIXK: for some reason the first row is getting a '﻿' char prepended, could be a stream issue
                $csvRow = str_replace('﻿', '', $csvRow);

                $row = array_map('trim', $csvRow);
                if (count($row) != 2 || strtolower($row[0]) == 'mentorid' || strtolower($csvRow[1]) == 'studentid') continue;  // ignore header row
                $rowId++;
                if (!trim(implode('', $row))) continue; // if empty row
                $this->insert($row[0], $row[1]);
            }
            fclose($temp);
        } catch (\Exception $e) {
            \Tk\Log::error($e->__toString());
        }
        return $rowId+1;
    }

    /**
     * @param false $clearExisting Clear all existing mentor links
     */
    public function executeImport($clearExisting = false)
    {

        $this->success = [];
        $this->fail = [];
        $this->error = [];
        $rowId = -1;

        if ($clearExisting) {
            $this->getDb()->query('TRUNCATE user_mentor');
            \Tk\Log::info('Clearing existing mentor list');
        }

        $importList = $this->find();
        foreach ($importList as $i => $row) {
            list($mentorId, $studentId) = $row;

            $staff = null;
            if ((preg_match('/^[0-9]{4,10}$/', $mentorId))) {
                $staff = $this->getConfig()->getUserMapper()->findByUid($mentorId, $this->getConfig()->getInstitutionId());
            } else if (filter_var($mentorId, \FILTER_VALIDATE_EMAIL)) {
                $staff = $this->getConfig()->getUserMapper()->findByEmail($mentorId, $this->getConfig()->getInstitutionId());
            } else {
                $staff = $this->getConfig()->getUserMapper()->findByUsername($mentorId, $this->getConfig()->getInstitutionId());
            }
            if ($staff && !$staff->isMentor()) {
                $staff = null;
            }
            if (!$staff) {
                $error[md5($mentorId)] = 'Staff not found: ' . $mentorId;
            }

            $student = null;
            if ((preg_match('/^[0-9]{4,10}$/', $studentId))) {
                $student = $this->getConfig()->getUserMapper()->findByUid($studentId, $this->getConfig()->getInstitutionId());
            } else if (filter_var($studentId, \FILTER_VALIDATE_EMAIL)) {
                $student = $this->getConfig()->getUserMapper()->findByEmail($studentId, $this->getConfig()->getInstitutionId());
            } else {
                $student = $this->getConfig()->getUserMapper()->findByUsername($studentId, $this->getConfig()->getInstitutionId());
            }
            if ($student && !$student->isStudent()) {
                $student = null;
            }
            if (!$student) {
                $error[md5($studentId)] = 'Student not found: ' . $studentId;
            }

            if (!$staff || !$student) {
                $fail[$rowId] = $row;
                continue;
            }

            $this->getConfig()->getUserMapper()->addMentor($staff->getId(), $student->getId());
            $this->remove($mentorId, $studentId);
            \Tk\Log::notice('Mentor/Student Added: ' . $staff->getName() . ' - ' . $student->getName());
            $success[$rowId] = $row;

        }

    }

    /**
     * @param string $mentorId
     * @param string $studentId
     * @return bool
     * @throws \Tk\Db\Exception
     */
    public function has($mentorId, $studentId)
    {
        $stm = $this->getDb()->prepare('SELECT * FROM user_mentor_import WHERE mentor_id = ? AND student_id = ?');
        $stm->execute(array($mentorId, $studentId));
        return ($stm->rowCount() > 0);
    }

    /**
     * @param null|string $mentorId
     * @param null|string $studentId
     * @return array
     * @throws \Tk\Db\Exception
     */
    public function find($mentorId = null, $studentId = null, $fetch = \PDO::FETCH_NUM)
    {
        $res = [];
        if ($mentorId && $studentId) {
            $stm = $this->getDb()->prepare('SELECT mentor_id, student_id FROM user_mentor_import WHERE mentor_id = ? AND student_id = ?');
            $stm->execute(array($mentorId, $studentId));
            $res = $stm->fetchAll($fetch);
        } else if(!$mentorId && $studentId) {
            $stm = $this->getDb()->prepare('SELECT mentor_id, student_id FROM user_mentor_import WHERE student_id = ?');
            $stm->execute(array($studentId));
            $res = $stm->fetchAll($fetch);
        } else if ($mentorId && !$studentId) {
            $stm = $this->getDb()->prepare('SELECT mentor_id, student_id FROM user_mentor_import WHERE mentor_id = ?');
            $stm->execute(array($mentorId));
            $res = $stm->fetchAll($fetch);
        } else if (!$mentorId && !$studentId) {
            $stm = $this->getDb()->prepare('SELECT mentor_id, student_id FROM user_mentor_import');
            $stm->execute();
            $res = $stm->fetchAll($fetch);
        }
        return $res;
    }

    /**
     * @param string $mentorId
     * @param string $studentId
     * @throws \Tk\Db\Exception
     */
    public function insert($mentorId, $studentId)
    {
        if ($this->has($mentorId, $studentId)) return;
        $stm = $this->getDb()->prepare('INSERT INTO user_mentor_import (mentor_id, student_id)  VALUES (?, ?)');
        $stm->execute(array($mentorId, $studentId));

    }

    /**
     * @param null|string $mentorId
     * @param null|string $studentId
     * @throws \Tk\Db\Exception
     */
    public function remove($mentorId = null, $studentId = null)
    {
        if ($mentorId && $studentId) {
            if (!$this->has($mentorId, $studentId)) return;
            $stm = $this->getDb()->prepare('DELETE FROM user_mentor_import WHERE mentor_id = ? AND student_id = ?');
            $stm->execute(array($mentorId, $studentId));
        } else if(!$mentorId && $studentId) {
            $stm = $this->getDb()->prepare('DELETE FROM user_mentor_import WHERE student_id = ?');
            $stm->execute(array($studentId));
        } else if ($mentorId && !$studentId) {
            $stm = $this->getDb()->prepare('DELETE FROM user_mentor_import WHERE mentor_id = ?');
            $stm->execute(array($mentorId));
        } else if (!$mentorId && !$studentId) {
            $stm = $this->getDb()->prepare('DELETE FROM user_mentor_import WHERE 1');
            $stm->execute();
        }
    }

}
