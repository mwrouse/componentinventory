<?php

if ( ! defined('_TB_VERSION_')) {
    exit;
}

class AdminComponentInventoryPurchaseOrdersController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->show_toolbar = true;
        $this->identifier = 'id';
        $this->table = 'componentinventory_purchase_orders';
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

        $this->page_header_toolbar_btn['view_inv'] = [
            'href' => 'index.php?controller=AdminComponentInventoryInventory&token='.Tools::getAdminTokenLite('AdminComponentInventoryInventory'),
            'desc' => $this->l('View Inventory', null, null, false),
            'icon' => 'process-icon-view',
        ];

        if (empty($this->display) || $this->display =='list') {

            $this->page_header_toolbar_btn['new_po'] = [
                'href' => static::$currentIndex.'&configure=po&id=new&update'.$this->table.'&token='.$this->token,
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
        $helper->table = $this->table;
        $helper->token = $this->token;
        $helper->currentIndex = AdminController::$currentIndex.'&configure=po';
        $helper->force_show_bulk_actions = true;
        $helper->bulk_actions = [
            'markAsReceived' => [
                'confirm' => 'Are you sure you want to mark these Purchase Orders as Received',
                'text' => 'Mark as Received'
            ]
        ];
        $content .= $helper->generateList($purchaseOrders, $fieldList);

        // Previous Purchase Orders
        $orderHistory = $this->module->getPreviousPurchaseOrders();
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
        $helper->listTotal = count($orderHistory);
        $helper->identifier = 'id';
        $helper->position_identifier = 'id';
        $helper->title = "Order History";
        $helper->orderBy = 'date_ordered';
        $helper->orderWay = 'ASC';
        $helper->table = $this->table;
        $helper->token = $this->token;
        $helper->currentIndex = AdminController::$currentIndex.'&configure=po';
        $helper->force_show_bulk_actions = true;
        $helper->bulk_actions = [
            'markAsNotReceived' => [
                'confirm' => 'Are you sure you want to un-receive this purchase order?',
                'text' => 'Un-Receive'
            ]
        ];
        $content .= $helper->generateList($orderHistory, $fieldList);

        return $content;
    }


    /**
     * Renders a form
     */
    public function renderForm()
    {
        $id_po = Tools::getValue('id', null);

        $po = $this->module->getPurchaseOrder($id_po);
        $this->fields_value = $po;

        $inputs[] = [
            'type' => 'hidden',
            'name' => 'id_po'
        ];

        $inputs[] = [
            'type' => 'text',
            'label' => $this->l("Nickname"),
            'name' => 'name'
        ];

        $inputs[] = [
            'type' => 'text',
            'label' => $this->l("Order Number"),
            'name' => 'order_number'
        ];

        $inputs[] = [
            'type' => 'text',
            'label' => $this->l("Tracking Number"),
            'name' => 'tracking_number'
        ];

        $inputs[] = [
            'type' => 'date',
            'label' => 'Order Date',
            'name' => 'date_ordered',
        ];

        $inputs[] = [
            'type' => 'date',
            'label' => 'Received Date',
            'name' => 'date_received',
            'hint' => 'Leave blank if not received yet'
        ];

        $inputs[] = [
            'type' => 'html',
            'label' => 'Components',
            'name' => 'part_list',
            'html_content' => $this->getInputTypeForPartsInPurchaseOrder($po),
        ];

        $inputs[] = [
            'type' => 'html',
            'label' => 'Expenses',
            'name' => 'expenses',
            'hint' => 'Expenses do not show up in inventory',
            'html_content' => $this->getInputTypeForExpensesInPurchaseOrder($po),
        ];

        $inputs[] = [
            'type' => 'html',
            'label' => 'Tax',
            'name' => 'tax',
            'html_content' => '<input type="number" name="tax" value="'.$po['tax'].'" style="margin-top: 5px" step="0.01" min="0.0" id="order-tax" onchange="updateOrderTotal()">'
        ];

        $inputs[] = [
            'type' => 'html',
            'label' => 'Shipping',
            'name' => 'shipping',
            'html_content' => '<input type="number" name="shipping" value="'.$po['shipping'].'" style="margin-top: 5px" step="0.01" min="0.0" id="order-shipping" onchange="updateOrderTotal()">'
        ];

        $inputs[] = [
            'type' => 'html',
            'label' => 'Order Total',
            'name' => 'total',
            'html_content' => '<div id="order-total" style="margin-top: 2px; font-size: 20px">$0.00</div><input type="hidden" id="order-total-input" name="order-total" value="'.$po['total'].'">'
        ];



        $action = 'submitEditPO';

        $this->fields_form = [
            'legend' => [
                'title' => (($id_po == 'new') ? 'New' : 'Edit') . ' Purchase Order',
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
     * Generates a specific input type for selecting parts
     */
    private function getInputTypeForPartsInPurchaseOrder($po)
    {
        $allParts = $this->module->getAllParts(true);

        $selectTag = '<select name="parts[]">';
        $options = "";
        foreach ($allParts as $part) {
            $options .= '<option value="'.$part['id_part'].'">'.$part['name'].'</option>';
        }
        $selectTag .= $options.'</select>';

        $selectTagStr = str_replace('"', '\\"', $selectTag);

        $this->context->smarty->assign([
            'po' => $po,
            'parts' => $allParts,
            'partOptions' => $options,
            'partsDropdown' => $selectTag,
            'partsDropdownStr' => $selectTagStr,
        ]);

        return $this->context->smarty->fetch(_PS_MODULE_DIR_ .'componentinventory/views/admin/po_parts_list.tpl');
    }


    /**
     * Returns input field for expenses
     */
    private function getInputTypeForExpensesInPurchaseOrder($po)
    {
        $this->context->smarty->assign([
            'po' => $po,
        ]);

        return $this->context->smarty->fetch(_PS_MODULE_DIR_ .'componentinventory/views/admin/po_expense_list.tpl');
    }



    /**
     * When something is submitted
     */
    public function postProcess()
    {
        $submitPO = $this->isSubmit('submitEditPO');

        if ($submitPO)
        {
            $id = Tools::getValue('id_po');

            if ($id == 'new') {
                $this->processAddPO();
            }
            else {
                $this->processUpdatePO($id);
            }

            // Mark the order as shipped if coming from the order page
            $markAsShipped = Tools::getValue('ci_markAsShipped', '0') != '0';
            if ($markAsShipped) {
                $orderId = Tools::getValue('order_id');
                $trackingNumber = Tools::getValue('tracking');

                $order = new Order($orderId);
                $order->setCurrentState(3 /* Shipped */, 1 /* Default Employee */);
            }
        }
        else {
            $bulkParts = Tools::getValue('componentinventory_purchase_ordersBox', null);
            if (!is_null($bulkParts)) {
                $this->processBulkUpdate($bulkParts);
            }
        }
    }



    /**
     * Adds a new Purchase Order
     */
    private function processAddPO()
    {
        $saveAndStay = $this->isSaveAndStay('submitEditPO');

        $name = Tools::getValue('name');
        $order_number = Tools::getValue('order_number');
        $tracking_number = Tools::getValue('tracking_number');
        $order_date = Tools::getValue('date_ordered');
        $received_date = '0000-00-00';
        $received_dateReal = Tools::getValue('date_received', null);
        $tax = Tools::getValue('tax');
        $shipping = Tools::getValue('shipping');
        $total = Tools::getValue('order-total');

        if (!$name || !Validate::isGenericName($name)) {
            $this->_errors[] = $this->l('Invalid Name');
        }
        else {
            $result = Db::getInstance()->insert(
                    $this->module->table_po,
                    [
                        'name' => pSQL(trim($name)),
                        'order_number' => pSQL(trim($order_number)),
                        'tracking_number' => pSQL(trim($tracking_number)),
                        'date_ordered' => $order_date,
                        'date_received' => $received_date,
                        'tax' => $tax,
                        'shipping' => $shipping,
                        'total' => $total,
                    ]
                );

            if (!$result) {
                $this->_errors[] = $this->l('Error while adding Purchase Order');
            }
            else {
                $id_po = Db::getInstance()->Insert_ID();

                $this->postProcessSaveComponentList($id_po, $received_date);
                $this->postProcessSaveExpensesList($id_po, $received_date);
            }
        }

        if ($received_date != $received_dateReal && !$this->isEmptyDate($received_dateReal)) {
            $this->module->receivePurchaseOrder($id_po, $received_dateReal);
        }

        if (empty($this->_errors)) {
            if (Tools::getValue('ajax', false) == true)
            {
                echo 'done';
                return;
            }

            if ($saveAndStay) {
                $this->redirect_after = static::$currentIndex . '&configure=po&id='. $id_po . '&update'.$this->table.'&token=' . $this->token;
            }
            else {
                $this->redirect_after = static::$currentIndex.'&conf=4&token='.$this->token;
            }
        }
    }


    /**
     * Updates a Purchase Order
     */
    private function processUpdatePO($id_po)
    {
        $oldPO = $this->module->getPurchaseOrder($id_po);
        $saveAndStay = $this->isSaveAndStay('submitEditPO');

        $name = Tools::getValue('name');
        $order_number = Tools::getValue('order_number');
        $tracking_number = Tools::getValue('tracking_number');
        $order_date = Tools::getValue('date_ordered');
        $received_date = Tools::getValue('date_received', null);
        $tax = Tools::getValue('tax');
        $shipping = Tools::getValue('shipping');
        $total = Tools::getValue('order-total');

        if (!$name || !Validate::isGenericName($name)) {
            $this->_errors[] = $this->l('Invalid Name');
        }
        else {
            $result = Db::getInstance()->update(
                    $this->module->table_po,
                    [
                        'name' => pSQL(trim($name)),
                        'order_number' => pSQL(trim($order_number)),
                        'tracking_number' => pSQL(trim($tracking_number)),
                        'date_ordered' => $order_date,
                        'date_received' => $received_date,
                        'tax' => $tax,
                        'shipping' => $shipping,
                        'total' => $total,
                    ],
                    'id_po='.$id_po
                );

            if (!$result) {
                $this->_errors[] = $this->l('Error while adding Purchase Order');
            }
            else {
                $this->postProcessSaveComponentList($id_po, $received_date);
                $this->postProcessSaveExpensesList($id_po, $received_date);

                // Receive it if not received
                if (!$this->isEmptyDate($received_date) && $this->isEmptyDate($oldPO['received_date']))
                {
                    $this->module->receivePurchaseOrder($id_po, $received_date);
                }

                // The UI shouldn't allow this, but just-in-case
                if ($this->isEmptyDate($received_date) && !$this->isEmptyDate($oldPO['received_date'])) {
                    //$this->module->unreceivePurchaseOrder($id_po);
                }
            }
        }

        if (empty($this->_errors)) {
            if ($saveAndStay) {
                $this->redirect_after = static::$currentIndex . '&configure=po&id='. $id_po . '&update'.$this->table.'&token=' . $this->token;
            }
            else {
                $this->redirect_after = static::$currentIndex.'&conf=4&token='.$this->token;
            }
        }
    }


    /**
     * Save the list of components for a PO
     */
    private function postProcessSaveComponentList($id_po, $received_date)
    {
        Db::getInstance()->delete($this->module->table_po_parts, 'id_po='.$id_po);

        // Save list of parts
        $partsList = Tools::getValue('parts');
        if (!is_array($partsList))
            $partsList = [$partsList];

        $quantities = Tools::getValue('qty');
        if (!is_array($quantities))
            $quantities = [$quantities];

        $prices = Tools::getValue('price');
        if (!is_array($prices))
            $prices = [$prices];

        $tariffs = Tools::getValue('tariffs');
        if (!is_array($tariffs))
            $tariffs = [$tariffs];

        $max = count($partsList);
        if (count($quantities) < $max)
            $max = count($quantities);

        if (count($partsList) == 0 || count($quantities) == 0 || count($prices) == 0 || count($tariffs) == 0 || $max == 0)
            return;

        for ($i = 0; $i < $max; $i++) {
            if ($quantities[$i] == 0)
                continue;

            Db::getInstance()->insert(
                $this->module->table_po_parts,
                [
                    'id_po' => $id_po,
                    'id_part' => $partsList[$i],
                    'qty' => $quantities[$i],
                    'total' => $prices[$i],
                    'tariff' => $tariffs[$i],
                    'received' => !$this->isEmptyDate($received_date),
                ]
            );
        }
    }

    /**
     * Save the list of expenses for a PO
     */
    private function postProcessSaveExpensesList($id_po, $received_date)
    {
        Db::getInstance()->delete($this->module->table_po_expenses, 'id_po='.$id_po);

        // Save list of parts
        $expenseList = Tools::getValue('expense');
        if (!is_array($expenseList))
            $expenseList = [$expenseList];

        $quantities = Tools::getValue('expense_qty');
        if (!is_array($quantities))
            $quantities = [$quantities];

        $prices = Tools::getValue('expense_price');
        if (!is_array($prices))
            $prices = [$prices];

        $max = count($expenseList);
        if (count($quantities) < $max)
            $max = count($quantities);

        if (count($expenseList) == 0 || count($quantities) == 0 || count($prices) == 0 || $max == 0)
            return;

        for ($i = 0; $i < $max; $i++) {
            if ($quantities[$i] == 0)
                continue;
            Db::getInstance()->insert(
                $this->module->table_po_expenses,
                [
                    'id_po' => $id_po,
                    'name' => $expenseList[$i],
                    'qty' => $quantities[$i],
                    'total' => $prices[$i],
                ]
            );
        }
    }


    /**
     * Process bulk items
     */
    private function processBulkUpdate($bulkList)
    {
        if ($this->hasValue('submitBulkmarkAsReceivedcomponentinventory_purchase_orders')) {
            foreach ($bulkList as $po) {
                $this->module->receivePurchaseOrder($po, date('Y-m-d'));
            }
        }
        else if ($this->hasValue('submitBulkmarkAsNotReceivedcomponentinventory_purchase_orders')) {
            foreach ($bulkList as $po) {
                $this->module->unreceivePurchaseOrder($po);
            }
        }
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