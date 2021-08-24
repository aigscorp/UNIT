<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 23.08.2021
 * Time: 22:43
 */

namespace App\Widgets;

use App\Application as App;
use Zippy\Html\Form\Form;
use \Zippy\Html\Link\ClickLink;

class MenuProduction extends \Zippy\Html\PageFragment
{
    private $_page;
    private $_event;

    public function __construct($id, $page, $event)
    {
        parent::__construct($id);
        $this->_page = $page;
        $this->_event = $event;

        $this->add(new Form('panelButton'));
        $this->panelButton->add(new ClickLink('showProduction'))->onClick($this, 'showProductionOnClick');
        $this->panelButton->add(new ClickLink('showWork'))->onClick($this, 'showWorkOnClick');
        $this->panelButton->add(new ClickLink('showStore'));
        $this->panelButton->add(new ClickLink('showCustomer'));
        $this->panelButton->add(new ClickLink('showDirector'));
    }

    public function showWorkOnClick()
    {
        App::Redirect("\\App\\Pages\\ControlProd");
    }
    public function showProductionOnClick()
    {
        App::Redirect("\\App\\Pages\\Production");
    }
}