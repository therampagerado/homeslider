{**
 * Copyright (C) 2017-2025 thirty bees
 * Copyright (C) 2007-2016 PrestaShop SA
 *
 * thirty bees is an extension to the PrestaShop software by PrestaShop SA.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2025 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   Academic Free License (AFL 3.0)
 * PrestaShop is an internationally registered trademark of PrestaShop SA.
 *}

<div class="panel">
  <h3><i class="icon-list-ul"></i> {l s='Slides list' mod='homeslider'}
    <span class="panel-heading-action">
      <a id="desc-product-new" class="list-toolbar-btn" href="{$link->getAdminLink('AdminModules')}&configure=homeslider&addSlide=1">
        <span title="" data-toggle="tooltip" class="label-tooltip"
              data-original-title="{l s='Add new' mod='homeslider'}" data-html="true">
          <i class="process-icon-new"></i>
        </span>
      </a>
    </span>
  </h3>

  <div id="slidesContent">
    <ul id="slides" class="list-unstyled">
      {foreach from=$slides item=slide}
        <li id="slide_{$slide.id_slide}" class="panel">
          <div class="row">
            <div class="col-lg-1"><span><i class="icon-arrows"></i></span></div>
            <div class="col-md-3">
              <img src="{$image_baseurl}{$slide.image}"
                   alt="{$slide.title}"
                   class="img-thumbnail" />
            </div>
            <div class="col-md-8">
              <h4 class="pull-left">
                #{$slide.id_slide} - {$slide.title}
                {if $slide.is_shared}
                  <div>
                    <span class="label color_field pull-left"
                          style="background-color:#108510;color:white;margin-top:5px;">
                      {l s='Shared slide' mod='homeslider'}
                    </span>
                  </div>
                {/if}
              </h4>
              <div class="btn-group-action pull-right">
                {$slide.status}
                <a class="btn btn-default"
                   href="{$link->getAdminLink('AdminModules')}&configure=homeslider&id_slide={$slide.id_slide}">
                  <i class="icon-edit"></i>
                  {l s='Edit' mod='homeslider'}
                </a>
                <a class="btn btn-default"
                   href="{$link->getAdminLink('AdminModules')}&configure=homeslider&delete_id_slide={$slide.id_slide}">
                  <i class="icon-trash"></i>
                  {l s='Delete' mod='homeslider'}
                </a>
              </div>
            </div>
          </div>
        </li>
      {/foreach}
    </ul>
  </div>