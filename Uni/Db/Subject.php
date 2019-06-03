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
    
    /**
     * @var int
     */
    public $id = 0;

    /**
     * @var int
     */
    public $institutionId = 0;

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
    public $notifications = true;

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
    protected $data = null;


    /**
     * Subject constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->_TimestampTrait();

        $this->dateStart = \Tk\Date::floor()->setDate($this->created->format('Y'), 1, 1);
        $this->dateEnd = \Tk\Date::ceil()->setDate($this->created->format('Y'), 12, 31);
    }

    /**
     * @throws \Exception
     */
    public function save()
    {
        $this->getData()->save();
        parent::save();
    }


    /**
     * @param $institutionId
     * @param array $emailList
     * @param string $uid
     * @param string $username
     * @return bool
     * @throws \Exception
     * @deprecated use subjectMap::isPreEnrolled()
     */
    public static function isPreEnrolled($institutionId, $emailList = array(), $uid = '', $username = '')
    {
        return self::createMapper()->isPreEnrolled($institutionId, $emailList, $uid, $username);
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
     * @throws \Exception
     */
    public function getDataPath()
    {
        return sprintf('%s/subject/%s', $this->getInstitutionObj()->getDataPath(), $this->getVolatileId());
    }

    /**
     *
     * @param $user
     * @return mixed
     * @throws \Exception
     */
    public function isUserEnrolled($user)
    {
        return $this->getConfig()->getSubjectMapper()->hasUser($this->id, $user->id);
    }

    /**
     * Enroll a user
     *
     * @param $user
     * @return $this
     * @throws \Exception
     */
    public function enrollUser($user)
    {
        if (!$this->isUserEnrolled($user)) {
            $this->getConfig()->getSubjectMapper()->addUser($this->id, $user->id);
        }
        return $this;
    }

    /**
     * @return int
     */
    public function getInstitutionId()
    {
        return $this->institutionId;
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return \DateTime
     */
    public function getDateStart()
    {
        return $this->dateStart;
    }

    /**
     * @return \DateTime
     */
    public function getDateEnd()
    {
        return $this->dateEnd;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return Subject
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return bool
     */
    public function isNotifications()
    {
        return $this->notifications;
    }

    /**
     * @param bool $notifications
     * @return Subject
     */
    public function setNotifications($notifications)
    {
        $this->notifications = $notifications;
        return $this;
    }

    /**
     * @return bool
     */
    public function isPublish()
    {
        return $this->publish;
    }

    /**
     * @param bool $publish
     * @return Subject
     */
    public function setPublish($publish)
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

        if ((int)$this->institutionId <= 0) {
            $errors['institutionId'] = 'Invalid Institution ID';
        }
        if (!$this->name) {
            $errors['name'] = 'Please enter a valid name';
        }
        if (!$this->code) {
            $errors['code'] = 'Please enter a valid code';
        } else {
            // Look for existing subjects with same code
            $c = $this->getConfig()->getSubjectMapper()->findByCode($this->code, $this->institutionId);
            if ($c && $c->id != $this->id) {
                $errors['code'] = 'Subject code already exists';
            }
        }

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        }
        
        return $errors;
    }

    /**
     * @return \Uni\Config|\Tk\Config
     */
    public function getConfig()
    {
        return parent::getConfig();
    }
}