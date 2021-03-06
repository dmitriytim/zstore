<?php

namespace App\Modules\NP;

/**
 * Вспомагательный  класс
 */
class Helper extends \LisDev\Delivery\NovaPoshtaApi2
{
    private $api;


    public function __construct() {

        global $_config;
        $modules = \App\System::getOptions("modules");

        parent::__construct($modules['npapikey'], $_config['common']['lang']);


    }

    public function getAreaList() {
        $list = $this->getAreas();
        $areas = array();
        foreach ($list['data'] as $a) {
            $areas[$a['Ref']] = $a['Description'];
        }

        return $areas;
    }

    public function getCityList($areaname) {
        $list = $this->findCityByRegion($this->getCities(), $areaname);
        $cities = array();
        foreach ($list as $a) {
            $cities[$a['Ref']] = $a['Description'];

        }
        return $cities;
    }

    public function getPointList($cityref) {

        $list = $this->getWarehouses($cityref);
        $cities = array();
        foreach ($list['data'] as $a) {
            $cities[$a['Ref']] = $a['Description'];
            // $cities[$a['CityID']]  = $a['Description'] ;
        }
        return $cities;
    }

    //проверка  экспрес накладной
    public function check($dec) {
        return $this->model('TrackingDocument"')->getStatusDocuments($dec);
        /*
        1    Нова пошта очікує надходження від відправника
2    Видалено
3    Номер не знайдено
4    Відправлення у місті ХХXХ. (Статус для межобластных отправлений)
NEW - 41    Відправлення у місті ХХXХ. (Статус для услуг локал стандарт и локал экспресс - доставка в пределах города)
5    Відправлення прямує до міста YYYY.
6    Відправлення у місті YYYY, орієнтовна доставка до ВІДДІЛЕННЯ-XXX dd-mm.Очікуйте додаткове повідомлення про прибуття.
7, 8    Прибув на відділення
9    Відправлення отримано
10    Відправлення отримано %DateReceived%.Протягом доби ви одержите SMS-повідомлення про надходження грошового переказута зможете отримати його в касі відділення «Нова пошта».
11    Відправлення отримано %DateReceived%.Грошовий переказ видано одержувачу.
14    Відправлення передано до огляду отримувачу
101    На шляху до одержувача
102, 103, 108    Відмова одержувача
104    Змінено адресу
105    Припинено зберігання
106    Одержано і створено ЄН зворотньої доставки

        */
    }

}
