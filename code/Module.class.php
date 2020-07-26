<?php

namespace FormTools\Modules\ForcePasswordChange;

use FormTools\Module as FormToolsModule;
use FormTools\Hooks;
use FormTools\Modules;
use FormTools\Core;
use FormTools\Accounts;
use FormTools\General;
use FormTools\Modules\ExtendedClientFields\Fields;
use FormTools\Modules\ExtendedClientFields\Module as ECFModule;

class Module extends FormToolsModule
{
    protected $moduleName = "Force Password change";
    protected $moduleDesc = "Force your users to change their passwords.";
    protected $author = "Jakub Jalowiec";
    protected $authorEmail = "kuba.jalowiec@protonmail.com";
    protected $authorLink = "https://github.com/kubajal";
    protected $version = "0.0.1";
    protected $date = "2020-07-26";
    protected $originLanguage = "en_us";

    private $dbColumn = 'force_change_password';

    protected $nav = array(
        "module_name" => array("index.php", false)
    );

	/**
	 * A new hook for Clients::updateClient (start). 
     * Passes on old password hash to check if the password has been changed.
	 * @param array $params the dictionary of parameters containing 'account_id' field
	 * @return array
	 */
    public function beforeUpdateClient($params)
    {
        $client_info = Accounts::getAccountInfo($params['account_id']);
        $params['info']['old_password_hash'] = $client_info['password'];
        return $params;
    }
    
	/**
	 * A new hook for Clients::updateClient (end). 
     * Checks if the password has been changed to a new one - if so, updates the ECF field to 'no' (the user no longer needs to change the password).
	 * @param array $params the dictionary of parameters containing 'password', 'old_password_hash', 'account_id' fields
	 * @return array
	 */
    public function afterUpdateClient($params)
    {
        $new_password_hash = General::encode($params['info']['password']);
        if($new_password_hash != $params['info']['old_password_hash'])
        {
            $db = Core::$db;
            $db->query("
                SELECT client_field_id
                FROM {PREFIX}module_extended_client_fields
                WHERE field_identifier = 'zmien_haslo'
                LIMIT 1
            ");
            $db->execute();
            $change_password_field = "ecf_" . $db->fetch()['client_field_id'];
            $db->query("
                UPDATE {PREFIX}account_settings
                SET setting_value = 'no'
                WHERE account_id = :account_id AND setting_name = :change_password_field
            ");
            $db->bind("account_id", $params['account_id']);
            $db->bind("change_password_field", $change_password_field);
            $db->execute();
        }
        return $params;
    }
    
	/**
	 * A new hook for General::checkClientMayView. 
     * Checks if the client has $dbColumn set to 'yes' which means that they are not supposed to view any submissions until they change their password.
	 * @param array $params the dictionary of parameters containing 'client_id' field
	 * @return array
	 */
    public function checkClientMayView($params)
    {
        $client_info = Accounts::getAccountInfo($params['client_id']);
        $db = Core::$db;
        $db->query("
            SELECT client_field_id
            FROM {PREFIX}module_extended_client_fields
            WHERE field_identifier = '" . $this->dbColumn ."'
            LIMIT 1
        ");
        $db->execute();
        $change_password_field = "ecf_" . $db->fetch()['client_field_id'];
        $db->query("
            SELECT setting_value
            FROM {PREFIX}account_settings
            WHERE account_id = :account_id AND setting_name = :change_password_field
            LIMIT 1
        ");
        $db->bind("account_id", $client_info['account_id']);
        $db->bind("change_password_field", $change_password_field);
        $db->execute();

        $fetched = $db->fetch();

        if($fetched['setting_value'] == 'yes')
        {
            header("location: /formtools/clients/account/index.php?page=main&message=force_password_change");
            exit;
        }
    }
    
	/**
	 * A new hook for General::displayCustomPageMessage. 
     * Checks if the message to be displayed is force_password_change.
	 * @param array $params the dictionary of parameters containing 'flag' field
	 * @return array
	 */
    public function displayCustomPageMessage($params)
    {
        $found = true;
        $g_success = true;
        $g_message= '';

        if($params['flag'] == 'force_password_change')
        {
            $found = true;
            $g_success = false;
            $g_message= 'some text';
        }
        return array(
            "found" => $found,
            "g_success" => $g_success,
            "g_message" => $g_message
        );
    }


	/**
	 * Checks if the 'change_password' flag ECF field already exists. If not, it creates it.
	 */
    function addColumnToTheDatabse()
    {
        $db = Core::$db;
        $db->query("
            SELECT field_identifier
            FROM {PREFIX}module_extended_client_fields
            WHERE field_identifier = '" . $this->dbColumn ."'
            LIMIT 1
        ");
        $db->execute();
        if ($db->numRows() == 0) {
            // we need to create a new ECF field (check Fields::addField($info, $L) in ECF for more info how to call it)
            $info = array(
                'num_rows' => "2",
                "template_hook" => "edit_client_main_top",
                "admin_only" => "yes",
                "field_label" => "Force password change flag",
                "field_type" => "radios",
                "field_identifier" => $this->dbColumn,
                "default_value" => "yes",
                "is_required" => "yes",
                "error_string" => "",
                "field_orientation" => "horizontal",
                "option_list_id" => "",
                "option_source" => "custom_list",
                "field_option_text_1" => "yes",
                "field_option_text_2" => "no",
                "add" => "Add Field"
            );

            // those 3 lines below are a bit of magic because there is no good API in ECF to add a new field from outside of ECF
            if(Modules::isValidModule("extended_client_fields"))
            {
                $ecf = Modules::instantiateModule("extended_client_fields");
                $L_for_ecf = $ecf->getLangStrings();
                Fields::addField($info, $L_for_ecf);
            }
        }
        else {
            // do field $dbColumn already exists (we assume that both 'yes' and 'no' option fields associated with it exist too)
            // do nothing (todo: check that option fields exist...)
        }
    }

    function removeColumnFromDatabase()
    {
        $db = Core::$db;
        $db->query("
            SELECT client_field_id
            FROM {PREFIX}module_extended_client_fields
            WHERE field_identifier = '" . $this->dbColumn . "'
            LIMIT 1
        ");
        $db->execute();
        $field_id = $db->fetch()['client_field_id'];
        
        $db->query("
            DELETE
            FROM {PREFIX}module_extended_client_fields
            WHERE client_field_id = '" . $field_id . "'
        ");
        $db->execute();
        
        $db->query("
            DELETE
            FROM {PREFIX}module_extended_client_field_options
            WHERE client_field_id = '" . $field_id . "'
        ");
        $db->execute();
    }
    
    public function install($module_id) {

		if (!Modules::checkModuleEnabled("extended_client_fields")) {
            $L = $this->getLangStrings();
            return array(false, $L["ecf_requirement_not_fulfiled"]);
        }

        $this->addColumnToTheDatabse();

        Hooks::registerHook("code", "force_password_change", "start", "FormTools\\Clients::updateClient", "beforeUpdateClient", 50, true);
        Hooks::registerHook("code", "force_password_change", "end", "FormTools\\Clients::updateClient", "afterUpdateClient", 50, true);
        Hooks::registerHook("code", "force_password_change", "main", "FormTools\\General::checkClientMayView", "checkClientMayView", 50, true);
        Hooks::registerHook("code", "force_password_change", "end", "FormTools\\General::displayCustomPageMessage", "displayCustomPageMessage", 50, true);
        return array(true, "");
    }

    public function uninstall($module_id) {
        Hooks::unregisterModuleHooks("force_password_change");
        $this->removeColumnFromDatabase();
        return array(true, "");
    }
}
