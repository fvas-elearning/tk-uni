<?php
namespace Uni\Ui\Dialog;

use Tk\Request;
use Uni\Db\User;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class ImportStudents extends \Tk\Ui\Dialog\Dialog
{

    /**
     * @var \Uni\Db\Subject
     */
    protected $subject = null;

    protected $headers = [
        'uid',
        'title',
        'nameFirst',
        'namePreferred',
        'nameLast',
        'email',
        'username'
    ];


    /**
     * @param $title
     */
    public function __construct($title)
    {
        parent::__construct($title);
        $this->getButtonList()->append(\Tk\Ui\Button::createButton('Import')->setAttr('id', $this->getImportButtonId())->addCss('btn-primary'));
        $this->addCss('tk-dialog-student-import');
    }

    /**
     * @return string
     */
    public function getImportButtonId()
    {
        return $this->getId().'-Import';
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

        if (!$request->has('import')) {
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
            $student = new User();
            $student->setInstitutionId($this->getConfig()->getInstitutionId());
            $student->setType(User::TYPE_STUDENT);
            $this->getConfig()->getUserMapper()->getFormMap()->loadObject($arr, $student);

            // Find any existing student records.
            $find = $this->getConfig()->getUserMapper()->findFiltered([
                'institutionId' => $this->getConfig()->getInstitutionId(),
                'type' => User::TYPE_STUDENT,
                'uid' => $student->getUid()
            ])->current();
            if (!$find) {
                $find = $this->getConfig()->getUserMapper()->findFiltered([
                    'institutionId' => $this->getConfig()->getInstitutionId(),
                    'type' => User::TYPE_STUDENT,
                    'username' => $student->getUid()
                ])->current();
            }
            if (!$find) {
                $find = $this->getConfig()->getUserMapper()->findFiltered([
                    'institutionId' => $this->getConfig()->getInstitutionId(),
                    'type' => User::TYPE_STUDENT,
                    'email' => $student->getEmail()
                ])->current();
            }
            if ($find) {
                $student = $find;
                $info[] = $i . ' - User ' . $student->getEmail() . ' already enrolled, update only.';
            } else {
                $success[] = $i . ' - Added ' . $student->getEmail() . ' to the subject enrollment list';
            }

            if (!empty($arr['namePreferred'])) {
                $student->setNotes(
                    'nameFirst: ' . $arr['nameFirst'] . "\n" .
                    'namePreferred: ' . $arr['namePreferred']
                );
                $student->setNameFirst($arr['namePreferred']);
            }
            $student->setActive(true);
            $student->save();

            // Add student to this subject
            $config->getSubjectMapper()->addUser($this->subject->getId(), $student->getId());

        }
        if (count($info)) {
            \Tk\Alert::addInfo(count($info) . ' records already exist.');
        }
        if (count($success)) {
            \Tk\Alert::addSuccess(count($success) . ' records successfully added to the subject.');
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
        $length = count($this->headers);
        $list = array();
        $row = 1;
        while (($data = fgetcsv($stream, 1000, ',')) !== FALSE) {
            $num = count($data);
            if ($num != $length) continue;
            // Check we are not in a header row
            $inHead = false;
            foreach ($this->headers as $k) {
                if (in_array($k, $data)) {
                    $inHead = true;
                    break;
                }
            }
            if ($inHead) continue;
            $list[$row] = array_combine($this->headers, $data);
            $list[$row]['username'] = strtolower($list[$row]['username']);

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
        $this->setAttr('data-import-btn', '#'.$this->getImportButtonId());
        $this->setAttr('data-import-form', '#addImportForm');

        $this->setContent($this->makeBodyHtml());
        $template = parent::show();
        $js = <<<JS
jQuery(function($) {
  
  $('.tk-dialog-student-import').each(function () {
    var dialog = $(this);
    var importBtn = $(dialog.data('importBtn'));
    var importForm = $(dialog.data('importForm'));
    importBtn.on('click', function(e) {
      var btn = importForm.find('input[name=import]');
      if (!btn.length) {
        btn = $('<input type="submit" name="import" value="Import" />');
        btn.hide().appendTo(importForm);
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
<form id="addImportForm" method="POST" action="$url" enctype="multipart/form-data">

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
  
  <p>Valid CSV format is:</p>
  <p><pre>
uid,title,nameFirst,namePreferred,nameLast,email,username
123456,Ms,First Name,Preffered Name,Last Name,student1@uni.edu.au,student1
</pre></p>
    
</form>
HTML;
        return $xhtml;
    }
}
