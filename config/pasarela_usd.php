<?php

return [
    //Rutas para api de pagos
//    '_ENDPOINT_'=>'https://api.micuentaweb.pe/',
    '_ENDPOINT_CREATE_PAY_' => 'https://secure.micuentaweb.pe/vads-payment/entry.silentInit.a',
    '_ID_PAYMENT_' => '79368309',
    // Prueba
    '_SIGNATURE_' => 'XsN7X23Zj3wMv8JZ',
    '_MODO_API_' => 'TEST',
    //Producccion
    //'_SIGNATURE_','HY47AshJ8o2sngwt',
    //'_MODO_API_','PRODUCTION',
    '_PASS_API_' => 'testpassword_zSVGceAklfnEALRF9oGPQ5dgYxy88NQUksUyagcFmzciu',
    //Parametros para pago
    '_vads_action_mode_'=>'INTERACTIVE',
    '_vads_language_'=>'es',
    '_vads_page_action_'=>'PAYMENT',
    '_vads_payment_cards_'=>'VISA;MASTERCARD;AMEX;DINERS',
    '_vads_payment_config_'=>'SINGLE',
    '_vads_shop_name_'=>'Misky Selva',
//    '_vads_shop_name_'=>'Misky Selva Test BufeoTec',
    '_vads_theme_config_'=>'SIMPLIFIED_DISPLAY=true',
    //URLs de Retorno
    '_vads_url_cancel_'=>config('globals.SERVER').'inicio/confirmacion/'.'CANCELADO/',
    '_vads_url_error_'=>config('globals.SERVER').'inicio/confirmacion/'.'ERROR/',
    '_vads_url_refused_'=>config('globals.SERVER').'inicio/confirmacion/'.'RECHAZADO/',
    '_vads_url_success_'=>config('globals.SERVER').'inicio/confirmacion/'.'CORRECTO/',


];
