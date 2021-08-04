<?php

namespace App\Entity\Doc;

use App\Entity\Pay;
use App\Helper as H;

/**
 * Класс-сущность  документ   выплата  зарплаты
 *
 */
class OutSalary extends Document
{

    public function Execute() {


        $payed = Pay::addPayment($this->document_id, $this->document_date, 0 - $this->amount, $this->headerdata['payment'], \App\Entity\IOState::TYPE_SALARY_OUTCOME,$this->notes);
        if ($payed > 0) {
            $this->payed = $payed;
        }
        \App\Entity\IOState::addIOState($this->document_id, 0- $this->amount,\App\Entity\IOState::TYPE_SALARY_OUTCOME);

        return true;
    }

    public function generateReport() {

        $detail = array();

        foreach ($this->unpackDetails('detaildata') as $emp) {


            $detail[] = array(
                "emp_name" => $emp->emp_name,
                "amount"   => H::fa($emp->amount)
            );
        }
        $header = array(
            "_detail"         => $detail,
            'total'           => H::fa($this->amount),
            'date'            => H::fd($this->document_date),
            "notes"           => nl2br($this->notes),
            "month"           => $this->headerdata["monthname"],
            "year"            => $this->headerdata["year"],
            "paymentname"     => $this->headerdata["paymentname"],
            "document_number" => $this->document_number
        );
        $report = new \App\Report('doc/outsalary.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'ВЗ-000000';
    }

}
