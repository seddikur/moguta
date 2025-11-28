<?php

class LegalEntityValidation
{
	private static $path = null;
    private static $lang = [];

    public function __construct($path, $lang)
	{
		$this->path = $path;
        $this->lang = $lang;
	}

    /**
     * 
     * Возвращяет шаблон модального окна с ошибкой.
     * 
     * @param bool $admin - шаблон модальное окно для административной части или публичной.
     * @param string|null $title - заголовок ошибки.
     * @param string|null $message - сообщение ошибки.
     * 
     * @return string $html
     * 
     */
    public function getHtmlModalError($admin = true, $title = null, $message = null)
    {
        if (!$title) $title = $this->lang['FATAL_ERROR_TITLE'];
        if (!$message) $message = $this->lang['FATAL_ERROR_MESSAGE'];

        ob_start();
        if ($admin) {
            include($this->path . '/views/admin/modal/error.php');
        } else {
            include($this->path . '/views/public/modal/error.php');
        }
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }

    /**
     * 
     * Проверяет данные перед сохранением записи.
     * 
     * @param array $request
     * 
     * @return array|bool
     * 
     */
    public function validation($request)
    {
        if (isset($request['legal_name']) && empty($request['legal_name'])) {
            $array = [
                'type' => 'input',
                'name' => 'legal_name'
            ];
            $errors[] =  $array;
        }

        if (isset($request['legal_inn']) && empty($request['legal_inn'])) {
            $array = [
                'type' => 'input',
                'name' => 'legal_inn'
            ];
            $errors[] =  $array;
        }

        if (isset($request['legal_address']) && empty($request['legal_address'])) {
            $array = [
                'type' => 'textarea',
                'name' => 'legal_address'
            ];
            $errors[] =  $array;
        }

        if ($errors) return $errors;

        return false;
    }
}