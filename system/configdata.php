<?php
/**
 ***********************************************************************************************
 * Konfigurationsdaten fuer das Admidio-Plugin DeclarationOfMembership
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 ***********************************************************************************************
 */

//Standardwerte einer Neuinstallation
$config_default['main_texts']  = array();
$config_default['cat_texts']   = array();
$config_default['field_texts'] = array();

$config_default['fields'] = array('profile_fields'  => array(''),
                                  'required_fields' => array(''));

$config_default['registration_org']['org_id'] = $GLOBALS['gCurrentOrgId'];

// Plugininformationen
$config_default['Plugininformationen']['version'] = '';
$config_default['Plugininformationen']['stand'] = '';

//Zugriffsberechtigung für das Modul preferences
$config_default['access']['preferences'] = array();

$config_default['emailnotification'] = array(
    'access_to_module' => '0',
    'msg_subject'      => '',
    'msg_body'         => ''
);

$config_default['options']['kiosk_mode'] = 0;

$config_default['usr_login_name'] = array('displayed' => '0',
                                          'required'  => '0',
                                          'fieldtext' => '');

/*
 *  Mittels dieser Zeichenkombination werden Konfigurationsdaten, die zur Laufzeit als Array verwaltet werden,
 *  zu einem String zusammengefasst und in der Admidiodatenbank gespeichert.
 *  Muessen die vorgegebenen Zeichenkombinationen (#_#) jedoch ebenfalls, z.B. in der Beschreibung
 *  einer Konfiguration, verwendet werden, so kann das Plugin gespeicherte Konfigurationsdaten
 *  nicht mehr richtig einlesen. In diesem Fall ist die vorgegebene Zeichenkombination abzuaendern (z.B. in !-!)
 *
 *  Achtung: Vor einer Aenderung muss eine Deinstallation durchgefuehrt werden!
 *  Bereits gespeicherte Werte in der Datenbank koennen nach einer Aenderung nicht mehr eingelesen werden!
 */
$dbtoken  = '#_#';
