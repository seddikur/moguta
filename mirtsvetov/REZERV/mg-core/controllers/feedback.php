<?php

/**
 * Контроллер Feedback
 *
 * Класс Controllers_Feedback обрабатывает действия пользователей на странице обратной связи.
 * - Проверяет корректность ввода данных с формы обратной связи;
 * - При успешной валидации данных, отправляет сообщение админам интернет магазина, и выводит сообщение об успешной отправке.
 *
 * @author Авдеев Марк <mark-avdeev@mail.ru>
 * @package moguta.cms
 * @subpackage Controller
 */
class Controllers_Feedback extends BaseController{

  function __construct(){
    $html = MG::get('pages')->getPageByUrl('feedback');
    if (!$html) {
      $tmp = explode('/', $_SERVER['REQUEST_URI']);
      if (strpos(end($tmp), 'feedback') === 0) {
        $tmp = explode('?', $_SERVER['REQUEST_URI']);
        $html = MG::get('pages')->getPageByUrl(PROTOCOL."://".$_SERVER['SERVER_NAME'].$tmp[0]);
      }
    }
    MG::loadLocaleData($html['id'], LANG, 'page', $html);
    $html['html_content'] = MG::inlineEditor(PREFIX.'page',"html_content", $html['id'], $html['html_content'], 'page'.DS.$html['id'], null, true);
    
    
    $data = array(
      'dislpayForm' => true,
      'meta_title' => $html['meta_title']?$html['meta_title']:$html['title'],
      'meta_keywords' => $html['meta_keywords'],
      'meta_desc' => $html['meta_desc'],
      'html_content' => $html['html_content'], 
      'title' => $html['title'],
    );
    
    // Если пришли данные с формы.
    if(isset($_POST['send'])){

      // Создает модель отправки сообщения.
      $feedBack = new Models_Feedback;

      // Проверяет на корректность вода.
      $error = $feedBack->isValidData($_POST);
      $data['error'] = $error;

      // Если есть ошибки заносит их в переменную.
      if(!$error){
        if (isset($_POST['formcheck']) && $_POST['formcheck'] == 'check') {
          $_POST['message'] = MG::nl2br($_POST['message']);     
          //Отправляем админам.
          $sitename = MG::getSetting('sitename');
          $tmpArr = array('msg'=>$_POST['message'], 'email'=>$feedBack->getEmail(), 'name'=>$feedBack->getFio());
          foreach ($_POST as $ke => $va) {
            $tmpArr[$ke] = $va;
          }
          unset($tmpArr['validform']);
          unset($tmpArr['validformcheck']);
          unset($tmpArr['send']);
          $body = MG::layoutManager('email_feedback', $tmpArr);
          $mails = explode(',', MG::getSetting('adminEmail'));    
          foreach($mails as $mail){
            if(MG::checkEmail($mail)){
              Mailer::addHeaders(array("Reply-to" => $feedBack->getEmail()));
              Mailer::sendMimeMail(array(
                'nameFrom' => $feedBack->getFio(),
                'emailFrom' => MG::getSetting('noReplyEmail'),
                'nameTo' => $sitename,
                'emailTo' => $mail,
                'subject' => 'Сообщение с формы обратной связи от '.$feedBack->getEmail(),
                'body' => $body,
                'html' => true
              ));
            }
          }
        }
        
        MG::redirect('/feedback?thanks=1');
      } else {
        if (isset($_POST['email'])) {
          unset($_POST['email']);
        }
      }
    }

    // Формирует сообщение.
    if(isset($_REQUEST['thanks'])){
      $data = array(
        // 'message' => 'Ваше сообщение отправлено!',
        'message' => MG::restoreMsg('msg__feedback_sent'),
        'dislpayForm' => false,
        'meta_title' => 'Обратная связь',
        'meta_keywords' => "Обратная сввязь, быстрое сообщение, вопрос в поддержку",
        'meta_desc' => "Задайте свой вопрос по средствам формы обратной связи.",
    );
    }

    $this->data = $data;
  }

}