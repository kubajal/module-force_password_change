<?php

namespace FormTools\Modules\ForcePasswordChange;

use FormTools\Core;
use FormTools\Accounts;
use FormTools\Modules\ExtendedClientFields;
use FormTools\Modules\ExtendedClientFields\Fields;
use FormTools\Modules;

class DBAccessLayer 
{
    public static function getChangePasswordFlagDbColumnName()
    {
        return 'force_change_password';
    }

    public static function setChangePasswordChangeFlagForUser($account_id, $flag)
    {
        $query_string = "
        SELECT client_field_id
        FROM {PREFIX}module_extended_client_fields
        WHERE field_identifier = '" . DBAccessLayer::getChangePasswordFlagDbColumnName() . "'
        LIMIT 1
    ";
        Core::$db->query($query_string);
        Core::$db->execute();
        $change_password_field = "ecf_" . Core::$db->fetch()['client_field_id'];
        Core::$db->query("
            UPDATE {PREFIX}account_settings
            SET setting_value = '" . $flag . "'
            WHERE account_id = :account_id AND setting_name = :change_password_field
        ");
        Core::$db->bind("account_id", $account_id);
        Core::$db->bind("change_password_field", $change_password_field);
        Core::$db->execute();
    }
    
    public static function getForcePasswordChangeFlagForUser($client_id)
    {
        $client_info = Accounts::getAccountInfo($client_id);
        Core::$db->query("
            SELECT client_field_id
            FROM {PREFIX}module_extended_client_fields
            WHERE field_identifier = '" . DBAccessLayer::getChangePasswordFlagDbColumnName() ."'
            LIMIT 1
        ");
        Core::$db->execute();
        $change_password_field = "ecf_" . Core::$db->fetch()['client_field_id'];
        Core::$db->query("
            SELECT setting_value
            FROM {PREFIX}account_settings
            WHERE account_id = :account_id AND setting_name = :change_password_field
            LIMIT 1
        ");
        Core::$db->bind("account_id", $client_info['account_id']);
        Core::$db->bind("change_password_field", $change_password_field);
        Core::$db->execute();

        
        if (Core::$db->numRows() == 0) {
            // the flag hasn't ever been set for this user
            // return default value
            return ChangePasswordFlag::getDefaultChangePasswordFlag();
        }

        $fetched = Core::$db->fetch();
        return $fetched['setting_value'];

    }

    public static function removeChangePasswordFlagColumnFromDatabase()
    {
        Core::$db->query("
            SELECT client_field_id
            FROM {PREFIX}module_extended_client_fields
            WHERE field_identifier = '" . DBAccessLayer::getChangePasswordFlagDbColumnName() . "'
            LIMIT 1
        ");
        Core::$db->execute();
        $field_id = Core::$db->fetch()['client_field_id'];
        
        // those 3 lines below are a bit of magic because there is no good API in ECF to add a new field from outside of ECF
        if(Modules::isValidModule("extended_client_fields"))
        {
            $ecf = Modules::instantiateModule("extended_client_fields");
            $L_for_ecf = $ecf->getLangStrings();
            Fields::deleteField($field_id, $L_for_ecf);
        }
    }

    public static function addChangePasswordFlagColumnToDatabase()
    {
            // we need to create a new ECF field (check Fields::addField($info, $L) in ECF for more info how to call it)
            $info = array(
                'num_rows' => "2",
                "template_hook" => "edit_client_main_top",
                "admin_only" => "yes",
                "field_label" => "Force password change flag",
                "field_type" => "radios",
                "field_identifier" => DBAccessLayer::getChangePasswordFlagDbColumnName(),
                "default_value" => ChangePasswordFlag::getDefaultChangePasswordFlag(),
                "is_required" => "yes",
                "error_string" => "",
                "field_orientation" => "horizontal",
                "option_list_id" => "",
                "option_source" => "custom_list",
                "field_option_text_1" => ChangePasswordFlag::FORCE_CHANGE,
                "field_option_text_2" => ChangePasswordFlag::NO_CHANGE_NEEDED,
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

    public static function doesChangePasswordEcfFieldExist()
    {
        Core::$db->query("
            SELECT field_identifier
            FROM {PREFIX}module_extended_client_fields
            WHERE field_identifier = '" . DBAccessLayer::getChangePasswordFlagDbColumnName() ."'
            LIMIT 1
        ");
        Core::$db->execute();
        if(Core::$db->numRows() == 0)
            return false;
        return true;
    }
}