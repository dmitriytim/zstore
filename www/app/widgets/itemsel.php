<?php
  
namespace App\Widgets;

use \Zippy\Binding\PropertyBinding as Prop;
use \Zippy\Html\DataList\ArrayDataSource;
use \Zippy\Html\DataList\DataView;
use \Zippy\Html\Form\AutocompleteTextInput;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Link\RedirectLink;
use \Zippy\Html\Link\BookmarkableLink;
use \App\Entity\Item;
use \App\Entity\Category;
use \App\Helper as H;
use \App\Application as App;
use \App\System;
use \Zippy\Html\DataList\DataTable;
use \Zippy\Html\DataList\Column;

/**
 * Виджет для подбора  товаров
 */
class ItemSel extends \Zippy\Html\PageFragment {
    
    private  $_page;
    private  $_event;
    private  $_pricetype;
    private  $_store=0;
    public  $_list=array();
    
    /**
    * 
    * 
    * @param mixed $id
    * @param mixed $page
    * @param mixed $event
    * @param mixed $pricetype
    */
    public function __construct($id,$page,$event ) {
        parent::__construct($id);
        $this->_page =  $page;
        $this->_event =  $event;

        $this->add(new Form('wisfilter'))->onSubmit($this, 'ReloadData');
     
        $this->wisfilter->add(new TextInput('wissearchkey'));
        $this->wisfilter->add(new DropDownChoice('wissearchcat', Category::findArray("cat_name", "", "cat_name"), 0));


        $ds =  new ArrayDataSource($this,'_list');
        
        $table = $this->add(new   DataTable('witemselt',$ds,true,true));
        $table->setPageSize(H::getPG());
        $table->AddColumn(new Column('itemname','Наименование',true,true,true));
        $table->AddColumn(new Column('item_code','Артикул',true,true,false));
 
        $table->setCellClickEvent($this,'OnSelect');

        
    }
    
    /**
    * тип  цены для  столбца  Цена
    * 
    * @param mixed $pricetype
    * @param mixed $store
    */
    public function setPriceType($pricetype,$store=0){
         $this->_pricetype =  $pricetype;
         $this->_store =  $store;
         if(strlen($this->_pricetype)>0)$this->witemselt->AddColumn(new Column('price','Цена',true,true,false,"text-right","text-right"));
      
    }
    
    /**
    * Обновление данных
    * 
    */
    public function Reload(){
        $this->wisfilter->clean();
        $this->ReloadData() ;
    }
  
    public function OnSelect($sender,$data){
        $item = $data['dataitem'];
        $this->_page->{$this->_event}($item->item_id,$item->itemname);
    }
    
    public function ReloadData(){
        
        $where = "disabled <> 1";
        $text = trim($this->wisfilter->wissearchkey->getText());
        $cat = $this->wisfilter->wissearchcat->getValue();
      
        if ($cat > 0) {
            $where = $where . " and cat_id=" . $cat;
        }
 
        if (strlen($text) > 0) {
            
              $text = Item::qstr('%' . $text . '%');
              $where = $where . " and (itemname like {$text} or item_code like {$text} )  ";
          
        }
      
      
      
      
       $list = Item::find($where ) ;
       
       $this->_list = array();
       foreach($list as $item) {
           
         if(strlen($this->_pricetype)>0)  {
             $item->price = $item->getPrice($this->_pricetype,$this->_store);  
         }
           
         $this->_list[] = $item;  
       }
       
       $this->witemselt->Reload();
       
    }    
}