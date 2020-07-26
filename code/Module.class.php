<?php

namespace FormTools\Modules\ForcePasswordChange;

use FormTools\Module as FormToolsModule;

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
}
