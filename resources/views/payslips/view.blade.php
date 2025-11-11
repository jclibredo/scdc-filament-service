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
            border-bottom: 1px dotted #000;
            border-top: 1px dotted #000;
            padding: 20px;
        }

        /* Left Section */
        .left-section {
            flex: 1;
            border-right: 1px solid #000;
            padding-right: 10px;
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
            /* margin-bottom: 20px; */
        }

        .info {
            display: flex;
            justify-content: space-between;
            /* margin-bottom: 2px; */
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
            /* border-bottom: 1px solid #000; */
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

        .assignment-name {
            display: inline-block;
            max-width: 100%;
            font-size: clamp(12px, 2vw, 16px);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: middle;
        }
    </style>
</head>

<body>

    <button onclick="printPayslips()" style="margin-bottom:20px; padding:10px 20px; font-size:16px; cursor:pointer;">
        Print Payslips
    </button>

    @php
    use Carbon\Carbon;

    $from = Carbon::parse($datePeriod->datefrom);
    $to = Carbon::parse($datePeriod->dateto);

    if ($from->format('F') === $to->format('F')) {
    // Same month
    $payPeriod = $from->format('M j') . '–' . $to->format('j, Y');
    } else {
    // Different months
    $payPeriod = $from->format('M j') . ' - ' . $to->format('M j, Y');
    }
    @endphp


    @foreach ($employees as $employee)
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
            <h2>SINCERE CONSTRUCTION & DEVELOPMENT CORP.<br>PAYSLIP</h2>

            <div class="info">
                <div>
                    <p><strong>Employee: </strong>{{ $employee->lastname.' '.$employee->firstname }}</p>
                    <p><strong>Pay Period:</strong> {{ $payPeriod}}</p>
                </div>
                <div>
                    <p><strong>Assignment:</strong>
                        <span class="assignment-name">
                            {{ optional($employee->projectHistories->first()->project)->name ?? 'N/A' }}
                        </span>
                    </p>
                </div>
            </div>


            <table cellpadding="6" cellspacing="0" style="width: 100%; border-collapse: collapse;">
                <thead style="border-top: 2px solid #000; border-bottom: 2px solid #000;">
                    <tr>
                        <th style="text-align: left;">Earnings</th>
                        <th style="text-align: left;">Days/Hrs</th>
                        <th style="text-align: left;">Amount</th>
                        <th style="text-align: left;">Deductions</th>
                        <th style="text-align: left;">Days/Hrs</th>
                        <th style="text-align: left;">Amount</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td>Basic</td>
                        <td></td>
                        <td style="text-align: left;">9,325.00</td>

                        <td>Absences</td>
                        <td>3.00</td>
                        <td style="text-align: left;">2,151.93</td>
                    </tr>

                    <tr>
                        <td>Allowance</td>
                        <td>10.00</td>
                        <td style="text-align: left;">961.54</td>

                        <td>Lates/Undertime</td>
                        <td>7.95</td>
                        <td style="text-align: left;">712.80</td>
                    </tr>

                    <tr>
                        <td>Transpo Allow</td>
                        <td>10.00</td>
                        <td style="text-align: left;">1,211.54</td>

                        <td>SSS Loan</td>
                        <td></td>
                        <td style="text-align: left;">800.00</td>
                    </tr>
                </tbody>
            </table>
            <div class="totals">
                <div>Total Earnings: 11,498.08</div>
                <div>Total Deduction: 3,664.73</div>
                <div>Net Pay: 7,833.35</div>
            </div>
        </div>
    </div>

    <hr style="margin: 40px 0;">
    @endforeach
    <script>
        function printPayslips() {
            // Print only the payslip content
            const content = document.getElementById('payslip-content').innerHTML;
            const printWindow = window.open('', '', 'height=800,width=1200');
            printWindow.document.write('<html><head><title>Payslips</title>');
            printWindow.document.write(
                '<style>body{font-family: Arial, sans-serif; margin:20px;} .payslip-container{display:flex; border-bottom:1px dotted #000; border-top:1px dotted #000; padding:20px;} .left-section{flex:1; border-right:1px solid #000; padding-right:20px;} .right-section{flex:3; padding-left:20px;} table{width:100%; border-collapse:collapse;} table th, table td{border:1px solid #000; padding:5px; text-align:left;} .totals{display:flex; justify-content:space-between; font-weight:bold; margin-top:20px;} </style>'
            );
            printWindow.document.write('</head><body>');
            printWindow.document.write(content);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
        }
    </script>
</body>

</html>