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

namespace FormTools\Modules\ForcePasswordChange;

abstract class PasswordExpiryFlag {
    const SOFT_EXPIRY = 'soft_expiry';
    const HARD_EXPIRY = 'hard_expiry';
    const NO_EXPIRY = 'no_expiry';
    
    public static function getPasswordExpiryFlagDbColumnName()
    {
        return 'password_expiry_flag';
    }

    public static function getDefaultPasswordExpiryFlag()
    {
        return PasswordExpiryFlag::NO_EXPIRY;
    }
}