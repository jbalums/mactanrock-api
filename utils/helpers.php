<?php




function businessUnits(): array
{

    return [
        'EBU' => 'Electrical Business Unit',
        'WBU' => 'Water Business Unit',
        'CBU' => 'Chemical Business Unit',
        'GBU' => 'General Business Unit',
        'SG' => 'Support Group',
    ];
}


function getUnit(?string $code): string
{
    $code = \Illuminate\Support\Str::upper($code ?? "");
    return businessUnits()[$code] ?? "";
}