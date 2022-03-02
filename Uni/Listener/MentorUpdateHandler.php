<?php
namespace Uni\Listener;

use Symfony\Component\HttpKernel\KernelEvents;
use Tk\ConfigTrait;
use Tk\Event\AuthEvent;
use Tk\Auth\AuthEvents;
use Tk\Event\Subscriber;
use Tk\Log;
use Uni\Db\Permission;
use Uni\Util\MentorTool;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class MentorUpdateHandler implements Subscriber
{
    use ConfigTrait;

    /**
     * @param AuthEvent $event
     * @throws \Exception
     */
    public function onLogin(AuthEvent $event)
    {
        $user = $this->getConfig()->getAuthUser();
        if ($user) {
            Log::warning('Running Mentor Import!');
            MentorTool::getInstance()->executeImport();
        }
    }


    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            AuthEvents::LOGIN_SUCCESS => array('onLogin', 0)
        );
    }


}
