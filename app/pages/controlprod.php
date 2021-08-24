<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 24.08.2021
 * Time: 20:30
 */

namespace App\Pages;

use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextArea;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Panel;
use \Zippy\Html\Label;
use \Zippy\Html\DataList\DataView;
use \Zippy\Html\DataList\ArrayDataSource;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Link\SubmitLink;

class ControlProd extends \App\Pages\Base
{
    public function __construct($params = null)
    {
        parent::__construct($params);

        $this->add(new \App\Widgets\MenuProduction('widgetMenu', $this, ''))->setVisible(true);

        $this->add(new Form('totalWorkForm'));
        $this->add(new ComponentProd('testComponent'));//->onClick($this, 'testOnClick');
        $this->add(new ClickLink('clock'))->onClick($this, 'testOnClick');

    }

    public function testOnClick($sender)
    {
//        var_dump($sender);
        $this->testComponent->testContent($sender);
    }
}

