<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Helper as H;

/**
 * Класс-сущность  локумент акт  о  выполненных работах
 *
 *
 */
class ServiceAct extends Document
{

    public function generateReport() {
        $firm = H::getFirmData($this->firm_id, $this->branch_id);

        $i = 1;

        $detail = array();
        foreach ($this->unpackDetails('detaildata') as $ser) {
            $detail[] = array("no"           => $i++,
                              "service_name" => $ser->service_name,
                              "desc"         => $ser->desc,
                              "qty"          => H::fqty($ser->quantity),
                              "price"        => H::fa($ser->price),
                              "amount"       => H::fa($ser->price * $ser->quantity)
            );
        }

        $header = array('date'            => H::fd($this->document_date),
                        "_detail"         => $detail,
                        "customer_name"   => $this->customer_name,
                        "firm_name"       => $firm['firm_name'],
                        "gar"             => $this->headerdata['gar'],
                        "isdevice"        => strlen($this->headerdata["device"]) > 0,
                        "device"          => $this->headerdata["device"],
                        "isfirm"          => strlen($firm["firm_name"]) > 0,
                        "iscontract"      => $this->headerdata["contract_id"] > 0,
                        "devsn"           => $this->headerdata["devsn"],
                        "document_number" => $this->document_number,
                        "payamount"       => H::fa($this->payamount),
                        "payed"           => H::fa($this->payed),
                        "total"           => H::fa($this->amount)
        );
        if ($this->headerdata["contract_id"] > 0) {
            $contract = \App\Entity\Contract::load($this->headerdata["contract_id"]);
            $header['contract'] = $contract->contract_number;
            $header['createdon'] = H::fd($contract->createdon);
        }


        $report = new \App\Report('doc/serviceact.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $conn = \ZDB\DB::getConnect();


        foreach ($this->unpackDetails('detaildata') as $ser) {

            $sc = new Entry($this->document_id, 0 - ($ser->price * $ser->quantity), $ser->quantity);
            $sc->setService($ser->service_id);

            $sc->setExtCode($ser->price); //Для АВС 
            //$sc->setCustomer($this->customer_id);
            $sc->save();
        }
        if ($this->headerdata['payment'] > 0 && $this->payed > 0) {
            $payed = \App\Entity\Pay::addPayment($this->document_id, $this->document_date, $this->payed, $this->headerdata['payment'], \App\Entity\Pay::PAY_BASE_INCOME);
            if ($payed > 0) {
                $this->payed = $payed;
            }
        }

        return true;
    }

    public function supportedExport() {
        return array(self::EX_EXCEL, self::EX_PDF, self::EX_POS);
    }

    protected function getNumberTemplate() {
        return 'АКТ-000000';
    }

    public function generatePosReport() {

        $common = \App\System::getOptions('common');
        $printer = \App\System::getOptions('printer');
        $firm = H::getFirmData($this->firm_id, $this->branch_id);

        $wp = 'style="width:40mm"';
        if (strlen($printer['pwidth']) > 0) {
            $wp = 'style="width:' . $printer['pwidth'] . 'mm"';
        }

        $header = array('printw'          => $wp, 'date' => H::fd(time()),
                        "document_number" => $this->document_number,
                        "firm_name"       => $firm['firm_name'],
                        "shopname"        => strlen($common['shopname']) > 0 ? $common['shopname'] : false,
                        "address"         => $firm['address'],
                        "phone"           => $firm['phone'],
                        "customer_name"   => $this->headerdata['customer_name'],
                        "isdevice"        => strlen($this->headerdata["device"]) > 0,
                        "device"          => $this->headerdata["device"] . (strlen($this->headerdata["devsn"]) > 0 ? ', с/н ' . $this->headerdata["devsn"] : ''),
                        "total"           => H::fa($this->amount)
        );
        if (strlen($this->headerdata['gar']) > 0) {
            $header['gar'] = H::l('garant') . ': ' . $this->headerdata['gar'];
        }
        $detail = array();
        $i = 1;
        foreach ($this->unpackDetails('detaildata') as $ser) {
            $detail[] = array("no"           => $i++,
                              "service_name" => $ser->service_name,
                              "qty"          => H::fqty($ser->quantity),
                              "price"        => H::fa($ser->price),
                              "amount"       => H::fa($ser->price * $ser->quantity)
            );
        }
        $header['slist'] = $detail;

        $pays = \App\Entity\Pay::getPayments($this->document_id);
        if (count($pays) > 0) {
            $header['plist'] = array();
            foreach ($pays as $pay) {
                $header['plist'][] = array('pdate' => H::fd($pay->paydate), 'ppay' => H::fa($pay->amount));
            }
        }
        $header['ispay'] = count($pays) > 0;

        $report = new \App\Report('doc/serviceact_bill.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function getRelationBased() {
        $list = array();
        $list['Task'] = self::getDesc('Task');
        $list['ProdIssue'] = self::getDesc('ProdIssue');
        $list['GoodsIssue'] = self::getDesc('GoodsIssue');

        return $list;
    }

}
