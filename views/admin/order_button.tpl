&nbsp;<a class="btn btn-default" href="{$transaction_link}" target="_blank"><i class="icon-money"></i>&nbsp;PayPal Order</a>
&nbsp;<a id="desc-order-componentinventory-ship" class="btn btn-default" href="#component-inventory-ship"><i class="icon-ship"></i>&nbsp;Inventory Ship</a>

<script type="text/javascript">
window.addEventListener('load', function(){
    $('#desc-order-componentinventory-ship').on('click', function(){
        var el = $('#component-inventory-template');
        el.css('display', (el.css('display') == 'none') ? 'block' : 'none');
    });

    $('#component-inventory-ship-form').on('submit', function(e){
        e.preventDefault();

        var data = $('#component-inventory-ship-form').serialize();

        var url = "{$submitLink}";

        $.ajax({
            url: url,
            type: "POST",
            data: data + '&submitEditPO=1',
            success: function(d) {
                // Manually set the tracking number in the form and submit it
                var el = $('#shipping_table');
                var input = el.find('input[name="tracking_number"]');
                var btn = el.find('button[name="submitShippingNumber"]');
                if (input && btn) {
                    input.val($('#ci_tracking_number').val());
                    btn.click();
                }
                else {
                    alert('Failed to set tracking number');
                    window.location.reload();
                }
            },
            error: function(a) {
                console.error(a);
                $('#component-inventory-template').html(e);
            }
        });
    });
});
</script>

<style>
    #component-inventory-template {
        min-height: 200px;
        margin-top: 10px;

        outline: 1px solid #ccc;
        padding: 10px;
    }
</style>

<div id="component-inventory-template" class="clearfix" style="display:none">
    <form id="component-inventory-ship-form" class="form-horizontal col-lg-12">
        <input type="hidden" name="name" value="Order {$order->reference} Shipping" />
        <input type="hidden" name="order_number" value="{$order->id} - {$order->reference}"/>
        <input type="hidden" name="id_po" value="new"/>

        <input type="hidden" name="date_ordered" value="{date("Y-m-d")}"/>
        <input type="hidden" name="date_received" value="{date("Y-m-d")}"/>

        <input type="hidden" id="order-tax" name="tax" value="0.0"/>
        <input type="hidden" id="order-total-input" name="order-total" value="0.0"/>

        <input type="hidden" name="order_id" value="{$order->id}"/>
        <input type="hidden" name="ci_markAsShipped" value="1" />

        <div class="form-group">
            <div class="col-lg-12 text-center">
                <a class="btn btn-default" href="{$transaction_link}" target="_blank">Ship on PayPal</a>
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-lg-2">Tracking Number</label>
            <div class="col-lg-10">
                <input type="text" id="ci_tracking_number" name="tracking_number" autocomplete="off">
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-lg-2">Shipping Cost</label>
            <div class="col-lg-10">
                <input type="number" id="order-shipping" name="shipping" min="0" step="0.01" style="margin-top: 5px" value="0.00">
            </div>
        </div>

        {$components}
        <br/>
        {$expenses}

        <div class="form-group" style="margin-top: 10px">
            <div class="col-lg-12 text-center">
                <button type="submit" class="btn btn-default" name="submitEditPO">Mark as Shipped & Create P.O.</button>
            </div>
        </div>

        <label id="order-total" style="display: none"></label>
    </form>
</div>