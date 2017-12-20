<?php


namespace core\base;


abstract class BaseUser extends BaseObject
{
    /**
     * Indicates whether the user is guest
     * @var boolean
     */
    protected $_isGuest;
    /**
     * Sign in user by login and password
     * @param string $login
     * @param string $password
     * @return boolean|void
     */
    public function login($login, $password){

    }
    /**
     * Sign in user by token
     * @param string $token
     * @return boolean|void
     */
    public function loginByToken($token){

    }
    /**
     * Sign out user
     * @return boolean|void
     */
    public abstract function logout();
    /**
     * Get user is guest
     * @return bool
     */
    public function getIsGuest(){
        return $this->_isGuest;
    }
    /**
     * Indicates if current logged in user has access to specific permission role
     * @param mixed $permissionRole
     * @return bool
     */
    public function can($permissionRole){
        return true;
    }
}