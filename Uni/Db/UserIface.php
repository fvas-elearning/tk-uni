<?php
namespace Uni\Db;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2017 Michael Mifsud
 */
interface UserIface
{

    /**
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getEmail();

    /**
     * @return null|\Tk\Uri
     */
    public function getHomeUrl();

    /**
     * @return InstitutionIface
     */
    public function getInstitution();

    /**
     * @return string
     */
    public function getRole();

    /**
     * @return bool
     */
    public function isActive();



}