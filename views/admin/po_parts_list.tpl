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

    #add-part-btn:hover {
        cursor: pointer;
        color: #ff6000;
    }


</style>

<div class="parts_list_container">
    <table>
        <thead>
            <tr>
                <th><strong>Component</strong></th>
                <th><strong>Qty</strong></th>
                <th><strong>Total Price</strong></th>
                <th><strong>Tariffs</strong></th>
                <th></th>
            </tr>
        </thead>
        <tbody id="parts-list-input">
            {foreach from=$po['part_list'] item=part}
                <tr>
                    <td>
                        <select name="parts[]">
                            {foreach from=$parts item=p}
                                <option value="{$p['id_part']}"{if $p['id_part'] == $part['id_part']}selected{/if}>{$p['name']}</option>
                            {/foreach}
                        </select>
                    </td>
                    <td>
                        <input type="number" name="qty[]" value="{$part['qty']}" onchange="updateOrderTotal()">
                    </td>
                    <td>
                        <input type="number" name="price[]" value="{$part['total']}" step="0.01" min="0.0" onchange="updateOrderTotal()">
                    </td>
                    <td>
                        <input type="number" name="tariffs[]" value="{$part['tariff']}" step="0.01" min="0.0" onchange="updateOrderTotal()">
                    </td>
                    <td class="delete">
                         <i class="process-icon-delete" title="Remove Part" onclick="removePart(event)"></i>
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>

    <div id="add-part-btn" style="width: 100%; text-align: center" onclick="addNewPart()">
        <i class="process-icon-new"></i>
        {l s="Add Part"}
    </div>
</div>


<script type="text/javascript">
    function addNewPart() {
        var container = document.getElementById('parts-list-input');
        if (container == null) {
            return;
        }

        var template = "<td> \
            {$partsDropdownStr} \
        </td> \
        <td> \
            <input type=\"number\" name=\"qty[]\" value=\"1\"/> \
        </td> \
        <td> \
            <input type=\"number\" name=\"price[]\" value=\"0.0\" step=\"0.01\" min=\"0.0\" onchange=\"updateOrderTotal()\"/> \
        </td> \
        <td> \
            <input type=\"number\" name=\"tariffs[]\" value=\"0.0\" step=\"0.01\" min=\"0.0\" onchange=\"updateOrderTotal()\"/> \
        </td> \
        <td class=\"delete\"> \
            <i class=\"process-icon-delete\" title=\"Remove Part\" onclick=\"removePart(event)\"></i> \
        </td>";

        var el = document.createElement('tr');
        el.innerHTML = template;

        container.appendChild(el);
    }

    function removePart(e) {
        var container = document.getElementById('parts-list-input');
        if (container == null) {
            return;
        }

        var row = e.currentTarget.parentNode.parentNode;
        if (confirm("Are you sure you want to remove this part from the order?"))
        {
            container.removeChild(row);
            updateOrderTotal();
        }
    }


    function updateOrderTotal() {
        var tax = document.getElementById('order-tax');
        var shipping = document.getElementById('order-shipping');
        var total = parseFloat(tax.value) + parseFloat(shipping.value);


        total += getTotalForComponents();
        total += getTotalForExpenses();

        var totalContainer = document.getElementById('order-total');
        totalContainer.innerHTML = '$' + total.toFixed(2);

        var input = document.getElementById('order-total-input');
        input.setAttribute('value', total);
    }


    function getTotalForComponents()
    {
        var quantities = document.querySelectorAll('input[name="qty[]"]');
        var prices = document.querySelectorAll('input[name="price[]"]');
        var tariffs = document.querySelectorAll('input[name="tariffs[]"]');

        var max = quantities.length;
        if (prices.length < max)
            max = prices.length;
        if (tariffs.length < max)
            max = tariffs.length;

        var total = 0;

        for (var i = 0; i < max; i++) {
            //var qty = parseInt(quantities[i].value);
            total += parseFloat(prices[i].value) + parseFloat(tariffs[i].value);
        }

        return total;
    }


    window.addEventListener('load', function(){
        updateOrderTotal();

        // Disable the received date once it has already been set
        var received_date = document.querySelector('input[name="date_received"]');
        var d = received_date.value;
        if (d != '0000-00-00' && d != '') {
            received_date.setAttribute('disabled', 'true');
        }
        //console.log(received_date.value);
    });
</script>