# Example:

```
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
    ->format_by_request()
    ->run();
```
