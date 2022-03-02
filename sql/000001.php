<?php
/**
 * @version 3.0
 *
 * @author: Michael Mifsud <info@tropotek.com>
 */

try {
    $config = \App\Config::getInstance();
    $db = $config->getDb();

    $tableInfo = $db->getTableInfo('subject');
    if (!array_key_exists('publish', $tableInfo)) {
        $sql = sprintf('alter table subject add publish TINYINT(1) default 1 not null after date_end;');
        $db->exec($sql);
    }
    if (!array_key_exists('notify', $tableInfo)) {
        $sql = sprintf('alter table subject add notify TINYINT(1) default 1 not null after date_end;');
        $db->exec($sql);
    }

    $tableInfo = $db->getTableInfo('subject_pre_enrollment');
    if (!array_key_exists('username', $tableInfo)) {
        $sql = sprintf('ALTER TABLE subject_pre_enrollment ADD username VARCHAR(64) DEFAULT \'\' NOT NULL;');
        $db->exec($sql);
    }
    if (!array_key_exists('email', $tableInfo)) {
        $sql = sprintf('ALTER TABLE subject_pre_enrollment MODIFY COLUMN email varchar(168) NOT NULL DEFAULT \'\' AFTER uid;');
        $db->exec($sql);
        $sql = sprintf('
alter table subject_pre_enrollment drop primary key;
alter table subject_pre_enrollment add constraint subject_pre_enrollment_pk unique (subject_id, uid, username, email);
		');
    }

} catch (\Tk\Db\Exception $e) {
}




