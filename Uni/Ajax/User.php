<?php
namespace Uni\Ajax;

use Tk\Request;
/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class User
{

    /**
     * @param Request $request
     * @return \Tk\Response
     * @throws \Exception
     */
    public function doFindFiltered(Request $request)
    {
        $status = 200;  // change this on error
        $config = \Uni\Config::getInstance();

        $data = array();
        $filter = $request->all();
        unset($filter['subjectId']);
        if (!empty($filter['keywords'])) {
            if (trim($filter['keywords']) == '*') {    // Keep wildcard char as an undocumented feature for now
                $filter['keywords'] = '';
            }
            $users = $config->getUserMapper()->findFiltered($filter, \Tk\Db\Tool::create('a.name_first DESC', 50))->toArray();
            /** @var \Uni\Db\User $user */
            foreach ($users as $user) {
                $u = new \stdClass();
                $u->id = $user->getId();
                $u->uid = $user->uid;
                $u->username = $user->username;
                $u->name = $user->getName();
                $u->email = $user->getEmail();
                $u->hash = $user->getHash();
                $data[] = $u;
            }
        }
        return \Tk\ResponseJson::createJson($data, $status);
    }

}
