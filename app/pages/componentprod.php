<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 24.08.2021
 * Time: 21:26
 */

namespace App\Pages;


class ComponentProd extends \Zippy\Html\CustomComponent implements \Zippy\Interfaces\Requestable
{
    public $str = "";

    public function __construct($id)
    {
        parent::__construct($id);

    }
    public function getContent($attributes)
    {
        // TODO: Implement getContent() method.
        $this->str = "<ul><li>100</li><li>200</li><li>300</li></ul>";
        return $this->str;
    }

    public function RequestHandle()
    {
        // TODO: Implement RequestHandle() method.

    }

    public function testContent($sender)
    {
//        return "<p>hello</p>";
        $this->str = "<div>abc</div>";
//        var_dump($sender);
        return $this->getContent($this->str);
    }
}