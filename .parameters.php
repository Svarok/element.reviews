<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Highloadblock as HL;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Svarok\Helpers\EnvironmentHelper;

/**
 * @var array $arCurrentValues
 */

Loc::loadMessages(__FILE__);

if (!Loader::includeModule('highloadblock')) {
    return;
}

$arHBlocks = [];
$rsHBlocks = HL\HighloadBlockTable::getList([
    'order'  => ['NAME' => 'ASC'],
    'filter' => [],
    'select' => ['ID', 'NAME']
]);

while ($arHBlock = $rsHBlocks->fetch()) {
    $arHBlocks[$arHBlock['ID']] = $arHBlock['NAME'];
}

$arUserTypeFields = [];

$hlBlockId = $arCurrentValues['HLBLOCK_ID']
    ? $arCurrentValues['HLBLOCK_ID']
    : EnvironmentHelper::getParam('reviewsHighloadblockId');

if ($hlBlockId) {
    $rsData = \CUserTypeEntity::GetList(
        ['SORT' => 'ASC'],
        [
            'ENTITY_ID'    => sprintf('HLBLOCK_%s', $hlBlockId),
            'LANG'         => 'ru',
            'USER_TYPE_ID' => ['string', 'integer']
        ]
    );
    while ($arData = $rsData->Fetch()) {
        $arUserTypeFields[$arData['FIELD_NAME']] = $arData['FIELD_NAME'];
    }
}

$arAscDesc = array(
    'ASC'  => GetMessage('REVIEWS_SORT_ASC'),
    'DESC' => GetMessage('REVIEWS_SORT_DESC'),
);

$arComponentParameters = [
    'GROUPS' => [],
    'PARAMETERS' => [
        'HLBLOCK_ID' => [
            'NAME'              => Loc::getMessage('HLBLOCK_ID'),
            'TYPE'              => 'LIST',
            'MULTIPLE'          => 'N',
            'ADDITIONAL_VALUES' => 'N',
            'VALUES'            => $arHBlocks,
            'PARENT'            => 'BASE',
            'REFRESH'           => 'Y',
            'DEFAULT'           => $hlBlockId,
        ],
        'ELEMENT_ID' => [
            'NAME'    => GetMessage('ELEMENT_ID'),
            'TYPE'    => 'STRING',
            'PARENT'  => 'BASE',
            'DEFAULT' => '',
        ],
        'SORT_FIELD' => [
            'NAME'              => GetMessage('SORT_FIELD'),
            'TYPE'              => 'LIST',
            'MULTIPLE'          => 'N',
            'ADDITIONAL_VALUES' => 'N',
            'VALUES'            => array_merge(['ID' => 'ID'], $arUserTypeFields),
            'PARENT'            => 'BASE',
        ],
        'SORT_ORDER' => [
            'NAME'              => GetMessage('SORT_ORDER'),
            'TYPE'              => 'LIST',
            'MULTIPLE'          => 'N',
            'ADDITIONAL_VALUES' => 'N',
            'VALUES'            => $arAscDesc,
            'PARENT'            => 'BASE',
        ],
        'SORT_FIELD_2' => [
            'NAME'              => GetMessage('SORT_FIELD_2'),
            'TYPE'              => 'LIST',
            'MULTIPLE'          => 'N',
            'ADDITIONAL_VALUES' => 'N',
            'VALUES'            => array_merge(['ID' => 'ID'], $arUserTypeFields),
            'PARENT'            => 'BASE',
        ],
        'SORT_ORDER_2' => [
            'NAME'              => GetMessage('SORT_ORDER_2'),
            'TYPE'              => 'LIST',
            'MULTIPLE'          => 'N',
            'ADDITIONAL_VALUES' => 'N',
            'VALUES'            => $arAscDesc,
            'PARENT'            => 'BASE',
        ],
        'REVIEWS_COUNT' => [
            'NAME'    => GetMessage('REVIEWS_COUNT'),
            'TYPE'    => 'STRING',
            'PARENT'  => 'BASE',
            'DEFAULT' => '10',
        ],
    ],
];
