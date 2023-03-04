<?php

/**
 * Formats a number as a currency with a specified number of decimal places and currency symbol.
 *
 * @param int $number The number to format.
 * @param int|null $decimals The number of decimal places to display (optional).
 * @param string $currency The currency symbol to use (optional).
 *
 * @return string The formatted currency string.
 */
function currencyFormat($number, $decimals = null, $currency = '') 
{
    // Convert the number to an integer to remove any decimals.
    $number = intval($number);

    // If $decimals is not provided or is less than zero, default to 0.
    $decimals = $decimals ?? 0;

    // Use the number_format function to format the number with the specified number of decimals.
    $formatted_number = number_format($number, $decimals);

    // Add the currency symbol to the formatted number.
    return $currency . ' ' . $formatted_number;
}

?>