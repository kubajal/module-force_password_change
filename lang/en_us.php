<?php

/* 
Author:  Jakub Jalowiec
E-mail:  kuba.jalowiec@protonmail.com
Website: https://github.com/kubajal/module-force_password_change
This file is part of Force Password Change - a Formtools module.

Force Password Change is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

Force Password Change is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Force Password Change.  If not, see <http://www.gnu.org/licenses/>.
*/

$L = array();

// required fields
$L["module_name"] = "Force Password Change";
$L["module_description"] = "Force your users to change their passwords. See more in the <b><a href=\"https://github.com/kubajal/module-force_password_change\"> git repo </a></b>.";

// custom fields
$L["ecf_requirement_not_fulfiled"] = "Module you are trying to install depends on the Extended Client Fields module which appears to be missing or is disabled.";
$L["passwords_are_the_same_message"] = "The provided password was the same as the old one. Choose a different password.";
$L["force_password_change_message"] = "Before viewing any sensitive data you must change your password to a new one.";
