<?php
namespace Uni\Db\Traits;

use Uni\Config;
use Uni\Db\InstitutionIface;
use Uni\Db\Institution;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2019 Michael Mifsud
 */
trait InstitutionTrait
{

    /**
     * @var InstitutionIface
     */
    private $_institution = null;



    /**
     * @return int
     */
    public function getInstitutionId()
    {
        return $this->institutionId;
    }

    /**
     * @param int $institutionId
     * @return $this
     */
    public function setInstitutionId($institutionId)
    {
        $this->institutionId = (int)$institutionId;
        return $this;
    }

    /**
     * Get the Institution object
     *
     * @return \Tk\Db\Map\Model|\Tk\Db\ModelInterface|InstitutionIface|Institution
     */
    public function getInstitution()
    {
        if (!$this->_institution) {
            try {
                $this->_institution = Config::getInstance()->getInstitutionMapper()->find($this->getInstitutionId());
            } catch (\Exception $e) {}
        }
        return $this->_institution;
    }

    /**
     * Get the Institution object found using $this->subjectId
     * 
     * Note: This is use as an alias incases where get{Object}()
     *   is already used in the main object for another reason
     *
     * @return InstitutionIface|null
     * @deprecated use getInstitution()
     */
    public function getInstitutionObj()
    {
        return $this->getInstitution();
    }

    /**
     * @param array $errors
     * @return array
     */
    public function validateInstitutionId($errors = [])
    {
        if (!$this->getInstitutionId()) {
            $errors['institutionId'] = 'Invalid value: institutionId';
        }
        return $errors;
    }

}