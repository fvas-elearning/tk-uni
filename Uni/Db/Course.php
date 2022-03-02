<?php
namespace Uni\Db;

use Bs\Db\Traits\TimestampTrait;
use Uni\Config;
use Uni\Db\Traits\InstitutionTrait;

/**
 * @author Mick Mifsud
 * @created 2019-12-05
 * @link http://tropotek.com.au/
 * @license Copyright 2019 Tropotek
 */
class Course extends \Tk\Db\Map\Model implements \Tk\ValidInterface
{
    use InstitutionTrait;
    use TimestampTrait;

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
    public $coordinatorId = 0;

    /**
     * @var string
     */
    public $code = '';

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var string
     */
    public $email = '';

    /**
     * @var string
     */
    public $emailSignature = '';

    /**
     * @var string
     */
    public $description = '';

    /**
     * @var \DateTime
     */
    public $modified = null;

    /**
     * @var \DateTime
     */
    public $created = null;

    /**
     * @var null|User
     */
    private $_coordinator = null;

    /**
     * @var null|\Tk\Db\Data
     */
    private $data = null;


    /**
     * Course
     */
    public function __construct()
    {
        $this->_TimestampTrait();

    }

    /**
     * Generate a course code from a subject code:
     *   EG:
     *         Subject Code            Created Course Code
     *       - VETS30014_2018_SM2   => VETS30014
     *       - COM_000297           => COM_000297
     *       - COM_COM_000297       => COM_COM_000297
     *       - MERGE_2019_271       => MERGE_2019_271
     *
     * @param string $subjectCode
     * @return string
     */
    public static function makeCourseCode($subjectCode)
    {
        $subjectCode = trim($subjectCode);
        $courseCode = $subjectCode;
        if (strstr($subjectCode, '_') !== false) {
            $courseCode = substr($subjectCode, 0, strpos($subjectCode, '_'));
        }
//        if (preg_match('/^(([A-Z]{4})([0-9]{5}))(\S*)/', $subjectCode, $regs)) {
//            $courseCode = $regs[1];
//        } else if (preg_match('/^((MERGE)_([0-9]{4}))_([0-9]+)/', $subjectCode, $regs)) {
//            $courseCode = $regs[1];
//        }
        return $courseCode;
    }

    /**
     * Get the data object
     *
     * @return \Tk\Db\Data
     */
    public function getData()
    {
        if (!$this->data)
            $this->data = \Tk\Db\Data::create(get_class($this), $this->getVolatileId());
        return $this->data;
    }

    /**
     * Get the path for all file associated to this object
     *
     * @return string
     */
    public function getDataPath()
    {
        return sprintf('%s/course/%s', $this->getInstitution()->getDataPath(), $this->getVolatileId());
    }

    /**
     * @throws \Exception
     */
    public function save()
    {
        parent::save();
        if ($this->getCoordinator())
            $this->addUser($this->getCoordinator());
    }

    /**
     * Get all the staff assigned to this course
     *
     * @return \Uni\Db\User[]|\Tk\Db\Map\ArrayObject
     * @throws \Exception
     */
    public function getUsers()
    {
        $ids = CourseMap::create()->findUsers($this->getId());
        return UserMap::create()->findFiltered(array('id' => $ids));
    }

    /**
     * @param \Uni\Db\UserIface|int $userId
     * @return Course
     * @throws \Exception
     */
    public function addUser($userId)
    {
        if ($userId instanceof \Tk\Db\ModelInterface)
            $userId = $userId->getId();
        if (!$this->getId())
            throw new \Tk\Exception('Only add staff users to a saved record!');
        CourseMap::create()->addUser($this->getId(), $userId);
        return $this;
    }

    /**
     * @return \Tk\Db\Map\Model|Subject|\App\Db\Subject
     * @throws \Exception
     */
    public function getCurrentSubject()
    {
        return $this->getConfig()->getSubjectMapper()->findFiltered(array('courseId' => $this->getId()), \Tk\Db\Tool::create('date_start DESC', 1))->current();
    }

    /**
     * @return \Tk\Db\Map\Model|\Tk\Db\ModelInterface|User|null
     */
    public function getCoordinator()
    {
        if (!$this->_coordinator) {
            try {
                $this->_coordinator = Config::getInstance()->getUserMapper()->find($this->getCoordinatorId());
            } catch (\Exception $e) {}
        }
        return $this->_coordinator;
    }

    /**
     * @param \Uni\Db\UserIface $user
     * @return Course
     * @throws \Exception
     */
    public function setCoordinator($user)
    {
        $this->setCoordinatorId($user->getId());
        $this->addUser($user);
        return $this;
    }

    /**
     * @param int $coordinatorId
     * @return Course
     */
    public function setCoordinatorId($coordinatorId) : Course
    {
        $this->coordinatorId = $coordinatorId;
        return $this;
    }

    /**
     * return int
     */
    public function getCoordinatorId() : int
    {
        return $this->coordinatorId;
    }

    /**
     * @param string $code
     * @return Course
     */
    public function setCode($code) : Course
    {
        $this->code = $code;
        return $this;
    }

    /**
     * return string
     */
    public function getCode() : string
    {
        return $this->code;
    }

    /**
     * @param string $name
     * @return Course
     */
    public function setName($name) : Course
    {
        $this->name = $name;
        return $this;
    }

    /**
     * return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @param string $email
     * @return Course
     */
    public function setEmail($email) : Course
    {
        $this->email = $email;
        return $this;
    }

    /**
     * return string
     */
    public function getEmail() : string
    {
        if ($this->email)
            return $this->email;
        if ($this->getCoordinator() && $this->getCoordinator()->getEmail())
            return $this->getCoordinator()->getEmail();
        if ($this->getInstitution() && $this->getInstitution()->getEmail())
            return $this->getInstitution()->getEmail();
        return '';
    }

    /**
     * @param string $emailSignature
     * @return Course
     */
    public function setEmailSignature($emailSignature) : Course
    {
        $this->emailSignature = $emailSignature;
        return $this;
    }

    /**
     * return string
     */
    public function getEmailSignature() : string
    {
        return $this->emailSignature;
    }

    /**
     * @param string $description
     * @return Course
     */
    public function setDescription($description) : Course
    {
        $this->description = $description;
        return $this;
    }

    /**
     * return string
     */
    public function getDescription() : string
    {
        return $this->description;
    }

    /**
     * @param \DateTime $modified
     * @return Course
     */
    public function setModified($modified) : Course
    {
        $this->modified = $modified;
        return $this;
    }

    /**
     * return \DateTime
     */
    public function getModified() : \DateTime
    {
        return $this->modified;
    }

    /**
     * @param \DateTime $created
     * @return Course
     */
    public function setCreated($created) : Course
    {
        $this->created = $created;
        return $this;
    }

    /**
     * return \DateTime
     */
    public function getCreated() : \DateTime
    {
        return $this->created;
    }
    
    /**
     * @return array
     */
    public function validate()
    {
        $errors = array();

        if (!$this->institutionId) {
            $errors['institutionId'] = 'Invalid value: institutionId';
        }

//        if (!$this->coordinatorId) {
//            $errors['coordinatorId'] = 'Invalid value: coordinatorId';
//        }

        if (!$this->code) {
            $errors['code'] = 'Invalid value: code';
        }

        if (!$this->name) {
            $errors['name'] = 'Invalid value: name';
        }

        if (!$this->getCoordinatorId() && !$this->email) {
            $errors['email'] = 'Please select a course coordinator or enter an email address for this course';
        }

        return $errors;
    }

}
