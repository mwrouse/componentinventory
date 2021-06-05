<?php

if ( ! defined('_TB_VERSION_')) {
    exit;
}

class AdminComponentInventoryDashboardController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->show_toolbar = true;
        $this->identifier = 'id';
        $this->table = 'componentinventory';
        $this->className = 'componentinventory';

        parent::__construct();
    }


    /**
     * Tool Bar Buttons
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display) || $this->display =='list') {
            $this->page_header_toolbar_btn['view_inv'] = [
                'href' => 'index.php?controller=AdminComponentInventoryInventory&token='.Tools::getAdminTokenLite('AdminComponentInventoryInventory'),
                'desc' => $this->l('View Inventory', null, null, false),
                'icon' => 'process-icon-view',
            ];

            $this->page_header_toolbar_btn['new_part'] = [
                'href' => 'index.php?controller=AdminComponentInventoryInventory&configure=parts&id=new&update'.$this->module->table_parts.'&token='.Tools::getAdminTokenLite('AdminComponentInventoryInventory'),
                'desc' => $this->l('New Part', null, null, false),
                'icon' => 'process-icon-new',
            ];

            $this->page_header_toolbar_btn['view_po'] = [
                'href' => 'index.php?controller=AdminComponentInventoryPurchaseOrders&token='.Tools::getAdminTokenLite('AdminComponentInventoryPurchaseOrders'),
                'desc' => $this->l('View P.O.s', null, null, false),
                'icon' => 'process-icon-view',
            ];

            $this->page_header_toolbar_btn['new_po'] = [
                'href' => 'index.php?controller=AdminComponentInventoryPurchaseOrders&configure=po&id=new&update'.$this->module->table_po.'&token='.Tools::getAdminTokenLite('AdminComponentInventoryPurchaseOrders'),
                'desc' => $this->l('New P.O.', null, null, false),
                'icon' => 'process-icon-cart',
            ];
        }


        parent::initPageHeaderToolbar();
    }


    public function renderList()
    {
        $this->context->smarty->assign([
            'ytd' => $this->module->yearToDatePurchaseOrderTotal(),
        ]);

        $content = $this->context->smarty->fetch(_PS_MODULE_DIR_ .'componentinventory/views/admin/po_summary.tpl');
        $content .= $this->_getActivePurchaseOrdersList();
        $content .= $this->_getLowOnInventoryList();

        return $content;
    }



    private function _getActivePurchaseOrdersList()
    {
        // Active Purchase Orders
        $purchaseOrders = $this->module->getAllActivePurchaseOrders();
        $fieldList = [
            'name' => [
                'title' => 'Name',
            ],
            'date_ordered' => [
                'title' => 'Ordered'
            ],
            'total' => [
                'title' => 'Total',
                'align'   => 'center',
                'class'   => 'fixed-width-xs',
                'type' => 'price',
                'currency' => true,
            ],
        ];

        $helper = new HelperList();
        $helper->no_link = true;
        $helper->shopLinkType = '';
        $helper->simple_header = true;
        $helper->actions = ["edit"];
        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->listTotal = count($purchaseOrders);
        $helper->identifier = 'id';
        $helper->position_identifier = 'id';
        $helper->title = "Active Purchase Orders";
        $helper->orderBy = 'date_ordered';
        $helper->orderWay = 'ASC';
        $helper->table = $this->module->table_po;
        $helper->token = Tools::getAdminTokenLite('AdminComponentInventoryPurchaseOrders');
        $helper->currentIndex = 'index.php?controller=AdminComponentInventoryPurchaseOrders&configure=po';
        $helper->force_show_bulk_actions = true;
        $helper->bulk_actions = [
            'markAsReceived' => [
                'confirm' => 'Are you sure you want to mark these Purchase Orders as Received',
                'text' => 'Mark as Received'
            ]
        ];

        return $helper->generateList($purchaseOrders, $fieldList);
    }


    private function _getLowOnInventoryList()
    {
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
        $helper->table = $this->module->table_parts;
        $helper->force_show_bulk_actions = true;
        $helper->bulk_actions = [
            'createPO' => [
                'confirm' => 'Are you sure you want to add these parts to a new Purchase Order?',
                'text' => 'Create Purchase Order'
            ]
        ];
        $helper->token = Tools::getAdminTokenLite('AdminComponentInventoryInventory');
        $helper->currentIndex = 'index.php?controller=AdminComponentInventoryInventory&configure=parts';

        return $helper->generateList($lowOnInventory, $fieldList);
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

}