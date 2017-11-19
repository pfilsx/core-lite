<?php


namespace core\base;


abstract class BaseUser extends BaseObject
{
    protected $_isGuest;

    public abstract function login($login, $password);
    public abstract function logout();

    public function getIsGuest(){
        return $this->_isGuest;
    }

    public function can($permissionRole){
        return true;
    }
}