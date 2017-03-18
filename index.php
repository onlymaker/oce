<?php
require_once 'vendor/autoload.php';

call_user_func(function ($f3) {
    if (!$f3->log) {
        $root = $f3->get('ROOT');

        $f3->config($root . '/src/cfg/system.ini');
        $f3->config($root . '/src/cfg/local.ini');

        $f3->mset([
            'AUTOLOAD' => $root . '/src/',
            'LOGS' => $root . '/src/log/'
        ]);

        $logger = new Log(date('Y-m-d') . '.log');

        $f3->log = function ($message, $context = []) use ($logger) {
            if (false !== strpos($message, '{') && !empty($context)) {
                $replacements = [];
                foreach ($context as $key => $val) {
                    if (is_null($val) || is_scalar($val) || (is_object($val) && method_exists($val, "__toString"))) {
                        $replacements['{' . $key . '}'] = $val;
                    } elseif (is_object($val)) {
                        $replacements['{' . $key . '}'] = '[object ' . get_class($val) . ']';
                    } else {
                        $replacements['{' . $key . '}'] = '[' . gettype($val) . ']';
                    }
                }
                $message = strtr($message, $replacements);
            }
            $logger->write($message, 'Y-m-d H:i:s');
        };

        if (PHP_SAPI != 'cli') {
            $f3->config($root . '/src/cfg/map.ini');
            $f3->config($root . '/src/cfg/route.ini');

            $f3->mset([
                'UI' => $root . '/src/tpl/',
                'UPLOADS' => $root . '/data/',
                'ONERROR' => function ($f3) {
                    $error = $f3->get('ERROR');

                    if (!$f3->get('DEBUG')) {
                        unset($error['trace']);
                    }

                    if ($f3->get('AJAX')) {
                        echo json_encode(['error' => $error], JSON_UNESCAPED_UNICODE);
                    } else {
                        $f3->set('error', $error);
                        echo Template::instance()->render('error.html');
                    }
                }
            ]);

            $f3->run();
        }
    }
}, Base::instance());
