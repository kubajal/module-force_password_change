<?php

namespace FormTools\Modules\ForcePasswordChange;

use FormTools\Module as FormToolsModule;
use FormTools\Hooks;
use FormTools\Modules;
use FormTools\Accounts;
use FormTools\General;
use FormTools\Core;
use Formtools\User;
use FormTools\Modules\ExtendedClientFields\Fields;
use FormTools\Modules\ForcePasswordChange\DBAccessLayer;

require "DBAccessLayer.class.php";
require "ChangePasswordFlag.class.php";

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

    protected $nav = array(
        "module_name" => array("index.php", false)
    );

	/**
	 * A new hook for Clients::updateClient (start). 
     * Passes on old password hash to check if the password has been changed.
	 * @param array $params the dictionary of parameters containing 'account_id' field
	 * @return array
	 */
    public function beforeUpdateClientHook($params)
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
    public function afterUpdateClientHook($params)
    {
        $new_password_hash = General::encode($params['info']['password']);
        if($new_password_hash != $params['info']['old_password_hash'])
        {
           DBAccessLayer::setPasswordChangeFlagForUser($params['account_id'], ChangePasswordFlag::NO_CHANGE_NEEDED);
        }
        else if ($new_password_hash == $params['info']['old_password_hash']){
            header("location: " . Core::getRootUrl() ."/clients/account/index.php?page=main&message=passwords_are_the_same_message");
            exit;
        }
        return $params;
    }
    
	/**
	 * A new hook for General::checkClientMayView. 
     * Checks if the client has $dbColumn set to 'yes' which means that they are not supposed to view any submissions until they change their password.
	 * @param array $params the dictionary of parameters containing 'client_id' field
	 * @return array
	 */
    public function checkClientMayViewHook($params)
    {
        $flag = DBAccessLayer::getForcePasswordChangeFlagForUser($params['client_id']);
        if($flag == ChangePasswordFlag::FORCE_CHANGE)
        {
            // redirect to Settings page if the user is required to change their password
            // use force_password_change_message as notification to the user that they are required to change the password
            header("location: " . Core::getRootUrl() ."/clients/account/index.php?page=main&message=force_password_change_message");
            exit;
        }
    }
    
	/**
	 * A new hook for General::displayCustomPageMessage. 
     * Checks if the message to be displayed is force_password_change.
	 * @param array $params the dictionary of parameters containing 'flag' field
	 * @return array
	 */
    public function displayCustomPageMessageHook($params)
    {
        $found = true;
        $g_success = true;
        $g_message= '';
        $L = $this->getLangStrings();
        
        if($params['flag'] == 'force_password_change_message')
        {
            $found = true;
            $g_success = false;
            $g_message= $L['force_password_change_message'];
        }
        else if($params['flag'] == 'passwords_are_the_same_message')
        {
            $found = true;
            $g_success = false;
            $g_message= $L['passwords_are_the_same_message'];
        }
        return array(
            "found" => $found,
            "g_success" => $g_success,
            "g_message" => $g_message
        );
    }

	/**
	 * A new hook for Accounts::sendPassword (end). 
     * Force password change after sending a new password to a user who used the "forgotten password" functionality.
	 * @param array $params
	 * @return array
	 */
    public function afterSendPasswordHook($params)
    {
        $account = Accounts::getAccountByUsername($params['info']["username"]);
        $account_id = $account['account_id'];
        DBAccessLayer::setPasswordChangeFlagForUser($account_id, ChangePasswordFlag::FORCE_CHANGE);
    }

	/**
	 * Checks if the 'change_password' flag ECF field already exists. If not, it creates it.
	 */
    function addChangePasswordFlagColumnToDatabase()
    {
        if (!DBAccessLayer::doesChangePasswordEcfFieldExist()) {
            DBAccessLayer::addChangePasswordFlagColumnToDatabase();
        }
        else {
            // do field $dbColumn already exists (we assume that both 'yes' and 'no' option fields associated with it exist too)
            // do nothing (todo: check that option fields exist...)
        }
    }
    
    public function install($module_id) {

		if (!Modules::checkModuleEnabled("extended_client_fields")) {
            $L = $this->getLangStrings();
            return array(false, $L["ecf_requirement_not_fulfiled"]);
        }

        $this->addChangePasswordFlagColumnToDatabase();

        Hooks::registerHook("code", "force_password_change", "start", "FormTools\\Clients::updateClient", "beforeUpdateClientHook", 50, true);
        Hooks::registerHook("code", "force_password_change", "end", "FormTools\\Clients::updateClient", "afterUpdateClientHook", 50, true);
        Hooks::registerHook("code", "force_password_change", "main", "FormTools\\General::checkClientMayView", "checkClientMayViewHook", 50, true);
        Hooks::registerHook("code", "force_password_change", "end", "FormTools\\General::displayCustomPageMessage", "displayCustomPageMessageHook", 50, true);
        Hooks::registerHook("code", "force_password_change", "end", "FormTools\\Accounts::sendPassword", "afterSendPasswordHook", 50, true);
        return array(true, "");
    }

    public function uninstall($module_id) {
        Hooks::unregisterModuleHooks("force_password_change");
        DBAccessLayer::removeChangePasswordFlagColumnFromDatabase();
        return array(true, "");
    }
}
