<?php

namespace Svarok\Component;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\DB\ArrayResult;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\Json;
use Monolog\Logger;
use Monolog\Registry;
use Svarok\UI\PageNavigation;

class ElementReviews extends \CBitrixComponent
{
    /** @var bool */
    protected $debugMode = false;

    /** @var array */
    private $debugData = [];

    /** @var int */
    private $startMicroTime;

    /** @var Logger */
    protected $logger;

    /** @var array */
    private $loggerErrorPool = [];

    /** @var array */
    private $loggerContext;

    /** @var PageNavigation */
    protected $pagination;

    public function __construct(\CBitrixComponent $component = null)
    {
        parent::__construct($component);

        \CPageOption::SetOptionString('main', 'nav_page_in_session', 'N');

        $this->logger = Registry::getInstance('app');

        /** @global \CUser $USER */
        global $USER;

        $this->debugMode = ($this->request->getQuery('debug_mode') === 'Y' && $USER->IsAdmin());

        $this->startMicroTime = microtime(true);

        $this->addDebugData(0, 'Start component');

        $this->loggerConfigure();
    }

    /**
     * Подготавливает контекст для логгера
     */
    protected function loggerConfigure()
    {
        /** @global \CUser $USER */
        global $USER;

        if (!(is_object($USER) && $USER instanceof \CUser)) {
            $USER = new \CUser();
        }

        $prepareScriptName = ltrim(
            str_replace(
                dirname($_SERVER['DOCUMENT_ROOT']),
                '',
                __FILE__
            ),
            DIRECTORY_SEPARATOR
        );

        $this->loggerContext = [
            'type' => 'component',
            'bitrix_user_id' => $USER->GetID(),
            'script_name' => $prepareScriptName,
            'component_name' => $this->getName()
        ];
    }

    /**
     * Load language file.
     */
    public function onIncludeComponentLang()
    {
        $this->includeComponentLang(basename(__FILE__));

        Loc::loadMessages(__FILE__);
    }

    /**
     * Prepare Component Params
     *
     * @param array $arParams
     * @return array
     */
    public function onPrepareComponentParams($arParams)
    {
        $arParams['HLBLOCK_ID'] = (int) $arParams['HLBLOCK_ID'];

        if (is_array($arParams['ELEMENT_ID'])) {
            foreach ($arParams['ELEMENT_ID'] as $key => $value) {
                if ((int) $value > 0) {
                    $arParams['ELEMENT_ID'][$key] = (int) $value;
                }
            }
        } else {
            $arParams['ELEMENT_ID'] = (int) $arParams['ELEMENT_ID'];
        }

        $arParams['SORT_FIELD'] = trim($arParams['SORT_FIELD']);
        if (empty($arParams['SORT_FIELD'])) {
            $arParams['SORT_FIELD'] = 'ID';
        }

        $arParams['SORT_ORDER'] = trim($arParams['SORT_ORDER']);
        if (!preg_match('/^(asc|desc|nulls)(,asc|,desc|,nulls){0,1}$/i', $arParams['SORT_ORDER'])) {
            $arParams['SORT_ORDER'] = 'ASC';
        }

        $arParams['SORT_FIELD_2'] = trim($arParams['SORT_FIELD_2']);
        if (empty($arParams['SORT_FIELD_2'])) {
            $arParams['SORT_FIELD_2'] = 'ID';
        }

        $arParams['SORT_ORDER_2'] = trim($arParams['SORT_ORDER_2']);
        if (!preg_match('/^(asc|desc|nulls)(,asc|,desc|,nulls){0,1}$/i', $arParams['SORT_ORDER_2'])) {
            $arParams['SORT_ORDER_2'] = 'ASC';
        }

        $arParams['REVIEWS_COUNT'] = (int) $arParams['REVIEWS_COUNT'];
        if (!$arParams['REVIEWS_COUNT']) {
            $arParams['REVIEWS_COUNT'] = 10;
        }

        return $arParams;
    }

    /**
     * Check Required Modules
     *
     * @throws \Exception
     */
    protected function checkModules()
    {
        foreach (['iblock', 'highloadblock'] as $moduleId) {
            if (!Loader::includeModule($moduleId)) {
                $errorMessage = Loc::getMessage(
                    'ELEMENT_REVIEWS_ERROR_INCLUDE_MODULE',
                    ['#MODULE#' => $moduleId]
                );

                throw new SystemException($errorMessage);
            }
        }
    }

    /**
     * Init page navigation
     */
    public function initPagination()
    {
        $this->pagination = (new PageNavigation('page'));

        $this->pagination->allowAllRecords(false)
            ->setPageSize($this->arParams['REVIEWS_COUNT'])
            ->initFromUri();

        if ($this->request->isAjaxRequest()) {
            $this->pagination->setCurrentPage($this->request->getQuery('PAGEN_1'));
        }
    }

    /**
     * Prepare component data
     * @throws \Exception
     */
    protected function prepareData()
    {
        $this->arResult['ITEMS'] = [];

        $reviewsFilter = [
            '=UF_PRODUCT' => $this->arParams['ELEMENT_ID'],
            '!UF_COMMENT' => false,
            '!UF_ACTIVE' => false,
        ];

        $entity = HighloadBlockTable::compileEntity(
            HighloadBlockTable::getById($this->arParams['HLBLOCK_ID'])->fetch()
        );
        $reviewsTable = $entity->getDataClass();

        $reviewsQuery = $reviewsTable::query()
            ->setSelect(['*'])
            ->setFilter($reviewsFilter)
            ->setOrder([
                $this->arParams['SORT_FIELD'] => $this->arParams['SORT_ORDER'],
                $this->arParams['SORT_FIELD_2'] => $this->arParams['SORT_ORDER_2']
            ])
            ->setOffset($this->pagination->getOffset())
            ->setLimit($this->pagination->getLimit())
            ->countTotal(true);

        try {

            $iterator = $reviewsQuery->exec();

        } catch (\Exception $e) {
            $iterator = new ArrayResult([]);

            $iterator->setCount(0);

            $this->loggerErrorPool[] = sprintf(
                "Reviews query error. \n--SQL--:\n%s\n--Error--:\n%s",
                $reviewsQuery->getQuery(),
                $e->getMessage()
            );
        }

        $this->pagination->setRecordCount($iterator->getCount());

        while ($row = $iterator->fetch()) {
            $this->arResult['ITEMS'][] = $row;
        }

        // {{{ рейтинг
        $reviewsRating = $reviewsTable::query()
            ->setSelect([
                new ExpressionField('RATING', 'AVG(%s)', 'UF_RATE'),
            ])
            ->setFilter($reviewsFilter)
            ->exec()
            ->fetch();
        // }}}

        // {{{ небольшая информация о товаре
        $product = ElementTable::getRow([
            'select' => ['ID', 'NAME', 'CODE'],
            'filter' => [
                '=ID' => $this->arParams['ELEMENT_ID'],
            ],
            'limit' => 1,
        ]);
        $product['DETAIL_PAGE_URL'] = sprintf(
            '/product/%s/',
            $product['CODE']
        );
        $this->arResult['PRODUCT'] = $product;
        // }}}

        $this->arResult['count_reviews'] = $iterator->getCount();
        $this->arResult['rating'] = round($reviewsRating['RATING']);
        $this->arResult['NAV'] = $this->pagination;
    }

    /**
     * Extract data from cache. No action by default.
     *
     * @return bool
     */
    protected function extractDataFromCache()
    {
        if ($this->arParams['CACHE_TYPE'] === 'N') {
            return false;
        }

        $page = 1;

        if (
            (is_object($this->pagination))
            && ($this->pagination instanceof PageNavigation)
        ) {
            $page = $this->pagination->getCurrentPage();
        }

        $cacheKeys =
            $this->arParams + [
                __FILE__,
                $page,
            ];

        /** @global \CUser $USER */
        global $USER;

        return !($this->StartResultCache(3600, [$USER->GetGroups(), $cacheKeys]));
    }

    /**
     * Is AJAX Request?
     *
     * @return bool
     */
    protected function isAjax()
    {
        return (
                !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
            ) || $this->request->isAjaxRequest();
    }

    /**
     * Start Component
     */
    public function executeComponent()
    {
        /** @global \CMain $APPLICATION */
        global $APPLICATION;

        try {
            $this->checkModules();

            $this->initPagination();

            if (!$this->extractDataFromCache()) {

                $this->prepareData();

                $this->includeComponentTemplate();

                $this->endResultCache();

            }

        } catch (\Exception $e) {
            $this->AbortResultCache();

            if ($this->isAjax()) {

                $APPLICATION->restartBuffer();

                echo Json::encode([
                    'STATUS' => 'ERROR',
                    'MESSAGE' => $e->getMessage(),
                ]);

                die();
            }

            if ($this->debugMode) {
                ShowError(sprintf(
                    '%s (%s:%s)%s',
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine(),
                    PHP_EOL . $e->getTraceAsString()
                ));
            } else {
                ShowError(sprintf('%s', $e->getMessage()));
            }

            $this->loggerErrorPool[] = sprintf(
                "Catch error of component executeComponent method.\n--Error--:\n%s\n--Error trace--:\n%s",
                $e->getMessage(),
                $e->getTraceAsString()
            );

            throw $e;
        }

        $this->addDebugData(microtime(true) - $this->startMicroTime, 'End component');

        $this->showDebugData();

        if (count($this->loggerErrorPool) > 0) {
            $this->logger->warn(
                sprintf(
                    'Error of component execute. Error pool: "%s"',
                    Json::encode($this->loggerErrorPool)
                ),
                $this->loggerContext
            );
        }
    }

    /**
     * Add debug data
     *
     * @param $data
     * @param null $key
     */
    private function addDebugData($data, $key = null)
    {
        if ($this->debugMode) {
            if ($key === null) {
                $key = microtime(true) . mt_rand();
            }

            $this->debugData[$key] = $data;
        }
    }

    /**
     * Show debug data
     */
    private function showDebugData()
    {
        if ($this->debugMode) {
            $debugData = $this->debugData;

            array_walk_recursive($debugData, function (&$item) {
                if (
                    is_object($item)
                    && ($item instanceof SqlTrackerQuery)
                ) {
                    $item = $item->getSql();
                }

                $item = htmlspecialcharsbx($item);
            });

            echo '<pre>';
            var_dump($debugData);
            echo '</pre>';
        }
    }
}
