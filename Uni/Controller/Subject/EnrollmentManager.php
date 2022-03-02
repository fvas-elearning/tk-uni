<?php
namespace Uni\Controller\Subject;

use Dom\Loader;
use Exception;
use Tk\Alert;
use Tk\Request;
use Dom\Template;
use Tk\Ui\Link;
use Uni\Config;
use Uni\Controller\AdminIface;
use Uni\Db\Permission;
use Uni\Db\Subject;
use Uni\Db\SubjectIface;
use Uni\Db\User;
use Uni\Table\Enrolled;
use Uni\Table\PreEnrollment;
use Tk\Ui\Dialog\AjaxSelect;
use Uni\Uri;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class EnrollmentManager extends AdminIface
{

    protected $filter = null;

    /**
     * @var Enrolled
     */
    protected $enrolledTable = null;

    /**
     * @var PreEnrollment
     */
    protected $preEnrolTable = null;

    /**
     * @var \Uni\Ui\Dialog\PreEnrollment
     */
    protected $preEnrolDialog = null;

    /**
     * @var AjaxSelect
     */
    protected $enrolStudentDialog = null;

    /**
     * @var AjaxSelect
     */
    protected $enrolClassDialog = null;

    /**
     * @var null
     */
    protected $enrolledAjaxDialogParams = null;


    /**
     * EnrollmentManager constructor.
     */
    public function __construct()
    {
        $this->setPageTitle('Subject Enrollments');
    }

    /**
     * @param \Tk\Request $request
     * @param string $subjectCode
     * @throws Exception
     */
    public function doSubject(\Tk\Request $request, $subjectCode)
    {
        $this->doDefault($request);
    }

    /**
     * @param Request $request
     * @throws Exception
     */
    public function doDefault(Request $request)
    {
        if (!$this->getAuthUser()->hasPermission(Permission::MANAGE_SUBJECT)) {
            \Tk\Alert::addWarning('You do not have permission to edit this resource.');
            $this->getConfig()->getBackUrl()->redirect();
        }

        $subject = $this->getSubject();
        if (!$subject) {
            throw new \Tk\Exception('Invalid subject details');
        }
        $this->setPageTitle("`" . $subject->name . '` Enrolments');

        // Pre-Enroll Csv import dialog
        $this->preEnrolDialog = new \Uni\Ui\Dialog\PreEnrollment('Pre-Enroll User');
        $this->preEnrolDialog->execute();


        $filter = array();
        $filter['institutionId'] = $subject->institutionId;
        $filter['exclude'] = $subject->getId();
        if ($this->filter) {
            $filter = $this->filter;
        }

        $this->enrolClassDialog = new AjaxSelect('Enroll Class', Uri::create('/ajax/subject/findFiltered.html'));
        $this->enrolClassDialog->setAjaxParams($filter);
        $this->enrolClassDialog->setNotes('Select the subject to enroll all the students into.');
        $this->enrolClassDialog->addOnSelect(function ($dialog) use ($subject) {
            /** @var AjaxSelect $dialog */
            /** @var Subject $destSubject */
            $config = Config::getInstance();
            $data = $dialog->getRequest()->all();
            $destSubject = $config->getSubjectMapper()->find($data['selectedId']);
            if (!$destSubject)
                throw new \Tk\Exception('Invalid destination subject');
            $filter = array(
                'institutionId' => $subject->institutionId,
                'subjectId' => $subject->getId()
            );
            $userList = $config->getUserMapper()->findFiltered($filter);
            $i = 0;
            /** @var \Uni\Db\User $user */
            foreach ($userList as $user) {
                if (!$user->isEnrolled($destSubject->getId()) && $user->isStudent()) {
                    $config->getSubjectMapper()->addUser($destSubject->getId(), $user->getId());
                    $i++;
                }
            }
            if ($i) {
                Alert::addSuccess('Added ' . $i . ' students to the subject `' . $destSubject->name . '`');
            }
            return Uri::create();
        });
        $this->enrolClassDialog->execute();


        // Enrol A single student dialog
        $filter = array();
        $filter['institutionId'] = $this->getSubject()->institutionId;
        $filter['active'] = '1';
        $filter['type'] = array(User::TYPE_STUDENT, User::TYPE_STAFF);

        $this->enrolStudentDialog = new AjaxSelect('Enrol Student', Uri::create('/ajax/user/findFiltered.html'));
        $this->enrolStudentDialog->setAjaxParams($filter);
        //$this->enrolStudentDialog->setNotes('');
        $this->enrolStudentDialog->addOnSelect(function ($dialog) {
            /** @var User $user */
            $config = Config::getInstance();
            $data = $config->$this->getRequest()->all();
            $subject = $config->getSubject();
            $user = $config->getUserMapper()->find($data['selectedId'], $subject->getInstitutionId());
            if (!$user)
                throw new \Tk\Exception('Invalid user selected');
            if (!$user || !$user->isStudent()) {
                Alert::addWarning('Invalid user.');
            } else {
                if (!$user->isEnrolled($subject->getId())) {
                    $config->getSubjectMapper()->addUser($subject->getId(), $user->getId());
                    Alert::addSuccess($user->getName() . ' added to the subject ' . $subject->getName());
                } else {
                    Alert::addWarning($user->getName() . ' already enrolled in the subject ' . $subject->getName());
                }
            }
            return Uri::create();
        });
        $this->enrolStudentDialog->execute();


        // Enrolled Table
        $this->enrolledTable = Enrolled::create();
        $this->enrolledTable->setAjaxDialogParams($this->enrolledAjaxDialogParams);
        $this->enrolledTable->init();
        $filter = array('subjectId' => $this->getSubject()->getId());
        $filter['type'] = array(User::TYPE_STAFF, User::TYPE_STUDENT);
        $this->enrolledTable->setList($this->enrolledTable->findList($filter));

        // Pre-Enrol table
        $this->preEnrolTable = PreEnrollment::create()->init();
        $this->preEnrolTable->prependAction(\Tk\Table\Action\Link::createLink('Pre-Enrol', '#', 'fa fa-plus')
            ->setAttr('data-toggle', 'modal')
            ->setAttr('data-target', '#'.$this->preEnrolDialog->getId()));
        $list = $this->preEnrolTable->findList(array('subjectId' => $this->getSubject()->getId()));
        $this->preEnrolTable->setList($list);

    }

    /**
     * @return null|Subject|SubjectIface
     */
    public function getSubject()
    {
        return $this->getConfig()->getSubject();
    }


    /**
     * @param null|array $ajaxDialogParams
     * @return EnrollmentManager
     */
    public function setEnrolledAjaxDialogParams($ajaxDialogParams)
    {
        $this->enrolledAjaxDialogParams = $ajaxDialogParams;
        return $this;
    }

    public function initActionPanel()
    {
//        $this->getActionPanel()->append(Link::createBtn('Enrol','#', 'fa fa-user-plus'))
//            ->setAttr('data-toggle', 'modal')->setAttr('data-target', '#'.$this->enrolStudentDialog->getId())
//            ->setAttr('title', 'Add an existing student to this subject');

        $this->getActionPanel()->append(Link::createBtn('Pre-Enrol','#', 'fa fa-user-plus'))
            ->setAttr('data-toggle', 'modal')->setAttr('data-target', '#'.$this->preEnrolDialog->getId())
            ->setAttr('title', 'Pre-Enrol a non-existing student, they will automatically be enrolled on login');

        $this->getActionPanel()->append(Link::createBtn('Enrol Into...', '#', 'fa fa-copy'))
            ->setAttr('data-toggle', 'modal')->setAttr('data-target', '#'.$this->enrolClassDialog->getId())
            ->setAttr('title', 'Copy this enrollment list into another subject.');
    }

    /**
     * @return \Dom\Template
     * @throws Exception
     */
    public function show()
    {
        $this->initActionPanel();
        $template = parent::show();

        // Enrolled Table
        $template->appendTemplate('enrolledTable', $this->enrolledTable->getRenderer()->show());
        // Pre Enrollment Table
        $template->appendTemplate('pendingTable', $this->preEnrolTable->getRenderer()->show());


        // Enrolled Dialog
        $template->appendTemplate('enrollment', $this->enrolStudentDialog->show());

        // Pre Enrolment Dialog
        $template->appendTemplate('enrollment', $this->preEnrolDialog->show());

        // Enrolment Copy Dialog
        $template->appendTemplate('enrollment', $this->enrolClassDialog->show());


        
        $js = <<<JS
jQuery(function($) {
  
  $('tr[data-user-id]').hover(
    function(e) {
      var userId = $(this).attr('data-user-id');
      $('tr[data-user-id="'+userId+'"]').addClass('tk-hover');
    },
    function(e) {
      var userId = $(this).attr('data-user-id');
      $('tr[data-user-id="'+userId+'"]').removeClass('tk-hover');
    }
  );
  
});
JS;
        $template->appendJs($js);

        $css = <<<CSS
.tk-table .tk-pending-users tr.enrolled td {
  color: #999;
}
.tk-table tr.tk-hover td {
  background-color: #7796b4;
}
CSS;
        $template->appendCss($css);

        return $template;
    }

    /**
     * @return Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div var="enrollment">

  <div class="row">
    <div class="col-md-8">

      <div class="tk-panel" data-panel-title="Enrolled" data-panel-icon="fa fa-users" var="enrolledTable"></div>

    </div>
    <div class="col-md-4">
    
      <div class="tk-panel" data-panel-title="Pre-Enrollment" data-panel-icon="fa fa-users" var="pendingTable">
          <div>
            <p>
              Pre-enrolled users will automatically be enrolled into this subject on their next login.
            </p>
            <p class="small">Note: Deleting a user from this list will <b>not</b> delete them from the `Enrolled` list.</p>
          </div>
      </div>

    </div>
  </div>

</div>
HTML;

        return Loader::load($xhtml);
    }
    
}







