<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 23.10.2021
 * Time: 19:50
 */

namespace App\Entity;

/**
 * Клас-сущность  ТМЦ
 *
 * @table=kindworks
 * @view=kindworks
 * @keyfield=id
 */

class Kind extends \ZCL\DB\Entity
{
    protected function init() {
        $this->item_id = 0;
        $this->parealist_id = 0;
        $this->price = 0;

    }

}