<?php
namespace helper;
use data\Database;
use DB\SQL\Mapper;

/**
 * Created by IntelliJ IDEA.
 * User: jibo
 * Date: 2017/3/13
 * Time: 11:10
 */
class Bridge
{
    function isTokenValid($token)
    {
        $user = new Mapper(Database::mysql(), 'oc_user');
        $user->load(['SHA1(concat(salt, SHA1(concat(salt, SHA1(password))))) = ?', $token]);
        return !$user->dry();
    }
}