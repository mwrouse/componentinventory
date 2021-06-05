<style>
    #summary-panel  {
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
        <span class="title">Year-to-Date Expenses</span>
        <span class="total">${$ytd}</span>
    </div>

</div>