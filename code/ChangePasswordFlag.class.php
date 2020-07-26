<?php

namespace FormTools\Modules\ForcePasswordChange;

abstract class ChangePasswordFlag {
    const FORCE_CHANGE = 'force_change';
    const NO_CHANGE_NEEDED = 'no_change_needed';

    public static function getDefaultChangePasswordFlag()
    {
        return ChangePasswordFlag::NO_CHANGE_NEEDED;
    }
}