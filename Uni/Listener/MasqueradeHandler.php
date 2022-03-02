<?php
namespace Uni\Listener;

use Uni\Db\Permission;
use Uni\Db\User;
use Uni\Db\UserIface;

/**
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class MasqueradeHandler extends \Bs\Listener\MasqueradeHandler
{

    /**
     * Add any headers to the final response.
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     */
    public function onMasquerade($event)
    {
        /** @var \Tk\Request $request */
        $request = $event->getRequest();
        if (!$request->has(static::MSQ)) return;
        try {
            $config = \Uni\Config::getInstance();
            /** @var User $user */
            $user = $config->getAuthUser();
            if (!$user) {
                throw new \Tk\Exception('Unknown User');
            }
            $iid = $config->getInstitutionId();
            if (!$iid || $request->has('institutionId'))
                $iid = (int)$request->get('institutionId');

            /** @var User $msqUser */
            $msqUser = $config->getUserMapper()->findByhash($request->get(static::MSQ), $iid);
            if (!$msqUser) {
                throw new \Tk\Exception('Invalid User');
            }
            $this->masqueradeLogin($user, $msqUser);
        } catch (\Exception $e) {
            \Tk\Alert::addWarning($e->getMessage());
            \Tk\Uri::create()->remove(static::MSQ)->remove('institutionId')->redirect();
        }
    }



    // -------------------  Masquerade functions  -------------------

    /**
     * Check if this user can masquerade as the supplied msqUser
     *
     * @param UserIface $user The current User
     * @param UserIface $msqUser
     * @return bool
     * @throws \Exception
     */
    public function canMasqueradeAs($user, $msqUser)
    {
        
        if (
            (!$user->hasType([User::TYPE_ADMIN, User::TYPE_CLIENT]) && !$user->hasPermission(Permission::CAN_MASQUERADE)) ||
            $user->getId() == $msqUser->getId() ||      // Cannot masquerade as self
            ($user->hasType(User::TYPE_STAFF) && $user->hasPermission(Permission::MANAGE_STAFF) && $msqUser->hasPermission(Permission::MANAGE_STAFF))   // Cannot masquerade as another manage staff user
        )
            return false;

        $b = parent::canMasqueradeAs($user, $msqUser);
        if (!$b && $user->hasPermission(Permission::MANAGE_STAFF)) {
            $b = true;
        }
        // If not admins they must be of the same institution
        if ($user->getInstitutionId() != 0 && $user->getInstitutionId() != $msqUser->getInstitutionId()) {
            $b = false;
        }
        return $b;
    }

}
