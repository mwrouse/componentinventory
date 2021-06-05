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
<div id="product-components" class="panel product-tab">
    <input type="hidden" name="submittted_tabs[]" value="ProductComponents">
    <h3>{l s='Components'}</h3>

    <div class="parts_list_container">
        <table>
            <thead>
                <tr>
                    <th><strong>Part</strong></th>
                    <th><strong>Qty</strong></th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="parts-list-input">
                {foreach from=$parts item=part}
                    <tr>
                        <td>
                            <select name="parts[]">
                                {foreach from=$allParts item=p}
                                    <option value="{$p['id_part']}"{if $p['id_part'] == $part['id_part']}selected{/if}>{$p['name']}</option>
                                {/foreach}
                            </select>
                        </td>
                        <td>
                            <input type="number" name="qty[]" value="{$part['qty']}">
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

     <div class="panel-footer">
		<a href="{$link->getAdminLink('AdminProducts')|escape:'html':'UTF-8'}{if isset($smarty.request.page) && $smarty.request.page > 1}&amp;submitFilterproduct={$smarty.request.page|intval}{/if}" class="btn btn-default"><i class="process-icon-cancel"></i> {l s='Cancel'}</a>
		<button type="submit" name="submitAddproduct" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> {l s='Save'}</button>
		<button type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> {l s='Save and Stay'}</button>
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
        if (confirm("Are you sure you want to remove this part from the product?"))
        {
            container.removeChild(row);
            updateOrderTotal();
        }
    }
</script>