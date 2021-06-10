<?php

if (!defined('_TB_VERSION_')) {
    exit;
}


/**
 * Module for Component Inventory
 */
class ComponentInventory extends Module
{
    protected $hooksList = [];


    public function __construct()
    {
        $this->name = 'componentinventory';
        $this->className = 'ComponentInventory';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Michael Rouse';
        $this->tb_min_version = '1.0.0';
        $this->tb_versions_compliancy = '> 1.0.0';
        $this->need_instance = 0;
        $this->table_name = 'componentinventory';
        $this->table_parts = $this->table_name . '_parts'; /* Keeps track of parts, where you get them, and quantity*/
        $this->table_product_parts = $this->table_name .'_product_parts'; /* Table for keeping track of  how many parts are in a certain product */
        $this->table_po = $this->table_name . '_purchase_orders'; /* Keeps track of purchase orders */
        $this->table_po_parts = $this->table_po . '_parts'; /* All parts in a PO */
        $this->table_po_expenses = $this->table_po . '_expenses';
        $this->bootstrap = true;

        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => '1.6.99.99'];

        // List of hooks
        $this->hooksList = [
            'displayBackOfficeHeader',
            'displayAdminProductsExtra',
            'actionProductUpdate',
            'actionOrderStatusPostUpdate',
        ];

        parent::__construct();

        $this->displayName = $this->l('Component Inventory');
        $this->description = $this->l('Keep track of component/part inventory');
    }



    /**
     * Add CSS/JS to the back office
     */
    public function hookDisplayBackOfficeHeader()
    {
        $this->context->controller->addCSS($this->_path . 'css/componentinventory.css', 'all');
    }

    /**
     * Display tab on the product page
     */
    public function hookDisplayAdminProductsExtra()
    {
        $productId = Tools::getValue('id_product');
        if (!isset($productId))
            return "Could not load Product ID from Tools::getValue('id_product')";

        $allParts = $this->getAllParts(true);

        $parts = $this->getPartsForProduct($productId);
        $this->context->smarty->assign([
            'parts' => $parts,
            'allParts' => $allParts,
        ]);


        $selectTag = '<select name="parts[]">';
        $options = "";
        foreach ($allParts as $part) {
            $options .= '<option value="'.$part['id_part'].'">'.$part['name'].'</option>';
        }
        $selectTag .= $options.'</select>';

        $selectTagStr = str_replace('"', '\\"', $selectTag);

        $this->context->smarty->assign([
            'parts' => $parts,
            'allParts' => $allParts,
            'partOptions' => $options,
            'partsDropdown' => $selectTag,
            'partsDropdownStr' => $selectTagStr,
        ]);

        return $this->display(__FILE__, 'views/admin/hooks/displayAdminProductsExtra.tpl');
    }

    /**
     * When the product is saved in the back office
     */
    public function hookActionProductUpdate()
    {
        if (Tools::isSubmit('submitAddproduct') || Tools::isSubmit('submitAddproductAndStay'))
        {
            $productId = Tools::getValue('id_product');

            Db::getInstance()->delete($this->table_product_parts, 'id_product='.$productId);

            // Save list of parts
            $partsList = Tools::getValue('parts');
            if (!is_array($partsList))
                $partsList = [$partsList];

            $quantities = Tools::getValue('qty');
            if (!is_array($quantities))
                $quantities = [$quantities];

            $max = count($partsList);
            if (count($quantities) < $max)
                $max = count($quantities);

            $parts = [];
            for ($i = 0; $i < $max; $i++) {
                Db::getInstance()->insert(
                    $this->table_product_parts,
                    [
                        'id_product' => $productId,
                        'id_part' => $partsList[$i],
                        'qty' => $quantities[$i],
                    ]
                );
            }
        }
    }

    /**
     * When an order is updated
     */
    public function hookActionOrderStatusPostUpdate($params)
    {
        $state = $params['newOrderStatus']->id;
        $id_order = $params['id_order'];
        $order = new Order($id_order);

        if ($state == 5) {
            // Re-add quantities
            $products = $order->getProducts();
            foreach($products as $product) {
                $partsForProduct = $this->getPartsForProduct($product['product_id']);
                if (count($partsForProduct) == 0)
                    continue;

                $qty = $product['product_quantity']; // Qty of the parts in the order

                $this->increaseQuantitiesForParts($partsForProduct, $qty);
            }
            $this->markOrderAsHandeled($id_order, false);
            return;
        }

        $hasOrderBeenHandeled = $this->hasOrderBeenHandeled($id_order);
        if($hasOrderBeenHandeled) {
            return;
        }

        // Update all products
        $products = $order->getProducts();
        foreach($products as $product) {
            $partsForProduct = $this->getPartsForProduct($product['product_id']);
            if (count($partsForProduct) == 0)
                continue;

            $qty = $product['product_quantity']; // Qty of the parts in the order

            $this->reduceQuantitiesForParts($partsForProduct, $qty);
        }

        $this->markOrderAsHandeled($id_order);
    }



    /**********************************
     *       Database Interface       *
     **********************************/

    /**
     * Returns all products low on inventory
     */
    public function getAllPartsLowOnInventory()
    {
        $qry = (new DbQuery())
                ->select('t1.*, t1.`id_part` as `id`, SUM(t2.`qty`) AS qty_on_order')
                ->from($this->table_parts, 't1')
                ->leftOuterJoin($this->table_po_parts, 't2', 't2.`id_part`=t1.`id_part` AND t2.`received`=0')
                ->groupBy('t1.`id_part`')
                ->orderBy('t1.`qty_available` ASC')
                ->where('t1.`qty_available` < 20'); /* TODO: Extract out into configuration */

        $result = Db::getInstance()->ExecuteS($qry);

        if (!is_array($result))
            return [];

        return $result;
    }


    /**
     * Returns all of the parts
     */
    public function getAllParts($alphabetical = false, $noSort = false)
    {
        $qry = (new DbQuery())
                ->select('t1.*, t1.`id_part` as `id`, SUM(t2.`qty`) AS qty_on_order')
                ->from($this->table_parts, 't1')
                ->leftOuterJoin($this->table_po_parts, 't2', 't2.`id_part`=t1.`id_part` AND t2.`received`=0')
                ->groupBy('t1.`id_part`');

        if (!$noSort)
        {
            if (!$alphabetical)
                $qry = $qry->orderBy('t1.`qty_available` ASC');
            else
                $qry = $qry->orderBy('t1.`name` ASC');
        }

        $result = Db::getInstance()->ExecuteS($qry);

        if (!is_array($result))
            return [];

        return $result;
    }


    /**
     * Gets all the parts for a product
     */
    public function getPartsForProduct($id_product)
    {
        $qry = (new DbQuery())
                ->select('t1.*, t2.qty')
                ->from($this->table_parts, 't1')
                ->leftJoin($this->table_product_parts, 't2', 't1.`id_part`=t2.`id_part`')
                ->where('t2.id_product='.$id_product);

        $result = Db::getInstance()->ExecuteS($qry);
        if (!$result)
            return [];

        return $result;
    }


    /**
     * Returns a single part
     */
    public function getPart($id_part)
    {
        if ($id_part == 'new') {
            return [
                'id_part' => 'new',
                'name' => '',
                'description' => '',
                'keywords' => '',
                'manufacturer' => '',
                'manufacturer_part_number' => '',
                'datasheet_link' => '',
                'supplier' => 'DigiKey',
                'supplier_part_number' => '',
                'supplier_link' => '',
                'qty_available' => 0,
            ];
        }
        $qry = (new DbQuery())
                ->select('*, `id_part` as `id`')
                ->from($this->table_parts)
                ->where('`id_part`='.$id_part.'');

        $result = Db::getInstance()->ExecuteS($qry);

        if (!$result)
            return null;

        return $result[0];
    }

    /**
     * Sets the new quantity
     */
    public function updateQuantities($id_part, $newQuantity)
    {
        DB::getInstance()->update(
            $this->table_parts,
            [
                'qty_available' => $newQuantity
            ],
            'id_part='.$id_part
        );
    }


    /**
     * Returns all current Purchase Orders
     */
    public function getAllActivePurchaseOrders()
    {
        $qry = (new DbQuery())
                ->select('*, `id_po` as `id`')
                ->from($this->table_po)
                ->orderBy('date_ordered ASC')
                ->where('date_received="0000-00-00"');

        $result = Db::getInstance()->ExecuteS($qry);

        if (!is_array($result))
            return [];

        return $result;
    }


    /**
     * Returns last x purchase orders
     */
    public function getPreviousPurchaseOrders()
    {
        $qry = (new DbQuery())
                ->select('*, `id_po` as `id`')
                ->from($this->table_po)
                ->where('date_received > 0000-00-00')
                ->orderBy('date_ordered DESC');

        $result = Db::getInstance()->ExecuteS($qry);

        if (!is_array($result))
            return [];

        return $result;
    }


    /**
     * Returns the entire purchase order
     */
    public function getPurchaseOrder($id_po)
    {
        if ($id_po == 'new') {
            return [
                'id_po' => 'new',
                'name' => '',
                'date_ordered' => date('Y-m-d'),
                'date_received' => '',
                'tax' => 0.0,
                'shipping' => 0.0,
                'part_list' => [],
                'expenses' => [],
                'total' => 0.0,
            ];
        }

        $qry = (new DbQuery())
                ->select('*')
                ->from($this->table_po)
                ->where('`id_po`='.$id_po);

        $result = Db::getInstance()->ExecuteS($qry);
        if (!$result)
            return null;

        $order = $result[0];
        $order['part_list'] = $this->getComponentsForPurchaseOrder($id_po);
        $order['expenses'] = $this->getExpensesForPurchaseOrder($id_po);

        return $order;
    }


    /**
     * Returns all of the components in a purchase order
     */
    private function getComponentsForPurchaseOrder($id_po)
    {
        $qry = (new DbQuery())
                ->select('*')
                ->from($this->table_po_parts)
                ->where('id_po='.$id_po);

        $result = Db::getInstance()->ExecuteS($qry);
        if (!$result)
            return [];

        return $result;
    }


    /**
     * Returns all the expenses for a purchase order
     */
    private function getExpensesForPurchaseOrder($id_po)
    {
        $qry = (new DbQuery())
                ->select('*')
                ->from($this->table_po_expenses)
                ->where('id_po='.$id_po);

        $result = Db::getInstance()->ExecuteS($qry);
        if (!$result)
            return [];

        return $result;
    }


    /**
     * Marks a Purchase Order as Received
     */
    public function receivePurchaseOrder($id_po, $date)
    {
        $po = $this->getPurchaseOrder($id_po);
        if ($po['date_received'] != '0000-00-00' && !empty($po['date_received']) && !is_null($po['date_received']))
            return;

        Db::getInstance()->update(
            $this->table_po,
            [
                'date_received' => $date
            ],
            'id_po='.$id_po
        );

        Db::getInstance()->update(
            $this->table_po_parts,
            [
                'received' => true
            ],
            'id_po='.$id_po
        );

        // Update Quantities
        $po = $this->getPurchaseOrder($id_po);
        foreach ($po['part_list'] as $ordered) {
            $p = $this->getPart($ordered['id_part']);
            Db::getInstance()->update(
                $this->table_parts,
                [
                    'qty_available' => $p['qty_available'] + $ordered['qty']
                ],
                'id_part='.$p['id_part']
            );
        }

    }

    /**
     * Unreceives a purchase order
     */
    public function unreceivePurchaseOrder($id_po)
    {
        $po = $this->getPurchaseOrder($id_po);
        if ($po['date_received'] == '0000-00-00' || empty($po['date_received']) || is_null($po['date_received']))
            return;

        Db::getInstance()->update(
            $this->table_po,
            [
                'date_received' => '0000-00-00'
            ],
            'id_po='.$id_po
        );

        Db::getInstance()->update(
            $this->table_po_parts,
            [
                'received' => false
            ],
            'id_po='.$id_po
        );

        // Update Quantities
        $po = $this->getPurchaseOrder($id_po);
        foreach ($po['part_list'] as $ordered) {
            $p = $this->getPart($ordered['id_part']);
            Db::getInstance()->update(
                $this->table_parts,
                [
                    'qty_available' => $p['qty_available'] - $ordered['qty']
                ],
                'id_part='.$p['id_part']
            );
        }
    }


    /**
     * Checks if an order has been handeled
     */
    public function hasOrderBeenHandeled($id_order)
    {
        $qry = (new DbQuery())
                    ->select('*')
                    ->from($this->table_name)
                    ->where('id_order='.intval($id_order));
        $result = Db::getInstance()->ExecuteS($qry);
        if (!$result) {
            return false;
        }

        if (is_array($result))
            return $result[0]['handeled'];

        return $result['handeled'];
    }


    /**
     * Mark an order as handeled
     */
    public function markOrderAsHandeled($id_order, $handeled = true)
    {
        Db::getInstance()->delete($this->table_name, 'id_order='.$id_order);

        Db::getInstance()->insert($this->table_name,
            [
                'id_order' => intval($id_order),
                'handeled' => $handeled
            ]
        );
    }


    /**
     * Reduces quantities for a part
     */
    public function reduceQuantitiesForParts($partsList, $qty)
    {
        foreach ($partsList as $part)
        {
            $amountInProduct = $part['qty'];
            $currentAvailable = $part['qty_available'];

            $newQuantity = $currentAvailable - ($amountInProduct * $qty);

            $this->updateQuantities($part['id_part'], $newQuantity);
        }
    }

    /**
     * Increases quantities for a part (when order is changed to cancelled)
     */
    public function increaseQuantitiesForParts($partsList, $qty)
    {
        foreach ($partsList as $part)
        {
            $amountInProduct = $part['qty'];
            $currentAvailable = $part['qty_available'];

            $newQuantity = $currentAvailable + ($amountInProduct * $qty);

            $this->updateQuantities($part['id_part'], $newQuantity);
        }
    }


    /**
     * Returns P.O. Totals for the year to date
     */
    public function yearToDatePurchaseOrderTotal()
    {
        $qry = (new DbQuery())
                ->select('SUM(total) as total')
                ->from($this->table_po)
                ->where('date_ordered >= '.date('Y').'-01-01');

        $result = Db::getInstance()->ExecuteS($qry);
        if (!$result)
            return 0.0;

        return floatval($result[0]['total']);
    }



    /****************************************
     *       Helper/Utility Functions       *
     ****************************************/


    /**
     * Returns how much has been made in orders this year
     */
    public function yearToDateOrderTotal($paypalFees = true)
    {
        $qry = (new DbQuery())
                ->select('id_order, current_state, total_paid_tax_incl as total')
                ->from('pp_orders')
                ->where('date_add >= '.date('Y').'-01-01');

        $result = Db::getInstance()->ExecuteS($qry);
        if (!$result)
            return 0.0;

        $total = 0.0;
        foreach ($result as $order) {
            if ($order['total'] <= 1 || $order['current_state'] == 5)
                continue;

            $o = new Order($order['id_order']);
            if ($o->hasBeenPaid())
            {
                $orderTotal = (!$paypalFees) ? $order['total'] : (($order['total'] - 0.30) - ($order['total'] * 0.029));
                $total += $orderTotal;
            }
        }
        return $total;
    }



    /**************************
     *    Install/Uninstall   *
     **************************/
    public function install()
    {
        if ( ! parent::install()
            || ! $this->_createTabs()
            || ! $this->_createDatabases()
        ) {
            return false;
        }

        foreach ($this->hooksList as $hook) {
            if ( ! $this->registerHook($hook)) {
                return false;
            }
        }

        return true;
    }


    public function uninstall()
    {
        if ( ! parent::uninstall()
            || ! $this->_eraseDatabases()
            || ! $this->_eraseTabs()
        ) {
            return false;
        }

        return true;
    }

    /**
     * Create tabs on the admin page
     */
    private function _createTabs()
    {
        $name = 'Admin'.$this->className;
        /* This is the main tab, all others will be children of this */
        $allLangs = Language::getLanguages();
        $parentTab = $this->_createSingleTab(0, $name, $this->displayName, $allLangs);

        $dashboardTab = $this->_createSingleTab($parentTab->id, $name.'Dashboard', 'Dashboard', $allLangs);
        $inventoryTab = $this->_createSingleTab($parentTab->id, $name.'Inventory', 'Inventory', $allLangs);
        //$partsTab = $this->_createSingleTab($parentTab->id, $name.'Parts', 'Parts', $allLangs);
        $poTab = $this->_createSingleTab($parentTab->id, $name.'PurchaseOrders', 'Purchase Orders', $allLangs);

        return true;
    }

    /**
     * Creates a single tab
     */
    private function _createSingleTab($idParent, $class, $name, $allLangs)
    {
        $tab = new Tab();
        $tab->active = 1;

        foreach ($allLangs as $language) {
            $tab->name[$language['id_lang']] = $name;
        }

        $tab->class_name = $class;
        $tab->module = $this->name;
        $tab->id_parent = $idParent;

        if ($tab->add()) {
            return $tab;
        }

        return null;
    }

    /**
     * Get rid of all installed back office tabs
     */
    private function _eraseTabs()
    {
        $idTabm = (int)Tab::getIdFromClassName('Admin'.ucfirst($this->name));
        if ($idTabm) {
            $tabm = new Tab($idTabm);
            $tabm->delete();
        }

        foreach ($this->_tabs as $class => $name) {
            $idTab = (int)Tab::getIdFromClassName($class);
            if ($idTab) {
                $tab = new Tab($idTab);
                $tab->delete();
            }
        }

        return true;
    }

    /**
     * Create Database Tables
     */
    private function _createDatabases()
    {
        $sql = 'CREATE TABLE  `'._DB_PREFIX_.$this->table_name.'` (
                    `id_order` INT( 12 ) NOT NULL,
                    `handeled` BOOLEAN NOT NULL DEFAULT 0,
                    PRIMARY KEY (  `id_order` )
                ) ENGINE =' ._MYSQL_ENGINE_;

        $sql2 = 'CREATE TABLE  `'._DB_PREFIX_.$this->table_parts.'` (
                    `id_part` INT( 12 ) AUTO_INCREMENT,
                    `name` VARCHAR( 255 ) NOT NULL,
                    `description` VARCHAR( 255 ) DEFAULT NULL,
                    `keywords` VARCHAR( 255 ) DEFAULT NULL,
                    `manufacturer` VARCHAR( 255 ) DEFAULT NULL,
                    `manufacturer_part_number` VARCHAR(255) DEFAULT NULL,
                    `datasheet_link` LONGTEXT DEFAULT NULL,
                    `supplier` VARCHAR( 255 ) NOT NULL,
                    `supplier_part_number` VARCHAR(255) DEFAULT NULL,
                    `supplier_link` LONGTEXT DEFAULT NULL,
                    `qty_available` INT(12) DEFAULT 0,
                    PRIMARY KEY (  `id_part` )
                ) ENGINE =' ._MYSQL_ENGINE_;

        $sql3 = 'CREATE TABLE  `'._DB_PREFIX_.$this->table_po.'` (
                    `id_po` INT( 12 ) AUTO_INCREMENT,
                    `name` VARCHAR( 255 ) NOT NULL,
                    `order_number` VARCHAR(255) DEFAULT NULL,
                    `tracking_number` VARCHAR(255) DEFAULT NULL,
                    `date_ordered` DATE NOT NULL DEFAULT CURRENT_DATE,
                    `date_received` DATE DEFAULT NULL,
                    `tax` DOUBLE NOT NULL DEFAULT 0,
                    `shipping` DOUBLE NOT NULL DEFAULT 0,
                    `total` DOUBLE NOT NULL DEFAULT 0,
                    PRIMARY KEY (  `id_po` )
                ) ENGINE =' ._MYSQL_ENGINE_;

        $sql4 = 'CREATE TABLE  `'._DB_PREFIX_.$this->table_po_parts.'` (
                    `id_po` INT( 12 ) NOT NULL,
                    `id_part` INT( 12 ) NOT NULL,
                    `qty` INT(12) NOT NULL DEFAULT 1,
                    `total` DOUBLE NOT NULL DEFAULT 0,
                    `tariff` DOUBLE NOT NULL DEFAULT 0,
                    `received` BOOLEAN NOT NULL DEFAULT 0,
                    PRIMARY KEY (  `id_po`, `id_part` )
                ) ENGINE =' ._MYSQL_ENGINE_;

        $sql5 = 'CREATE TABLE  `'._DB_PREFIX_.$this->table_po_expenses.'` (
                    `id_expense` INT( 12 ) AUTO_INCREMENT,
                    `id_po` INT( 12 ) NOT NULL,
                    `name` VARCHAR(255) DEFAULT NULL,
                    `qty` INT(12) NOT NULL DEFAULT 1,
                    `total` DOUBLE NOT NULL DEFAULT 0,
                    PRIMARY KEY (  `id_expense`, `id_po` )
                ) ENGINE =' ._MYSQL_ENGINE_;

        $sql6 = 'CREATE TABLE  `'._DB_PREFIX_.$this->table_product_parts.'` (
                    `id_product` INT( 12 ) NOT NULL,
                    `id_part` INT( 12 ) NOT NULL,
                    `qty` INT(12) NOT NULL DEFAULT 1,
                    PRIMARY KEY (  `id_product`, `id_part` )
                ) ENGINE =' ._MYSQL_ENGINE_;



        if (!Db::getInstance()->Execute($sql) || !Db::getInstance()->Execute($sql2) || !Db::getInstance()->Execute($sql3)
            || !Db::getInstance()->Execute($sql4) || !Db::getInstance()->Execute($sql5) || !Db::getInstance()->Execute($sql6))
        {
            return false;
        }

        return true;
    }

    /**
     * Remove Database Tables
     */
    private function _eraseDatabases()
    {
        if ( ! Db::getInstance()->Execute('DROP TABLE `'._DB_PREFIX_.$this->table_name.'`') ||
            ! Db::getInstance()->Execute('DROP TABLE `'._DB_PREFIX_.$this->table_parts.'`') ||
            ! Db::getInstance()->Execute('DROP TABLE `'._DB_PREFIX_.$this->table_product_parts.'`') ||
            ! Db::getInstance()->Execute('DROP TABLE `'._DB_PREFIX_.$this->table_po.'`') ||
            ! Db::getInstance()->Execute('DROP TABLE `'._DB_PREFIX_.$this->table_po_parts.'`') ||
            ! Db::getInstance()->Execute('DROP TABLE `'._DB_PREFIX_.$this->table_po_expenses.'`'))
        {
            return false;
        }

        return true;
    }
}