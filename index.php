<?php

/**
 * @package Routiny
 * @copyright &copy; 2010
 * @author Nikita Titov <nikita@zencode.ru>
 */
//Use lazy loading with files in /lib directory and 
//you custom autoload() function with 
//spl_autoload_register() if you need
//
//require_once dirname(__FILE__)
//        . DIRECTORY_SEPARATOR . 'compressed' . DIRECTORY_SEPARATOR . 'rt.php';
error_reporting(E_ALL);

function __autoload($class) {
    require_once "lib/$class.php";
}

try {
    $rt = new Rt();
    $rt
            ->get('/(.*)/i')
            ->dispatch(function() {
                        return '<h1>404</h1>';
                    })
            ->get('/^\/($|index$|index.(html|php|asp|jsp|htm)$)/i')
            ->dispatch(function() {
                        return '<h1>It works!</h1>';
                    })
            ->get('/^\/string$/i')
            ->dispatch(function() {
                        return array('json_string' => str_shuffle('hello world'));
                    })
            ->json()
            ->get('/^\/random$/i')
            ->dispatch(function() {
                        return array('rand' => rand(0, 1000));
                    })
            ->json()
            ->post('/^\/test$/i')
            ->dispatch(function($msg='default', $test='not default') {
                        return 'this is reply for POST request with params '
                        . $msg
                        . ' and also '
                        . $test;
                    })
            ->get('/^\/xslt$/i')
            ->dispatch(function($data='test') {
                        return array('input' => $data);
                    })
            ->xml()
            ->xslt(array('stylesheet' => 'sample.xslt'))
            ->get('/\/new/i')
            ->dispatch(function() {
                        return array(
                            'Movie' => 'SpongeBob SquarePants',
                            'Date' => 1999,
                            'Title' => 'Theme Song lyrics',
                            'Lyrics' =>
                            array(
                                'Captain' => 'Are you ready kids?',
                                'Kids' => 'Aye-aye Captain.',
                                'Captain' => "I can't hear you...",
                                'Kids' => 'Aye-aye Captain!!',
                                'Captain' => 'Oh! Who lives in a pineapple under the sea?',
                                'Kids' => 'SpongeBob SquarePants!',
                                'Captain' => 'Absorbent and yellow and porous is he!',
                                'Kids' => 'SpongeBob SquarePants!',
                                'Captain' => 'If nautical nonsense be something you wish...',
                                'Kids' => 'SpongeBob SquarePants!',
                                'Captain' => 'Then drop on the deck and flop like a fish!',
                                'Kids' => 'SpongeBob SquarePants!',
                                'Captain' => 'Ready?',
                                'Everybody' => 'SpongeBob SquarePants! SpongeBob SquarePants! SpongeBob SquarePants!',
                                'Captain' => 'SpongeBob.... SquarePants! Haha!',
                            ),
                        );
                    })
            ->format_by_request()
            ->run();
} catch (RoutinyException $e) {
    echo $e->getMessage();
}
