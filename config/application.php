<?php
/*
 * Application default config values
 * This file should not need to be edited
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */

$config = \Uni\Config::getInstance();

/**************************************
 * Default app config values
 **************************************/

$config['site.title'] = 'Uni Base Template';
$config['site.email'] = 'user@example.com';

/*
 * Setup what paths to check when migrating SQL
 */
$config['sql.migrate.list'] = array(
    'Lib Sql' => $config->getVendorPath() . '/ttek/tk-uni',
    'Plugin Sql' => $config->getPluginPath(),
    'App Sql' => $config->getSrcPath() . '/config'
);

/*
 * The user types available to the system
 */
$config['user.type.list'] = array(
    'Administrator' => 'admin',
    'Client' => 'client',
    'Staff' => 'staff',
    'Student' => 'student'
);

/*
 * Template folders for pages
 */
$config['system.template.path'] = '/html';

$config['system.theme.admin']   = $config['system.template.path'] . '/admin';
$config['system.theme.public']  = $config['system.template.path'] . '/admin';

$config['template.admin']       = $config['system.theme.admin'] . '/admin.html';
//$config['template.lti']         = $config['system.theme.admin'] . '/lti.html';        // Use this if you want to enable an LTI only template (see tk2uni base project)
$config['template.client']      = $config['system.theme.admin'] . '/admin.html';
$config['template.staff']       = $config['system.theme.admin'] . '/admin.html';
$config['template.student']     = $config['system.theme.admin'] . '/admin.html';
$config['template.public']      = $config['system.theme.admin'] . '/public.html';

$config['template.login']       = $config['system.theme.admin'] . '/login.html';


/*
 * Does this html template use bootstrap4 markup
 * Default: 'bs4'
 */
$config['css.framework']         = 'bs4';


/*
 * ---- AUTH CONFIG ----
 */

/*
 * The email address of the system developer
 */
$config['system.email.developer'] = 'developer@example.com';

/*
 * The email address of the department maintaining this system
 */
$config['system.email.department'] = 'department@example.com';

/*
 * Should the system use a salted password?
 */
$config['system.auth.salted'] = true;


/**
 * This is to be set to the sites default domain name so we can use it for links from the
 * institution pages back to the site
 */
$config['site.public.domain'] = '';


/*
 * Config for the \Tk\Auth\Adapter\DbTable
 */
$config['system.auth.dbtable.tableName']      = 'user';
$config['system.auth.dbtable.usernameColumn'] = 'username';
$config['system.auth.dbtable.passwordColumn'] = 'password';
$config['system.auth.dbtable.saltColumn']     = 'hash';
$config['system.auth.dbtable.activeColumn']   = 'active';

$config['system.auth.email.require']   = true;
$config['system.auth.email.unique']    = true;


