<?php
namespace Uni\Listener;

use Symfony\Component\HttpKernel\KernelEvents;
use Tk\ConfigTrait;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class UserLogHandler extends \Bs\Listener\PageTemplateHandler
{
    use ConfigTrait;

    /**
     * If we are in a subject URL then get the subject object and set it in the config
     * for global accessibility.
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     * @throws \Exception
     */
    public function onRequest($event)
    {
        $config = $this->getConfig();
        $request = $event->getRequest();

        // deselect the subject and redirect to the main dashboard
        if (\App\Config::getInstance()->getRequest()->has('cc')) {
            \App\Config::getInstance()->unsetSubject();
            \Tk\Log::info('Clearing subject and redirecting to main dashboard.');
            \Uni\Uri::createHomeUrl()->redirect();
        }

        if ($config->getAuthUser()) {
            \Tk\Log::info('- User: ' . $config->getAuthUser()->getName() . ' <' . $config->getAuthUser()->getEmail() . '> [ID: ' . $config->getAuthUser()->getId() . ']');
            if ($config->getMasqueradeHandler()->isMasquerading()) {
                $msq = $config->getMasqueradeHandler()->getMasqueradingUser();
                \Tk\Log::info('  └ Msq: ' . $msq->getName() . ' [ID: ' . $msq->getId() . ']');
            }
        }
        if ($config->getInstitution()) {
            \Tk\Log::info('- Institution: ' . $config->getInstitution()->getName() . ' [ID: ' . $config->getInstitution()->getId() . ']');
        }
        if ($request->attributes->has('subjectCode') && $config->getCourse()) {
            \Tk\Log::info('- Course: ' . $config->getCourse()->getName() . ' [ID: ' . $config->getCourse()->getId() . ']');
        }
        if ($request->attributes->has('subjectCode') && $config->getSubject()) {
            \Tk\Log::info('- Subject: ' . $config->getSubject()->getName() . ' [ID: ' . $config->getSubject()->getId() . ']');
        }

        //\Tk\Log::info(\Tk\Listener\StartupHandler::$SCRIPT_LINE);
    }


    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array('onRequest', -1)
        );
    }

}
