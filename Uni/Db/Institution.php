<?php
namespace Uni\Db;

use Tk\Db\Data;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Institution extends \Tk\Db\Map\Model implements \Tk\ValidInterface, InstitutionIface
{
    use \Bs\Db\Traits\TimestampTrait;
    use \Bs\Db\Traits\UserTrait;

    /**
     * @var int
     */
    public $id = 0;

    /**
     * @var int
     */
    public $userId = 0;

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var string
     */
    public $domain = '';

    /**
     * @var string
     */
    public $email = '';

    /**
     * @var string
     */
    public $phone = '';

    /**
     * @var string
     */
    public $street = '';

    /**
     * @var string
     */
    public $city = '';

    /**
     * @var string
     */
    public $state = '';

    /**
     * @var string
     */
    public $country = '';

    /**
     * @var string
     */
    public $postcode = '';

    /**
     * @var string
     */
    public $address = '';

    /**
     * @var float
     */
    public $mapLat = -37.797441;

    /**
     * @var float
     */
    public $mapLng = 144.960773;

    /**
     * @var float
     */
    public $mapZoom = 14.0;

    /**
     * @var string
     */
    public $description = '';

    /**
     * @var string
     */
    public $logo = '';

    /**
     * @var string
     */
    public $feature = '';

    /**
     * @var boolean
     */
    public $active = true;

    /**
     * @var string
     */
    public $hash = '';

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
     *
     */
    public function __construct()
    {
        $this->_TimestampTrait();
    }

    /**
     * @throws \Exception
     */
    public function save()
    {
        if (!$this->userId && $this->_user) $this->userId = $this->getUser()->getId();
        $this->getHash();
        $this->getData()->save();
        parent::save();
    }

    /**
     * Find this institutions owner user
     *
     * @return User
     * @throws \Exception
     */
    public function getUser()
    {
        if (!$this->_user) {
            if (!$this->getId()) {
                $this->_user = \Uni\Config::getInstance()->createUser();
                $this->_user->setType(User::TYPE_CLIENT);
            } else {
                $this->_user = \Uni\Config::getInstance()->getUserMapper()->find($this->userId);
            }
        }
        return $this->_user;
    }

    /**
     * Get the user hash or generate one if needed
     *
     * @return string
     */
    public function getHash()
    {
        if (!$this->hash) {
            $this->hash = $this->generateHash();
        }
        return $this->hash;
    }

    /**
     * Helper method to generate user hash
     *
     * @return string
     */
    public function generateHash()
    {
        return hash('md5', sprintf('%s', $this->getVolatileId()));
    }

    /**
     * Get the path for all file associated to this object
     *
     * @return string
     */
    public function getDataPath()
    {
        return sprintf('/institution/%s', $this->getVolatileId());
    }

    /**
     * Get the institution data object
     *
     * @return Data
     */
    public function getData()
    {
        if (!$this->data)
            $this->data = Data::create(get_class($this), $this->getVolatileId());
        return $this->data;
    }

    /**
     * Returns null if no logo available
     *
     * @return \Tk\Uri|null
     */
    public function getLogoUrl()
    {
        if ($this->logo)
            return \Tk\Uri::create(\Uni\Config::getInstance()->getDataUrl().$this->logo);
    }

    /**
     * Returns null if no feature available
     *
     * @return \Tk\Uri|null
     */
    public function getFeatureUrl()
    {
        if ($this->feature)
            return \Tk\Uri::create(\App\Config::getInstance()->getDataUrl().$this->feature);
    }

    /**
     * @return \Tk\Uri
     */
    public function getLoginUrl()
    {
        $loginUrl = \Uni\Uri::createInstitutionUrl('/login.html', $this);
        if ($this->getDomain()) {
            $loginUrl = \Uni\Uri::create('/login.html');
            $loginUrl->setHost($this->getDomain());
        }
        return $loginUrl;
    }

    /**
     * @param string $subjectCode
     * @return \Tk\Db\Map\Model|\Tk\Db\ModelInterface|SubjectIface|null
     * @throws \Exception
     */
    public function findSubjectByCode($subjectCode)
    {
        return \Uni\Config::getInstance()->getSubjectMapper()->findByCode($subjectCode, $this->getId());
    }

    /**
     * @param int $subjectId
     * @return \Tk\Db\Map\Model|\Tk\Db\ModelInterface|SubjectIface|null
     * @throws \Exception
     */
    public function findSubject($subjectId)
    {
        return \Uni\Config::getInstance()->getSubjectMapper()->find($subjectId);
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
     * @return Institution
     */
    public function setName(string $name): Institution
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * @param string $domain
     * @return Institution
     */
    public function setDomain(string $domain): Institution
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return Institution
     */
    public function setEmail(string $email): Institution
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     * @return Institution
     */
    public function setPhone(string $phone): Institution
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * @return string
     */
    public function getStreet(): string
    {
        return $this->street;
    }

    /**
     * @param string $street
     * @return Institution
     */
    public function setStreet(string $street): Institution
    {
        $this->street = $street;
        return $this;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @param string $city
     * @return Institution
     */
    public function setCity(string $city): Institution
    {
        $this->city = $city;
        return $this;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param string $state
     * @return Institution
     */
    public function setState(string $state): Institution
    {
        $this->state = $state;
        return $this;
    }

    /**
     * @return string
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * @param string $country
     * @return Institution
     */
    public function setCountry(string $country): Institution
    {
        $this->country = $country;
        return $this;
    }

    /**
     * @return string
     */
    public function getPostcode(): string
    {
        return $this->postcode;
    }

    /**
     * @param string $postcode
     * @return Institution
     */
    public function setPostcode(string $postcode): Institution
    {
        $this->postcode = $postcode;
        return $this;
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @param string $address
     * @return Institution
     */
    public function setAddress(string $address): Institution
    {
        $this->address = $address;
        return $this;
    }

    /**
     * @return float
     */
    public function getMapLat(): float
    {
        return $this->mapLat;
    }

    /**
     * @param float $mapLat
     * @return Institution
     */
    public function setMapLat(float $mapLat): Institution
    {
        $this->mapLat = $mapLat;
        return $this;
    }

    /**
     * @return float
     */
    public function getMapLng(): float
    {
        return $this->mapLng;
    }

    /**
     * @param float $mapLng
     * @return Institution
     */
    public function setMapLng(float $mapLng): Institution
    {
        $this->mapLng = $mapLng;
        return $this;
    }

    /**
     * @return float
     */
    public function getMapZoom(): float
    {
        return $this->mapZoom;
    }

    /**
     * @param float $mapZoom
     * @return Institution
     */
    public function setMapZoom(float $mapZoom): Institution
    {
        $this->mapZoom = $mapZoom;
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
     * @return Institution
     */
    public function setDescription(string $description): Institution
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return $this->logo;
    }

    /**
     * @param string $logo
     * @return Institution
     */
    public function setLogo(string $logo): Institution
    {
        $this->logo = $logo;
        return $this;
    }

    /**
     * @return string
     */
    public function getFeature(): string
    {
        return $this->feature;
    }

    /**
     * @param string $feature
     * @return Institution
     */
    public function setFeature(string $feature): Institution
    {
        $this->feature = $feature;
        return $this;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     * @return Institution
     */
    public function setActive(bool $active): Institution
    {
        $this->active = $active;
        return $this;
    }



    /**
     * Implement the validating rules to apply.
     *
     * @throws \Exception
     */
    public function validate()
    {
        $error = array();

        if (!$this->name) {
            $error['name'] = 'Invalid name value';
        }
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $error['email'] = 'Please enter a valid email address';
        }

        // Ensure the domain is unique if set.
        if ($this->domain) {
            //if (!preg_match('/^(?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$/g', $obj->domain)) {
            if (!preg_match(self::REG_DOMAIN, $this->domain)) {
                $error['domain'] = 'Please enter a valid domain name (EG: example.com.au)';
            } else {
                $dup = InstitutionMap::create()->findByDomain($this->domain);
                if ($dup && $dup->getId() != $this->getId()) {
                    $error['domain'] = 'This domain name is already in use';
                }
            }
        }

        return $error;
    }
}
