<?php
namespace Uni\Db;

use Uni\Db\Traits\InstitutionTrait;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class User extends \Bs\Db\User implements UserIface
{
    use InstitutionTrait;

    /**
     *
     */
    const TYPE_CLIENT           = 'client';
    /**
     *
     */
    const TYPE_STAFF            = 'staff';
    /**
     *
     */
    const TYPE_STUDENT          = 'student';

    /**
     * @var int
     */
    public $institutionId = 0;


    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get the path for all file associated to this object
     *
     * @return string
     */
    public function getDataPath()
    {
        if ($this->getInstitutionId()) {
            return sprintf('%s/user/%s', $this->getInstitution()->getDataPath(), $this->getVolatileId());
        }
        return sprintf('user/%s', $this->getVolatileId());
    }

    /**
     * Helper method to generate user hash
     *
     * @param bool $isTemp
     * @return string
     */
    public function generateHash($isTemp = false)
    {
        $key = sprintf('%s%s%s', $this->getVolatileId(), $this->getInstitutionId(), $this->getUsername());
        if ($isTemp) {
            $key .= date('-YmdHis');
        }
        return $this->getConfig()->hash($key);
    }

    /**
     * @return \Tk\Db\Map\Model|\Tk\Db\ModelInterface|Institution|InstitutionIface|null
     */
    public function getInstitution()
    {
        if (!$this->_institution) {
            try {
                $this->_institution = $this->getConfig()->getInstitutionMapper()->find($this->getInstitutionId());
                if (!$this->_institution && $this->isClient()) {
                    $this->_institution = $this->getConfig()->getInstitutionMapper()->findByUserId($this->getId());
                }
            } catch (\Exception $e) { }
        }
        return $this->_institution;
    }



    /**
     * return true if the user is a lecturer OR a coordinator
     * @return boolean
     */
    public function isLearner()
    {
        return $this->hasPermission(array(Permission::IS_LECTURER, Permission::IS_COORDINATOR));
    }

    /**
     * @return boolean
     */
    public function isCoordinator()
    {
        return $this->hasPermission(Permission::IS_COORDINATOR);
    }

    /**
     * @return boolean
     */
    public function isLecturer()
    {
        return $this->hasPermission(Permission::IS_LECTURER);
    }

    /**
     * @return boolean
     */
    public function isMentor()
    {
        return $this->hasPermission(Permission::IS_MENTOR);
    }


    /**
     * @return boolean
     */
    public function isClient()
    {
        return $this->getType() == self::TYPE_CLIENT;
    }

    /**
     * @return boolean
     */
    public function isStaff()
    {
        return $this->getType() == self::TYPE_STAFF;
    }

    /**
     * @return boolean
     */
    public function isStudent()
    {
        return $this->getType() == self::TYPE_STUDENT;
    }

    /**
     * Returns true if the user is enrolled fully into the subject
     *
     * @param int $subjectId
     * @return bool
     * @throws \Exception
     */
    public function isEnrolled($subjectId)
    {
        /** @var \Uni\Db\SubjectIface $subject */
        $subject = $this->getConfig()->getSubjectMapper()->find($subjectId);
        if ($subject) {
            return $subject->isUserEnrolled($this);
        }
        return false;
    }


    /**
     * Can the user change their password
     *
     * @return bool
     * @throws \Exception
     */
    public function canChangePassword()
    {
        if ($this->getConfig()->getMasqueradeHandler()->isMasquerading()) {
            return false;
        }
        return $this->getConfig()->getSession()->get('auth.password.access');
    }

    /**
     * Validate this object's current state and return an array
     * with error messages. This will be useful for validating
     * objects for use within forms.
     *
     * @return array
     * @throws \Exception
     */
    public function validate()
    {
        $errors = array();
        $usermap = $this->getConfig()->getUserMapper();

        if (!$this->getInstitutionId() && !$this->isAdmin() && !$this->isClient()) {
            $errors['institutionId'] = 'Invalid field institutionId value';
        }

        if (!$this->getType()) {
            $errors['type'] = 'Cannot save a Guest user record.';
        }

        if (!$this->getUsername()) {
            $errors['username'] = 'Invalid field username value';
        } else {
            $dup = $usermap->findByUsername($this->getUsername(), $this->getInstitutionId());
            if ($dup && $dup->getId() != $this->getId()) {
                $errors['username'] = 'This username is already in use';
            }
        }

        if ($this->getConfig()->get('system.auth.email.require')) {
            if (!filter_var($this->getEmail(), FILTER_VALIDATE_EMAIL))
                $errors['email'] = 'Please enter a valid email address';
        } else {
            if ($this->getEmail() && !filter_var($this->getEmail(), FILTER_VALIDATE_EMAIL))
                $errors['email'] = 'Please enter a valid email address';
        }
        if ($this->getConfig()->get('system.auth.email.unique') && $this->getEmail()) {
            $dup = $usermap->findByEmail($this->getEmail(), $this->getInstitutionId());
            $dup = $usermap->findFiltered(array(
                'email' => $this->getEmail(),
                'institutionId' => $this->getInstitutionId(),
                'type' => $this->getType()
            ))->current();
            if ($dup && $dup->getId() != $this->getId()) {
                $errors['email'] = 'This email is already in use';
            }
        }
        if ($this->getUid()) {
            $dup = $usermap->findByUid($this->getUid(), $this->getInstitutionId());
            if ($dup && $dup->getId() != $this->getId()) {
                $errors['uid'] = 'This UID is already in use';
            }
        }

        return $errors;
    }


}
