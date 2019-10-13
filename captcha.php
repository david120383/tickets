<?php

session_save_path('sessions');

/**
 * Created by PhpStorm.
 * User: Yang
 * Date: 07/12/2015
 * Time: 20:12
 */
require_once('../shared-util/constants.php');
require_once('../shared-util/classes/securimage/securimage.php');

$options = array('no_session' => false,
    'captcha_type' => Securimage::SI_CAPTCHA_STRING,
);
$img = new Securimage($options);
$img->code_length = 4;
$img->text_color = new Securimage_Color("#000000");
$img->perturbation = 0;
$img->show();

