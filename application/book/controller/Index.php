<?php
/**
 * Created by PhpStorm.
 * User: MOXIZW
 * Date: 2019/8/15
 * Time: 19:45
 */

namespace app\dbase\controller;

use think\Controller;

class Index extends Controller
{
    public function index()
    {
        $this->redirect('/admin/');
    }
}
