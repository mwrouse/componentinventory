<style>
    .parts_list_container table {
        width: 100%;
    }
    .parts_list_container table td {
            padding: 10px;
            padding-left: 0;
    }
    .parts_list_container table tbody tr:hover {
        background-color: #f7f7f7;
    }

    .parts_list_container td.delete i:hover {
        cursor: pointer;
        color: #dc3545;
    }

    #add-expense-btn:hover {
        cursor: pointer;
        color: #ff6000;
    }
</style>

<div class="parts_list_container">
    <table>
        <thead>
            <tr>
                <th><strong>Expense</strong></th>
                <th><strong>Qty</strong></th>
                <th><strong>Total Price</strong></th>
                <th></th>
            </tr>
        </thead>
        <tbody id="expense-list-input">
            {foreach from=$po['expenses'] item=expense}
                <tr>
                    <td>
                        <input type="text" name="expense[]" value="{$expense['name']}">
                    </td>
                    <td>
                        <input type="number" name="expense_qty[]" value="{$expense['qty']}" onchange="updateOrderTotal()">
                    </td>
                    <td>
                        <input type="number" name="expense_price[]" value="{$expense['total']}" step="0.01" onchange="updateOrderTotal()">
                    </td>
                    <td class="delete">
                         <i class="process-icon-delete" title="Remove Expense" onclick="removeExpense(event)"></i>
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>

    <div id="add-expense-btn" style="width: 100%; text-align: center" onclick="addNewExpense()">
        <i class="process-icon-new"></i>
        {l s="Add Expense"}
    </div>
</div>


<script type="text/javascript">
    function addNewExpense() {
        var container = document.getElementById('expense-list-input');
        if (container == null) {
            return;
        }

        var template = "<td> \
            <input type=\"text\" name=\"expense[]\" /> \
        </td> \
        <td> \
            <input type=\"number\" name=\"expense_qty[]\" value=\"1\"/> \
        </td> \
        <td> \
            <input type=\"number\" name=\"expense_price[]\" value=\"0.0\" step=\"0.01\" min=\"0.0\" onchange=\"updateOrderTotal()\"/> \
        </td> \
        <td class=\"delete\"> \
            <i class=\"process-icon-delete\" title=\"Remove Part\" onclick=\"removePart(event)\"></i> \
        </td>";

        var el = document.createElement('tr');
        el.innerHTML = template;

        container.appendChild(el);
    }

    function removeExpense(e) {
        var container = document.getElementById('expense-list-input');
        if (container == null) {
            return;
        }

        var row = e.currentTarget.parentNode.parentNode;
        if (confirm("Are you sure you want to remove this expense from the order?"))
        {
            container.removeChild(row);
            updateOrderTotal();
        }
    }



    function getTotalForExpenses()
    {
        var prices = document.querySelectorAll('input[name="expense_price[]"]');

        var max = prices.length;

        var total = 0;

        for (var i = 0; i < max; i++) {
            total += parseFloat(prices[i].value);
        }

        return total;
    }

</script>