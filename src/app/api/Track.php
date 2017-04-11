<?php
namespace app\api;

use Purl\Url;

class Track
{
    function build($f3) {
        $url = $f3->get('GET.url') ?? '';
        if (!empty($url)) {
            $url = new Url($url);
            $url->query->set('track_code', $f3->get('GET.code'));
        }
        echo (string) $url;
    }
}