<?php
namespace app;

use app\common\Url;
use data\Database;
use DB\SQL\Mapper;

class Login
{
    use Url;

    function get()
    {
        echo \Template::instance()->render('login.html');
    }

    function post($f3)
    {
        $username = $f3->get('POST.username');
        $password = $f3->get('POST.password');

        $user = new Mapper(Database::mysql(), 'oc_user');

        $user->load(['username = ?', $username]);
        if ($user->dry()) {
            $error = [
                'code' => 1,
                'text' => '[' . $username . '] not existed'
            ];
        } else {
            $filter = ['username = ? AND password = SHA1(concat(salt, SHA1(concat(salt, SHA1(?)))))', $username, $password];
            $user->reset();
            $user->load($filter);
            if ($user->dry()) {
                $error = [
                    'code' => 2,
                    'text' => 'password incorrect'
                ];
            } else {
                $f3->set('SESSION.AUTH', true);
                $error = [
                    'code' => 0,
                    'text' => 'success'
                ];
            }
        }

        echo json_encode(['error' => $error]);
    }
}
