<?php

class Authentication {

    public static function login($username, $password) {
        // Implement login logic here

    }

    public static function logout() {
        // Implement logout logic here
        Cookie::delete("user");
    }


    public static function isLoggedIn() {
        // Implement check for logged-in user
        return Cookie::get("user") !== null;
        
    }

}
