<?php
namespace Uni\Controller\Course;

use Bs\Controller\AdminEditIface;
use Dom\Template;
use Tk\Request;
use Tk\Ui\Dialog\AjaxSelect;
use Uni\Db\Permission;
use Uni\Table\User;
use Uni\Uri;

/**
 * TODO: Add Route to routes.php:
 *      $routes->add('course-edit', Route::create('/staff/courseEdit.html', 'Uni\Controller\Course\Edit::doDefault'));
 *
 * @author Mick Mifsud
 * @created 2019-12-05
 * @link http://tropotek.com.au/
 * @license Copyright 2019 Tropotek
 */
class Edit extends AdminEditIface
{

    /**
     * @var \Uni\Db\Course
     */
    protected $course = null;

    /**
     * @var null|\Uni\Table\UserList
     */
    protected $userTable = null;


    /**
     * Iface constructor.
     */
    public function __construct()
    {
        $this->setPageTitle('Course Edit');
    }

    /**
     * @return \Tk\Db\Map\Model|\Tk\Db\ModelInterface|\Uni\Db\Course|null|\App\Db\Course
     * @throws \Exception
     */
    public function getCourse()
    {
        if (!$this->course) {
            $this->course = new \Uni\Db\Course();
            $this->course->setInstitutionId($this->getConfig()->getInstitutionId());
            if ($this->getConfig()->getRequest()->get('courseId')) {
                $this->course = $this->getConfig()->getCourseMapper()->find($this->getConfig()->getRequest()->get('courseId'));
            }
        }
        return $this->course;
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        if (!$this->getAuthUser()->hasPermission(Permission::MANAGE_SUBJECT) && !$this->getAuthUser()->isClient()) {
            \Tk\Alert::addWarning('You do not have permission to edit this resource.');
            $this->getConfig()->getBackUrl()->redirect();
        }
        $this->getCourse();

        $this->setForm(\Uni\Form\Course::create()->setModel($this->getCourse()));
        if (!$this->getCourse()->getId()) {
            $this->getForm()->removeField('update');
        }
        $this->initForm($request);
        $this->getForm()->execute();

        if ($this->getCourse()->getId()) {
            $this->userTable = \Uni\Table\UserList::create();
            $this->userTable->setUserType(\Uni\Db\User::TYPE_STAFF);
            $this->userTable->setEditUrl(\Uni\Uri::createHomeUrl('/staffUserEdit.html'));
            $this->userTable->setAjaxParams(array(
                'institutionId' => $this->getConfig()->getInstitutionId(),
                'active' => 1,
                'permission' => \Uni\Db\Permission::IS_COORDINATOR
            ));
            $this->userTable->setOnSelect(function (AjaxSelect $dialog) {
                /** @var User $user */
                $data = $dialog->getConfig()->getRequest()->all();
                $course = $dialog->getConfig()->getCourseMapper()->find($dialog->getConfig()->getRequest()->get('courseId'));
                $user = $dialog->getConfig()->getUserMapper()->find($data['selectedId']);
                if (!$user) {
                    \Tk\Alert::addWarning('User not found!');
                } else if (!$course) {
                    \Tk\Alert::addWarning('Course not found!');
                } else if (!$dialog->getConfig()->getCourseMapper()->hasUser($course->getId(), $user->getId())) {
                    $dialog->getConfig()->getCourseMapper()->addUser($course->getId(), $user->getId());
                    \Tk\Alert::addSuccess($user->getName() . ' has been linked to this Course.');
                } else {
                    \Tk\Alert::addInfo($user->getName() . ' is already linked to this Course.');
                }
                return Uri::create();
            });

            $this->userTable->init();
            $filter = array(
                'id' => $this->getConfig()->getCourseMapper()->findUsers($this->getCourse()->getId()),
                'permission' => \Uni\Db\Permission::IS_COORDINATOR
            );
            if (count($filter['id']))
                $this->userTable->setList($this->userTable->findList($filter));
        }
    }

    /**
     * @throws \Exception
     */
    public function initActionPanel()
    {
        if ($this->course->getId() && ($this->getAuthUser()->isClient() || $this->getAuthUser()->isStaff())) {
            $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('Subjects',
                \Uni\Uri::createHomeUrl('/subjectManager.html')->set('courseId', $this->course->getId()), 'fa fa-graduation-cap'));
        }
    }

    /**
     * @return \Dom\Template
     * @throws \Exception
     */
    public function show()
    {
        $this->initActionPanel();
        $template = parent::show();

        // Render the form
        $template->appendTemplate('panel', $this->getForm()->show());

        if (!$this->course->getId()) {
            $template->setVisible('right-panel', false);
            $template->removeCss('left-panel', 'col-8')->addCss('left-panel', 'col-12');
        } else {
            if ($this->userTable)
                $template->appendTemplate('right-panel-01', $this->userTable->show());
        }

        return $template;
    }

    /**
     * @return Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="row">
  <div class="col-8" var="left-panel">
    <div class="tk-panel" data-panel-icon="fa fa-book" var="panel"></div>
  </div>
  <div class="col-4" var="right-panel">
    <div class="tk-panel" data-panel-title="Staff" data-panel-icon="fa fa-group" var="right-panel-01"></div>
  </div>
</div>
HTML;
        return \Dom\Loader::load($xhtml);
    }

}