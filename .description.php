<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentDescription = [
    'NAME' => 'Отзывы',
    'DESCRIPTION' => 'Выводит отзывы в карточке товара',
    'ICON' => 'images/hl_list.gif',
    'CACHE_PATH' => 'Y',
    'SORT' => 30,
    'PATH' => [
        'ID' => 'sv_component',
        'NAME' => 'Svarok',
        'CHILD' => [
            'ID' => 'sv_content',
            'NAME' => 'Контент',
        ],
    ],
];
