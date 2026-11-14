
<?php

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatRupiah($harga)
{
    return 'Rp ' . number_format((float) $harga, 0, ',', '.');
}
