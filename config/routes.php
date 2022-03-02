<?php
/* 
 * NOTE: Be sure to add routes in correct order as the first match will win
 * 
 * Route Structure
 * $route = new Route(
 *     '/archive/{month}',              // path
 *     '\Namespace\Class::method',      // Callable or class::method string
 *     array('month' => 'Jan'),         // Params and defaults to path params... all will be sent to the request object.
 *     array('GET', 'POST', 'HEAD')     // methods
 * );
 */
use Tk\Routing\Route;

$config = \Uni\Config::getInstance();
$routes = $config->getRouteCollection();
if (!$routes) return;



// Public Pages
$routes->add('login', Route::create('/login.html', 'Uni\Controller\Login::doInsLogin')); // Assumes the first institution is the only institution
$routes->add('institution-login', Route::create('/inst/{instHash}/login.html', 'Uni\Controller\Login::doInsLogin'));
$routes->add('admin-login', Route::create('/xlogin.html', 'Uni\Controller\Login::doDefault'));

$routes->add('recover', Route::create('/recover.html', 'Uni\Controller\Recover::doDefault'));
$routes->add('register', Route::create('/register.html', 'Uni\Controller\Register::doDefault'));
$routes->add('logout', Route::create('/logout.html', 'Uni\Controller\Logout::doDefault'));
$routes->add('institution-list', Route::create('/institutions.html', 'Uni\Controller\Institution\Listing::doDefault'));

$routes->add('install', Route::create('/install.html', 'Uni\Controller\Install::doDefault'));


// Admin Pages
//$routes->add('admin-dashboard', Route::create('/admin/index.html', 'Uni\Controller\Admin\Dashboard::doDefault'));
//$routes->add('admin-dashboard-base', Route::create('/admin/', 'Uni\Controller\Admin\Dashboard::doDefault'));

$routes->add('admin-institution-plugin-manager', Route::create('/admin/{zoneName}/{zoneId}/plugins.html', 'Uni\Controller\PluginZoneManager::doDefault',
    array('zoneName' => 'institution', 'zoneId' => '0') ));

$routes->add('admin-settings', Route::create('/admin/settings.html', 'Uni\Controller\Admin\Settings::doDefault'));

$routes->add('admin-institution-manager', Route::create('/admin/institutionManager.html', 'Uni\Controller\Institution\Manager::doDefault'));
$routes->add('admin-institution-edit', Route::create('/admin/institutionEdit.html', 'Uni\Controller\Institution\Edit::doDefault'));

$routes->add('admin-user-manager', Route::create('/admin/{targetType}UserManager.html', 'Uni\Controller\User\Manager::doDefaultType'));
$routes->add('admin-user-edit', Route::create('/admin/{targetType}UserEdit.html', 'Uni\Controller\User\Edit::doDefaultType'));
$routes->add('admin-user-profile', Route::create('/admin/profile.html', 'Uni\Controller\User\Profile::doDefault'));




// Client Pages
//$routes->add('client-dashboard', Route::create('/client/index.html', 'Uni\Controller\Client\Dashboard::doDefault'));
//$routes->add('client-dashboard-base', Route::create('/client/', 'Uni\Controller\Client\Dashboard::doDefault'));

$routes->add('client-institution-plugin-manager', Route::create('/client/{zoneName}/{zoneId}/plugins.html', 'Uni\Controller\PluginZoneManager::doDefault',
    array('zoneName' => 'institution', 'zoneId' => '0') ));

$routes->add('client-settings', Route::create('/client/settings.html', 'Uni\Controller\Institution\Edit::doDefault'));
$routes->add('client-user-profile', Route::create('/client/profile.html', 'Uni\Controller\User\Profile::doDefault'));

$routes->add('client-user-manager', Route::create('/client/{targetType}UserManager.html', 'Uni\Controller\User\Manager::doDefaultType'));
$routes->add('client-user-edit', Route::create('/client/{targetType}UserEdit.html', 'Uni\Controller\User\Edit::doDefaultType'));

$routes->add('client-course-manager', Route::create('/client/courseManager.html', 'Uni\Controller\Course\Manager::doDefault'));
$routes->add('client-course-edit', Route::create('/client/courseEdit.html', 'Uni\Controller\Course\Edit::doDefault'));

$routes->add('client-subject-manager', Route::create('/client/subjectManager.html', 'Uni\Controller\Subject\Manager::doDefault'));
$routes->add('client-subject-edit', Route::create('/client/subjectEdit.html', 'Uni\Controller\Subject\Edit::doDefault'));
$routes->add('client-subject-enrollment', Route::create('/client/subjectEnrollment.html', 'Uni\Controller\Subject\EnrollmentManager::doDefault'));


$routes->add('client-student-list', new \Tk\Routing\Route('/client/mentorList.html', 'Uni\Controller\Mentor\StudentList::doDefault'));
$routes->add('client-student-import', new \Tk\Routing\Route('/client/mentorImport.html', 'Uni\Controller\Mentor\Import::doDefault'));

// Mentor Pages
//$routes->add('mentor-dashboard', new \Tk\Routing\Route('/staff/mentor/index.html', 'App\Controller\Mentor\Dashboard::doDefault'));
//$routes->add('mentor-dashboard-base', new \Tk\Routing\Route('/staff/mentor/', 'App\Controller\Mentor\Dashboard::doDefault'));
//$routes->add('mentor-student-view', new \Tk\Routing\Route('/staff/mentor/studentView.html', 'App\Controller\Mentor\StudentView::doDefault'));



// Staff Pages
//$routes->add('staff-dashboard', Route::create('/staff/index.html', 'Uni\Controller\Staff\Dashboard::doDefault'));
//$routes->add('staff-dashboard-base', Route::create('/staff/', 'Uni\Controller\Staff\Dashboard::doDefault'));
//$routes->add('staff-subject-dashboard', Route::create('/staff/{subjectCode}/index.html', 'Uni\Controller\Staff\SubjectDashboard::doDefault'));

$routes->add('staff-subject-plugin-manager', Route::create('/staff/{zoneName}/{zoneId}/plugins.html', 'Uni\Controller\PluginZoneManager::doDefault',
    array('zoneName' => 'subject', 'zoneId' => '0') ));

$routes->add('staff-institution-edit', Route::create('/staff/settings.html', 'Uni\Controller\Institution\Edit::doDefault'));

$routes->add('staff-user-manager', Route::create('/staff/{targetType}UserManager.html', 'Uni\Controller\User\Manager::doDefaultType'));
$routes->add('staff-user-edit', Route::create('/staff/{targetType}UserEdit.html', 'Uni\Controller\User\Edit::doDefaultType'));

$routes->add('staff-user-profile', Route::create('/staff/profile.html', 'Uni\Controller\User\Profile::doDefault'));
$routes->add('staff-subject-user-profile', Route::create('/staff/{subjectCode}/profile.html', 'Uni\Controller\User\Profile::doDefault'));

$routes->add('staff-student-list', new \Tk\Routing\Route('/staff/mentorList.html', 'Uni\Controller\Mentor\StudentList::doDefault'));
$routes->add('staff-student-import', new \Tk\Routing\Route('/staff/mentorImport.html', 'Uni\Controller\Mentor\Import::doDefault'));

$routes->add('staff-course-manager', Route::create('/staff/courseManager.html', 'Uni\Controller\Course\Manager::doDefault'));
$routes->add('staff-course-edit', Route::create('/staff/courseEdit.html', 'Uni\Controller\Course\Edit::doDefault'));

$routes->add('staff-subject-manager', Route::create('/staff/subjectManager.html', 'Uni\Controller\Subject\Manager::doDefault'));
$routes->add('staff-subject-edit', Route::create('/staff/{subjectCode}/subjectEdit.html', 'Uni\Controller\Subject\Edit::doDefault'));
$routes->add('staff-subject-add', Route::create('/staff/subjectEdit.html', 'Uni\Controller\Subject\Edit::doDefault'));
$routes->add('staff-subject-add-enrollment', Route::create('/staff/subjectEnrollment.html', 'Uni\Controller\Subject\EnrollmentManager::doDefault'));


$routes->add('staff-subject-enrollment', Route::create('/staff/{subjectCode}/subjectEnrollment.html', 'Uni\Controller\Subject\EnrollmentManager::doSubject'));

$routes->add('staff-subject-user-manager', Route::create('/staff/{subjectCode}/{targetType}UserManager.html', 'Uni\Controller\User\Manager::doDefaultType'));
$routes->add('staff-subject-user-edit', Route::create('/staff/{subjectCode}/{targetType}UserEdit.html', 'Uni\Controller\User\Edit::doDefaultType'));



// Student Pages
//$routes->add('student-dashboard', Route::create('/student/index.html', 'Uni\Controller\Student\Dashboard::doDefault'));
//$routes->add('student-dashboard-base', Route::create('/student/', 'Uni\Controller\Student\Dashboard::doDefault'));
//$routes->add('student-subject-dashboard', Route::create('/student/{subjectCode}/index.html', 'Uni\Controller\Student\SubjectDashboard::doDefault'));

$routes->add('student-user-profile', Route::create('/student/profile.html', 'Uni\Controller\User\Profile::doDefault'));
$routes->add('student-subject-user-profile', Route::create('/student/{subjectCode}/profile.html', 'Uni\Controller\User\Profile::doDefault'));



// Ajax Urls
$routes->add('ajax-user-findFiltered', Route::create('/ajax/user/findFiltered.html', 'Uni\Ajax\User::doFindFiltered'));
$routes->add('ajax-subject-findFiltered', Route::create('/ajax/subject/findFiltered.html', 'Uni\Ajax\Subject::doFindFiltered'));



