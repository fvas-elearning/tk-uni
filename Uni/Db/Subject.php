<?php
namespace Uni\Db;

use Tk\Db\Data;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Subject extends \Tk\Db\Map\Model implements \Uni\Db\SubjectIface
{
    use \Uni\Db\Traits\InstitutionTrait;
    use \Bs\Db\Traits\TimestampTrait;
    use \Uni\Db\Traits\CourseTrait;
    
    /**
     * @var int
     */
    public $id = 0;

    /**
     * @var int
     */
    public $institutionId = 0;

    /**
     * @var int
     */
    public $courseId = 1;

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var string
     */
    public $code = '';

    /**
     * @var string
     */
    public $email = '';

    /**
     * @var string
     */
    public $description = '';

    /**
     * @var \DateTime
     */
    public $dateStart = null;

    /**
     * @var \DateTime
     */
    public $dateEnd = null;

    /**
     * @var boolean
     */
    public $notify = true;

    /**
     * @var boolean
     */
    public $publish = true;

    /**
     * @var \DateTime
     */
    public $modified = null;

    /**
     * @var \DateTime
     */
    public $created = null;

    /**
     * @var Data
     */
    protected $_data = null;


    /**
     * Subject constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->_TimestampTrait();
        $this->setInstitutionId($this->getConfig()->getInstitutionId());
        $this->dateStart = \Tk\Date::floor()->setDate($this->created->format('Y'), 1, 1);
        $this->dateEnd = \Tk\Date::ceil()->setDate($this->created->format('Y'), 12, 31);
    }

    /**
     * @throws \Exception
     */
    public function save()
    {
        $this->code = self::cleanCode($this->code);
        if ($this->getData()->count())
            $this->getData()->save();
        parent::save();
    }

    /**
     * Get the data object
     *
     * @return \Tk\Db\Data
     */
    public function getData()
    {
        if (!$this->_data)
            $this->_data = \Tk\Db\Data::create(get_class($this), $this->getVolatileId());
        return $this->_data;
    }

    /**
     * Get the path for all file associated to this object
     *
     * @return string
     * @throws \Exception
     */
    public function getDataPath()
    {
        // TODO: Use this path, but we will have to move existing subject files to the new location first..
        //return sprintf('%s/subject/%s', $this->getCourse()->getDataPath(), $this->getVolatileId());
        return sprintf('%s/subject/%s', $this->getInstitution()->getDataPath(), $this->getVolatileId());
    }

    /**
     *
     * @param UserIface|User $user
     * @return mixed
     * @throws \Exception
     */
    public function isUserEnrolled($user)
    {
        return $this->getConfig()->getSubjectMapper()->hasUser($this->getId(), $user->getId());
    }

    /**
     * Enroll a student. Staff should be attached to the course not the subject at this time.
     *
     * @param UserIface $user
     * @return $this
     * @throws \Exception
     * @deprecated Use addUser()
     */
    public function enrollUser($user)
    {
        if ($user->isStudent()) {
            $this->getConfig()->getSubjectMapper()->addUser($this->getId(), $user->getId());
        }
        return $this;
    }

    /**
     * Get all the students fully enrolled to this course
     *
     * @return \Uni\Db\UserIface[]|\Tk\Db\Map\ArrayObject
     * @throws \Exception
     */
    public function getUsers()
    {
        $ids = $this->getConfig()->getSubjectMapper()->findUsers($this->getId());
        if (!count($ids)) return [];
        return $this->getConfig()->getUserMapper()->findFiltered(array('id' => $ids));
    }

    /**
     * Enroll/Add students to a subject
     *
     * @param \Uni\Db\UserIface $user
     * @return Subject
     * @throws \Exception
     */
    public function addUser($user)
    {
        if (!$user || !$this->getId() || !$user->isStudent())
            throw new \Tk\Exception('Only add Students to a saved subject!');
        $this->getConfig()->getSubjectMapper()->addUser($this->getId(), $user->getId());
        return $this;
    }

    /**
     * Clean a subject code to be compatible with LMS/LTI systems
     * valid chars are  [a-zA-Z0-9_-]
     *
     * @param $subjectCode
     * @return mixed
     */
    public static function cleanCode($subjectCode)
    {
        $s = preg_replace('/[^a-z0-9_-]/i', '_', $subjectCode);
        //$s = preg_replace('/__++/', '_', $s);     // TODO: check if this is needed
        return $s;
    }

    /**
     * @param $str
     * @return null|string|string[]
     */
    public static function incrementString($str)
    {
        // increment years
        $s = preg_replace_callback('/(.*)(20[0-9]{2})(.*)/', function ($regs) {
            if (count($regs) == 4) {
                return $regs[1] . ($regs[2]+1) . $regs[3];
            }
            return '';
        }, $str);
        if ($s) $str = $s;

        // increment semester
        $s = preg_replace_callback('/(.*SEM)([12])(.*)/', function ($regs) {
            if (count($regs) == 4) {
                $sem = '2';
                if ($regs[2] == '2') {
                    $sem = '1';
                }
                return $regs[1] . $sem . $regs[3];
            }
            return '';
        }, $str);
        if ($s) $str = $s;

        return $str;
    }

    /**
     * If false, the student should be denied access to creating new submissions of any type for that subject.
     * If false, the UI should display historic grades and placement data.
     *
     * @return bool
     */
    public function isActive()
    {
        if (!$this->dateStart || !$this->dateEnd) return true;
        $now = \Tk\Date::create();
        return (\Tk\Date::greaterThan($now, $this->dateStart) && \Tk\Date::lessThan($now, $this->dateEnd));
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Subject
     */
    public function setName(string $name): Subject
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return Subject
     */
    public function setCode(string $code): Subject
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        if ($this->email)
            return $this->email;
        if ($this->getCourse()->getEmail())
            $this->getCourse()->getEmail();
        return '';
    }

    /**
     * @param string $email
     * @return Subject
     */
    public function setEmail(string $email): Subject
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return Subject
     */
    public function setDescription(string $description): Subject
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param null|string $format   If supplied then a string of the formatted date is returned
     * @return \DateTime|string
     */
    public function getDateStart($format = null)
    {
        if ($format && $this->dateStart)
            return $this->dateStart->format($format);
        return $this->dateStart;
    }

    /**
     * @param \DateTime $dateStart
     * @return Subject
     */
    public function setDateStart(\DateTime $dateStart): Subject
    {
        $this->dateStart = $dateStart;
        return $this;
    }

    /**
     * @param null|string $format   If supplied then a string of the formatted date is returned
     * @return \DateTime|string
     */
    public function getDateEnd($format = null)
    {
        if ($format && $this->dateEnd)
            return $this->dateEnd->format($format);
        return $this->dateEnd;
    }

    /**
     * @param \DateTime $dateEnd
     * @return Subject
     */
    public function setDateEnd(\DateTime $dateEnd): Subject
    {
        $this->dateEnd = $dateEnd;
        return $this;
    }

    /**
     * @return bool
     */
    public function isNotify(): bool
    {
        return $this->notify;
    }

    /**
     * @param bool $notify
     * @return Subject
     */
    public function setNotify(bool $notify): Subject
    {
        $this->notify = $notify;
        return $this;
    }

    /**
     * @return bool
     */
    public function isPublish(): bool
    {
        return $this->publish;
    }

    /**
     * @return bool
     * @alias isPublish()
     */
    public function isPublished(): bool
    {
        return $this->publish;
    }

    /**
     * @param bool $publish
     * @return Subject
     */
    public function setPublish(bool $publish): Subject
    {
        $this->publish = $publish;
        return $this;
    }


    /**
     * @return array
     * @throws \Exception
     */
    public function validate()
    {
        $errors = array();
        $errors = $this->validateInstitutionId($errors);
        $errors = $this->validateCourseId($errors);

        if (!$this->getName()) {
            $errors['name'] = 'Please enter a valid name';
        }
        if (!$this->getCode()) {
            $errors['code'] = 'Please enter a valid code';
        } else {
            // Look for existing subjects with same code
            $c = $this->getConfig()->getSubjectMapper()->findByCode($this->getCode(), $this->getInstitutionId());
            if ($c && $c->getId() != $this->getId()) {
                $errors['code'] = 'Subject code already exists';
            }
        }

        if ($this->email && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        }
        
        return $errors;
    }
}