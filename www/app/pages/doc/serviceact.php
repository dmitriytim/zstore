<?php

namespace App\Pages\Doc;

use \App\Application as App;
use \App\Entity\Customer;
use \App\Entity\Doc\Document;
use \App\Entity\Service;
use \App\System;
use \Zippy\Html\DataList\DataView;
use \Zippy\Html\Form\AutocompleteTextInput;
use \Zippy\Html\Form\Button;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\Date;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Form\TextArea;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Link\SubmitLink;
use \App\Entity\MoneyFund;
use \App\Helper as H;

/**
 * Страница  ввода  акта выполненных работ
 */
class ServiceAct extends \App\Pages\Base {

    public $_servicelist = array();
    private $_doc;
    private $_rowid = 0;
    private $_basedocid = 0;

    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date'))->setDate(time());
        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');

        $this->docform->add(new TextInput('notes'));
        $this->docform->add(new TextInput('gar'));
        $this->docform->add(new TextInput('device'));
        $this->docform->add(new TextInput('devsn'));

        $this->docform->add(new DropDownChoice('payment', MoneyFund::getList(true, true), H::getDefMF()))->onChange($this, 'OnPayment');

        $this->docform->add(new TextInput('editpayamount'));
        $this->docform->add(new SubmitButton('bpayamount'))->onClick($this, 'onPayAmount');
        $this->docform->add(new TextInput('editpayed', "0"));
        $this->docform->add(new SubmitButton('bpayed'))->onClick($this, 'onPayed');

        $this->docform->add(new Label('payed', 0));
        $this->docform->add(new Label('payamount', 0));

        $this->docform->add(new Label('discount'))->setVisible(false);
        $this->docform->add(new TextInput('editpaydisc'));
        $this->docform->add(new SubmitButton('bpaydisc'))->onClick($this, 'onPayDisc');
        $this->docform->add(new Label('paydisc', 0));


        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new SubmitLink('addcust'))->onClick($this, 'addcustOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('inprocdoc'))->onClick($this, 'savedocOnClick');

        $this->docform->add(new Label('total'));
        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new AutocompleteTextInput('editservice'))->onText($this, 'OnAutoServive');
        $this->editdetail->editservice->onChange($this, 'OnChangeServive', true);


        $this->editdetail->add(new TextInput('editprice'));
        $this->editdetail->add(new TextArea('editdesc'));

        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('saverow'))->onClick($this, 'saverowOnClick');

        //добавление нового кантрагента
        $this->add(new Form('editcust'))->setVisible(false);
        $this->editcust->add(new TextInput('editcustname'));
        $this->editcust->add(new TextInput('editphone'));
        $this->editcust->add(new Button('cancelcust'))->onClick($this, 'cancelcustOnClick');
        $this->editcust->add(new SubmitButton('savecust'))->onClick($this, 'savecustOnClick');

        if ($docid > 0) { //загружаем   содержимое   документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->notes->setText($this->_doc->headerdata['notes']);
            $this->docform->gar->setText($this->_doc->headerdata['gar']);


            $this->docform->payment->setValue($this->_doc->headerdata['payment']);
            $this->docform->payamount->setText($this->_doc->payamount);
            $this->docform->editpayamount->setText($this->_doc->payamount);
            $this->docform->payment->setValue($this->_doc->headerdata['payment']);
            $this->docform->payed->setText($this->_doc->payed);
            $this->docform->editpayed->setText($this->_doc->payed);
            $this->docform->device->setText($this->_doc->device);
            $this->docform->devsn->setText($this->_doc->devsn);
            $this->docform->paydisc->setText($this->_doc->headerdata['paydisc']);
            $this->docform->editpaydisc->setText($this->_doc->headerdata['paydisc']);

            $this->OnPayment($this->docform->payment);

            $this->docform->total->setText($this->_doc->amount);

            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->customer->setKey($this->_doc->customer_id);
            $this->docform->customer->setText($this->_doc->customer_name);

            foreach ($this->_doc->detaildata as $item) {
                $item = new Service($item);
                $this->_servicelist[$item->service_id] = $item;
            }
        } else {
            $this->_doc = Document::create('ServiceAct');
            $this->docform->document_number->setText($this->_doc->nextNumber());
        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_servicelist')), $this, 'detailOnRow'))->Reload();
        $this->calcTotal();
        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }
    }

    public function detailOnRow($row) {
        $service = $row->getDataItem();

        $row->add(new Label('item', $service->service_name));
        $row->add(new Label('desc', $service->desc));


        $row->add(new Label('price', H::fa($service->price)));


        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function editOnClick($sender) {
        $service = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail->editdesc->setText(($service->desc));

        $this->editdetail->editprice->setText($service->price);

        $this->editdetail->editservice->setKey($service->service_id);
        $this->editdetail->editservice->setText($service->service_name);
        $this->_rowid = $service->service_id;
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }

        $service = $sender->owner->getDataItem();

        $this->_servicelist = array_diff_key($this->_servicelist, array($service->service_id => $this->_servicelist[$service->service_id]));
        $this->docform->detail->Reload();
        $this->calcTotal();
        $this->calcPay();
    }

    public function addrowOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);
        $this->_rowid = 0;
        $this->editdetail->editdesc->setText('');

        $this->editdetail->editprice->setText(0);
    }

    public function saverowOnClick($sender) {
        $id = $this->editdetail->editservice->getKey();
        if ($id == 0) {
            $this->setError("Не выбрана  услуга");
            return;
        }
        $service = Service::load($id);

        $service->price = $this->editdetail->editprice->getText();
        $service->desc = $this->editdetail->editdesc->getText();

        $this->_servicelist[$service->service_id] = $service;
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();
        $this->calcTotal();
        $this->calcPay();
        //очищаем  форму
        $this->editdetail->editservice->setKey(0);
        $this->editdetail->editdesc->setText('');
        $this->editdetail->editservice->setText('');


        $this->editdetail->editprice->setText("0");
    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
    }

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }

        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());
        $this->_doc->notes = $this->docform->notes->getText();
        $this->_doc->customer_id = $this->docform->customer->getKey();
        if ($this->_doc->customer_id > 0) {
            $customer = Customer::load($this->_doc->customer_id);
            $this->_doc->headerdata['customer_name'] = $this->docform->customer->getText() . ' ' . $customer->phone;
        }
        $this->_doc->headerdata['device'] = $this->docform->device->getText();
        $this->_doc->headerdata['devsn'] = $this->docform->devsn->getText();

        $this->calcTotal();

        $this->_doc->headerdata['gar'] = $this->docform->gar->getText();
        $this->_doc->headerdata['payment'] = $this->docform->payment->getValue();
        $this->_doc->headerdata['paydisc'] = $this->docform->paydisc->getText();

        $this->_doc->payamount = $this->docform->payamount->getText();
        $this->_doc->payed = $this->docform->payed->getText();
        if ($this->_doc->headerdata['payment'] == \App\Entity\MoneyFund::PREPAID) {
            $this->_doc->headerdata['paydisc'] = 0;
            $this->_doc->payed = 0;
            $this->_doc->payamount = 0;
        }
        if ($this->_doc->headerdata['payment'] == \App\Entity\MoneyFund::CREDIT) {
            $this->_doc->payed = 0;
        }
        if ($this->checkForm() == false) {
            return;
        }

        $this->_doc->detaildata = array();
        foreach ($this->_servicelist as $item) {
            $this->_doc->detaildata[] = $item->getData();
        }

        $isEdited = $this->_doc->document_id > 0;
        $this->_doc->amount = $this->docform->total->getText();


        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {
            if ($this->_basedocid > 0) {
                $this->_doc->parent_id = $this->_basedocid;
                $this->_basedocid = 0;
            }

            $this->_doc->save();

            if ($sender->id != 'savedoc') {
                if (!$isEdited) {
                    $this->_doc->updateStatus(Document::STATE_NEW);
                }

                if ($sender->id == 'execdoc') {
                    $this->_doc->updateStatus(Document::STATE_EXECUTED);
                    $this->_doc->updateStatus(Document::STATE_CLOSED);
                }

                if ($sender->id == 'inprocdoc') {
                    $this->_doc->updateStatus(Document::STATE_INPROCESS);
                }
            } else {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }

            $conn->CommitTrans();
            App::RedirectBack();
        } catch (\Exception $ee) {
            global $logger;
            $conn->RollbackTrans();
            $this->setError($ee->getMessage());

            $logger->error($ee->getMessage() . " Документ " . $this->_doc->meta_desc);
            return;
        }
    }

    /**
     * Расчет  итого
     *
     */
    private function calcTotal() {

        $total = 0;

        foreach ($this->_servicelist as $item) {
            $item->amount = $item->price;

            $total = $total + $item->amount;
        }
        $this->docform->total->setText(H::fa($total));


        $disc = 0;

        $customer_id = $this->docform->customer->getKey();
        if ($customer_id > 0) {
            $customer = Customer::load($customer_id);

            if ($customer->discount > 0) {
                $disc = round($total * ($customer->discount / 100));
            } else if ($customer->bonus > 0) {
                if ($total >= $customer->bonus) {
                    $disc = $customer->bonus;
                } else {
                    $disc = $total;
                }
            }
        }


        $this->docform->paydisc->setText($disc);
        $this->docform->editpaydisc->setText($disc);
    }

    public function OnPayment($sender) {
        $this->docform->payed->setVisible(true);
        $this->docform->payamount->setVisible(true);
        $this->docform->paydisc->setVisible(true);

        $b = $sender->getValue();


        if ($b == \App\Entity\MoneyFund::PREPAID) {
            $this->docform->payed->setVisible(false);
            $this->docform->payamount->setVisible(false);
            $this->docform->paydisc->setVisible(false);
        }
        if ($b == \App\Entity\MoneyFund::CREDIT) {
            $this->docform->payed->setVisible(false);
            $this->docform->payed->setText(0);
            $this->docform->editpayed->setText(0);
        }
    }

    public function onPayAmount($sender) {
        $this->docform->payamount->setText($this->docform->editpayamount->getText());
        $this->docform->payed->setText($this->docform->editpayamount->getText());
        $this->docform->editpayed->setText($this->docform->editpayamount->getText());
    }

    public function onPayed($sender) {
        $this->docform->payed->setText($this->docform->editpayed->getText());
    }

    private function CalcPay() {
        $total = $this->docform->total->getText();
        $disc = $this->docform->paydisc->getText();

        $this->docform->editpayamount->setText(H::fa($total - $disc));
        $this->docform->payamount->setText(H::fa($total - $disc));
        $this->docform->editpayed->setText(H::fa($total - $disc));
        $this->docform->payed->setText(H::fa($total - $disc));
    }

    /**
     * Валидация   формы
     *
     */
    private function checkForm() {
        if (strlen($this->_doc->document_number) == 0) {
            $this->setError('Введите номер документа');
        }
        if(false == $this->_doc->checkUniqueNumber()){
              $this->docform->document_number->setText($this->_doc->nextNumber()); 
              $this->setError('Не уникальный номер документа. Сгенерирован новый номер') ;
             
        }
        if (count($this->_servicelist) == 0) {
            $this->setError("Не введена  ни одна позиция");
        }
        if ($this->docform->payment->getValue() == 0) {
            $this->setError("Не указан  способ  оплаты");
        }

        return !$this->isError();

        $this->docform->detail->Reload();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnAutoCustomer($sender) {
        $text = Customer::qstr('%' . $sender->getText() . '%');
        return Customer::findArray("customer_name", "status=0 and (customer_name like {$text}  or phone like {$text} )");
    }

    public function OnAutoServive($sender) {

        $text = Service::qstr('%' . $sender->getText() . '%');
        return Service::findArray("service_name", "    service_name like {$text}");
    }

    public function OnChangeServive($sender) {
        $id = $sender->getKey();

        $item = Service::load($id);
        $price = $item->price;


        $this->editdetail->editprice->setText($price);

        $this->updateAjax(array('editprice'));
    }

    //добавление нового контрагента
    public function addcustOnClick($sender) {
        $this->editcust->setVisible(true);
        $this->docform->setVisible(false);

        $this->editcust->editcustname->setText('');
        $this->editcust->editphone->setText('');
    }

    public function savecustOnClick($sender) {
        $custname = trim($this->editcust->editcustname->getText());
        if (strlen($custname) == 0) {
            $this->setError("Не введено имя");
            return;
        }
        $cust = new Customer();
        $cust->customer_name = $custname;
        $cust->phone = $this->editcust->editcustname->getText();
        $cust->save();
        $this->docform->customer->setText($cust->customer_name);
        $this->docform->customer->setKey($cust->customer_id);

        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
    }

    public function cancelcustOnClick($sender) {
        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
    }

}
