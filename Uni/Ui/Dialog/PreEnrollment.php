<?php
namespace Uni\Ui\Dialog;

use Tk\Request;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class PreEnrollment extends \Tk\Ui\Dialog\Dialog
{

    /**
     * @var \Uni\Db\Subject
     */
    protected $subject = null;

    /**
     * @param $title
     */
    public function __construct($title)
    {
        parent::__construct($title);
        $this->getButtonList()->append(\Tk\Ui\Button::createButton('Enroll')->setAttr('id', $this->getEnrollButtonId())->addCss('btn-primary'));
        $this->addCss('tk-dialog-pre-enrollment');
    }

    /**
     * @return string
     */
    public function getEnrollButtonId()
    {
        return $this->getId().'-Enroll';
    }

    /**
     * Process the enrolments as submitted from the dialog
     *
     * @throws \Exception
     */
    public function execute()
    {
        $request = $this->getRequest();
        $config = $this->getConfig();

        $this->subject = $this->getConfig()->getSubject();
        if ($request->get('subjectId'))
            $this->subject = $config->getSubjectMapper()->find($request->get('subjectId'));
        if (!$this->subject) {
            throw new \Tk\Exception('Invalid subject details');
        }
        
        if (!$request->has('enroll')) {
            return;
        }

        $list = array();
        // Check file list
        if ($request->getUploadedFile('csvFile') && $request->getUploadedFile('csvFile')->getError() == \UPLOAD_ERR_OK) {
            $file = $request->getUploadedFile('csvFile');
            if (($handle = fopen($file->getPathname(), 'r')) !== FALSE) {
                $list = $this->processCsv($handle);
            }
        } else if($request->get('csvList')) {
            // Check textarea list
            $csvList = $request->get('csvList');
            if (($handle = fopen('data://text/plain,'.$csvList, 'r')) !== FALSE) {
                $list = $this->processCsv($handle);
            }
        }
        
        $error = array();
        $success = array();
        $info = array();
        foreach ($list as $i => $arr) {
            $uid = '';          // TODO: Make this the primary search param
            $username = '';     // TODO: Make this the secondary search param
            $email = '';        // TODO: We need to keep this for LTI integrations

            foreach ($arr as $val) {
                if ((preg_match('/^[0-9]{4,10}$/', $val))) {
                    $uid = $val;
                } else if (filter_var($val, \FILTER_VALIDATE_EMAIL)) {
                    $email = $val;
                } else {
                    $username = $val;
                }
            }
            /*
            if (isset($arr['uid']))
                $uid = trim(strip_tags($arr['uid']));
            if (isset($arr['username']))
                $username = trim(strtolower(strip_tags($arr['username'])));
            if (isset($arr['email']))
                $email = trim(strip_tags($arr['email']));
            */

            if (!$uid && !$username) {
                continue;
            }

            // Add users if found
            if (!$config->getSubjectMapper()->hasPreEnrollment($this->subject->getId(), $email, $uid, $username)) {
                $config->getSubjectMapper()->addPreEnrollment($this->subject->getId(), $email, $uid, $username);

                $user = $config->getUserMapper()->findByEmail($email, $this->subject->institutionId);
                if (!$user) $user = $config->getUserMapper()->findByUsername($username, $this->subject->institutionId);
                if (!$user) $user = $config->getUserMapper()->findFiltered(array('institutionId' => $this->subject->institutionId, 'uid' => $uid))->current();
                if ($user) {
                    if ($user->isStudent()) {
                        $config->getSubjectMapper()->addUser($this->subject->getId(), $user->getId());
                        $user->setActive(true);
                    } else if ($user->isStaff()) {
                        $config->getCourseMapper()->addUser($this->subject->getCourseId(), $user->getId());
                        $user->setActive(true);
                    }
                    $user->save();
                }
                $success[] = $i . ' - Added ' . $email . ' to the subject enrollment list';
            } else {
                $info[] = $i . ' - User ' . $email . ' already enrolled, nothing done.';
            }
        }
        if (count($info)) {
            \Tk\Alert::addInfo(count($info) . ' records already enrolled and ignored.');
        }
        if (count($success)) {
            \Tk\Alert::addSuccess(count($success) . ' records successfully added to the enrollment list.');
        }
        if (count($error)) {
            \Tk\Alert::addError(count($error) . ' records contained errors.');
        }

        $request->getTkUri()->redirect();
    }


    /**
     * @param $stream
     * @return array
     */
    private function processCsv($stream)
    {
        $list = array();
        $row = 1;
        while (($data = fgetcsv($stream, 1000, ',')) !== FALSE) {
            $num = count($data);
            $list[$row] = array('email' => '', 'uid' => '', 'username' => '');
            // TODO: this line is not working, I think we need to remove whitespaces before checking??
            if (in_array('uid', $data) || in_array('email', $data) || in_array('username', $data)) continue;
            for ($c=0; $c < $num; $c++) {
                if (filter_var($data[$c], FILTER_VALIDATE_EMAIL)) {
                    $list[$row]['email'] = $data[$c];
                } else if (preg_match('/^[0-9]+$/', $data[$c])) {
                    $list[$row]['uid'] = $data[$c];
                } else if (!preg_match('/.+@.+/', $data[$c])) {
                    //$list[$row]['username'] = $data[$c];
                    $list[$row]['username'] = strtolower($data[$c]);
                }
            }
            $row++;
        }
        fclose($stream);
        return $list;
    }


    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $this->setAttr('data-enroll-btn', '#'.$this->getEnrollButtonId());
        $this->setAttr('data-enroll-form', '#addEnrollmentForm');

        $this->setContent($this->makeBodyHtml());
        $template = parent::show();
        $js = <<<JS
jQuery(function($) {
  
  $('.tk-dialog-pre-enrollment').each(function () {
    var dialog = $(this);
    var enrollBtn = $(dialog.data('enrollBtn'));
    var enrollForm = $(dialog.data('enrollForm'));
    enrollBtn.on('click', function(e) {
      //$('<input type="submit" name="enroll" value="Enroll" />').hide().appendTo(enrollForm).click().remove();
      var btn = enrollForm.find('input[name=enroll]');
      if (!btn.length) {
        btn = $('<input type="submit" name="enroll" value="Enroll" />');
        btn.hide().appendTo(enrollForm);
      }
      btn.click();
    });
  });
  
});
JS;
        $template->appendJs($js);

        return $template;
    }

    /**
     * DomTemplate magic method
     *
     * @return string
     */
    public function makeBodyHtml()
    {
        $url = htmlentities(\Uni\Config::getInstance()->getRequest()->getTkUri()->set('subjectId', $this->subject->getId())->toString());
        $xhtml = <<<HTML
<form id="addEnrollmentForm" method="POST" action="$url" enctype="multipart/form-data">

  <div class="form-group form-group-sm">
    <label for="fid-csvFile" class="control-label">* Csv File:</label>
    <div>
    <input type="file" class="form-control tk-fileinput" id="fid-csvFile" name="csvFile"/>
    </div>
  </div>
  <p>OR</p>
  <div class="form-group form-group-sm">
    <label for="fid-csvList" class="control-label">* CSV List:</label>
    <textarea class="form-control" id="fid-csvList" name="csvList" style="height: 90px;"></textarea>
  </div>
  
  <p>Valid CSV formats are:</p>
  <p>Preferred Method:</p>
  <p><pre>
uid,username,email
123456,student1,student1@uni.edu.au
123457,staff1,staff2@uni.edu.au
</pre></p>

  <p><small>NOTE: The uid and username are preffered for LDAP authentication and the email being required for LTI authentication.</small></p>
    
</form>
HTML;
        return $xhtml;
    }
}
