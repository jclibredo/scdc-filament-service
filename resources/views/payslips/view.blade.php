<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
    }

    .payslip-container {
        display: flex;
        border: 1px dotted #000;
        padding: 20px;
    }

    /* Left Section */
    .left-section {
        flex: 1;
        border-right: 1px solid #000;
        padding-right: 20px;
    }

    .left-section p {
        margin: 5px 0;
    }

    .signature {
        margin-top: 50px;
    }

    /* Right Section */
    .right-section {
        flex: 3;
        padding-left: 20px;
    }

    .right-section h2 {
        text-align: center;
        margin-bottom: 20px;
    }

    .info {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .info div {
        flex: 1;
    }

    /* Table Section */
    .earnings-deductions {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .earnings,
    .deductions {
        width: 48%;
    }

    .earnings h3,
    .deductions h3 {
        border-bottom: 1px solid #000;
        padding-bottom: 5px;
        margin-bottom: 10px;
    }

    .row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
    }

    .totals {
        display: flex;
        justify-content: space-between;
        font-weight: bold;
        margin-top: 20px;
    }
    </style>
</head>

<body>

    <div class="payslip-container">
        <!-- Left Section -->
        <div class="left-section">
            <p>Sincere</p>
            <p>Construction & Development Corp.</p>
            <p>Sample text is here in the proper text will reflected soon</p>
            <div class="signature">
                <p>______________________</p>
                <p>Signature</p>
            </div>
        </div>

        <!-- Right Section -->
        <div class="right-section">
            <h2>SINCERE CONSTRUCTION & DEVELOPMENT CORP.<br>PAYSILP</h2>

            <div class="info">
                <div>
                    <p><strong>Employee:</strong> JUAN DELA CRUZ</p>
                    <p><strong>Pay Period:</strong> Oct 6-20, 2025</p>
                </div>
                <div>
                    <p><strong>Assignment:</strong> SINCERE - ADMIN</p>
                </div>
            </div>

            <div class="earnings-deductions">
                <!-- Earnings -->
                <div class="earnings">
                    <h3>Earnings</h3>
                    <div class="row"><span>Basic</span><span>9,325.00</span></div>
                    <div class="row"><span>Allowance 10.00</span><span>961.54</span></div>
                    <div class="row"><span>Transpo Allow 10.00</span><span>1,211.54</span></div>
                </div>

                <!-- Deductions -->
                <div class="deductions">
                    <h3>Deductions</h3>
                    <div class="row"><span>Absences 3.00</span><span>2,151.93</span></div>
                    <div class="row"><span>Lates/Undertime 7.95</span><span>712.80</span></div>
                    <div class="row"><span>SSS Loan</span><span>800.00</span></div>
                </div>
            </div>

            <div class="totals">
                <div>Total Earnings: 11,498.08</div>
                <div>Total Deduction: 3,664.73</div>
                <div>Net Pay: 7,833.35</div>
            </div>
        </div>
    </div>

</body>

</html>