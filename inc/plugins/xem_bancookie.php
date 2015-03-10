<?php
/**
 * Author: Szczepan 'Xemix' Machaj
 * WWW: xemix.eu / xemix.pl
 * Copyright (c) 2015
 * License: Creative Commons BY-NC-SA 4.0
 * License URL: http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

if(!defined("IN_MYBB")) exit();

$plugins -> add_hook('no_permission', array('xem_bancookie', 'set_cookie'));
$plugins -> add_hook('member_register_agreement', array('xem_bancookie', 'disable_register'));
$plugins -> add_hook('member_logout_start', array('xem_bancookie', 'disable_logout'));

function xem_bancookie_info()
{
    return array(
        'name'          => 'xemBanCookie',
        'description'   => 'This plugin creates a cookie, whereby banned users cannot register again or logout.',
        'website'       => 'http://xemix.eu',
        'author'        => 'Xemix',
        'authorsite'    => 'http://xemix.eu',
        'version'       => '1.2',
        'codename'      => 'xem_bancookie',
        'compatibility' => '18*'
    );
}

function xem_bancookie_install()
{
    global $db, $mybb;

    $setting_group_id = $db->insert_query('settinggroups', array(
        'name'        => 'xem_bancookie_settings',
        'title'       => 'xemBanCookie settings',
        'description' => 'Settings for xemBanCookie.',
    ));
    
    $settings = array(
        array(   
            'name'        => 'xem_bancookie_active',
            'title'       => 'xemBanCookie active',
            'description' => 'The BanCookie plugin is activated?',
            'optionscode' => 'yesno',
            'value'       => '1'
        ),
        array(  
            'name'        => 'xem_bancookie_disable_logout',
            'title'       => 'Block the logout after ban?',
            'description' => 'Users cannot logout when the account has been banned.',
            'optionscode' => 'yesno',
            'value'       => '1'
        ),
    );

    $i = 1;

    foreach($settings as &$row) {
        $row['gid']         = $setting_group_id;
        $row['title']       = $db -> escape_string($row['title']);
        $row['description'] = $db -> escape_string($row['description']);
        $row['disporder']   = $i++;
    }

    $db -> insert_query_multiple('settings', $settings);

    rebuild_settings();
    
}

function xem_bancookie_uninstall()
{
    global $db;

    $setting_group_id = $db -> fetch_field(
        $db -> simple_select('settinggroups', 'gid', "name='xem_bancookie_settings'"),
        'gid'
    );

    $db -> delete_query('settinggroups', "name='xem_bancookie_settings'");
    $db -> delete_query('settings', 'gid=' . $setting_group_id);

    rebuild_settings();
}

function xem_bancookie_is_installed()
{
    global $db;

    $query = $db -> simple_select('settinggroups', 'gid', "name='xem_bancookie_settings'");
    return (bool)$db -> num_rows($query);
}

class xem_bancookie 
{

    public function set_cookie()
    {
        global $mybb, $db;

        if((int)$mybb -> settings['xem_bancookie_active'] && $mybb -> usergroup['isbannedgroup'])
        {
            $query = $db -> simple_select('banned', 'lifted', "uid = '{$mybb->user['uid']}'", array('limit' => 1));
            $ban = $db -> fetch_array($query);
            my_setcookie("mybb[userban]", md5($ban['lifted']), ($ban['lifted'] - TIME_NOW));
        }
    }

    public function disable_register()
    {
        global $lang;

        $lang -> load("xem_bancookie");

        if(self::isset_cookie('xem_bancookie_active'))
        {
            error($lang -> xem_bancookie_user_banned);
        }
    }

    public function disable_logout()
    {
        global $lang;

        $lang -> load("xem_bancookie");

        if(self::isset_cookie('xem_bancookie_disable_logout'))
        {
            error($lang -> xem_bancookie_logout_disabled);
        }
    }

    static function isset_cookie($xem_bancookie_setting)
    {
        global $mybb;

        if($mybb -> cookies['mybb']['userban'] && (int)$mybb -> settings[$xem_bancookie_setting]) 
        {
            return true;
        } 
        else 
        {
            return false;
        }
    }
}