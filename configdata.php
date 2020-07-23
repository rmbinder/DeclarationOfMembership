<?php
/**
 ***********************************************************************************************
 * Konfigurationsdaten fuer das Admidio-Plugin DeclarationOfMembership
 *
 * @copyright 2004-2020 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 ***********************************************************************************************
 */

global $gL10n, $gCurrentOrganization;

//Standardwerte einer Neuinstallation
$config_default['main_texts'] = array();
$config_default['cat_texts'] = array();
$config_default['field_texts'] = array();

$config_default['fields'] = array('profile_fields'  => array(''),
                                  'required_fields' => array(''));

$config_default['registration_org']['org_id'] = $gCurrentOrganization->getValue('org_id');

// Plugininformationen
$config_default['Plugininformationen']['version'] = '';
$config_default['Plugininformationen']['stand'] = '';

//Zugriffsberechtigung für das Modul preferences
$config_default['access']['preferences'] = array(getRole_IDPDM($gL10n->get('SYS_ADMINISTRATOR')));

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
