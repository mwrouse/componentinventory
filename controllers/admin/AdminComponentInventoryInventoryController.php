<?php

if ( ! defined('_TB_VERSION_')) {
    exit;
}

class AdminComponentInventoryInventoryController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->show_toolbar = true;
        $this->identifier = 'id';
        $this->table = 'componentinventory_parts';
        $this->className = 'componentinventory';

        parent::__construct();
    }

    /**
     * Tool Bar Buttons
     */
    public function initPageHeaderToolbar()
    {
        $this->page_header_toolbar_btn['view_db'] = [
            'href' => 'index.php?controller=AdminComponentInventoryDashboard&token='.Tools::getAdminTokenLite('AdminComponentInventoryDashboard'),
            'desc' => $this->l('Dashboard', null, null, false),
            'icon' => 'process-icon-dashboard',
        ];

        if (empty($this->display) || $this->display =='list') {
            $this->page_header_toolbar_btn['new_part'] = [
                'href' => static::$currentIndex.'&configure=parts&id=new&update'.$this->table.'&token='.$this->token,
                'desc' => $this->l('New Part', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        $this->page_header_toolbar_btn['view_po'] = [
            'href' => 'index.php?controller=AdminComponentInventoryPurchaseOrders&token='.Tools::getAdminTokenLite('AdminComponentInventoryPurchaseOrders'),
            'desc' => $this->l('View P.O.s', null, null, false),
            'icon' => 'process-icon-view',
        ];

        if (empty($this->display) || $this->display =='list') {
            $this->page_header_toolbar_btn['new_po'] = [
                'href' => 'index.php?controller=AdminComponentInventoryPurchaseOrders&configure=po&id=new&update'.$this->module->table_po.'&token='.Tools::getAdminTokenLite('AdminComponentInventoryPurchaseOrders'),
                'desc' => $this->l('New P.O.', null, null, false),
                'icon' => 'process-icon-cart',
            ];
        }

        parent::initPageHeaderToolbar();
    }


    /**
     * Render the list of items
     */
    public function renderList()
    {
        $content = '';

        // Low on Inventory
        $lowOnInventory = $this->module->getAllPartsLowOnInventory();
        $fieldList = [
            'name' => [
                'title' => 'Name',
            ],
            'description' => [
                'title' => 'Description',
            ],
            'supplier' => [
                'title' => 'Supplier'
            ],
            'qty_available' => [
                'title' => 'Qty.',
                'align'   => 'center',
                'class'   => 'fixed-width-xs',
                'color' => 'red',
            ],
            'qty_on_order' => [
                'title' => 'On Order',
                'align'   => 'center',
                'class'   => 'fixed-width-xs',
            ],
            'supplier_link' => [
                'title' => 'View Supplier Page',
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'search' => false,
                'callback' => 'renderLinkOnHelperList'
            ]
        ];

        $helper = new HelperList();
        $helper->no_link = true;
        $helper->shopLinkType = '';
        $helper->simple_header = true;
        $helper->actions = ["edit"];
        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->listTotal = count($lowOnInventory);
        $helper->identifier = 'id';
        $helper->position_identifier = 'id';
        $helper->title = "Low Inventory";
        $helper->orderBy = 'qty_available';
        $helper->orderWay = 'ASC';
        $helper->table = $this->table;
        $helper->force_show_bulk_actions = true;
        $helper->bulk_actions = [
            'createPO' => [
                'confirm' => 'Are you sure you want to add these parts to a new Purchase Order?',
                'text' => 'Create Purchase Order'
            ]
        ];
        $helper->token = Tools::getAdminTokenLite('AdminComponentInventoryInventory');
        $helper->currentIndex = AdminController::$currentIndex.'&configure=parts';

        $content .= $helper->generateList($lowOnInventory, $fieldList);

        // All parts
        $allParts = $this->module->getAllParts();
        $fieldList = [
            'name' => [
                'title' => 'Name',
            ],
            'description' => [
                'title' => 'Description',
            ],
            'supplier' => [
                'title' => 'Supplier'
            ],
            'qty_available' => [
                'title' => 'Qty.',
                'align'   => 'center',
                'class'   => 'fixed-width-xs',
                'color' => 'red',
            ],
            'qty_on_order' => [
                'title' => 'On Order',
                'align'   => 'center',
                'class'   => 'fixed-width-xs',
            ],
            'supplier_link' => [
                'title' => 'View Supplier Page',
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'search' => false,
                'callback' => 'renderLinkOnHelperList'
            ],
            'datasheet_link' => [
                'title' => 'Datasheet',
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'search' => false,
                'callback' => 'renderDatasheetLinkOnHelperList'
            ],
        ];

        $helper = new HelperList();
        $helper->no_link = true;
        $helper->shopLinkType = '';
        $helper->simple_header = true;
        $helper->actions = ["edit"];
        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->listTotal = count($lowOnInventory);
        $helper->identifier = 'id';
        $helper->position_identifier = 'id';
        $helper->title = "Entire Inventory";
        $helper->orderBy = 'qty_available';
        $helper->orderWay = 'ASC';
        $helper->table = $this->table;
        $helper->token = Tools::getAdminTokenLite('AdminComponentInventoryInventory');
        $helper->currentIndex = AdminController::$currentIndex.'&configure=parts';

        $content .= $helper->generateList($allParts, $fieldList);

        return $content;
    }


    /**
     * Renders a form
     */
    public function renderForm()
    {
        $id = Tools::getValue('id', null);

        return $this->renderPartForm($id);

    }


    /**
     * Renders the part form
     */
    private function renderPartForm($id_part)
    {
        $part = $this->module->getPart($id_part);
        $this->fields_value = $part;

        $lang = $this->context->language;

        $inputs[] = [
            'type' => 'text',
            'label' => $this->l("Name"),
            'name' => 'name'
        ];
        $inputs[] = [
            'type' => 'text',
            'label' => $this->l("Description"),
            'name' => 'description'
        ];
        $inputs[] = [
            'type' => 'tags',
            'label' => 'Keywords',
            'name' => 'keywords',
            'lang' => true,
        ];
        $inputs[] = [
            'type' => 'text',
            'label' => $this->l("Manufacturer"),
            'name' => 'manufacturer'
        ];
        $inputs[] = [
            'type' => 'text',
            'label' => $this->l("Manufacturer Part Number"),
            'name' => 'manufacturer_part_number'
        ];
        $inputs[] = [
            'type' => 'text',
            'label' => $this->l("Data Sheet Link"),
            'name' => 'datasheet_link'
        ];
        $inputs[] = [
            'type' => 'text',
            'label' => $this->l("Supplier"),
            'name' => 'supplier'
        ];
        $inputs[] = [
            'type' => 'text',
            'label' => $this->l("Supplier Part Number"),
            'name' => 'supplier_part_number'
        ];
        $inputs[] = [
            'type' => 'text',
            'label' => $this->l("Link"),
            'name' => 'supplier_link'
        ];
        $inputs[] = [
            'type' => 'hidden',
            'name' => 'id_part'
        ];

        $inputs[] = [
            'type' => 'html',
            'label' => 'Quantity Available',
            'name' => 'qty_available',
            'html_content' => '<input type="number" name="qty_available" value="'.$part['qty_available'].'" style="margin-top: 5px">',
        ];

        $action='submitEditPart';

        $this->fields_form = [
            'legend' => [
                'title' => ($part['id_part'] == 'new') ? 'New Part' : 'Edit Part',
                'icon'  => 'icon-cogs',
            ],
            'input' => $inputs,
            'buttons' => [
                'save-and-stay' => [
                    'title' => $this->l('Save and Stay'),
                    'class' => 'btn btn-default pull-right',
                    'name' => $action.'AndStay',
                    'icon' => 'process-icon-save',
                    'type' => 'submit'
                ]

            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
                'name'  => $action,
            ],

        ];

        return parent::renderForm();
    }





    /**
     * When something is submitted
     */
    public function postProcess()
    {
        $submitPart = $this->isSubmit('submitEditPart');

        if ($submitPart) {
            $id = Tools::getValue('id_part');
            if ($id == 'new') {
                $this->processAddPart();
            }
            else {
                $this->processUpdatePart($id);
            }
        }
        else {
            $bulkParts = Tools::getValue('componentinventory_partsBox', null);
            if (!is_null($bulkParts)) {
                $this->processBulkUpdate($bulkParts);
            }
        }
    }


    /**
     * Adds a new part
     */
    private function processAddPart()
    {
        $saveAndStay = $this->isSaveAndStay('submitEditPart');

        $name = Tools::getValue('name');
        $desc = Tools::getValue('description');
        $keywords = Tools::getValue('keywords');
        $manufacturer = Tools::getValue('manufacturer');
        $manPartNum = Tools::getValue('manufacturer_part_number');
        $datasheet = Tools::getValue('datasheet_link');
        $supplier = Tools::getValue('supplier');
        $supPartNum = Tools::getValue('supplier_part_number');
        $supLink = Tools::getValue('supplier_link');
        $qty = Tools::getValue('qty_available');

        if (!$name || !Validate::isGenericName($name)) {
            $this->_errors[] = $this->l('Invalid Name');
        }
        else {
            $result = Db::getInstance()->insert(
                        $this->module->table_parts,
                        [
                            'name' => pSQL(trim($name)),
                            'description' => pSQL(trim($desc)),
                            'manufacturer' => pSQL(trim($manufacturer)),
                            'manufacturer_part_number' => pSQL(trim($manPartNum)),
                            'datasheet_link' => pSQL(trim($datasheet)),
                            'supplier' => pSQL(trim($supplier)),
                            'supplier_part_number' => pSQL(trim($supPartNum)),
                            'supplier_link' => pSQL(trim($supLink)),
                            'qty_available' => $qty
                        ],
                    );

            if (!$result) {
                $this->_errors[] = $this->l('Error while adding Part');
            }
            else {
                $id_part = Db::getInstance()->Insert_ID();
            }
        }

        if (empty($this->_errors)) {
            if ($saveAndStay) {
                $this->redirect_after = static::$currentIndex . '&configure=parts&id='. $id_part . '&update'.$this->table.'&token=' . $this->token;
            }
            else {
                $this->redirect_after = static::$currentIndex.'&conf=4&token='.$this->token;
            }
        }
    }

    /**
     * Updates a part
     */
    private function processUpdatePart($id_part)
    {
        $saveAndStay = $this->isSaveAndStay('submitEditPart');

        $name = Tools::getValue('name');
        $desc = Tools::getValue('description');
        $keywords = Tools::getValue('keywords');
        $manufacturer = Tools::getValue('manufacturer');
        $manPartNum = Tools::getValue('manufacturer_part_number');
        $datasheet = Tools::getValue('datasheet_link');
        $supplier = Tools::getValue('supplier');
        $supPartNum = Tools::getValue('supplier_part_number');
        $supLink = Tools::getValue('supplier_link');
        $qty = Tools::getValue('qty_available');

        if (!$name || !Validate::isGenericName($name)) {
            $this->_errors[] = $this->l('Invalid Name');
        }
        else {
            $result = Db::getInstance()->update(
                        $this->module->table_parts,
                        [
                            'name' => pSQL(trim($name)),
                            'description' => pSQL(trim($desc)),
                            'manufacturer' => pSQL(trim($manufacturer)),
                            'manufacturer_part_number' => pSQL(trim($manPartNum)),
                            'datasheet_link' => pSQL(trim($datasheet)),
                            'supplier' => pSQL(trim($supplier)),
                            'supplier_part_number' => pSQL(trim($supPartNum)),
                            'supplier_link' => pSQL(trim($supLink)),
                            'qty_available' => $qty,
                        ],
                        'id_part ='.$id_part
                    );

            if (!$result) {
                $this->_errors[] = $this->l('Error while updating Part');
            }
        }

        if (empty($this->_errors)) {
            if ($saveAndStay) {
                $this->redirect_after = static::$currentIndex . '&configure=parts&id='. $id_part . '&update'.$this->table.'&token=' . $this->token;
            }
            else {
                $this->redirect_after = static::$currentIndex.'&conf=4&token='.$this->token;
            }
        }
    }



    /**
     * Process bulk items
     */
    private function processBulkUpdate($bulkList)
    {

    }


    /**
     * Shows button link to view the supplier page for a part
     */
    public function renderLinkOnHelperList($link) {
        return '<span class="btn-group-action">
            <span class="btn-group">
                <a class="btn btn-default" href="'.$link.'" target="_blank"><i class="icon-search-plus"></i>&nbsp;'.$this->l('Part Page').'
                </a>
            </span>
        </span>';
    }

    public function renderDatasheetLinkOnHelperList($link) {
        return '<span class="btn-group-action">
            <span class="btn-group">
                <a class="btn btn-default" href="'.$link.'" target="_blank"><i class="icon-search-plus"></i>&nbsp;'.$this->l('Datasheet').'
                </a>
            </span>
        </span>';
    }


    private function isSubmit($action) {
        return Tools::isSubmit($action) || $this->isSaveAndStay($action);
    }

    private function isSaveAndStay($action) {
        return Tools::isSubmit($action.'AndStay');
    }

    private function hasValue($v) {
        return Tools::getValue($v, 'bob') != 'bob';
    }


    private function isEmptyDate($d) {
        return empty($d) || $d == '0000-00-00';
    }
}