<?php
/**
 * Author: Szczepan 'Xemix' Machaj
 * WWW: xemix.eu / xemix.pl
 * Copyright (c) 2015
 * License: Creative Commons BY-NC-SA 4.0
 * License URL: http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("no_permission", "xem_bancookie_setcookie");
$plugins->add_hook("member_register_agreement", "xem_bancookie_check");
$plugins->add_hook("member_logout_start", "xem_bancookie_disable_logout");

function xem_bancookie_info()
{
    return [
        'name'          => 'xemBanCookie',
        'description'   => 'The plugin creates a cookie, whereby banned users cannot register again or logout.',
        'website'       => 'http://xemix.eu',
        'author'        => 'Xemix.eu',
        'authorsite'    => 'http://xemix.eu',
        'version'       => '1.1',
        'codename'      => 'xem_bancookie',
        'compatibility' => '18*'
    ];
}

function xem_bancookie_install()
{
    global $db, $mybb;

    $settingGroupId = $db->insert_query('settinggroups', [
        'name'        => 'xem_bancookie_settings',
        'title'       => 'xemBanCookie settings',
        'description' => 'Settings for BanCookie.',
    ]);
    
    $settings = [
        [   
            'name'        => 'xem_bancookie_active',
            'title'       => 'xemBanCookie active',
            'description' => 'The BanCookie plugin is activated?',
            'optionscode' => 'yesno',
            'value'       => '1'
        ],
        [   
            'name'        => 'xem_bancookie_disable_logout',
            'title'       => 'Block the logout after ban?',
            'description' => 'Users cannot logout when the account has been banned.',
            'optionscode' => 'yesno',
            'value'       => '1'
        ],
    ];

    $i = 1;

    foreach($settings as &$row) {
        $row['gid']         = $settingGroupId;
        $row['title']       = $db->escape_string($row['title']);
        $row['description'] = $db->escape_string($row['description']);
        $row['disporder']   = $i++;
    }

    $db->insert_query_multiple('settings', $settings);

    rebuild_settings();
    
}

function xem_bancookie_uninstall()
{
    global $db;

    $settingGroupId = $db->fetch_field(
        $db->simple_select('settinggroups', 'gid', "name='xem_bancookie_settings'"),
        'gid'
    );

    $db->delete_query('settinggroups', "name='xem_bancookie_settings'");
    $db->delete_query('settings', 'gid=' . $settingGroupId);

    rebuild_settings();
}

function xem_bancookie_is_installed()
{
    global $db;

    $query = $db->simple_select('settinggroups', 'gid', "name='xem_bancookie_settings'");
    return (bool)$db->num_rows($query);
}

function xem_bancookie_setcookie()
{
    global $mybb, $db;

    if(xem_bancookie_is_installed() && 
      (int)$mybb->settings['xem_bancookie_active'] &&
       $mybb->usergroup['isbannedgroup'])
    {
        $query = $db->simple_select('banned', '*', "uid = '{$mybb->user['uid']}'", ['limit' => 1]);
        $ban = $db->fetch_array($query);
        $expire = $ban['lifted'] - TIME_NOW;
        my_setcookie("mybb[userban]", md5($ban['lifted']), $expire);
    }
}

function xem_bancookie_check()
{
    global $mybb, $lang;

    $lang->load("xem_bancookie");

    if($mybb->cookies['mybb']['userban'] && (int)$mybb->settings['xem_bancookie_active'])
    {
        error($lang->xem_bancookie_user_banned);
    }
}

function xem_bancookie_disable_logout()
{
    global $mybb, $lang;

    $lang->load("xem_bancookie");

    if($mybb->cookies['mybb']['userban'] && (int)$mybb->settings['xem_bancookie_disable_logout'])
    {
        error($lang->xem_bancookie_logout_disabled);
    }
}