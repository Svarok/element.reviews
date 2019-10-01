<?php
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

use Bitrix\Main\UI\PageNavigation;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$getSocialIconLink = function($link) {
    $icon = '';

    $map = [
        'vk.com' => 'sprite.svg#vk-1',
        'facebook.com' => 'sprite.svg#facebook',
        'twitter.com' => 'images/sprite.svg#twitter',
    ];

    foreach ($map as $k => $image) {
        if (strpos($link,$k) !== false) {
            $icon = sprintf('
                <svg class="svg-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 17">
                    <use xlink:href="%s/images/%s" />
                </svg>',
                SITE_TEMPLATE_PATH,
                $image
            );

            break;
        }
    }

    return $icon;
};
?>

<div class="reviews" id="reviews" data-element-id="<?php echo $arParams['ELEMENT_ID']; ?>">
    <?php
    if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
        $APPLICATION->RestartBuffer();
    }
    ?>
    <div class="reviews__header">
        <div class="reviews__title">Отзывы товара</div>
        <?php if (empty($arResult['ITEMS'])) { ?>
            <div class="reviews__text">Напишите отзыв о товаре, чтобы помочь в выборе другим покупателям</div>
        <?php } ?>
        <div class="reviews__header-grid">

        <?php if($arResult['NAV']->getRecordCount() > 0 && is_object($arResult['NAV'])) {?>
            <div data-transport="screenMdMax!.reviews_paging__new-transport">
                <?php
                $APPLICATION->IncludeComponent(
                    'bitrix:main.pagenavigation',
                    'reviews-bottom',
                    [
                        'NAV_OBJECT' => $arResult['NAV'],
                        'SEF_MODE' => 'Y',
                        'PAGE_WINDOW' => 7,
                    ],
                    false
                );
                ?>
            </div>
        <?php } ?>

            <div class="reviews__rating">
                <div class="b-stars b-stars--<?php echo $arResult['rating']; ?>"></div>
                <div class="reviews__rating-counter">
                    <?php
                    echo sprintf(
                        '%d %s',
                        $arResult['count_reviews'],
                        \Svarok\Helpers\StringHelper::pluralForm(
                            $arResult['count_reviews'],
                            ['отзыв', 'отзыва', 'отзывов']
                        )
                    );
                    ?>
                </div>
                <div data-transport="screenMdMax!.reviews__new-transport">
                    <div class="reviews__new button button-blue js-reviews-new">Написать отзыв</div>
                </div>
            </div>
        </div>
        <div class="reviews__new-transport"></div>
        <div class="reviews_paging__new-transport"></div>
    </div>
    <?php if (!empty($arResult['ITEMS'])) { ?>
    <div class="reviews__body">

        <?php foreach ($arResult['ITEMS'] as $item) { ?>
            <div class="reviews__item">
                <div class="reviews__top">
                    <div class="reviews__top-right">
                        <span class="date"><?php echo $item['UF_DATE']; ?></span>
                        <a class="social-logo"
                           rel="nofollow"
                           target="_blank"
                           href="<?php echo $item['UF_PROFILE_LINK']; ?>">
                            <?php echo $getSocialIconLink($item['UF_PROFILE_LINK']); ?>
                        </a>
                    </div>
                    <span class="avatar">
                        <?php
                        if ($item['UF_AVATAR']) {
                            $y = \CFile::ResizeImageGet(
                                $item['UF_AVATAR'],
                                ['width' => 50, 'height' => 50],
                                BX_RESIZE_IMAGE_EXACT,
                                true
                            );?>
                            <a href="<?php echo $item['UF_PROFILE_LINK']; ?>" target="_blank" rel="nofollow">
                                <img src="<?php echo $y['src']; ?>" alt="" />
                            </a>
                        <?php } else { ?>
                            <img src="<?php echo SITE_TEMPLATE_PATH . '/images/avatar_default.png'?>" alt="" />
                        <?php } ?>
                    </span>
                    <?php if ($item['UF_PROFILE_LINK']) { ?>
                        <span class="name">
                            <a href="<?php echo $item['UF_PROFILE_LINK']; ?>" target="_blank" rel="nofollow">
                                <?php echo $item['UF_NAME']; ?>
                            </a>
                        </span>
                    <?php } else { ?>
                        <span class="name"><?php echo $item['UF_NAME']; ?></span>
                    <?php } ?>
                </div>
                <div class="reviews__content">
                    <div class="b-stars b-stars--<?php echo $item['UF_RATE']; ?>"></div>
                    <?php if ($item['UF_WORTH']) { ?>
                        <div class="reviews__plus">Достоинства:</div>
                        <p><?php echo $item['UF_WORTH']; ?></p>
                    <?php } ?>
                    <?php if ($item['UF_LACK']) { ?>
                        <div class="reviews__minus">Недостатки:</div>
                        <p><?php echo $item['UF_LACK']; ?></p>
                    <?php } ?>
                    <?php if ($item['UF_COMMENT']) { ?>
                        <div class="reviews__comment">Комментарий:</div>
                        <p><?php echo $item['UF_COMMENT']; ?></p>
                    <?php } ?>

                    <?php if (!empty($item['UF_IMAGES'])) { ?>
                        <div class="review-gallery">
                            <?php foreach ($item['UF_IMAGES'] as $imageKey => $imageId) { ?>
                                <?php if (!$imageId) {continue;} ?>
                                <div class="review-gallery__item">
                                    <?php
                                    $small_modal = \CFile::ResizeImageGet(
                                        $imageId,
                                        ['width' => 114, 'height' => 114],
                                        BX_RESIZE_IMAGE_PROPORTIONAL,
                                        true
                                    );
                                    $y = \CFile::ResizeImageGet(
                                        $imageId,
                                        ['width' => 100, 'height' => 65],
                                        BX_RESIZE_IMAGE_PROPORTIONAL,
                                        true
                                    );?>
                                    <div data-toggle="modal"
                                         data-target="#modal-review-gallery"
                                         data-small-image-src="<?php echo $small_modal['src']; ?>"
                                         data-big-image-src="<?php echo \CFile::GetPath($imageId); ?>"
                                         data-index="<?php echo $imageKey;?>"
                                         tabindex="-1" role="dialog">
                                        <img src="<?php echo $y['src'];?>" alt="" class="review-gallery__img">
                                    </div>
                                </div>
                            <?php } ?>

                        </div>
                    <?php } ?>

                </div>
                <div class="reviews__bottom">
                    <div class="author-sity">
                        <?php echo $item['UF_CITY'] ? 'г. ' . $item['UF_CITY'] : ' '; ?>
                    </div>
                    <div class="review-utility js-review-vote">
                        Отзыв полезен?
                        <a href="javascript:void(0);" class="review-plus"
                           data-review-id="<?php echo $item['ID']; ?>" data-vote="Y">Да</a>
                        <span class="review-plus-count"><?php echo (int) $item['UF_LIKE']; ?></span>
                        / <a href="javascript:void(0);" class="review-minus"
                             data-review-id="<?php echo $item['ID']; ?>" data-vote="N">Нет</a>
                        <span class="review-minus-count"><?php echo (int) $item['UF_DIZLIKE']; ?></span>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
    <div class="reviews__footer">
        <?php
        $APPLICATION->IncludeComponent(
            'bitrix:main.pagenavigation',
            'reviews-bottom',
            [
                'NAV_OBJECT' => $arResult['NAV'],
                'SEF_MODE' => 'Y',
                'PAGE_WINDOW' => 7,
            ],
            false
        );
        ?>
        <div class="reviews__submit button button-blue js-reviews-new">Написать отзыв</div>
    </div>
    <?php } ?>
    <?php
    if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
        die();
    }
    ?>
</div>

<form name="send-reviews">
    <input type="hidden" name="UF_PRODUCT" value="<?php echo $arParams['ELEMENT_ID']; ?>">
    <input type="hidden" name="PRODUCT_NAME" value="<?php echo $arResult['NAME']; ?>">
    <div class="my-review hide my-review_product">
        <div class="my-review__title">Поделитесь вашими впечатлениями о товаре</div>
        <div class="my-review__body">
            <div class="my-review__inner">
                <div class="form-horizontal">
                    <div class="my-review__grid has-error">
                        <label class="control-label my-review__grid-item">
                            Общая оценка
                        </label>
                        <div class="my-review__grid-item my-review__grid-item_stars">
                            <div class="starsDynamic js-starsDynamic">
                                <span></span>
                                <span></span>
                                <span></span>
                                <span></span>
                                <span></span>
                                <input type="hidden" name="UF_RATE" value="">
                            </div>
                        </div>
                    </div>
                    <div class="my-review__grid">
                        <label class="control-label my-review__grid-item">Достоинства:</label>
                        <div class="my-review__grid-item">
                            <textarea rows="1" class="form-control" name="UF_WORTH"
                                      placeholder="Напишите, чем вам понравился товар">
                            </textarea>
                        </div>
                    </div>
                    <div class="my-review__grid">
                        <label class="control-label my-review__grid-item">Недостатки:</label>
                        <div class="my-review__grid-item">
                            <textarea rows="1" class="form-control" name="UF_LACK"
                                      placeholder="Что вам не понравилось в товаре">
                            </textarea>
                        </div>
                    </div>
                    <div class="my-review__grid">
                        <label class="control-label my-review__grid-item">Комментарий:</label>
                        <div class="my-review__grid-item">
                            <textarea rows="4" class="form-control" name="UF_COMMENT"
                                      placeholder="Опишите ваши общие впечатления о товаре">
                            </textarea>
                        </div>
                    </div>
                </div>
                <div class="my-review__grid">
                    <div class="my-review__grid-item"></div>
                    <div class="my-review__grid-item">
                        <div class="b-attachments js-attachment">
                            <div class="b-attachments__body">
                                <div class="b-attachments-chose-file">
                                    <div class="b-attachments-chose-file__button">
                                        <span class="button button-gray button-block js-file-btn">
                                            Добавить фотографии
                                        </span>
                                        <input style="display: none;" type="file" name="UF_IMAGES[]"
                                               multiple="multiple" accept="image/*"
                                               class="js-file-inp">
                                    </div>
                                </div>
                            </div>
                            <div class="b-attachments__footer">
                                <div class="b-attachments-imgs">
                                    <input type="hidden" class="js-filenames" name="filenames" value="">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="my-review__footer">
            <div class="my-review__inner">
                <div class="my-review__grid">
                    <div class="my-review__grid-item"></div>
                    <div class="my-review__grid-item">
                        <div class="b-user-form-data">
                            <div class="b-user-form-data__content js-not-publish-block">
                                <div class="b-user-form-data__header">
                                    <div class="b-user-form-data__social">
                                        <div class="b-social b-social--xs" id="auth2vk">
                                            <script src="//ulogin.ru/js/ulogin.js"></script>
                                            <noindex>
                                                <span id="uLogin"
                                                      data-ulogin="display=small;fields=first_name,last_name,photo;providers=vkontakte,facebook,odnoklassniki,mailru,twitter,google,yandex;hidden=;redirect_uri=http://<?php echo $_SERVER['SERVER_NAME'] . $arResult['PRODUCT']['DETAIL_PAGE_URL']; ?>#add-review"></span>
                                            </noindex>
                                        </div>
                                    </div>
                                    <span class="b-user-form-data__title">Чтобы закончить отзыв
                                        войдите на сайт через социальную сеть или укажите свои
                                        контактые данные:
                                    </span>
                                </div>
                                <div class="b-user-form-data__body">
                                    <div class="row row-xs">
                                        <div class="col-xs-6">
                                            <div class="form-group">
                                                <input type="text" name="UF_NAME" class="form-control" placeholder="Имя">
                                            </div>
                                        </div>
                                        <div class="col-xs-6">
                                            <div class="form-group">
                                                <input type="text" name="UF_EMAIL" class="form-control" placeholder="E-mail">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="b-user-form-data__footer">
                                <input type="checkbox" name="show_data" value="Y"
                                       id="checkbox-not-publish"
                                       class="checkbox-styled">
                                <label for="checkbox-not-publish">Отправить анонимно</label>
                            </div>
                        </div>
                        <button class="my-review__submit button button-blue">Отправить отзыв</button>
                        <button class="my-review__cansel button button-link">Отмена</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<div class="modal modal_with_sidebar modal_gallery" id="modal-review-gallery" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-full" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title"><?php echo $arResult['NAME']; ?></div>
            </div>
            <div class="modal-body">
                <div class="b-slider b-slider--modal">
                    <div class="b-slider__inner">
                        <div class="js-slider"></div>
                        <div class="b-slider__arrows b-arrows b-arrows_theme_component b-arrows_theme_component_lg">
                            <div class="b-arrows__arrow b-arrows__arrow_prev js-slider-prev">
                                <svg class="svg-icon" viewBox="0 0 257.56948 451.84853" xmlns="http://www.w3.org/2000/svg">
                                    <path d="m9.27 248 194 194c12.4 12.4 32.4 12.4 44.8 0 12.4-12.4 12.4-32.4 0-44.7l-172-172 172-172c12.4-12.4 12.4-32.4 0-44.7-12.4-12.4-32.4-12.4-44.8 0l-194 194c-6.18 6.18-9.26 14.3-9.26 22.4 0 8.1 3.09 16.2 9.27 22.4z"
                                    />
                                </svg>
                            </div>
                            <div class="b-arrows__arrow b-arrows__arrow_next js-slider-next">
                                <svg class="svg-icon" viewBox="0 0 257.56948 451.84853" xmlns="http://www.w3.org/2000/svg">
                                    <path d="m248 248-194 194c-12.4 12.4-32.4 12.4-44.8 0-12.4-12.4-12.4-32.4 0-44.7l172-172-172-172c-12.4-12.4-12.4-32.4 0-44.7 12.4-12.4 32.4-12.4 44.8 0l194 194c6.18 6.18 9.26 14.3 9.26 22.4 0 8.1-3.09 16.2-9.27 22.4z"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-sidebar">
            <span class="modal-close" data-dismiss="modal" aria-label="Close">
                <svg class="svg-icon" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="14" height="14" viewBox="0 0 14 14">
                    <path transform="translate(-1180 -312)" d="M1182.96 314.05l4.03 4.04 4.04-4.04a.63.63 0 0 1 .9 0c.24.25.24.65 0 .9l-4.04 4.04 4.03 4.04a.64.64 0 0 1-.9.9l-4.03-4.04-4.03 4.04a.64.64 0 0 1-.9-.9l4.03-4.04-4.03-4.04a.64.64 0 0 1 0-.9.63.63 0 0 1 .9 0z"
                    />
                </svg>
            </span>
            <div class="modal-product-colors-side">
                <div class="b-modal-photogallery js-product-modal-sidebar b-slider b-slider_gallery-thumbs-modal">
                    <div class="b-modal-photogallery__inner js-thumbs"></div>
                    <div class="b-slider__arrows b-arrows b-arrows_theme_component">
                        <div class="b-arrows__arrow b-arrows__arrow_prev js-thumbs-prev">
                            <svg class="svg-icon" viewBox="0 0 257.56948 451.84853" xmlns="http://www.w3.org/2000/svg">
                                <path d="m9.27 248 194 194c12.4 12.4 32.4 12.4 44.8 0 12.4-12.4 12.4-32.4 0-44.7l-172-172 172-172c12.4-12.4 12.4-32.4 0-44.7-12.4-12.4-32.4-12.4-44.8 0l-194 194c-6.18 6.18-9.26 14.3-9.26 22.4 0 8.1 3.09 16.2 9.27 22.4z"
                                />
                            </svg>
                        </div>
                        <div class="b-arrows__arrow b-arrows__arrow_next js-thumbs-next">
                            <svg class="svg-icon" viewBox="0 0 257.56948 451.84853" xmlns="http://www.w3.org/2000/svg">
                                <path d="m248 248-194 194c-12.4 12.4-32.4 12.4-44.8 0-12.4-12.4-12.4-32.4 0-44.7l172-172-172-172c-12.4-12.4-12.4-32.4 0-44.7 12.4-12.4 32.4-12.4 44.8 0l194 194c6.18 6.18 9.26 14.3 9.26 22.4 0 8.1-3.09 16.2-9.27 22.4z"
                                />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
