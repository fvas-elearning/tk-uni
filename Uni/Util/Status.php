<?php
namespace Uni\Util;

use Bs\Db\Traits\StatusTrait;
use Exception;
use Tk\Db\ModelInterface;
use Uni\Db\Course;
use Uni\Db\Institution;
use Uni\Db\Subject;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Status
{

    /**
     * @param ModelInterface|StatusTrait $model
     * @return \Bs\Db\Status
     * @throws Exception
     */
    public static function create($model)
    {
        $status = \Bs\Db\Status::create($model);
        self::updateStatus($status, $model);
        return $status;
    }

    /**
     * Populate the InstitutionId, CourseId, SubjectId where possible
     * @param \Bs\Db\Status $status
     * @param ModelInterface|StatusTrait $model
     */
    public static function updateStatus($status, $model)
    {
        // Auto set the subject and Course ID's if possible, when not possible execute should be set to false and set manually
        if (!$status->getInstitutionId()) {
            if (method_exists($model, 'getInstitutionId')) {
                $status->setInstitutionId($model->getInstitutionId());
            } else if ($status->getConfig()->getInstitutionId()) {
                $status->setInstitutionId($status->getConfig()->getInstitutionId());
            }
        }

        if (!$status->getCourseId()) {
            if (method_exists($model, 'getCourseId')) {
                $status->setCourseId($model->getCourseId());
                if (!$status->getInstitutionId() && method_exists($model, 'getCourse')) {
                    /** @var Course $course */
                    $course = $model->getCourse();
                    $status->setCourseId($course->getInstitutionId());
                }
            } else if ($status->getConfig()->getCourseId()) {
                $status->setCourseId($status->getConfig()->getCourseId());
            }
        }

        if (!$status->getSubjectId()) {
            if (method_exists($model, 'getSubjectId')) {
                $status->setSubjectId($model->getSubjectId());
                if (!$status->getCourseId() && method_exists($model, 'getSubject')) {
                    /** @var Subject $subject */
                    $subject = $model->getSubject();
                    if ($subject)
                        $status->setCourseId($subject->getCourseId());
                }
            } else if ($status->getConfig()->getSubjectId()) {
                $status->setSubjectId($status->getConfig()->getSubjectId());
            }
        }
    }

    /**
     * @param \Bs\Db\Status $status
     * @return \Tk\Db\Map\Model|ModelInterface|Institution|null
     * @throws Exception
     */
    public static function getInstitution($status)
    {
        $institution = \Uni\Config::getInstance()->getInstitutionMapper()->find($status->getInstitutionId());
        if (!$institution && self::getCourse($status)) {
            $institution = self::getCourse($status)->getInstitution();
        }
        return $institution;
    }

    /**
     * @param \Bs\Db\Status $status
     * @return \Tk\Db\Map\Model|ModelInterface|Course|null
     * @throws Exception
     */
    public static function getCourse($status)
    {
        $course = \Uni\Config::getInstance()->getCourseMapper()->find($status->getCourseId());
        if (!$course && self::getSubject($status)) {
            $course = self::getSubject($status)->getCourse();
        }
        return $course;
    }

    /**
     * @param \Bs\Db\Status $status
     * @return \Tk\Db\Map\Model|ModelInterface|Subject|null
     * @throws Exception
     */
    public static function getSubject($status)
    {
        return \Uni\Config::getInstance()->getSubjectMapper()->find($status->getSubjectId());
    }

    /**
     * Get some valid subject name text
     * @return string
     * @throws Exception
     */
    public static function getSubjectName($status)
    {
        $course = null;
        $subject = null;
        $name = '';
        $course = self::getCourse($status);
        $subject = self::getSubject($status);
        if ($course)
            $name = $course->getName();
        if ($subject)
            $name = $subject->getName();
        return $name;
    }

}