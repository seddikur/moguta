<?php

/**
 * Класс Pactioner наследник стандарного Actioner
 * Предназначен для выполнения действий,  AJAX запросов плагина
 *
 * @author Avdeev Mark <mark-avdeev@mail.ru>
 */
class Pactioner extends Actioner
{

    private static $pluginName = 'mg-slider';

    public function getSliderPreview()
    {
        $this->messageSucces = $this->lang['ENTITY_SAVE'];
        $this->messageError = $this->lang['ENTITY_SAVE_NOT'];

        $request = $_POST;

        $validate = self::validate($request['data']);
        if ($validate) {
            $this->messageError = $validate;
            return false;
        } else {
            $slider = $request['data'];
            $path = Slider::$path;

            ob_start();
            include 'views/slider.php';
            $html = ob_get_clean();

            $this->data = $html;
        }

        return true;
    }

    /**
     * Метод для сохранения слайдера
     *
     * @return void
     */
    public function saveSlider()
    {
        USER::AccessOnly('1,4', 'exit()');
        $this->messageSucces = $this->lang['ENTITY_SAVE'];
        $this->messageError = $this->lang['ENTITY_SAVE_NOT'];

        $request = $_POST;

        $validate = self::validate($request['data']);
        if ($validate) {
            $this->messageError = $validate;
            return false;
        } else {
            if (!empty($request['data']['options'])) {
                $options = addslashes(serialize($request['data']['options']));
            } else {
                $options = '';
            }
            if (!empty($request['data']['slides'])) {
                foreach ($request['data']['slides'] as $key => $slide) {
                    $request['data']['slides'][$key]['img_path'] = $this->urlencodeSkipSlashes($slide['img_path']);
                    $request['data']['slides'][$key]['md_img_path'] = $this->urlencodeSkipSlashes($slide['md_img_path']);
                    $request['data']['slides'][$key]['t_img_path'] = $this->urlencodeSkipSlashes($slide['t_img_path']);
                    $request['data']['slides'][$key]['m_img_path'] = $this->urlencodeSkipSlashes($slide['m_img_path']);
                }
                $slides = addslashes(serialize($request['data']['slides']));
            } else {
                $slides = '';
            }
            $name_slider = $request['data']['name_slider'];

            if ($request['id'] == 'new') {
                //New
                $id = Slider::addSlider($name_slider, $options, $slides);
            } else {
                //Update
                Slider::updateSlider($request['id'], $name_slider, $options, $slides);
                $id = $request['id'];
            }
        }

        return true;
    }

    private static function urlencodeSkipSlashes($url)
    {
        return implode('/', array_map('rawurlencode', explode('/', $url)));
    }

    public function deleteSlider()
    {
        USER::AccessOnly('1,4', 'exit()');
        $this->messageSucces = $this->lang['ENTITY_DEL'];
        $this->messageError = $this->lang['ENTITY_DEL_NOT'];

        $request = $_POST;

        $result = Slider::deleteSlider($request['id']);

        return $result;
    }

    /**
     * Метод для проверки данных слайдера перед сохранением
     *
     * @param array $data
     * @return string|boolean
     */
    public function validate($data)
    {
        return false;
    }


    /**
     * Переключатель слайдеров
     * @return boolean
     */
    public function getSlider()
    {
        //доступно только модераторам и админам.
        USER::AccessOnly('1,4', 'exit()');
        $res = DB::query('SELECT * FROM `' . PREFIX . self::$pluginName . '` WHERE `id`=' . DB::quote($_POST['id']));
        //var_dump($res);
        if ($row = DB::fetchAssoc($res)) {
            $data = array();
            $data['options'] = unserialize(stripslashes($row['options']));
            $data['slides'] = unserialize(stripslashes($row['slides']));
            $data['name_slider'] = $row['name_slider'];
            $this->data = $data;
            return true;

        } else {
            return false;
        }
        return false;
    }

    /**
     * Устанавливает количество отображаемых записей в разделе новостей
     * @return boolean
     */
    public function setCountPrintRowsNews()
    {

        $count = 20;
        if (is_numeric($_POST['count']) && !empty($_POST['count'])) {
            $count = $_POST['count'];
        }

        MG::setOption(array('option' => 'countPrintRowsNews ', 'value' => $count));
        return true;
    }

    /**
     * Устанавливает флаг  активности
     * @return type
     */
    public function visibleEntity()
    {
        $this->messageSucces = $this->lang['ACT_V_ENTITY'];
        $this->messageError = $this->lang['ACT_UNV_ENTITY'];

        //обновление
        if (!empty($_POST['id'])) {
            unset($_POST['pluginHandler']);
            $this->updateEntity($_POST);
        }

        if ($_POST['invisible']) {
            return true;
        }

        return false;
    }

}

