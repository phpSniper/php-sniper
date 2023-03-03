<?php



function currencyFormat($number, $decimals = null, $currency = '')
{
    return $currency . ' ' . number_format(intval($number), $decimals);
}

?>