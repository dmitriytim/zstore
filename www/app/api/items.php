<?php

namespace App\API;

use \App\Entity\Item;
use \App\Helper as H;

class items extends \App\API\Base\JsonRPC
{
    // список категорий  ТМЦ
    public function catlist() {


        $list = array();
        foreach (\App\Entity\Category::find('', 'cat_name') as $cat) {
            $list[] = array('id' => $cat->cat_id, 'name' => $cat->cat_name);

        }
        return $list;
    }

    //список  складов
    public function storelist() {


        $list = array();
        foreach (\App\Entity\Store::find('', 'store_name') as $store) {
            $list[] = array('id' => $store->store_id, 'name' => $store->store_name);

        }
        return $list;
    }

    // список артикулов
    public function articlelist() {


        $list = array();
        $conn = \ZDB\DB::getConnect();

        $res = $conn->GetCol("select item_code from items order  by  item_code");
        foreach ($res as $code) {
            if (strlen($code) > 0) {
                $list[] = $code;
            }
        }

        return $list;
    }

    //  список  ТМЦ
    public function itemlist() {

        $list = array();
        $w = 'disabled<> 1 ';

        if ($args['cat'] > 0) {
            $w .= " and cat_id=" . $args['cat'];
        }

        foreach (Item::find($w, 'itemname') as $item) {
            $plist = array();


            $it =

            $it = array(
                'item_code'    => $item->item_code,
                'bar_code'     => $item->bar_code,
                'itemname'     => $item->itemname,
                'description'  => base64_encode($item->description),
                'measure'      => $item->msr,
                'manufacturer' => $item->manufacturer,
                'cat_name'     => $item->cat_name,
                'cat_id'       => $item->cat_id

            );


            if (strlen($item->price1) > 0) {
                $it['price1'] = $item->price1;
            }
            if (strlen($item->price2) > 0) {
                $it['price2'] = $item->price2;
            }
            if (strlen($item->price3) > 0) {
                $it['price3'] = $item->price3;
            }
            if (strlen($item->price4) > 0) {
                $it['price4'] = $item->price4;
            }
            if (strlen($item->price5) > 0) {
                $it['price5'] = $item->price5;
            }

            $list[] = $it;
        }


        return $list;
    }

    //  количества на  складе
    public function getqty() {
        $list = array();
        $conn = \ZDB\DB::getConnect();

        $sql = "select  item_code,coalesce(sum(qty),0)  as qty from store_stock_view ";
        if ($args['store_id'] > 0) {
            $sql .= " and store_id=" . $args['store_id'];

        }
        $sql .= " group by   item_code";
        $res = $conn->Execute($sql);
        foreach ($res as $row) {
            $list[] = array(

                'item_code' => $row['item_code'],
                'qty'       => H::fqty($row['qty'])

            );
        }

        return $list;


    }


    // запись  ТМЦ.
    public function save($args) {
        if (strlen($args['item_code']) == 0) {
            throw  new \Exception(H::l("apientercode"));
        }

        $code = Item::qstr($args['item_code']);
        $item = Item::getFirst("   item_code = {$code}  ");

        if ($item == null) {
            $item = new  Item();
        }

        $item->item_code = $args['item_code'];
        $item->bar_code = $args['bar_code'];
        $item->itemname = $args['itemname'];
        $item->measure = $args['measure'];
        $item->manufacturer = $args['manufacturer'];
        $item->description = @base64_decode($args['description']);
        $item->cat_id = $args['cat_id'];

        if ($args['price1'] > 0) {
            $item->price1 = $args['price1'];
        }
        if ($args['price2'] > 0) {
            $item->price2 = $args['price2'];
        }
        if ($args['price3'] > 0) {
            $item->price3 = $args['price3'];
        }
        if ($args['price4'] > 0) {
            $item->price4 = $args['price4'];
        }
        if ($args['price5'] > 0) {
            $item->price5 = $args['price5'];
        }

        if (strlen($item->itemname) == 0) {
            throw  new \Exception(H::l("apientername"));
        }

        $item->save();
        return array('item_code' => $item->item_code);

    }


}