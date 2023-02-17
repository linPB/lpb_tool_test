<?php
/**
 * Created by PhpStorm
 * Func:
 * User: lpb
 * Date: 2023/2/17
 * Time: 16:08
 */

require "vendor/autoload.php";
use Linpeibing\LpbTool\DB;

try {
    $db = (new DB)->get();
    $article = $db::table('test_tb')->first();
    var_dump($article);

    $db::table('test_tb')->insert(
        ['title' => 'c', 'content' => "cc"]
    );

} catch (Exception $e) {
    var_dump($e->getMessage());
}
