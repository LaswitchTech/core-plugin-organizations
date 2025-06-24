<?php

/**
 * Core Framework - OrganizationsHelper
 *
 * @license    MIT (https://mit-license.org/)
 * @author     Louis Ouellet <louis@laswitchtech.com>
 */

// Import additionnal class into the global namespace
use \LaswitchTech\Core\Abstracts\Helper;

class OrganizationsHelper extends Helper {

    /**
     * Generate a random string
     *
     * @param int $length
     * @return string
     */
    public function generate($length = 8): string
    {
        $characters = '0123456789';
        $characters .= 'abcdefghijklmnopqrstuvwxyz';
        $characters .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $characters .= '!@#$%^&*()_+{}:<>?';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
