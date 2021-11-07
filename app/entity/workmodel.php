<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 24.10.2021
 * Time: 11:10
 */

namespace App\Entity;

/**
 * Клас-сущность  работы
 *
 * @table=pasport_tax
 * @view=pasport_tax
 * @keyfield=id
 */

class WorkModel
{
    protected function init() {
        $this->item_id = 0;
        $this->pasport_id = 0;
        $this->model_item = "";
        $this->detail = "";
        $this->qty_material = false;
    }
}