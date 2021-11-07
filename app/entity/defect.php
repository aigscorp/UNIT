<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 06.11.2021
 * Time: 23:20
 */
namespace App\Entity;

/**
 * Клас-сущность брак
 *
 * @table=defect_model
 * @view=defect_model
 * @keyfield=id
 */

class Defect extends \ZCL\DB\Entity
{
    protected function init() {
        $this->id = 0;
        $this->master_id = 0;
        $this->size = '';
        $this->qty = 0;
        $this->monitor = '';
        $this->detail = '';
        $this->status = false;
        $this->created = new \DateTime();
    }

}