<?php
namespace Uni\Controller\Mentor;

use Tk\Form;
use Tk\Request;
use Dom\Template;
use \Tk\Form\Field;
use \Tk\Form\Event;
use Tk\Table;
use Uni\Uri;
use Uni\Util\MentorTool;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Import extends \Uni\Controller\AdminEditIface
{

    /**
     * @var null|Table
     */
    protected $mentorTable = null;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->setPageTitle('Mentor Import');
        $this->getConfig()->unsetSubject();

    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {

        $this->setForm($this->getConfig()->createForm('formEdit'));
        $this->getForm()->setRenderer($this->getConfig()->createFormRenderer($this->getForm()));

        $this->getForm()->appendField(Field\File::create('csvFile'))->addCss('tk-fileinput')->setTabGroup('File');
        $this->getForm()->appendField(Field\Textarea::create('csvText'))->setTabGroup('Text');

        $this->getForm()->appendField(Field\Checkbox::create('clear')->setCheckboxLabel('Clear existing mentor lists'));

        $this->getForm()->appendField(Event\Submit::create('import', array($this, 'doSubmit'))->setIcon('fa fa-download'));

        //$this->getForm()->load($this->data->toArray());
        $this->getForm()->execute();

        if ($request->has('updateList')) {
            MentorTool::getInstance()->executeImport();
            Uri::create()->remove('updateList')->redirect();
        }

        //$this->mentorTable = Table::create('mentorImport');
        $this->mentorTable = $this->getConfig()->createTable('mentorImport');
        $this->getConfig()->createTableRenderer($this->mentorTable);
        $this->mentorTable->appendCell(Table\Cell\Text::create('row'))->setLabel('#')->addOnPropertyValue(
            function (\Tk\Table\Cell\Iface $cell, $obj, $value) {
                return $cell->getRow()->getRowId()+1;
            }
        );
        $this->mentorTable->appendCell(Table\Cell\Text::create('mentor_id'));
        $this->mentorTable->appendCell(Table\Cell\Text::create('student_id'));
        $this->mentorTable->appendAction(Table\Action\Csv::create());
        $this->mentorTable->appendAction(Table\Action\Link::createLink('update', Uri::create()->set('updateList'), 'fa fa-refresh'))->setAttr('title', 'Assign mentor associations.');

        $list = MentorTool::getInstance()->find(null, null, \Pdo::FETCH_ASSOC);
        $this->mentorTable->setList($list);

    }

    /**
     * @param Form $form
     * @param Event\Iface $event
     * @throws \Exception
     */
    public function doSubmit($form, $event)
    {
        $values = $form->getValues();

        /** @var Field\File $file */
        $file = $form->getField('csvFile');
        $csv = '';
        if ($file && $file->hasFile()) {
            $csv = trim(file_get_contents($file->getUploadedFile()->getRealPath()));
        } else if (!empty($values['csvText'])) {
            $csv = $values['csvText'];
        }
        if (!$csv) {
            $form->addFieldError('csvFile', 'Please upload a valid CSV file.');
            $form->addFieldError('csvText', 'Please past valid CSV text.');
        }

        if ($form->hasErrors()) {
            return;
        }

        $mentorTool = MentorTool::getInstance();
        $mentorTool->setCsv($csv);
        $mentorTool->executeImport($form->getFieldValue('clear'));


        //\Tk\Alert::addInfo('Mentor CSV list imported: Success['.count($results['success']).'] Fail['.count($results['fail']).']');

        //$this->getSession()->set('csvMentorImportResult', $results);
        //vd($results['error'], $results['remCsv']);
//        if (count($results['error'])) {
//            $err = trim(implode("<br/>\n", $results['error']), "<br/>\n");
//            \Tk\Alert::addWarning('Import Error: <br/>' . $err . '<br/>Remaining CSV: <br/><pre>'.$results['remCsv'].'</pre>');
//        }

        $event->setRedirect(\Tk\Uri::create());
    }

    /**
     * @param string $csv
     * @return array|null
     * @throws \Exception
     * @deprecated: moved to MentorTool
     */
    public function executeCsv($csv)
    {
        $csv = trim($csv);
        if (!$csv) throw new \Tk\Exception('Empty CSV string');
        try {
            $success = array();
            $fail = array();
            $error = array();
            $rowId = -1;

            $temp = fopen('php://temp', 'r+');
            fputs($temp, $csv);
            rewind($temp);

            while ($csvRow = fgetcsv($temp)) {
                $row = array_map('trim', $csvRow);
                if (strtolower($row[0]) == 'mentorid' || strtolower($csvRow[1]) == 'studentid') continue;       // ignore header row
                $rowId++;
                if (!trim(implode('', $row))) continue;


                $staff = null;
                if ((preg_match('/^[0-9]{4,10}$/', $row[0]))) {
                    $staff = $this->getConfig()->getUserMapper()->findByUid($row[0], $this->getConfig()->getInstitutionId());
                } else if (filter_var($row[0], \FILTER_VALIDATE_EMAIL)) {
                    $staff = $this->getConfig()->getUserMapper()->findByEmail($row[0], $this->getConfig()->getInstitutionId());
                } else {
                    $staff = $this->getConfig()->getUserMapper()->findByUsername($row[0], $this->getConfig()->getInstitutionId());
                }
                if ($staff && !$staff->isMentor()) {
                    $staff = null;
                }
                if (!$staff) {
                    $error[md5($row[0])] = 'Staff not found: ' . $row[0];
                }

                $student = null;
                if ((preg_match('/^[0-9]{4,10}$/', $row[1]))) {
                    $student = $this->getConfig()->getUserMapper()->findByUid($row[1], $this->getConfig()->getInstitutionId());
                } else if (filter_var($row[1], \FILTER_VALIDATE_EMAIL)) {
                    $student = $this->getConfig()->getUserMapper()->findByEmail($row[1], $this->getConfig()->getInstitutionId());
                } else {
                    $student = $this->getConfig()->getUserMapper()->findByUsername($row[1], $this->getConfig()->getInstitutionId());
                }
                if ($student && !$student->isStudent()) {
                    $student = null;
                }
                if (!$student) {
                    $error[md5($row[1])] = 'Student not found: ' . $row[1];
                }

                if (!$staff || !$student) {
                    $fail[$rowId] = $row;
                    continue;
                }

                $this->getConfig()->getUserMapper()->addMentor($staff->getId(), $student->getId());
                $success[$rowId] = $row;

            }
            fclose($temp);

            $remCsv = '';
            foreach ($fail as $row) {
                $remCsv .= implode(',', $row) . "\n";
            }
            return array('success' => $success, 'fail' => $fail, 'error' => $error, 'csv' => $csv, 'remCsv' => $remCsv, 'total' => $rowId+1);
        } catch (\Exception $e) {
            \Tk\Log::error($e->__toString());
        }
    }

    /**
     * @return Template
     */
    public function show()
    {
        $template = parent::show();

        $template->appendTemplate('panel', $this->getForm()->show());

        if ($this->mentorTable) {
            $template->appendTemplate('right-panel-01', $this->mentorTable->getRenderer()->show());
        }

        return $template;
    }

    /**
     * DomTemplate magic method
     *
     * @return Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML

<div class="row">
  <div class="col-8" var="left-panel">
    <div class="tk-panel" data-panel-title="Example CSV Format" data-panel-icon="fa fa-info" var="info">
<pre style="border: 1px solid #CCC; padding: 5px;background: #EFEFEF;">mentorId, studentId  // (optional)
<b>115254,114241242</b>
username,114241242
email@example.com,114241242
</pre>
    <p>
      Both fields can be either staff/student numbers, emails, or username`s.<br/>
      Recommended: Use the Staff Number for the mentorId and the Student Number for the studentId
    </p>
    </div>
    <div class="tk-panel" data-panel-icon="fa fa-users" var="panel"></div>
  </div>
  <div class="col-4" var="right-panel">

    <div class="tk-panel" data-panel-title="Current Unassociated Mentor List" data-panel-icon="fa fa-group" var="right-panel-01">
      <p><small>
        This is the current unassociated mentor lookup list. As users log in associations are updated then removed from this list.<br/>
        NOTE: You may not see the student on your mentor list until they log in at least once.
      </small></p>
    </div>
  </div>
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }


}
