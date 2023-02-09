<?php
/**
 ***********************************************************************************************
 * Class manages the configuration table
 *
 * @copyright 2004-2023 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/******************************************************************************
 * Klasse verwaltet die Konfigurationstabelle "adm_plugin_preferences"
 *
 * Folgende Methoden stehen zur Verfuegung:
 *
 * init()                       :   prueft, ob die Konfigurationstabelle existiert,
 *                                  legt sie ggf. an und befuellt sie mit Default-Werten
 * save()                       :   schreibt die Konfiguration in die Datenbank
 * read()                       :   liest die Konfigurationsdaten aus der Datenbank
 * checkforupdate()             :   vergleicht die Angaben in der Datei version.php
 *                                  mit den Daten in der DB
 * delete()                     :   loescht Konfigurationsdaten in der Datenbank
 *
 *****************************************************************************/

class ConfigTablePDM
{
    public $config = array();     ///< Array mit allen Konfigurationsdaten

    protected $table_name;
    protected static $shortcut =  'PDM';
    protected static $version;
    protected static $stand;
    protected static $dbtoken;

    public $config_default = array();

    /**
     * ConfigTablePDM constructor
     */
    public function __construct()
    {
        global $g_tbl_praefix;

        require_once(__DIR__ . '/../version.php');
        include(__DIR__ . '/../configdata.php');

        $this->table_name = $g_tbl_praefix.'_plugin_preferences';

        if (isset($plugin_version))
        {
            self::$version = $plugin_version;
        }
        if (isset($plugin_stand))
        {
            self::$stand = $plugin_stand;
        }
        if (isset($dbtoken))
        {
            self::$dbtoken = $dbtoken;
        }
        $this->config_default = $config_default;
    }

    /**
     * Prueft, ob die Konfigurationstabelle existiert, legt sie ggf an und befuellt sie mit Standardwerten
     * @return void
     */
    public function init()
    {
        // pruefen, ob es die Tabelle bereits gibt
        $sql = 'SHOW TABLES LIKE \''.$this->table_name.'\' ';
        $statement = $GLOBALS['gDb']->queryPrepared($sql);

        // Tabelle anlegen, wenn es sie noch nicht gibt
        if (!$statement->rowCount())
        {
            // Tabelle ist nicht vorhanden --> anlegen
            $sql = 'CREATE TABLE '.$this->table_name.' (
                plp_id      integer     unsigned not null AUTO_INCREMENT,
                plp_org_id  integer     unsigned not null,
                plp_name    varchar(255) not null,
                plp_value   text,
                primary key (plp_id) )
                engine = InnoDB
                auto_increment = 1
                default character set = utf8
                collate = utf8_unicode_ci';
            $GLOBALS['gDb']->queryPrepared($sql);
        }

        $this->read();

        $this->config['Plugininformationen']['version'] = self::$version;
        $this->config['Plugininformationen']['stand'] = self::$stand;

        // die eingelesenen Konfigurationsdaten in ein Arbeitsarray kopieren
        $config_ist = $this->config;

        // die Default-config durchlaufen
        foreach ($this->config_default as $section => $sectiondata)
        {
            foreach ($sectiondata as $key => $value)
            {
                // gibt es diese Sektion bereits in der config?
                if (isset($config_ist[$section][$key]))
                {
                    // wenn ja, diese Sektion in der Ist-config loeschen
                    unset($config_ist[$section][$key]);
                }
                else
                {
                    // wenn nicht, diese Sektion in der config anlegen und mit den Standardwerten aus der Soll-config befuellen
                    $this->config[$section][$key] = $value;
                }
            }
            // leere Abschnitte (=leere Arrays) loeschen
            if ((isset($config_ist[$section]) && count($config_ist[$section]) === 0) || (isset($config_ist[$section]) && count($this->config_default[$section]) === 0) )
            {
                unset($config_ist[$section]);
            }
        }

        // die Ist-config durchlaufen
        // jetzt befinden sich hier nur noch die DB-Eintraege, die nicht verwendet werden und deshalb:
        // 1. in der DB geloescht werden koennen
        // 2. in der normalen config geloescht werden koennen
        foreach ($config_ist as $section => $sectiondata)
        {
            foreach ($sectiondata as $key => $value)
            {
                $plp_name = self::$shortcut.'__'.$section.'__'.$key;
                $sql = 'DELETE FROM '.$this->table_name.'
                              WHERE plp_name = ?
                                AND plp_org_id = ? ';
                $GLOBALS['gDb']->queryPrepared($sql, array($plp_name, $GLOBALS['gCurrentOrgId']));
                
                unset($this->config[$section][$key]);
            }
            // leere Abschnitte (=leere Arrays) loeschen
            if (count($this->config[$section]) === 0)
            {
                unset($this->config[$section]);
            }
        }

        // die aktualisierten und bereinigten Konfigurationsdaten in die DB schreiben
        $this->save();
    }

    /**
     * Schreibt die Konfigurationsdaten in die Datenbank
     * @return void
     */
    public function save()
    {
        // die aktuellen Konfigurationsdaten aus der DB lesen und in ein Arbeitsarray kopieren
        $this->config_work = array();
        
        $sql = ' SELECT plp_id, plp_name, plp_value
                   FROM '.$this->table_name.'
                  WHERE plp_name LIKE ?
                    AND ( plp_org_id = ?
                     OR plp_org_id IS NULL ) ';
        $statement = $GLOBALS['gDb']->queryPrepared($sql, array(self::$shortcut.'__%', $GLOBALS['gCurrentOrgId']));	
        
        while ($row = $statement->fetch())
        {
            $array = explode('__', $row['plp_name']);
            
            // wenn plp_value von ((  )) eingeschlossen ist, dann ist es als Array einzulesen
            if ((substr($row['plp_value'], 0, 2) == '((') && (substr($row['plp_value'], -2) == '))'))
            {
                $row['plp_value'] = substr($row['plp_value'], 2, -2);
                $this->config_work[$array[1]] [$array[2]] = explode(self::$dbtoken, $row['plp_value']);
            }
            else
            {
                $this->config_work[$array[1]] [$array[2]] = $row['plp_value'];
            }
        }
        
        foreach ($this->config as $section => $sectiondata)
        {
            foreach ($sectiondata as $key => $value)
            {
                if (is_array($value))
                {
                    // um diesen Datensatz in der Datenbank als Array zu kennzeichnen, wird er von Doppelklammern eingeschlossen
                    $value = '(('.implode(self::$dbtoken, $value).'))';
                }

                $plp_name = self::$shortcut.'__'.$section.'__'.$key;

                $sql = ' SELECT plp_id
                           FROM '.$this->table_name.'
                          WHERE plp_name = ?
                            AND ( plp_org_id = ?
                             OR plp_org_id IS NULL ) ';
                $statement = $GLOBALS['gDb']->queryPrepared($sql, array($plp_name, $GLOBALS['gCurrentOrgId']));	
                $row = $statement->fetchObject();

                // Gibt es den Datensatz bereits?
                // wenn ja: UPDATE des bestehende Datensatzes
                if (isset($row->plp_id) && strlen($row->plp_id) > 0)
                {
                    $sql = 'UPDATE '.$this->table_name.'
                               SET plp_value = ?
                             WHERE plp_id = ? ';
                    $GLOBALS['gDb']->queryPrepared($sql, array($value, $row->plp_id));
                }
                // wenn nicht: INSERT eines neuen Datensatzes
                else
                {
 					$sql = 'INSERT INTO '.$this->table_name.' (plp_org_id, plp_name, plp_value) 
  							VALUES (? , ? , ?)  -- $GLOBALS[\'gCurrentOrgId\'], self::$shortcut.\'__\'.$section.\'__\'.$key, $value '; 
            		$GLOBALS['gDb']->queryPrepared($sql, array($GLOBALS['gCurrentOrgId'], self::$shortcut.'__'.$section.'__'.$key, $value));
                }
                
                //diesen Datensatz im Arbeitsarray löschen
                unset($this->config_work[$section][$key]);
            }
        }
        
        //jetzt befinden sich im Arbeitsarray nur noch NICHT verwendete Einträge
        //die Abschnitte cat_texts und main_texts durchlaufen und nicht verwendete Einträge in der DB löschen
        if (isset($this->config_work['cat_texts']))                //Sicherheitsüberprüfung: hat das betreffende Array überhaupt noch Einträge?
        {
            foreach ($this->config_work['cat_texts'] as $section => $dummy)
            {
                $sql = 'DELETE FROM '.$this->table_name.'
        			          WHERE plp_name = ?
        			           AND plp_org_id = ? ';
                $GLOBALS['gDb']->queryPrepared($sql, array(self::$shortcut.'__cat_texts__'.$section, $GLOBALS['gCurrentOrgId']));
            }
        }
        if (isset($this->config_work['main_texts']))                //Sicherheitsüberprüfung: hat das betreffende Array überhaupt noch Einträge?
        {
            foreach ($this->config_work['main_texts'] as $section => $dummy)
            {
                $sql = 'DELETE FROM '.$this->table_name.'
        			          WHERE plp_name = ?
        			            AND plp_org_id = ? ';
                $GLOBALS['gDb']->queryPrepared($sql, array(self::$shortcut.'__main_texts__'.$section, $GLOBALS['gCurrentOrgId']));
            }
        }
    }

    /**
     * Liest die Konfigurationsdaten aus der Datenbank
     * @return void
     */
    public function read()
    {
        $sql = ' SELECT plp_id, plp_name, plp_value
                   FROM '.$this->table_name.'
                  WHERE plp_name LIKE ?
                    AND ( plp_org_id = ?
                     OR plp_org_id IS NULL ) ';
        $statement = $GLOBALS['gDb']->queryPrepared($sql, array(self::$shortcut.'__%', $GLOBALS['gCurrentOrgId']));

        while ($row = $statement->fetch())
        {
            $array = explode('__', $row['plp_name']);

            // wenn plp_value von ((  )) eingeschlossen ist, dann ist es als Array einzulesen
            if ((substr($row['plp_value'], 0, 2) == '((') && (substr($row['plp_value'], -2) == '))'))
            {
                $row['plp_value'] = substr($row['plp_value'], 2, -2);
                $this->config[$array[1]] [$array[2]] = explode(self::$dbtoken, $row['plp_value']);
            }
            else
            {
                $this->config[$array[1]] [$array[2]] = $row['plp_value'];
            }
        }
    }

    /**
    
    * Vergleicht die Daten in der version.php mit den Daten in der DB    
    * @return bool   
    */
    
    public function checkforupdate()
    {   
        $ret = false;

        // pruefen, ob es die Tabelle ueberhaupt gibt
        $sql = 'SHOW TABLES LIKE \''.$this->table_name.'\' ';
        $tableExistStatement = $GLOBALS['gDb']->queryPrepared($sql);

        if ($tableExistStatement->rowCount())
        {
            $plp_name = self::$shortcut.'__Plugininformationen__version';

            $sql = 'SELECT plp_value
            		  FROM '.$this->table_name.'  
            		 WHERE plp_name = ? 
            		   AND ( plp_org_id = ? 
            	    	OR plp_org_id IS NULL ) ';
            $statement = $GLOBALS['gDb']->queryPrepared($sql, array($plp_name, $GLOBALS['gCurrentOrgId']));
            $row = $statement->fetchObject();

            // Vergleich Version.php  ./. DB (hier: version)
            if (!isset($row->plp_value) || strlen($row->plp_value) === 0 || $row->plp_value<>self::$version)
            {
                $ret = true;
            }

            $plp_name = self::$shortcut.'__Plugininformationen__stand';

            $sql = 'SELECT plp_value
            		  FROM '.$this->table_name.' 
            		 WHERE plp_name = ?
            		   AND ( plp_org_id = ?  
                 		OR plp_org_id IS NULL ) ';            
            $statement = $GLOBALS['gDb']->queryPrepared($sql, array($plp_name, $GLOBALS['gCurrentOrgId']));
            $row = $statement->fetchObject();
            
            // Vergleich Version.php  ./. DB (hier: stand)
            if (!isset($row->plp_value) || strlen($row->plp_value) === 0 || $row->plp_value<>self::$stand)
            {
                $ret = true;
            }
        }
        else
        {
            $ret = true; 
        }

        return $ret;
    }

    /**
     * Loescht die Konfigurationsdaten in der Datenbank
     * @param   int     $deinst_org_select  0 = Daten nur in aktueller Org loeschen, 1 = Daten in allen Org loeschen
     * @return  string  $result             Meldung
     */
    public function delete($deinst_org_select)
    {
        $result = '';
        $result_data = false;
        $result_db = false;
        
        if ($deinst_org_select == 0)                    //0 = Daten nur in aktueller Org loeschen
        {
            $sql = 'DELETE FROM '.$this->table_name.'
        			      WHERE plp_name LIKE ?
        			        AND plp_org_id = ? ';
            $result_data = $GLOBALS['gDb']->queryPrepared($sql, array(self::$shortcut.'__%', $GLOBALS['gCurrentOrgId']));
        }
        elseif ($deinst_org_select == 1)              //1 = Daten in allen Orgs loeschen
        {
            $sql = 'DELETE FROM '.$this->table_name.'
        			      WHERE plp_name LIKE ? ';
            $result_data = $GLOBALS['gDb']->queryPrepared($sql, array(self::$shortcut.'__%'));
        }
        
        // wenn die Tabelle nur Eintraege dieses Plugins hatte, sollte sie jetzt leer sein und kann geloescht werden
        $sql = 'SELECT * FROM '.$this->table_name.' ';
        $statement = $GLOBALS['gDb']->queryPrepared($sql);
        
        if ($statement->rowCount() == 0)
        {
            $sql = 'DROP TABLE '.$this->table_name.' ';
            $result_db = $GLOBALS['gDb']->queryPrepared($sql);
        }
        
        $result  = ($result_data ? $GLOBALS['gL10n']->get('PLG_DECLARATION_OF_MEMBERSHIP_DEINST_DATA_DELETE_SUCCESS') : $GLOBALS['gL10n']->get('PLG_DECLARATION_OF_MEMBERSHIP_DEINST_DATA_DELETE_ERROR') );
        $result .= ($result_db ? $GLOBALS['gL10n']->get('PLG_DECLARATION_OF_MEMBERSHIP_DEINST_TABLE_DELETE_SUCCESS') : $GLOBALS['gL10n']->get('PLG_DECLARATION_OF_MEMBERSHIP_DEINST_TABLE_DELETE_ERROR') );
        $result .= ($result_data ? $GLOBALS['gL10n']->get('PLG_DECLARATION_OF_MEMBERSHIP_DEINST_ENDMESSAGE') : '' );
        
        return $result;
    }
}