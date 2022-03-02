<?php
/**
 * @version 3.0
 *
 * @author: Michael Mifsud <info@tropotek.com>
 */

$config = \Uni\Config::getInstance();
try {
    $data = \Tk\Db\Data::create();
    if (!$data->get('site.title')) {
        $data->set('site.title', 'Base TkUni Site');
        $data->set('site.short.title', 'TkUni');
    }
    if (!$data->get('site.email'))
        $data->set('site.email', 'fvas-elearning@unimelb.edu.au');

    $data->save();

} catch (\Exception $e) {}





