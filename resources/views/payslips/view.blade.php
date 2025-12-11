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
        padding: 10px;
    }

    /* Left Section */
    .left-section {
        flex: 1;
        border-right: 1px solid #000;
        /* padding-right: 50px; */
    }

    .left-section {
        font-size: 10px;
        /* smaller text for the whole section */
        line-height: 1.2;
        /* keep line spacing consistent */
    }

    .left-section p,
    .left-section .emp-details p,
    .left-section .signature p {
        margin: 0;
        /* preserve existing text structure */
        padding: 0;
    }

    .left-section p {
        margin: 5px 0;
    }

    .signature {
        margin-top: 20px;
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
    h4,
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
        font-size: 10px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        vertical-align: middle;
    }
    </style>
</head>

<body>
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

    @php
    // fetch values from the thirteenth_months table
    // $thirteenth = $employee->total_amount;
    $totalAmount = $employee->thirteenth_total ?? 0;
    $employeeDeductions = $deductions[$employee->employeeid] ?? collect();
    $totalDeduction = $employeeDeductions->sum('amount');
    @endphp
    <div class="payslip-container">
        <!-- Left Section -->
        <div class="left-section">
            <p>I acknowledge to have received from</p>
            <p><strong>SINCERE CONSTRUCTION &</strong></p>
            <p><strong>DEVELOPMENT CORP.</strong> the amount</p>
            <p>stated below and have no further claims for</p>
            <p>service rendered</p>
            <br>
            <div class="emp-details">
                <p><strong>Pay Period:</strong> {{ $payPeriod}}</p>
                <p><strong>Employee No.: </strong>{{ $employee->employeeid }}</p>
                <p><strong>Employee: </strong>{{ $employee->lastname.' '.$employee->firstname }}</p>
                <p><strong>Net Pay: </strong>{{ number_format($totalAmount-$totalDeduction, 2) }}</p>
            </div>
            <center>
                <div class="signature">
                    <p>______________________</p>
                    <p> Signature
                    </p>
                </div>
            </center>

        </div>

        <!-- Right Section -->
        <div class="right-section" style="font-size: 10px;">
            <h4>
                <center>SINCERE CONSTRUCTION & DEVELOPMENT CORP.<br>PAYSLIP </center>
            </h4>
            <div class="info">
                <div>
                    <p><strong>Employee: </strong>{{ $employee->lastname.' '.$employee->firstname }}</p>
                    <p><strong>Pay Period:</strong> {{ $payPeriod}}</p>
                </div>
                <div>
                    <p><strong>Assignment:</strong>
                        <span class="assignment-name">
                            {{ $employee->project_name ?? 'N/A' }}
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
                    {{-- Earnings --}}
                    <tr>
                        <td>{{ $category->name }}</td>
                        <td></td>
                        <td style="text-align: left;">{{ number_format($totalAmount, 2) }}</td>

                        {{-- First deduction (if any) --}}
                        @if ($employeeDeductions->isNotEmpty())
                        @php $first = $employeeDeductions->shift(); @endphp
                        <td>{{ $first->otherDeduction->title }}</td>
                        <td></td>
                        <td style="text-align: left;">{{ number_format($first->amount, 2) }}</td>
                        @else
                        <td></td>
                        <td></td>
                        <td></td>
                        @endif
                    </tr>

                    {{-- Remaining deductions --}}
                    @foreach ($employeeDeductions as $deduction)
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td>{{ $deduction->otherDeduction->title }}</td>
                        <td></td>
                        <td style="text-align: left;">{{ number_format($deduction->amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="totals">
                <div>Total Earnings: {{ number_format($totalAmount, 2) }}</div>
                <div>Total Deduction: {{ number_format($totalDeduction, 2) }}</div>
                <div>Net Pay: {{ number_format($totalAmount - $totalDeduction, 2) }}</div>
            </div>
        </div>
    </div>
    <hr style="margin: 5px 0;">
    @endforeach

    <!--  THIS AREA IS FOR THE GRAND TOTAL -->
    <br><br>
    <div class="payslip-container">
        <!-- Right Section -->
        <div class="right-section" style="font-size: 10px;">
            <h4>
                <center>SINCERE CONSTRUCTION & DEVELOPMENT CORP.<br>PAYSLIP SUMMARY</center>
            </h4>
            <div class="info">
                <div>
                    <p><strong>Pay Period:</strong> {{ $payPeriod}}</p>
                </div>
                <div>
                    <p><strong>Total Employee:</strong>{{ $employees->count() }}</p>
                </div>
                <div>
                    <p><strong>Employee Type :</strong>
                        {{ $type == 'W' ? 'Weekly' : ($type == 'SM' ? 'Semi Monthly' : $type) }}
                    </p>
                </div>
            </div>

            <table cellpadding="6" cellspacing="0" style="width: 100%; border-collapse: collapse;">
                <thead style="border-top: 2px solid #000; border-bottom: 2px solid #000;">
                    <tr>
                        <th style="text-align: left;">Earnings</th>
                        <th style="text-align: left;">Amount</th>
                        <th style="text-align: left;">Deductions</th>
                        <th style="text-align: left;">Amount</th>
                    </tr>
                </thead>

                <tbody>
                    @php
                    // Total earnings (from thirteenthMonth relationship)
                    $earnings = $employees->sum(function($employee) {
                    return $employee->thirteenth_total ?? 0;
                    });
                    @endphp
                    {{-- Earnings --}}
                    <tr>
                        <td>{{ $category->name }}</td>
                        <td style="text-align: left;"> {{ number_format($earnings, 2) }}</td>
                        {{-- First deduction (if any) --}}
                        <td>Deductions</td>
                        <td style="text-align: left;"> {{ number_format($SumDeduction, 2) }}</td>

                    </tr>
                </tbody>
            </table>
            <div class="totals">
                <div>Total Earnings: {{ number_format($earnings, 2) }}</div>
                <div>Total Deduction: {{ number_format($SumDeduction, 2) }}</div>
                <div>Net Pay: {{ number_format($earnings - $SumDeduction, 2) }}</div>
            </div>
        </div>
    </div>

</body>

</html>