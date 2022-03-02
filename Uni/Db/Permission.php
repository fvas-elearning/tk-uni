<?php
namespace Uni\Db;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Permission extends \Bs\Db\Permission
{

    /**
     * Add/Edit Staff user accounts
     * @target staff
     */
    const MANAGE_STAFF          = 'perm.manage.staff';

    /**
     * Add/Edit subject and student enrollments
     * @target staff
     */
    const MANAGE_SUBJECT        = 'perm.manage.subject';

    /**
     * Coordinator: Manage settings/students/staff for linked subjects.
     * Advanced access rights to student and other course/subject system data allowed
     * @target staff
     */
    const IS_COORDINATOR        = 'perm.is.coordinator';

    /**
     * Lecturer: Manage student submissions/communications for linked subjects
     * Basic access rights to view student data, no system config should be available
     * @target staff
     */
    const IS_LECTURER           = 'perm.is.lecturer';

    /**
     * Staff member is a mentor to students
     * @target staff
     */
    const IS_MENTOR             = 'perm.is.mentor';



    /**
     * @param string $type (optional) If set returns only the permissions for that user type otherwise returns all permissions
     * @return array|string[]
     */
    public function getAvailablePermissionList($type = '')
    {
        $arr = array();
        switch ($type) {
            case User::TYPE_ADMIN;
            case User::TYPE_CLIENT:
                $arr = array(
                    'Manage Site Plugins' => self::MANAGE_PLUGINS,
                    'Can Masquerade' => self::CAN_MASQUERADE
                );
                break;
            case User::TYPE_STUDENT:
                $arr = array();
                break;
            default:          // TYPE_STAFF
                $arr = array(
                    'Manage Site' => self::MANAGE_SITE,
                    //'Manage Site Plugins' => self::MANAGE_PLUGINS,
                    'Can Masquerade' => self::CAN_MASQUERADE,
                    'Manage Staff Records' => self::MANAGE_STAFF,
                    'Course, Subject And Enrollment Settings' => self::MANAGE_SUBJECT,
                    'Staff Member is a Course Coordinator' => self::IS_COORDINATOR,
                    'Staff Member is a Lecturer' => self::IS_LECTURER,
                    'Staff Member is a Student Mentor' => self::IS_MENTOR,
                );
        }
        return $arr;
    }

    /**
     * Return the default user permission when creating a user
     *
     * @param string $type (optional) If set returns only the permissions for that user type otherwise returns all permissions
     * @return array|string[]
     */
    public function getDefaultUserPermissions($type = '')
    {
        $list = array();
        if ($type = User::TYPE_ADMIN || $type = User::TYPE_CLIENT) {
            $list = $this->getAvailablePermissionList($type);
        } else if ($type = User::TYPE_STAFF) {
            $list = array(
                'Staff Member is a Lecturer' => self::IS_LECTURER,
                //'Manage Site Plugins' => self::MANAGE_PLUGINS,
                'Can Masquerade' => self::CAN_MASQUERADE
            );
        }
        return $list;
    }
}
