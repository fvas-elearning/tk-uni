<?php
namespace Uni\Listener;


use Tk\ConfigTrait;
use Uni\Uri;

/**
 * This object helps cleanup the structure of the controller code
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class PageTemplateHandler extends \Bs\Listener\PageTemplateHandler
{
    use ConfigTrait;

    /**
     * @param \Tk\Event\Event $event
     * @throws \Exception
     */
    public function showPage(\Tk\Event\Event $event)
    {
        parent::showPage($event);
        $controller = \Tk\Event\Event::findControllerObject($event);
        if ($controller instanceof \Bs\Controller\Iface) {
            $page = $controller->getPage();
            if (!$page) return;

            $template = $page->getTemplate();
            /** @var \Uni\Db\User $user */
            $user = $controller->getAuthUser();

            // Add anything to the page template here ...
            if ($user) {
                $institutionId = $this->getConfig()->getInstitutionId();
                $subjectId = $this->getConfig()->getSubjectId();

                $js = <<<JS
config.subjectId = $subjectId;
config.institutionId = $institutionId;

JS;
                if ($this->getConfig()->getSubjectId()) {
                    $subjectUrl = Uri::createSubjectUrl('/', $this->getConfig()->getSubject())->getPath();
                    $js .= 'config.subjectUrl = \'' . $subjectUrl . '\';';
                }

                $template->appendJs($js, array('data-jsl-priority' => -1000));
            }

            /* @var \DOMElement[] $nodes */
            $nodes = $template->get('default-url');
            foreach ($nodes as $node) {
                $node->setAttribute('href', \Uni\Uri::createDefaultUrl($node->getAttribute('href')));
            }

            // get the unimelb login url
            /** @var \Uni\Db\Institution $inst */
            $inst = $this->getConfig()->getInstitutionMapper()->findFiltered(array('active' => true))->current();
            if ($inst) {
                $template->setAttr('institution-login', 'href', $inst->getLoginUrl());
            }
            $template->setAttr('client-login', 'href', \Uni\Uri::createDefaultUrl('/login.html'));

        }
    }


}
