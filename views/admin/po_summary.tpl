<style>
    #summary-panel  {
        margin-top: -10px;
        margin-bottom: 20px;

        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        align-items: stretch;
    }

    #summary-panel::after {
        content: "";
        clear: both;
        display: table;
    }

    #summary-panel .expense-square {
        text-align: center !important;
        width: auto !important;
        border: 1px solid #ccc;
        border-radius: 10px;
        padding: 20px;

        background-color: white;

        margin-left: 10px;
    }

    #summary-panel .expense-square:first-of-type {
        margin-left: 0;
    }

    #summary-panel .expense-square .title {
        font-size: 25px;
        font-weight: 200;
        display: block;
    }

    #summary-panel .expense-square .total {
        font-size: 30px;
        font-weight: bold;
    }
</style>

<div id="summary-panel">
    <div class="expense-square">
        <span class="title">YTD Income</span>
        <span class="total">${$ytd_income}</span>
    </div>

    <div class="expense-square">
        <span class="title">YTD Expenses</span>
        <span class="total">${$ytd_expense}</span>
    </div>
    <div class="expense-square">
        <span class="title">YTD Net</span>
        <span class="total" style="color: {if $ytd_net < 0}#dc3545{else}#02b916{/if}">${$ytd_net}</span>
    </div>

</div>