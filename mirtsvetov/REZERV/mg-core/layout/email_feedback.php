<h1 style="margin: 0 0 10px 0; font-size: 16px;padding: 0;">Сообщение с формы обратной связи!</h1>
<p style="padding: 0;margin: 10px 0;font-size: 12px;">
    Пользователь <strong><?php echo $data['name'] ?></strong> с почтовым ящиком <strong><?php echo $data['email'] ?></strong> <?php if(isset($data['phone']) && !empty($data['phone'])):?>, контактным номером телефона <strong><?php echo $data['phone'] ?></strong> <?php endif;?>пишет:
</p>
<div style="margin: 0;padding: 10px;background: #FFF5B5; font-weight: bold;">
    <?php echo $data['msg'] ?>
</div>

