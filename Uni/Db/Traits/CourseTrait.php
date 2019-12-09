<?php
namespace Uni\Db\Traits;

use Uni\Config;
use Uni\Db\CourseIface;
use Uni\Db\Course;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2019 Michael Mifsud
 */
trait CourseTrait
{


    /**
     * @var CourseIface
     */
    private $_course = null;



    /**
     * @return int
     */
    public function getCourseId()
    {
        return $this->courseId;
    }

    /**
     * @param int $courseId
     * @return CourseTrait
     */
    public function setCourseId($courseId)
    {
        $this->courseId = (int)$courseId;
        return $this;
    }

    /**
     * Get the course related to this object
     *
     * @return Course|null
     */
    public function getCourse()
    {
        return $this->getCourseObj();
    }

    /**
     * Get the Course object found using $this->courseId
     *
     * Note: This is use as an alias in cases where get{Object}()
     *   is already used in the main object for another reason
     *
     * @return Course|CourseIface|null
     */
    public function getCourseObj()
    {
        if (!$this->_course) {
            try {
                $this->_course = Config::getInstance()->getCourseMapper()->find($this->getCourseId());
            } catch (\Exception $e) {}
        }
        return $this->_course;
    }

    /**
     * @param array $errors
     * @return array
     */
    public function validateCourseId($errors = [])
    {
        if (!$this->getCourseId()) {
            $errors['courseId'] = 'Invalid value: courseId';
        }
        return $errors;
    }

}