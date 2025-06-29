<?php
/**
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
 */

/**
 * @since   1.5.0
 */

if (!defined('_TB_VERSION_')) {
    exit;
}

include_once(__DIR__ . '/HomeSlide.php');

class HomeSlider extends Module
{
    const DEFAULT_WIDTH =  1140;
    const DEFAULT_SPEED = 500;
    const DEFAULT_PAUSE = 3000;
    const DEFAULT_LOOP = 1;

    /**
     * @var string
     */
    protected $_html = '';

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name = 'homeslider';
        $this->tab = 'front_office_features';
        $this->version = '2.1.0';
        $this->author = 'thirty bees';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Block Image Slider');
        $this->description = $this->l('Adds an image slider to your homepage.');
        $this->tb_versions_compliancy = '> 1.0.0';
        $this->tb_min_version = '1.0.0';
        $this->ps_versions_compliancy = array('min' => '1.6.0.4', 'max' => '1.6.99.99');
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @see Module::install()
     */
    public function install()
    {
        /* Adds Module */
        if (parent::install() &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayTopColumn') &&
            $this->registerHook('actionShopDataDuplication')
        ) {
            $shops = Shop::getContextListShopID();
            $shop_groups_list = array();

            $res = true;

            /* Setup each shop */
            foreach ($shops as $shop_id) {
                $shop_group_id = (int)Shop::getGroupFromShop($shop_id, true);

                if (!in_array($shop_group_id, $shop_groups_list)) {
                    $shop_groups_list[] = $shop_group_id;
                }

                /* Sets up configuration */
                $res = Configuration::updateValue('HOMESLIDER_WIDTH', static::DEFAULT_WIDTH, false, $shop_group_id, $shop_id) && $res;
                $res = Configuration::updateValue('HOMESLIDER_SPEED', static::DEFAULT_SPEED, false, $shop_group_id, $shop_id) && $res;
                $res = Configuration::updateValue('HOMESLIDER_PAUSE', static::DEFAULT_PAUSE, false, $shop_group_id, $shop_id) && $res;
                $res = Configuration::updateValue('HOMESLIDER_LOOP', static::DEFAULT_LOOP, false, $shop_group_id, $shop_id) && $res;
            }

            /* Sets up Shop Group configuration */
            if ($shop_groups_list) {
                foreach ($shop_groups_list as $shop_group_id) {
                    $res = Configuration::updateValue('HOMESLIDER_WIDTH', static::DEFAULT_WIDTH, false, $shop_group_id) && $res;
                    $res = Configuration::updateValue('HOMESLIDER_SPEED', static::DEFAULT_SPEED, false, $shop_group_id) && $res;
                    $res = Configuration::updateValue('HOMESLIDER_PAUSE', static::DEFAULT_PAUSE, false, $shop_group_id) && $res;
                    $res = Configuration::updateValue('HOMESLIDER_LOOP', static::DEFAULT_LOOP, false, $shop_group_id) && $res;
                }
            }

            /* Sets up Global configuration */
            $res = Configuration::updateValue('HOMESLIDER_WIDTH', static::DEFAULT_WIDTH) && $res;
            $res = Configuration::updateValue('HOMESLIDER_SPEED', static::DEFAULT_SPEED) && $res;
            $res = Configuration::updateValue('HOMESLIDER_PAUSE', static::DEFAULT_PAUSE) && $res;
            $res = Configuration::updateValue('HOMESLIDER_LOOP', static::DEFAULT_LOOP) && $res;

            /* Creates tables */
            $res = $this->createTables() && $res;

            /* Adds samples */
            if ($res) {
                $this->installSamples();
            }

            return $res;
        }

        return false;
    }

    /**
     * Adds samples
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function installSamples()
    {
        $dir = __DIR__ . '/fixtures/';
        $languages = Language::getLanguages(false, false, true);
        $fixtures = json_decode(file_get_contents($dir . 'fixtures.json'), true);

        $position = 0;
        foreach ($fixtures as $entry) {
            $position++;
            $slide = new HomeSlide();
            $slide->position = $position;
            $slide->active = 1;
            $imageUrl = $this->processImage($dir . $entry['image'], $entry['image']);
            foreach ($languages as $langId) {
                $slide->title[$langId] = $entry['title'];
                $slide->legend[$langId] = $entry['legend'];
                $slide->url[$langId] = 'https://thirtybees.com';
                $slide->image[$langId] = $imageUrl;
                $slide->description[$langId] = $entry['description'];
            }
            $slide->add();
        }
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @see Module::uninstall()
     */
    public function uninstall()
    {
        /* Deletes Module */
        if (parent::uninstall()) {
            /* Deletes tables */
            $res = $this->deleteTables();

            /* Unsets configuration */
            $res = Configuration::deleteByName('HOMESLIDER_WIDTH') && $res;
            $res = Configuration::deleteByName('HOMESLIDER_SPEED') && $res;
            $res = Configuration::deleteByName('HOMESLIDER_PAUSE') && $res;
            $res = Configuration::deleteByName('HOMESLIDER_LOOP') && $res;

            // delete all images
            static::cleanImages([]);

            return $res;
        }

        return false;
    }

    /**
     * Creates tables
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function createTables()
    {
        /* Slides */
        $res = (bool)Db::getInstance()->execute('
			CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'homeslider` (
				`id_homeslider_slides` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`id_shop` int(10) unsigned NOT NULL,
				PRIMARY KEY (`id_homeslider_slides`, `id_shop`)
			) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;
		');

        /* Slides configuration */
        $res = Db::getInstance()->execute('
			CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'homeslider_slides` (
			  `id_homeslider_slides` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `position` int(10) unsigned NOT NULL DEFAULT \'0\',
			  `active` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
			  PRIMARY KEY (`id_homeslider_slides`)
			) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;
		') && $res;

        /* Slides lang configuration */
        $res = Db::getInstance()->execute('
			CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'homeslider_slides_lang` (
			  `id_homeslider_slides` int(10) unsigned NOT NULL,
			  `id_lang` int(10) unsigned NOT NULL,
			  `title` varchar(255) NOT NULL,
			  `description` text NOT NULL,
			  `legend` varchar(255) NOT NULL,
			  `url` varchar(255) NOT NULL,
			  `image` varchar(255) NOT NULL,
			  PRIMARY KEY (`id_homeslider_slides`,`id_lang`)
			) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;
		') && $res;

        return $res;
    }

    /**
     * deletes tables
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function deleteTables()
    {
        $slides = $this->getSlides();
        foreach ($slides as $slide) {
            $to_del = new HomeSlide($slide['id_slide']);
            $to_del->delete();
        }

        return Db::getInstance()->execute('
			DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'homeslider`, `' . _DB_PREFIX_ . 'homeslider_slides`, `' . _DB_PREFIX_ . 'homeslider_slides_lang`;
		');
    }

    /**
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getContent()
    {
        $this->_html .= $this->headerHTML();

        /* Validate & process */
        if (Tools::isSubmit('submitSlide') || Tools::isSubmit('delete_id_slide') ||
            Tools::isSubmit('submitSlider') ||
            Tools::isSubmit('changeStatus')
        ) {
            if ($this->_postValidation()) {
                $this->_postProcess();
                $this->_html .= $this->renderForm();
                $this->_html .= $this->renderList();
            } else {
                $this->_html .= $this->renderAddForm();
            }

            $this->clearCache();
        } elseif (Tools::isSubmit('addSlide') || (Tools::isSubmit('id_slide') && $this->slideExists((int)Tools::getValue('id_slide')))) {
            if (Tools::isSubmit('addSlide')) {
                $mode = 'add';
            } else {
                $mode = 'edit';
            }

            if ($mode == 'add') {
                if (Shop::getContext() != Shop::CONTEXT_GROUP && Shop::getContext() != Shop::CONTEXT_ALL) {
                    $this->_html .= $this->renderAddForm();
                } else {
                    $this->_html .= $this->getShopContextError(null, $mode);
                }
            } else {
                $associated_shop_ids = HomeSlide::getAssociatedIdsShop((int)Tools::getValue('id_slide'));
                $context_shop_id = (int)Shop::getContextShopID();

                if ($associated_shop_ids === false) {
                    $this->_html .= $this->getShopAssociationError((int)Tools::getValue('id_slide'));
                } else {
                    if (Shop::getContext() != Shop::CONTEXT_GROUP && Shop::getContext() != Shop::CONTEXT_ALL && in_array($context_shop_id, $associated_shop_ids)) {
                        if (count($associated_shop_ids) > 1) {
                            $this->_html = $this->getSharedSlideWarning();
                        }
                        $this->_html .= $this->renderAddForm();
                    } else {
                        $shops_name_list = array();
                        foreach ($associated_shop_ids as $shop_id) {
                            $associated_shop = new Shop((int)$shop_id);
                            $shops_name_list[] = $associated_shop->name;
                        }
                        $this->_html .= $this->getShopContextError($shops_name_list, $mode);
                    }
                }
            }
        } else // Default viewport
        {
            $this->_html .= $this->getWarningMultishopHtml() . $this->getCurrentShopInfoMsg() . $this->renderForm();

            if (Shop::getContext() != Shop::CONTEXT_GROUP && Shop::getContext() != Shop::CONTEXT_ALL) {
                $this->_html .= $this->renderList();
            }
        }

        return $this->_html;
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function _postValidation()
    {
        $errors = array();

        if (Tools::isSubmit('submitSlider')) {
            /* Validation for Slider configuration */
            if (!Validate::isInt(Tools::getValue('HOMESLIDER_SPEED')) || !Validate::isInt(Tools::getValue('HOMESLIDER_PAUSE')) ||
                !Validate::isInt(Tools::getValue('HOMESLIDER_WIDTH'))
            ) {
                $errors[] = $this->l('Invalid values');
            }
        } elseif (Tools::isSubmit('changeStatus')) {
            /* Validation for status */
            if (!Validate::isInt(Tools::getValue('id_slide'))) {
                $errors[] = $this->l('Invalid slide');
            }
        } elseif (Tools::isSubmit('submitSlide')) {
            /* Validation for Slide */
            /* Checks state (active) */
            if (!Validate::isInt(Tools::getValue('active_slide')) || (Tools::getValue('active_slide') != 0 && Tools::getValue('active_slide') != 1)) {
                $errors[] = $this->l('Invalid slide state.');
            }
            /* Checks position */
            if (!Validate::isInt(Tools::getValue('position')) || (Tools::getValue('position') < 0)) {
                $errors[] = $this->l('Invalid slide position.');
            }
            /* If edit : checks id_slide */
            if (Tools::isSubmit('id_slide')) {

                if (!Validate::isInt(Tools::getValue('id_slide')) && !$this->slideExists(Tools::getValue('id_slide'))) {
                    $errors[] = $this->l('Invalid slide ID');
                }
            }
            /* Checks title/url/legend/description/image */
            $languages = Language::getLanguages(false);
            foreach ($languages as $language) {
                if (mb_strlen(Tools::getValue('title_' . $language['id_lang'])) > 255) {
                    $errors[] = $this->l('The title is too long.');
                }
                if (mb_strlen(Tools::getValue('legend_' . $language['id_lang'])) > 255) {
                    $errors[] = $this->l('The caption is too long.');
                }
                if (mb_strlen(Tools::getValue('url_' . $language['id_lang'])) > 255) {
                    $errors[] = $this->l('The URL is too long.');
                }
                if (mb_strlen(Tools::getValue('description_' . $language['id_lang'])) > 4000) {
                    $errors[] = $this->l('The description is too long.');
                }
                if (mb_strlen(Tools::getValue('url_' . $language['id_lang'])) > 0 && !Validate::isUrl(Tools::getValue('url_' . $language['id_lang']))) {
                    $errors[] = $this->l('The URL format is not correct.');
                }
                if (Tools::getValue('image_' . $language['id_lang']) != null && !Validate::isFileName(Tools::getValue('image_' . $language['id_lang']))) {
                    $errors[] = $this->l('Invalid filename.');
                }
            }

            /* Checks title/url/legend/description for default lang */
            $id_lang_default = (int)Configuration::get('PS_LANG_DEFAULT');
            if (mb_strlen(Tools::getValue('title_' . $id_lang_default)) == 0) {
                $errors[] = $this->l('The title is not set.');
            }
            if (mb_strlen(Tools::getValue('legend_' . $id_lang_default)) == 0) {
                $errors[] = $this->l('The caption is not set.');
            }
            if (mb_strlen(Tools::getValue('url_' . $id_lang_default)) == 0) {
                $errors[] = $this->l('The URL is not set.');
            }
            if (!Tools::isSubmit('has_picture') && (!isset($_FILES['image_' . $id_lang_default]) || empty($_FILES['image_' . $id_lang_default]['tmp_name']))) {
                $errors[] = $this->l('The image is not set.');
            }
        } elseif (Tools::isSubmit('delete_id_slide') && (!Validate::isInt(Tools::getValue('delete_id_slide')) || !$this->slideExists((int)Tools::getValue('delete_id_slide')))) {
            /* Validation for deletion */
            $errors[] = $this->l('Invalid slide ID');
        }

        /* Display errors if needed */
        if (count($errors)) {
            $this->_html .= $this->displayError(implode('<br />', $errors));

            return false;
        }

        /* Returns if validation is ok */

        return true;
    }

    /**
     * @return false|void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function _postProcess()
    {
        $errors = [];
        $shop_context = Shop::getContext();

        /* Processes Slider */
        if (Tools::isSubmit('submitSlider')) {
            $shop_groups_list = array();
            $shops = Shop::getContextListShopID();

            $res = true;
            foreach ($shops as $shop_id) {
                $shop_group_id = (int)Shop::getGroupFromShop($shop_id, true);

                if (!in_array($shop_group_id, $shop_groups_list)) {
                    $shop_groups_list[] = $shop_group_id;
                }

                $res = Configuration::updateValue('HOMESLIDER_WIDTH', (int)Tools::getValue('HOMESLIDER_WIDTH'), false, $shop_group_id, $shop_id) && $res;
                $res = Configuration::updateValue('HOMESLIDER_SPEED', (int)Tools::getValue('HOMESLIDER_SPEED'), false, $shop_group_id, $shop_id) && $res;
                $res = Configuration::updateValue('HOMESLIDER_PAUSE', (int)Tools::getValue('HOMESLIDER_PAUSE'), false, $shop_group_id, $shop_id) && $res;
                $res = Configuration::updateValue('HOMESLIDER_LOOP', (int)Tools::getValue('HOMESLIDER_LOOP'), false, $shop_group_id, $shop_id) && $res;
            }

            /* Update global shop context if needed*/
            switch ($shop_context) {
                case Shop::CONTEXT_ALL:
                    $res = Configuration::updateValue('HOMESLIDER_WIDTH', (int)Tools::getValue('HOMESLIDER_WIDTH')) && $res;
                    $res = Configuration::updateValue('HOMESLIDER_SPEED', (int)Tools::getValue('HOMESLIDER_SPEED')) && $res;
                    $res = Configuration::updateValue('HOMESLIDER_PAUSE', (int)Tools::getValue('HOMESLIDER_PAUSE')) && $res;
                    $res = Configuration::updateValue('HOMESLIDER_LOOP', (int)Tools::getValue('HOMESLIDER_LOOP')) && $res;
                    if (count($shop_groups_list)) {
                        foreach ($shop_groups_list as $shop_group_id) {
                            $res = Configuration::updateValue('HOMESLIDER_WIDTH', (int)Tools::getValue('HOMESLIDER_WIDTH'), false, $shop_group_id) && $res;
                            $res = Configuration::updateValue('HOMESLIDER_SPEED', (int)Tools::getValue('HOMESLIDER_SPEED'), false, $shop_group_id) && $res;
                            $res = Configuration::updateValue('HOMESLIDER_PAUSE', (int)Tools::getValue('HOMESLIDER_PAUSE'), false, $shop_group_id) && $res;
                            $res = Configuration::updateValue('HOMESLIDER_LOOP', (int)Tools::getValue('HOMESLIDER_LOOP'), false, $shop_group_id) && $res;
                        }
                    }
                    break;
                case Shop::CONTEXT_GROUP:
                    if (count($shop_groups_list)) {
                        foreach ($shop_groups_list as $shop_group_id) {
                            $res = Configuration::updateValue('HOMESLIDER_WIDTH', (int)Tools::getValue('HOMESLIDER_WIDTH'), false, $shop_group_id) && $res;
                            $res = Configuration::updateValue('HOMESLIDER_SPEED', (int)Tools::getValue('HOMESLIDER_SPEED'), false, $shop_group_id) && $res;
                            $res = Configuration::updateValue('HOMESLIDER_PAUSE', (int)Tools::getValue('HOMESLIDER_PAUSE'), false, $shop_group_id) && $res;
                            $res = Configuration::updateValue('HOMESLIDER_LOOP', (int)Tools::getValue('HOMESLIDER_LOOP'), false, $shop_group_id) && $res;
                        }
                    }
                    break;
            }

            $this->clearCache();

            if (!$res) {
                $errors[] = $this->l('The configuration could not be updated.');
            } else {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&conf=6&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name);
            }
        } elseif (Tools::isSubmit('changeStatus') && Tools::isSubmit('id_slide')) {
            /* Process Slide status */

            $slide = new HomeSlide((int)Tools::getValue('id_slide'));
            if ($slide->active == 0) {
                $slide->active = 1;
            } else {
                $slide->active = 0;
            }
            $res = $slide->update();
            $this->clearCache();
            $this->_html .= ($res ? $this->displayConfirmation($this->l('Configuration updated')) : $this->displayError($this->l('The configuration could not be updated.')));
        } elseif (Tools::isSubmit('submitSlide')) {
            /* Processes Slide */
            /* Sets ID if needed */
            if (Tools::getValue('id_slide')) {
                $slide = new HomeSlide((int)Tools::getValue('id_slide'));
                if (!Validate::isLoadedObject($slide)) {
                    $this->_html .= $this->displayError($this->l('Invalid slide ID'));
                    return false;
                }
            } else {
                $slide = new HomeSlide();
            }
            /* Sets position */
            $slide->position = (int)Tools::getValue('position');
            /* Sets active */
            $slide->active = (int)Tools::getValue('active_slide');

            /* Sets each language fields */
            $languages = Language::getLanguages(false);
            foreach ($languages as $language) {
                $langId = (int)$language['id_lang'];
                $slide->title[$langId] = Tools::getValue('title_' . $langId);
                $slide->url[$langId] = Tools::getValue('url_' . $langId);
                $slide->legend[$langId] = Tools::getValue('legend_' . $langId);
                $slide->description[$langId] = Tools::getValue('description_' . $langId);

                /* Uploads image and sets slide */
                if (isset($_FILES['image_' . $langId])) {
                    $fileEntry = $_FILES['image_' . $langId];
                    $tempFile = null;
                    try {
                        $tempFile = $this->uploadImage($fileEntry);
                        if ($tempFile) {
                            $slide->image[$langId] = $this->processImage($tempFile, $fileEntry['name']);
                        }
                    } catch (Exception $e) {
                        $errors[] = sprintf($this->l('Failed to upload image for language %s: %s'), $language['name'], $e->getMessage());
                    } finally {
                        if ($tempFile) {
                            unlink(@$tempFile);
                        }
                    }
                }
            }

            /* Processes if no errors  */
            if (!$errors) {
                /* Adds */
                if (!Tools::getValue('id_slide')) {
                    if (!$slide->add()) {
                        $errors[] = $this->l('The slide could not be added.');
                    }
                } elseif (!$slide->update()) {
                    /* Update */
                    $errors[] = $this->l('The slide could not be updated.');
                }
                $this->clearCache();
            }
        } elseif (Tools::isSubmit('delete_id_slide')) {
            /* Deletes */
            $slide = new HomeSlide((int)Tools::getValue('delete_id_slide'));
            $res = $slide->delete();
            $this->clearCache();
            if (!$res) {
                $errors[] = $this->l('Could not delete.');
            } else {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&conf=1&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name);
            }
        }

        /* Display errors if needed */
        if (count($errors)) {
            $this->_html .= $this->displayError(implode('<br />', $errors));
        } elseif (Tools::isSubmit('submitSlide') && Tools::getValue('id_slide')) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&conf=4&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name);
        } elseif (Tools::isSubmit('submitSlide')) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&conf=3&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name);
        }
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function _prepareHook()
    {
        if (!$this->isCached('homeslider.tpl', $this->getCacheId())) {
            $slides = $this->getSlides(true);
            if (is_array($slides)) {
                foreach ($slides as &$slide) {
                    $slide['imageUrl'] = static::getImageBaseUri() . $slide['image'];
                    $slide['sizes'] = @getimagesize(static::getImageDir() . $slide['image']);
                    if (isset($slide['sizes'][3]) && $slide['sizes'][3]) {
                        $slide['size'] = $slide['sizes'][3];
                    }
                }
            }

            if (!$slides) {
                return false;
            }

            $this->smarty->assign(array('homeslider_slides' => $slides));
        }

        return true;
    }

    /**
     * @param $params
     *
     * @return string|void
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookdisplayHeader($params)
    {
        if (!isset($this->context->controller->php_self) || $this->context->controller->php_self != 'index') {
            return;
        }
        $this->context->controller->addCSS($this->_path . 'homeslider.css');
        $this->context->controller->addJS($this->_path . 'js/homeslider.js');
        $this->context->controller->addJqueryPlugin(array('bxslider'));

        $config = $this->getConfigFieldsValues();
        $slider = array(
            'width' => $config['HOMESLIDER_WIDTH'],
            'speed' => $config['HOMESLIDER_SPEED'],
            'pause' => $config['HOMESLIDER_PAUSE'],
            'loop' => (bool)$config['HOMESLIDER_LOOP'],
        );

        $this->smarty->assign('homeslider', $slider);
        return $this->display(__FILE__, 'header.tpl');
    }

    /**
     * @param $params
     *
     * @return false|string|null
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookdisplayTop($params)
    {
        return $this->hookdisplayTopColumn($params);
    }

    /**
     * @param $params
     *
     * @return false|string|void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookdisplayTopColumn($params)
    {
        if (!isset($this->context->controller->php_self) || $this->context->controller->php_self != 'index') {
            return;
        }

        if (!$this->_prepareHook()) {
            return false;
        }

        return $this->display(__FILE__, 'homeslider.tpl', $this->getCacheId());
    }

    /**
     * @return false|string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayHome()
    {
        if (!$this->_prepareHook()) {
            return false;
        }

        return $this->display(__FILE__, 'homeslider.tpl', $this->getCacheId());
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    public function clearCache()
    {
        $this->_clearCache('homeslider.tpl');
    }

    /**
     * @param $params
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookActionShopDataDuplication($params)
    {
        Db::getInstance()->execute('
			INSERT IGNORE INTO ' . _DB_PREFIX_ . 'homeslider (id_homeslider_slides, id_shop)
			SELECT id_homeslider_slides, ' . (int)$params['new_id_shop'] . '
			FROM ' . _DB_PREFIX_ . 'homeslider
			WHERE id_shop = ' . (int)$params['old_id_shop']
        );
        $this->clearCache();
    }

    /**
     * @return string|void
     */
    public function headerHTML()
    {
        if (Tools::getValue('controller') != 'AdminModules' || Tools::getValue('configure') != $this->name) {
            return '';
        }

        $this->context->controller->addJqueryUI('ui.sortable');

        $ajaxUrl = $this->context->shop->getBaseURL(true)
                 . 'modules/' . $this->name
                 . '/ajax_' . $this->name . '.php?secure_key=' . $this->getSecureKey()
                 . '&action=updateSlidesPosition';

        return "
    <script type=\"text/javascript\">
      \$(function() {
        \$('#slides').sortable({
          axis: 'y',
          cursor: 'move',
          opacity: 0.6,
          update: function() {
            // collect IDs in order
            var order = \$('#slides li').map(function() {
              return this.id.replace('slide_','');
            }).get();

            // send slides[] array to PHP
            \$.post('{$ajaxUrl}', { 'slides[]': order }, function(resp){
              // optional: show a tiny confirmation
            });
          }
        });
      });
    </script>
    ";
    }

    /**
     * @return int|mixed
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getNextPosition()
    {
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
			SELECT MAX(hss.`position`) AS `next_position`
			FROM `' . _DB_PREFIX_ . 'homeslider_slides` hss, `' . _DB_PREFIX_ . 'homeslider` hs
			WHERE hss.`id_homeslider_slides` = hs.`id_homeslider_slides` AND hs.`id_shop` = ' . (int)$this->context->shop->id
        );

        return (++$row['next_position']);
    }

    /**
     * @param $active
     *
     * @return array|bool|PDOStatement
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getSlides($active = null)
    {
        $this->context = Context::getContext();
        $id_shop = $this->context->shop->id;
        $id_lang = $this->context->language->id;

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT hs.`id_homeslider_slides` as id_slide, hss.`position`, hss.`active`, hssl.`title`,
			hssl.`url`, hssl.`legend`, hssl.`description`, hssl.`image`
			FROM ' . _DB_PREFIX_ . 'homeslider hs
			LEFT JOIN ' . _DB_PREFIX_ . 'homeslider_slides hss ON (hs.id_homeslider_slides = hss.id_homeslider_slides)
			LEFT JOIN ' . _DB_PREFIX_ . 'homeslider_slides_lang hssl ON (hss.id_homeslider_slides = hssl.id_homeslider_slides)
			WHERE id_shop = ' . (int)$id_shop . '
			AND hssl.id_lang = ' . (int)$id_lang .
            ($active ? ' AND hss.`active` = 1' : ' ') . '
			ORDER BY hss.position'
        );
    }

    /**
     * @param $id_slides
     * @param $active
     * @param $id_shop
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getAllImagesBySlidesId($id_slides, $active = null, $id_shop = null)
    {
        $this->context = Context::getContext();
        $images = [];

        if (!isset($id_shop)) {
            $id_shop = $this->context->shop->id;
        }

        $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT hssl.`image`, hssl.`id_lang`
			FROM ' . _DB_PREFIX_ . 'homeslider hs
			LEFT JOIN ' . _DB_PREFIX_ . 'homeslider_slides hss ON (hs.id_homeslider_slides = hss.id_homeslider_slides)
			LEFT JOIN ' . _DB_PREFIX_ . 'homeslider_slides_lang hssl ON (hss.id_homeslider_slides = hssl.id_homeslider_slides)
			WHERE hs.`id_homeslider_slides` = ' . (int)$id_slides . ' AND hs.`id_shop` = ' . (int)$id_shop .
            ($active ? ' AND hss.`active` = 1' : ' ')
        );

        foreach ($results as $result) {
            $images[$result['id_lang']] = $result['image'];
        }

        return $images;
    }

    /**
     * @param $id_slide
     * @param $active
     *
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function displayStatus($id_slide, $active)
    {
        $title = ((int)$active == 0 ? $this->l('Disabled') : $this->l('Enabled'));
        $icon = ((int)$active == 0 ? 'icon-remove' : 'icon-check');
        $class = ((int)$active == 0 ? 'btn-danger' : 'btn-success');
        $html = '<a class="btn ' . $class . '" href="' . AdminController::$currentIndex .
            '&configure=' . $this->name . '
				&token=' . Tools::getAdminTokenLite('AdminModules') . '
				&changeStatus&id_slide=' . (int)$id_slide . '" title="' . $title . '"><i class="' . $icon . '"></i> ' . $title . '</a>';

        return $html;
    }

    /**
     * @param $id_slide
     *
     * @return array|false|object|null
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function slideExists($id_slide)
    {
        $req = 'SELECT hs.`id_homeslider_slides` as id_slide
				FROM `' . _DB_PREFIX_ . 'homeslider` hs
				WHERE hs.`id_homeslider_slides` = ' . (int)$id_slide;
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($req);

        return ($row);
    }

    /**
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderList()
    {
        $slides = $this->getSlides();
        foreach ($slides as $key => $slide) {
            $slides[$key]['status'] = $this->displayStatus($slide['id_slide'], $slide['active']);
            $associated_shop_ids = HomeSlide::getAssociatedIdsShop((int)$slide['id_slide']);
            if ($associated_shop_ids && count($associated_shop_ids) > 1) {
                $slides[$key]['is_shared'] = true;
            } else {
                $slides[$key]['is_shared'] = false;
            }
        }

        $this->context->smarty->assign(
            array(
                'link' => $this->context->link,
                'slides' => $slides,
                'image_baseurl' => static::getImageBaseUri(),
            )
        );

        return $this->display(__FILE__, 'list.tpl');
    }

    /**
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderAddForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Slide information'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'file_lang',
                        'label' => $this->l('Select a file'),
                        'name' => 'image',
                        'required' => true,
                        'lang' => true,
                        'desc' => sprintf(
                            $this->l('Maximum image size: %s. Allowed extensions: %s.'), ini_get('upload_max_filesize'), implode(', ', ImageManager::getAllowedImageExtensions(true, true, true, null))
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Slide title'),
                        'name' => 'title',
                        'required' => true,
                        'lang' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Target URL'),
                        'name' => 'url',
                        'required' => true,
                        'lang' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Caption'),
                        'name' => 'legend',
                        'required' => true,
                        'lang' => true,
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->l('Description'),
                        'name' => 'description',
                        'autoload_rte' => true,
                        'lang' => true,
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enabled'),
                        'name' => 'active_slide',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No')
                            )
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            ),
        );

        if (Tools::isSubmit('id_slide') && $this->slideExists((int)Tools::getValue('id_slide'))) {
            $slide = new HomeSlide((int)Tools::getValue('id_slide'));
            $fields_form['form']['input'][] = array('type' => 'hidden', 'name' => 'id_slide');
            $fields_form['form']['images'] = $slide->image;

            $has_picture = true;
            foreach (Language::getLanguages(false) as $lang) {
                if (!isset($slide->image[$lang['id_lang']])) {
                    $has_picture = false;
                }
            }

            if ($has_picture) {
                $fields_form['form']['input'][] = array('type' => 'hidden', 'name' => 'has_picture');
            }
        }

        /** @var AdminModulesController $controller */
        $controller = $this->context->controller;
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSlide';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $language = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->tpl_vars = array(
            'base_url' => $this->context->shop->getBaseURL(),
            'language' => array(
                'id_lang' => $language->id,
                'iso_code' => $language->iso_code
            ),
            'fields_value' => $this->getAddFieldsValues(),
            'languages' => $controller->getLanguages(),
            'id_language' => $this->context->language->id,
            'image_baseurl' => static::getImageBaseUri(),
        );

        $helper->override_folder = '/';

        $languages = Language::getLanguages(false);

        if (count($languages) > 1) {
            return $this->getMultiLanguageInfoMsg() . $helper->generateForm(array($fields_form));
        } else {
            return $helper->generateForm(array($fields_form));
        }
    }

    /**
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Maximum image width'),
                        'name' => 'HOMESLIDER_WIDTH',
                        'suffix' => 'pixels',
                        'desc' => $this->l('This is the width of the slider. Images get scaled to fit. Height of the container adjusts automatically to fit the highest enabled image.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Speed'),
                        'name' => 'HOMESLIDER_SPEED',
                        'suffix' => 'milliseconds',
                        'desc' => $this->l('The duration of the transition between two slides.')
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Pause'),
                        'name' => 'HOMESLIDER_PAUSE',
                        'suffix' => 'milliseconds',
                        'desc' => $this->l('The delay between two slides.')
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Auto play'),
                        'name' => 'HOMESLIDER_LOOP',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            ),
        );

        /** @var AdminModulesController $controller */
        $controller = $this->context->controller;
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSlider';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }

    /**
     * @return array
     * @throws PrestaShopException
     */
    public function getConfigFieldsValues()
    {
        $id_shop_group = Shop::getContextShopGroupID();
        $id_shop = Shop::getContextShopID();

        return array(
            'HOMESLIDER_WIDTH' => Tools::getValue('HOMESLIDER_WIDTH', Configuration::get('HOMESLIDER_WIDTH', null, $id_shop_group, $id_shop)),
            'HOMESLIDER_SPEED' => Tools::getValue('HOMESLIDER_SPEED', Configuration::get('HOMESLIDER_SPEED', null, $id_shop_group, $id_shop)),
            'HOMESLIDER_PAUSE' => Tools::getValue('HOMESLIDER_PAUSE', Configuration::get('HOMESLIDER_PAUSE', null, $id_shop_group, $id_shop)),
            'HOMESLIDER_LOOP' => Tools::getValue('HOMESLIDER_LOOP', Configuration::get('HOMESLIDER_LOOP', null, $id_shop_group, $id_shop)),
        );
    }

    /**
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getAddFieldsValues()
    {
        $fields = array();

        if (Tools::isSubmit('id_slide') && $this->slideExists((int)Tools::getValue('id_slide'))) {
            $slide = new HomeSlide((int)Tools::getValue('id_slide'));
            $fields['id_slide'] = (int)Tools::getValue('id_slide', $slide->id);
        } else {
            $slide = new HomeSlide();
        }

        $fields['active_slide'] = Tools::getValue('active_slide', $slide->active);
        $fields['has_picture'] = true;

        $languages = Language::getLanguages(false);

        foreach ($languages as $lang) {
            $langId = (int)$lang['id_lang'];
            $fields['image'][$langId] = Tools::getValue('image_' . $langId);
            $fields['title'][$langId] = Tools::getValue('title_' . $langId, static::getLangValue($slide->title, $langId));
            $fields['url'][$langId] = Tools::getValue('url_' . $langId, static::getLangValue($slide->url, $langId));
            $fields['legend'][$langId] = Tools::getValue('legend_' . $langId, static::getLangValue($slide->legend, $langId));
            $fields['description'][$langId] = Tools::getValue('description_' . $langId, static::getLangValue($slide->description, $langId));
        }

        return $fields;
    }

    /**
     * @param array|null $array
     * @param int $langId
     *
     * @return string
     */
    protected static function getLangValue($array, $langId)
    {
        return isset($array[$langId]) ? (string)$array[$langId] : '';
    }

    /**
     * @return string
     */
    protected function getMultiLanguageInfoMsg()
    {
        return '<p class="alert alert-warning">' .
            $this->l('Since multiple languages are activated on your shop, please mind to upload your image for each one of them') .
            '</p>';
    }

    /**
     * @return string
     */
    protected function getWarningMultishopHtml()
    {
        if (Shop::getContext() == Shop::CONTEXT_GROUP || Shop::getContext() == Shop::CONTEXT_ALL) {
            return '<p class="alert alert-warning">' .
                $this->l('You cannot manage slides items from a "All Shops" or a "Group Shop" context, select directly the shop you want to edit') .
                '</p>';
        } else {
            return '';
        }
    }

    /**
     * @param $shop_contextualized_name
     * @param $mode
     *
     * @return string
     */
    protected function getShopContextError($shop_contextualized_name, $mode)
    {
        if (is_array($shop_contextualized_name)) {
            $shop_contextualized_name = implode('<br/>', $shop_contextualized_name);
        }

        if ($mode == 'edit') {
            return '<p class="alert alert-danger">' .
                sprintf($this->l('You can only edit this slide from the shop(s) context: %s'), $shop_contextualized_name) .
                '</p>';
        } else {
            return '<p class="alert alert-danger">' .
                $this->l('You cannot add slides from a "All Shops" or a "Group Shop" context') .
                '</p>';
        }
    }

    /**
     * @param $id_slide
     *
     * @return string
     */
    protected function getShopAssociationError($id_slide)
    {
        return '<p class="alert alert-danger">' .
            sprintf($this->l('Unable to get slide shop association information (id_slide: %d)'), (int)$id_slide) .
            '</p>';
    }


    /**
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function getCurrentShopInfoMsg()
    {
        if (Shop::isFeatureActive()) {
            if (Shop::getContext() == Shop::CONTEXT_SHOP) {
                $shop_info = sprintf($this->l('The modifications will be applied to shop: %s'), $this->context->shop->name);
            } else {
                if (Shop::getContext() == Shop::CONTEXT_GROUP) {
                    $shop_info = sprintf($this->l('The modifications will be applied to this group: %s'), Shop::getContextShopGroup()->name);
                } else {
                    $shop_info = $this->l('The modifications will be applied to all shops and shop groups');
                }
            }

            return '<div class="alert alert-info">' .
                $shop_info .
                '</div>';
        } else {
            return '';
        }
    }

    /**
     * @return string
     */
    protected function getSharedSlideWarning()
    {
        return '<p class="alert alert-warning">' .
            $this->l('This slide is shared with other shops! All shops associated to this slide will apply modifications made here') .
            '</p>';
    }

    /**
     * @return string
     */
    public function getSecureKey()
    {
        return Tools::encrypt($this->name);
    }

    /**
     * Validates uploaded image file, and moves it to temp location
     *
     * @return string|false temp location
     *
     * @throws PrestaShopException
     */
    protected function uploadImage($fileEntry)
    {
        if (empty($fileEntry['name']) || empty($fileEntry['tmp_name'])) {
            return false;
        }
        if ($error = ImageManager::validateUpload($fileEntry)) {
            // Pull just the main extensions your store actually accepts
            $allowed = ImageManager::getAllowedImageExtensions(true, true, null, true);

            $message = sprintf(
                $this->l('Image format not recognized, allowed formats are: %s'),
                implode(', ', $allowed)
            );
            throw new PrestaShopException($message);
        }
        $tempFile = tempnam(_PS_TMP_IMG_DIR_, $this->name . '_');
        if (! move_uploaded_file($fileEntry['tmp_name'], $tempFile)) {
            throw new PrestaShopException($this->l('Failed to move file to temp location'));
        }
        return $tempFile;
    }

    /**
     * @param string $sourceFile
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    protected function processImage($sourceFile, $sourceFileName)
    {
        // make sure it's actually an image
        if (!@getimagesize($sourceFile)) {
            throw new PrestaShopException($this->l('Failed to resolve image size'));
        }

        // 1) Extract extension, 2) check against core’s list, 3) pass extension into resize()
        $ext = Tools::strtolower(pathinfo($sourceFileName, PATHINFO_EXTENSION));
        $allowed = ImageManager::getAllowedImageExtensions();
        if (!in_array($ext, $allowed)) {
            throw new PrestaShopException(sprintf($this->l('Unsupported file extension %s'), $ext));
        }

        // build target filename & path
        $filename = md5(microtime()) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $sourceFileName);
        $filepath = static::getImageDir() . $filename;

        // now resize using the extension (not the mime‐type)
        if (!ImageManager::resize($sourceFile, $filepath, null, null, $ext)) {
            throw new PrestaShopException($this->l('An error occurred during the image resizing'));
        }
        return $filename;
    }

    /**
     * Helper method to delete unused image files from img directory
     *
     * @param array|null $imagesToKeep
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function cleanImages($imagesToKeep = null)
    {
        if (is_null($imagesToKeep)) {
            $imagesToKeep = array_column(Db::getInstance()->executeS((new DbQuery())
                ->select('DISTINCT(image) as image')
                ->from('homeslider_slides_lang')
            ), 'image');
        }

        $dir = static::getImageDir();
        $allowed = ImageManager::getAllowedImageExtensions();
        foreach (scandir($dir) as $file) {
            if (in_array(pathinfo($file, PATHINFO_EXTENSION), $allowed)
                && !in_array($file, $imagesToKeep)
            ) {
                @unlink($dir . $file);
            }
        }
    }

    /**
     * Returns path to image directory
     *
     * @return string
     */
    public static function getImageDir()
    {
        $imageDir = rtrim(_PS_IMG_DIR_, '/') . '/homeslider/';
        // create directory if it doesn't exist yet
        if (! file_exists($imageDir)) {
            mkdir($imageDir);
        }
        return $imageDir;
    }

    /**
     * Returns base URI to images
     *
     * @return string
     */
    public static function getImageBaseUri()
    {
        return rtrim(_PS_IMG_, '/') . '/homeslider/';
    }

}
