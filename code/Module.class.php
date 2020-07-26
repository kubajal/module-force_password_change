<?php

namespace FormTools\Modules\ForcePasswordChange;

use FormTools\Module as FormToolsModule;
use FormTools\Hooks;
use FormTools\Core;
use FormTools\Accounts;
use FormTools\General;

class Module extends FormToolsModule
{
    protected $moduleName = "Force Password change";
    protected $moduleDesc = "Force your users to change their passwords.";
    protected $author = "Jakub Jalowiec";
    protected $authorEmail = "kuba.jalowiec@protonmail.com";
    protected $authorLink = "https://github.com/kubajal";
    protected $version = "3.0.0";
    protected $date = "2020-07-26";
    protected $originLanguage = "en_us";

    protected $nav = array(
        "module_name" => array("index.php", false)
    );

    public function beforeUpdateClient($params)
    {
        $client_info = Accounts::getAccountInfo($params['account_id']);
        $params['info']['old_password_hash'] = $client_info['password'];
        return $params;
    }
    
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
    
    public function checkClientMayView($params)
    {
        $client_info = Accounts::getAccountInfo($params['client_id']);
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
    
    public function install($module_id) {

        Hooks::registerHook("code", "force_password_change", "start", "FormTools\\Clients::updateClient", "beforeUpdateClient", 50, true);
        Hooks::registerHook("code", "force_password_change", "end", "FormTools\\Clients::updateClient", "afterUpdateClient", 50, true);
        Hooks::registerHook("code", "force_password_change", "main", "FormTools\\General::checkClientMayView", "checkClientMayView", 50, true);
        Hooks::registerHook("code", "force_password_change", "end", "FormTools\\General::displayCustomPageMessage", "displayCustomPageMessage", 50, true);
        return array(true, "");
    }

    public function uninstall($module_id) {
        Hooks::unregisterModuleHooks("force_password_change");
        return array(true, "");
    }
}
