<?php
namespace Uni\Controller;


abstract class Iface extends \Tk\Controller\Iface
{

    /**
     * @return string
     * @todo: we should come up with a more solid routing naming convention
     */
    public function getDefaultTitle()
    {
        $replace = array('admin-', 'client-', 'staff-', 'student-', '-base');
        /** @var \Tk\Request $request */
        $request = $this->getConfig()->getRequest();
        if ($request) {
            $routeName = $request->getAttribute('_route');
            $routeName = str_replace($replace, '', $routeName);
            return ucwords(trim(str_replace('-', ' ', $routeName)));
        }
        return '';
    }

    /**
     * Get the currently logged in user
     *
     * @return \Uni\Db\UserIface|\App\Db\User
     */
    public function getUser()
    {
        return $this->getConfig()->getUser();
    }

    /**
     * Get the global config object.
     *
     * @return \Tk\Config|\Uni\Config|\App\Config
     */
    public function getConfig()
    {
        return \Tk\Config::getInstance();
    }

    /**
     * DomTemplate magic method example
     *
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $html = <<<HTML
<div></div>
HTML;
        return \Dom\Loader::load($html);
        // OR FOR A FILE
        //return \Dom\Loader::loadFile($this->getTemplatePath().'/public.xtpl');
    }

}