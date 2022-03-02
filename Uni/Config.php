<?php
namespace Uni;

use Bs\Db\Status;
use Bs\Db\StatusMap;
use Tk\Db\ModelInterface;
use Uni\Db\Course;
use Uni\Db\Permission;
use Uni\Db\User;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Michael Mifsud
 */
class Config extends \Bs\Config
{

    const SID_SUBJECT = 'last.subjectId';


    /**
     *
     */
    protected function init()
    {
        parent::init();
        $this->set('system.lib.uni.path', $this['system.vendor.path'] . '/ttek/tk-uni');
    }

    /**
     * Load the site route config files
     */
    public function loadConfig()
    {
        include($this->getLibBasePath() . '/config/application.php');
        include($this->getLibUniPath() . '/config/application.php');
        $this->loadAppConfig();
    }

    /**
     * Load the site route config files
     */
    public function loadRoutes()
    {
        include($this->getLibBasePath() . '/config/routes.php');
        include($this->getLibUniPath() . '/config/routes.php');
        $this->loadAppRoutes();
    }


    /**
     * @return string
     */
    public function getLibUniUrl()
    {
        return $this->getSiteUrl() . rtrim($this->get('system.lib.uni.path'), '/');
    }

    /**
     * @return string
     */
    public function getLibUniPath()
    {
        return $this->getSitePath() . rtrim($this->get('system.lib.uni.path'), '/');
    }

    /**
     * Get the Institution object for the logged in user
     *
     * @return null|Db\Institution|Db\InstitutionIface
     */
    public function getInstitution()
    {
        // TODO: this needs to be refactored and made simpler and as getSubject()
        if (!$this->get('institution')) {
            $obj = null;
            try {
                if ($this->getAuthUser()) {
                    $obj = $this->getAuthUser()->getInstitution();          // TODO: This should be all we need???
                    // TODO: In cases of public pages there should be an institution url that
                    // TODO: uses the domain or the has path of that institution....
                    // OH! What about Client with institution_id = 0
                } else if ($this->getRequest()->has('subjectId')) {
                    //\TK\Log::warning('This code should not be reached ever???');
                    /** @var Db\Subject $subject */
                    try {
                        $subject = $this->getSubjectMapper()->find($this->getRequest()->has('subjectId'));
                        if ($subject) $obj = $subject->getInstitution();
                    } catch (\Exception $e) {
                        \Tk\Log::error($e->__toString());
                    }
                } else {
                    $obj = $this->getInstitutionMapper()->findByDomain(\Tk\Uri::create()->getHost());
                }
                if (!$obj && $this->getInstitutionMapper()->findActive()->count() == 1) {
                    $obj = $this->getInstitutionMapper()->findActive()->current();
                }
            } catch (\Exception $e) { \Tk\Log::error($e->__toString()); }
            $this->set('institution', $obj);
        }
        return $this->get('institution');
    }

    /**
     * @return int
     */
    public function getInstitutionId()
    {
        $institutionId = 0;
        if ($this->getInstitution()) {
            $institutionId = $this->getInstitution()->getId();
        }
        return (int)$institutionId;
    }

    /**
     * Get the current course, or null if no course selected
     *
     * @return \Uni\Db\CourseIface|\Uni\Db\Course|\App\Db\Course
     */
    public function getCourse()
    {
        if (!$this->get('course')) {
            if ($this->getSubject()) {
                $this->set('course', $this->getSubject()->getCourse());
            } else if ($this->getRequest()->has('courseId')) {
                /** @var Course $course */
                try {
                    $course = $this->getCourseMapper()->find($this->getRequest()->get('courseId'));
                    $this->set('course', $course);
                } catch (\Exception $e) {}
            }
        }
        return $this->get('course');
    }

    /**
     * Get the current course ID or 0 if no course selected
     *
     * @return int
     */
    public function getCourseId()
    {
        $courseId = 0;
        if ($this->getCourse()) {
            $courseId = $this->getCourse()->getId();
        }
        return (int)$courseId;

    }

    /**
     * If the the current page is a subject page this wi;ll return the subject object
     * based on the subject code in the URI: /staff/VETS50001_2014_SM1/index.html
     *
     * @return null|Db\Subject|Db\SubjectIface
     */
    public function getSubject()
    {
        // TODO: this needs to be refactored and made simpler same as getInstitution()
        if (!$this->get('subject') && $this->getAuthUser()) {
            try {
                $subject = null;
                if ($this->getInstitution() && $this->getRequest()->attributes->get('subjectCode')) {
                    $subjectCode = strip_tags(trim($this->getRequest()->attributes->get('subjectCode')));
                    $subject = $this->getInstitution()->findSubjectByCode($subjectCode);
                } else if ($this->getRequest()->has('subjectId')) {
                    /** @var Db\Subject $c */
                    $c = $this->getSubjectMapper()->find($this->getRequest()->get('subjectId'));
                    if ($c && $this->getInstitution() && $c->getInstitutionId() == $this->getInstitution()->getId()) {
                        $subject = $c;
                    }
                }
                if (!$subject && $this->getSession()->has('lti.subjectId')) { // Check for an LTI default subject selection
                    $subject = $this->getSubjectMapper()->find(self::getSession()->get('lti.subjectId'));
                }
                $route = $this->getRequest()->attributes->get('_route');
                $routePath = $this->getRouteCollection()->get($route)->getPath();
                // TODO: this is kinda odd and does not make sense to have the rout path if using the session (code need optimising)
                if (!$subject && $this->getSession()->has(self::SID_SUBJECT) && strpos($routePath, '/{subjectCode}') !== false) {
                    $subject = $this->getSubjectMapper()->find(self::getSession()->get(self::SID_SUBJECT));
                }
                $this->set('subject', $subject);
                if ($subject) {
                    $this->getSession()->set(self::SID_SUBJECT, $subject->getId());
                }
            } catch (\Exception $e) {
                \Tk\Log::error($e->__toString());
            }
        }

        return $this->get('subject');
    }

    /**
     * @return int
     */
    public function getSubjectId()
    {
        $sid = 0;
        if ($this->getSubject()) {
            $sid = $this->getSubject()->getId();
        }
        return (int)$sid;
    }

    /**
     * unset the subject from the session
     */
    public function unsetSubject()
    {
        $this->getSession()->remove(self::SID_SUBJECT);
        $this->remove('subject');
    }

    /**
     * @param bool $skip
     * @return null|\Tk\Db\Map\Model|Db\SubjectIface
     */
    public function getLastCreatedSubject($skip = false)
    {
        $subject = null;
        try {
            $list = $this->getSubjectMapper()->findFiltered(array('institutionId' => $this->getInstitutionId()),
                \Tk\Db\Tool::create('created DESC', 2));
            $subject = $list->get(0);
            if ($skip) $subject = $list->get(1);
        } catch (\Exception $e) {}
        return $subject;
    }


    /**
     * @param null|\Uni\Db\Subject $subject
     * @return $this
     */
    public function resetCrumbs($subject = null)
    {
        if ($this->getCrumbs()) {
            $this->getCrumbs()->reset();
            if ($subject) {
                $this->getCrumbs()->addCrumb($subject->getName(), \Uni\Uri::createSubjectUrl('/index.html', $subject));
            }
        }
        return $this;
    }

    /**
     * @return \Tk\Crumbs
     */
    public function getCrumbs()
    {
        $crumbs = parent::getCrumbs();
        if ($this->isLti()) {
            $list = $crumbs->getList();
            if (isset($list[$crumbs->getHomeTitle()])) {
                unset($list[$crumbs->getHomeTitle()]);
                $crumbs->setList($list);
            }
        }
        return $this->get('crumbs');
    }

    /**
     * This function returns true if the url is one that uses the {subjectCode}
     *
     * @return bool
     * @todo: fix this as stoin this value within the config object is senseless as false is a valid value
     */
    public function isSubjectUrl()
    {
        if (!$this->get('is.subject.url')) {
            $b = false;
            /** @var \Tk\Routing\Route $route */
            $route = $this->getRouteCollection()->get($this->getRequest()->attributes->get('_route'));
            if ($route) {
                $vars = $route->compile()->getPathVariables();
                $b = in_array('subjectCode', $vars);
            }
            $this->set('is.subject.url', $b);
        }
        return $this->get('is.subject.url');
    }

    /**
     * @return boolean
     */
    public function isMentorUrl()
    {
        $url = \Tk\Uri::create();
        if ($url->basename() == 'mentorImport.html' || $url->basename() == 'mentorList.html') return false;
        return (bool)preg_match('|(staff/mentor)|', $url->getRelativePath());
    }

    /**
     * A helper method to create an instance of an Auth adapter
     *
     * @param array $submittedData
     * @return \Tk\Auth\Adapter\Iface
     * @throws \Exception
     */
    public function getAuthDbTableAdapter($submittedData = array())
    {
        $adapter = new \Uni\Auth\Adapter\DbTable(
            $this->getDb(),
            \Tk\Db\Map\Mapper::$DB_PREFIX . str_replace(\Tk\Db\Map\Mapper::$DB_PREFIX, '', $this['system.auth.dbtable.tableName']),
            $this['system.auth.dbtable.usernameColumn'],
            $this['system.auth.dbtable.passwordColumn']);

        if (isset($submittedData['instHash'])) {
            $institution = $this->getInstitutionMapper()->findByHash($submittedData['instHash']);
            $adapter->setInstitution($institution);
        }
        $adapter->setHashCallback(array($this, 'hashPassword'));
        $adapter->replace($submittedData);
        return $adapter;
    }

    /**
     * @return string
     */
    public function makePageTitle()
    {
        $replace = array('admin-', 'client-', 'staff-', 'student-', '-base');
        /** @var \Tk\Request $request */
        $routeName = $this->getRequest()->attributes->get('_route');
        if ($routeName) {
            $routeName = str_replace($replace, '', $routeName);
            return ucwords(trim(str_replace('-', ' ', $routeName)));
        }
        return '';
    }
//
//    /**
//     * @param string $formId
//     * @return \Tk\Form
//     */
//    public function createForm($formId)
//    {
//        $form = \Tk\Form::create($formId);
//        $form->setDispatcher($this->getEventDispatcher());
//        // TODO: check this does not cause issues
//        $form->setRenderer($this->createFormRenderer($form));
//        return $form;
//    }
//
//    /**
//     * @param \Tk\Form $form
//     * @return \Tk\Form\Renderer\Dom|\Tk\Form\Renderer\Iface
//     */
//    public function createFormRenderer($form)
//    {
//        $obj = \Tk\Form\Renderer\Dom::create($form);
//        //$obj = \Tk\Form\Renderer\DomRenderer::create($form);
//        $obj->setFieldGroupRenderer($this->getFormFieldGroupRenderer($form));
//        $obj->getLayout()->setDefaultCol('col');
//        return $obj;
//    }



    // ------------------------------- Commonly Overridden ---------------------------------------


    /**
     * @param int $id
     * @return \Uni\Db\InstitutionIface|\Tk\Db\ModelInterface|\Uni\Db\Institution
     * @deprecated Use the getInstitutionMapper() method
     */
    public function findInstitution($id)
    {
        try {
            return $this->getInstitutionMapper()->find($id);
        } catch (\Exception $e) {}
        return null;
    }

    /**
     * @param int $id
     * @return null|\Tk\Db\Map\Model|\Tk\Db\ModelInterface|\Uni\Db\Subject
     * @deprecated Use the getSubjectMapper() method
     */
    public function findSubject($id)
    {
        try {
            return $this->getSubjectMapper()->find($id);
        } catch (\Exception $e) {}
        return null;
    }

    /**
     * @param int $id
     * @return null|\Tk\Db\Map\Model|\Tk\Db\ModelInterface|Db\User|Db\UserIface
     * @throws \Exception
     * @deprecated Use the getUserMapper() method
     */
    public function findUser($id)
    {
        return $this->getUserMapper()->find($id);
    }

    /**
     * Get the user identity used by the auth object
     *
     * @param Db\User $user
     * @return int|string
     */
    public function getUserIdentity($user)
    {
        return $user->getId();
    }

    /**
     * @return Db\InstitutionMap
     */
    public function getInstitutionMapper()
    {
        if (!$this->get('obj.mapper.institution')) {
            $this->set('obj.mapper.institution', Db\InstitutionMap::create());
        }
        return $this->get('obj.mapper.institution');
    }

    /**
     * @return Db\Institution|Db\InstitutionIface
     */
    public function createInstitution()
    {
        return new Db\Institution();
    }

    /**
     * @return Db\CourseMap
     */
    public function getCourseMapper()
    {
        if (!$this->get('obj.mapper.course')) {
            $this->set('obj.mapper.course', Db\CourseMap::create());
        }
        return $this->get('obj.mapper.course');
    }

    /**
     * @return Db\Course|Db\CourseIface
     * @throws \Exception
     */
    public function createCourse()
    {
        return new Db\Course();
    }

    /**
     * @return Db\SubjectMap
     */
    public function getSubjectMapper()
    {
        if (!$this->get('obj.mapper.subject')) {
            $this->set('obj.mapper.subject', Db\SubjectMap::create());
        }
        return $this->get('obj.mapper.subject');
    }

    /**
     * @return Db\Subject|Db\SubjectIface
     * @throws \Exception
     */
    public function createSubject()
    {
        return new Db\Subject();
    }

    /**
     * @return Db\UserMap
     */
    public function getUserMapper()
    {
        if (!$this->get('obj.mapper.user')) {
            $this->set('obj.mapper.user', Db\UserMap::create());
        }
        return $this->get('obj.mapper.user');
    }

    /**
     * @return Db\User|Db\UserIface
     */
    public function createUser()
    {
        return new Db\User();
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function canChangePassword()
    {
        //return (!$this->getSession()->has('auth.password.access') || $this->getSession()->get('auth.password.access', true));
        return $this->getSession()->get('auth.password.access', true);
    }

    /**
     * Return the users home|dashboard relative url
     *
     * @param \Uni\Db\UserIface|\Uni\Db\User|null $user
     * @return \Tk\Uri
     */
    public function getUserHomeUrl($user = null)
    {
        if (!$user) $user = $this->getAuthUser();
        if (!$user) return \Uni\Uri::create('/login.html');
        return \Uni\Uri::createHomeUrl('/index.html', $user);
    }

    /**
     * Get the current logged in user
     * @return Db\User|Db\UserIface
     */
    public function getAuthUser()
    {
        return $this->get('user');
    }

    /**
     * Set the current logged in user
     * @param Db\User|Db\UserIface $user
     * @return $this
     */
    public function setAuthUser($user)
    {
        $this->set('user', $user);
        return $this;
    }

    /**
     * @return Permission|null
     */
    public function getPermission()
    {
        return \Uni\Db\Permission::getInstance();
    }



    /**
     * @param \Tk\EventDispatcher\EventDispatcher $dispatcher
     */
    public function setupDispatcher($dispatcher)
    {
        Dispatch::create($dispatcher);
    }

    /**
     * @return \Bs\Listener\AuthHandler
     */
    public function getAuthHandler()
    {
        if (!$this->get('auth.handler')) {
            $this->set('auth.handler', new \Uni\Listener\AuthHandler());
        }
        return $this->get('auth.handler');
    }

    /**
     * @return \Uni\Listener\MasqueradeHandler
     */
    public function getMasqueradeHandler()
    {
        if (!$this->get('auth.masquerade.handler')) {
            $this->set('auth.masquerade.handler', new \Uni\Listener\MasqueradeHandler());
        }
        return $this->get('auth.masquerade.handler');
    }

    /**
     * @return \Bs\Listener\PageTemplateHandler
     */
    public function getPageTemplateHandler()
    {
        if (!$this->get('page.template.handler')) {
            $this->set('page.template.handler', new \Uni\Listener\PageTemplateHandler());
        }
        return $this->get('page.template.handler');
    }

    /**
     * @return \Bs\Listener\InstallHandler
     */
    public function getInstallHandler()
    {
        if (!$this->get('handler.installer')) {
            $this->set('handler.installer', new \Uni\Listener\InstallHandler());
        }
        return $this->get('handler.installer');
    }

    /**
     * Is this request within an LTI session
     *
     * @return bool
     */
    public function isLti()
    {
        return $this->getSession()->get('isLti', false);
//        if ($this->getAuthUser() && $this->getAuthUser()->hasType(User::TYPE_STAFF, User::TYPE_STUDENT)) {
//            return $this->getSession()->get('isLti', false);
//        }
//        $this->getSession()->remove('isLti');
//        return true;
    }

    /**
     * @param string $templatePath
     * @return Page|\Bs\Page
     */
    public function getPage($templatePath = '')
    {
        if (!$this->get('controller.page')) {
            if (($this->isLti() || $this->get('force.lti.template')) && $this->has('template.lti') && $this->isSubjectUrl()) {
                $templatePath = $this->getSitePath() . $this->get('template.lti');
            }
        }
        return parent::getPage($templatePath);
    }

    /**
     * @param string $templatePath (optional)
     * @return Page|null
     */
    public function createPage($templatePath = '')
    {
        return new Page($templatePath);
    }

    /**
     *
     * @param ModelInterface $model
     * @return Status
     */
    public function createStatus($model)
    {
        return \Uni\Util\Status::create($model);
    }

    /**
     * @param string $customDataPath
     * @return array
     */
    public function getElfinderPath($customDataPath = '')
    {
        $dataPath = $this->getDataPath() . $customDataPath;
        $dataUrl = $this->getDataUrl() . $customDataPath;
        if (!$customDataPath) {
            try {
                $institution = $this->getInstitution();
                if ($this->getRequest()->get('institutionId'))
                    $institution = $this->getInstitutionMapper()->find($this->getRequest()->get('institutionId'));
                if ($institution) {
                    $dataPath = $this->getDataPath() . $institution->getDataPath() . '/media';
                    $dataUrl = $this->getDataUrl() . $institution->getDataPath() . '/media';
                }
            } catch (\Exception $e) {
                \Tk\Log::warning($e->getMessage());
            }
        }
        if (!is_dir($dataPath)) {
            mkdir($dataPath, 0777, true);
        }
        if (!is_dir($dataPath . '/.trash/')) {
            mkdir($dataPath . '/.trash/', 0777, true);
        }
        return array($dataPath, $dataUrl);
    }

    /**
     * @return string
     */
    public function getAdminEmailMsg()
    {
        $email = 'your Subject Coordinator';
        if ($this->getInstitution() && $this->getInstitution()->getEmail())
            $email = sprintf('<a href="mailto:%s">%s</a>.', $this->getInstitution()->getEmail(), $this->getInstitution()->getEmail());
        if ($this->getCourse() && $this->getCourse()->getEmail())
            $email = sprintf('<a href="mailto:%s">%s</a>.', $this->getCourse()->getEmail(), $this->getCourse()->getEmail());
        if ($this->getSubject() && $this->getSubject()->getEmail())
            $email = sprintf('<a href="mailto:%s">%s</a>.', $this->getSubject()->getEmail(), $this->getSubject()->getEmail());

        return $email;
    }

}
