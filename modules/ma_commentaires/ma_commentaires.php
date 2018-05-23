<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class Ma_Commentaires
 *
 * @author                 Maxime AVELINE <aveline.maxime@gmail.com>
 * @copyright              Copyright (c) 2018
 * @license                http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link                   http://www.maximeaveline.fr/
 */
class Ma_Commentaires extends Module
{

    /** @const string TABLE_NAME */
    const TABLE_NAME = 'macommentaires';

    /**
     * Ma_Commentaires constructor.
     */
    public function __construct()
    {
        $this->name                   = 'ma_commentaires';
        $this->tab                    = 'front_office_features';
        $this->version                = '1.0.0';
        $this->author                 = 'Maxime AVELINE';
        $this->need_instance          = 0;
        $this->ps_versions_compliancy = array(
            'min' => '1.7',
            'max' => _PS_VERSION_
        );

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('MA Commentaires');
        $this->description = $this->l('Ceci est un module de commentaires présent sur les pages produits');

        $this->confirmUninstall = $this->l('Êtes-vous sûr de vouloir désinstaller ce module ?');

        if (!Configuration::get('MACOMMENTAIRES_NAME')) {
            $this->warning = $this->l('No name provided');
        }
    }

    /**
     * @return bool
     */
    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        return parent::install() && $this->registerHook('maCommentaires') && $this->registerHook('actionFrontControllerSetMedia') && $this->installDb() && Configuration::updateValue('MACOMMENTAIRES_NAME', 'my friend');
    }

    /**
     * Get datetime with basic format
     *
     * @return string
     */
    public function getBasicDatetime()
    {
        /** @var string $basicDatetime */
        $basicDatetime = date('Y-m-d h:i:s');

        return $basicDatetime;
    }

    /**
     * Get form's username and comment message
     *
     * @return array
     */
    public function getCommentInfos()
    {
        /** @var string $username */
        $username = Tools::getValue('username');
        /** @var string $comment */
        $comment_message = Tools::getValue('comment_message');
        /** @var array $commentInfos */
        $commentInfos = [
            'username'        => $username,
            'comment_message' => $comment_message,
        ];

        return $commentInfos;
    }

    /**
     * @param $params
     *
     * @return string
     */
    public function hookMaCommentaires($params)
    {
        /** @var object $link */
        $link = new Link();
        /** @var object $db */
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);

        /** @var string $basicDatetime */
        $basicDatetime = $this->getBasicDatetime();

        /** @var int $productId */
        $productId = $params['product']['id'];
        /** @var string $productUrl */
        $productUrl = $link->getProductLink($productId);

        /** @var array $commentInfos */
        $commentInfos = $this->getCommentInfos();
        /** @var array $commentAdditionalInfos */
        $commentAdditionalInfos = [
            'product_id' => $productId,
            'date_add'   => $basicDatetime
        ];
        /** @var array $FormInfos */
        $formInfos = $commentAdditionalInfos + $commentInfos;

        /** @var boolean $usernameSubmit */
        $usernameIsSubmit = Tools::isSubmit('username');
        /** @var boolean $commentMessageSubmit */
        $commentMessageIsSubmit = Tools::isSubmit('comment_message');

        /** @var string $selectCommentsSql */
        $selectCommentsSql = 'SELECT * FROM ' . _DB_PREFIX_ . self::TABLE_NAME . ' WHERE `product_id` = ' . $productId;
        /** @var array $comments */
        $allComments = $db->executeS($selectCommentsSql);

        // Check if comment form fields are filled to insert form's data
        if ($usernameIsSubmit && $commentMessageIsSubmit) {
            $db->insert(self::TABLE_NAME, $formInfos);
        }

        // Assign data to send in the template
        $this->context->smarty->assign(array(
            'ma_commentaires_name' => Configuration::get('MACOMMENTAIRES_NAME'),
            'ma_commentaires_link' => $this->context->link->getModuleLink('ma_commentaires', 'display'),
            'product_id'           => $productId,
            'url'                  => $productUrl,
            'all_comments'         => $allComments,
        ));

        return $this->display(__FILE__, 'ma_commentaires.tpl');
    }

    /**
     * @return bool
     */
    public function installDB()
    {
        $return = true;
        $return &= Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::TABLE_NAME . '` (
              `id_comment` int(11) NOT NULL AUTO_INCREMENT,
              `product_id` int(11) NOT NULL,
              `comment_message` varchar(255) NOT NULL,
              `username` varchar(255) NOT NULL,
              `date_add` datetime NOT NULL,
              PRIMARY KEY (`id_comment`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
        ');

        return $return;
    }

    public function hookActionFrontControllerSetMedia($params)
    {
        $this->context->controller->registerStylesheet(
            'module-ma_commentaires-style',
            'modules/' . $this->name . '/css/macommentaires.css',
            [
                'media'    => 'all',
                'priority' => 200,
            ]
        );
    }
}