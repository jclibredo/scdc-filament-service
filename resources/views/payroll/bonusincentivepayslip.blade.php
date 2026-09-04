<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Year End Payslips</title>
    <script src="{{ asset('js/tailwindcss.js') }}"></script>
    <style>
        @media print {
            body {
                background-color: #fff;
                color: #000;
                padding: 0 !important;
                margin: 0 !important;
            }

            .no-print {
                display: none !important;
            }

            /* Vertical wrapper that forces exactly 4 slips per dynamic print page area */
            .print-page-wrapper {
                page-break-after: always;
                break-after: page;
                block-size: 100vh;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                padding: 20px;
                box-sizing: border-box;
            }

            .print-page-wrapper:last-child {
                page-break-after: avoid;
                break-after: avoid;
            }

            .payslip-card {
                border: 1.5px solid #000 !important;
                box-shadow: none !important;
                margin-block-end: 0 !important;
                /* Lock height so 4 items fit perfectly into 100vh page heights */
                block-size: 23vh !important;
            }

            .divide-black>*+* {
                border-color: #000 !important;
            }

            .border-black {
                border-color: #000 !important;
            }
        }
    </style>
</head>

<body class="bg-gray-100 p-4 md:p-8">

    <div class="max-w-5xl mx-auto mb-6 flex justify-between items-center no-print bg-white p-4 rounded shadow-sm border text-xs">
        <span class="text-gray-600 font-medium">Ready to print <strong class="text-blue-600">{{ $employees->count() }}</strong>
            payslip(s) <span class="text-gray-400">({{ ceil($employees->count() / 4) }} pages total)</span></span>
        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded shadow-sm transition">
            Print All Payslips
        </button>
    </div>

    @foreach($employees->chunk(4) as $chunk)
    <div class="print-page-wrapper max-w-5xl mx-auto space-y-4 mb-8">

        @foreach($chunk as $employee)
        @php
        // Extract single HasOne relationship value safely
        $earningsTotal = (float) ($employee->incentiveBonus->earnings ?? 0);
        $adjTotal = (float) optional($employee->adjustmentData)->sum('amount');
        $govDeductions = (float) optional($employee->govdeductionData)->sum('amount');
        $otherDeductions = (float) optional($employee->otherdeductionData)->sum('amount');

        $totalEarnings = $earningsTotal + $adjTotal;
        $totalDeductions = $govDeductions + $otherDeductions;
        $calculatedNetPay = $totalEarnings - $totalDeductions;

        $netPay = $earningsTotal + $adjTotal - $govDeductions - $otherDeductions;
        @endphp

        <div class="payslip-card bg-white border border-black font-sans text-xs text-black shadow-sm select-none flex flex-col justify-between">
            <div class="grid grid-cols-12 divide-x divide-black h-full flex-grow">

                <div class="col-span-3 p-2.5 flex flex-col justify-between text-[10px] leading-tight">
                    <div>
                        <p class="text-justify mb-2 text-gray-900">
                            I acknowledge to have received from <strong class="font-bold">SINCERE CONSTRUCTION & DEVELOPMENT CORP.</strong> The amount stated below and have no further claims for service rendered.
                        </p>
                        <div class="space-y-0.5 mt-2">
                            <div>Date Covered : <strong class="font-bold">{{ \Carbon\Carbon::parse($period->datefrom)->format('M d') }} - {{ \Carbon\Carbon::parse($period->dateto)->format('M d, Y') }}</strong></div>
                            <div>Employee ID : <strong class="font-bold">{{ $employee->employeeid }}</strong></div>
                            <div>Employee Status : <strong class="font-bold">{{ $employee->empStat?->name ?? 'N/A' }}</strong></div>
                            <div>Net Pay : <strong class="font-bold">Php. {{ number_format($netPay, 2) }}</strong></div>
                            <div class="truncate">Project : <strong class="font-bold">{{ $employee->project?->name ?? 'N/A' }}</strong></div>
                        </div>
                    </div>

                    <div class="mt-2 text-center">
                        <div class="font-bold underline uppercase tracking-wide text-[10px] truncate">{{ $employee->lastname }}, {{ $employee->firstname }} {{ $employee->middlename }}</div>
                        <div class="italic text-[9px] text-gray-500 mt-0.5">Signature</div>
                    </div>
                </div>

                <div class="col-span-9 flex flex-col justify-between">
                    <div class="p-2 text-center border-b border-black">
                        <h1 class="text-sm font-extrabold tracking-wide uppercase">SINCERE CONSTRUCTION & DEVELOPMENT CORP.</h1>
                        <h2 class="text-xs font-bold tracking-wider uppercase">{{ $period->rep_type ?? 'YEAR END' }} : PAYSLIP</h2>
                        <div class="grid grid-cols-2 text-left mt-1.5 text-[10px] gap-y-0.5 font-medium">
                            <div class="truncate">Name : <span class="font-bold uppercase">{{ $employee->lastname }}, {{ $employee->firstname }} {{ $employee->middlename }} - {{ $employee->employeeid }}</span></div>
                            <div class="truncate">Assignment : <span class="font-bold uppercase">{{ $employee->project?->name ?? 'N/A' }}</span></div>
                            <div class="truncate">Employee Status : <span class="font-bold uppercase">{{ $employee->empStat?->name ?? 'N/A' }}</span></div>
                            <div class="col-span-2">Pay Period : <span class="font-bold">{{ \Carbon\Carbon::parse($period->datefrom)->format('M d') }} - {{ \Carbon\Carbon::parse($period->dateto)->format('M d, Y') }}</span></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 divide-x divide-black flex-grow min-h-[70px]">

                        <div class="flex flex-col">
                            <table class="w-full text-left border-collapse text-[10px]">
                                <thead>
                                    <tr class="border-b border-black font-bold bg-gray-50/50">
                                        <th class="p-0.5 px-2 w-1/3">Earnings</th>
                                        <th class="p-0.5 px-2 w-1/3 text-center">Days/Hours</th>
                                        <th class="p-0.5 px-2 w-1/3 text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="leading-tight font-mono font-medium">
                                    <!-- INCENTIVE BONUS (HasOne Instance) -->
                                    @if($employee->incentiveBonus)
                                    <tr class="whitespace-nowrap">
                                        <td class="p-0.5 px-2 font-sans max-w-[100px] text-[10px] sm:text-[9px] truncate" title="Incentive Bonus">
                                            {{ $period->rep_type ?? 'YEAR END' }}
                                        </td>
                                        <td class="p-0.5 px-2 text-center text-[10px]"></td>
                                        <td class="p-0.5 px-2 text-right font-mono text-[10px]">
                                            {{ number_format($employee->incentiveBonus->earnings ?? 0, 2) }}
                                        </td>
                                    </tr>
                                    @endif

                                    <!-- ADJUSTMENTS -->
                                    @forelse($employee->adjustmentData as $adjustment)
                                    <tr class="whitespace-nowrap">
                                        <td class="p-0.5 px-2 font-sans max-w-[100px] text-[10px] sm:text-[9px] truncate" title="{{ $adjustment->adjustmentName->name ?? 'Adjustment' }}">
                                            {{ $adjustment->adjustmentName->name ?? 'Adjustment' }}
                                        </td>
                                        <td class="p-0.5 px-2 text-center text-[10px]"></td>
                                        <td class="p-0.5 px-2 text-right font-mono text-[10px]">
                                            {{ number_format($adjustment->amount ?? 0, 2) }}
                                        </td>
                                    </tr>
                                    @empty
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="flex flex-col">
                            <table class="w-full text-left border-collapse text-[10px]">
                                <thead>
                                    <tr class="border-b border-black font-bold bg-gray-50/50">
                                        <th class="p-0.5 px-2 w-1/3">Deduction</th>
                                        <th class="p-0.5 px-2 w-1/3 text-center">Days/Hours</th>
                                        <th class="p-0.5 px-2 w-1/3 text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="leading-tight font-mono font-medium">
                                    @forelse($employee->otherdeductionData as $otherdata)
                                    <tr class="whitespace-nowrap">
                                        <td class="p-0.5 px-2 font-sans max-w-[100px] text-[10px] sm:text-[9px] truncate" title="{{ $otherdata->otherDeduction->title ?? 'Deduction' }}">
                                            {{ $otherdata->otherDeduction->title ?? 'Deduction' }}
                                        </td>
                                        <td class="p-0.5 px-2 text-center text-[10px]"></td>
                                        <td class="p-0.5 px-2 text-right font-mono text-[10px]">
                                            {{ number_format($otherdata->amount ?? 0, 2) }}
                                        </td>
                                    </tr>
                                    @empty
                                    @endforelse

                                    @forelse($employee->govdeductionData as $govdata)
                                    <tr class="whitespace-nowrap">
                                        <td class="p-0.5 px-2 font-sans max-w-[100px] text-[10px] sm:text-[9px] truncate" title="{{ $govdata->govDeduction->title ?? 'Gov Deduction' }}">
                                            {{ $govdata->govDeduction->title ?? 'Gov Deduction' }}
                                        </td>
                                        <td class="p-0.5 px-2 text-center text-[10px]"></td>
                                        <td class="p-0.5 px-2 text-right font-mono text-[10px]">
                                            {{ number_format($govdata->amount ?? 0, 2) }}
                                        </td>
                                    </tr>
                                    @empty
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="border-t border-black p-1.5 px-2.5 bg-gray-50/50 flex flex-row justify-between items-center text-[10px] font-bold">
                        <div>
                            Total Earnings : <span class="font-mono ml-1 font-medium">{{ number_format($totalEarnings, 2) }}</span>
                        </div>
                        <div>
                            Total Deduction : <span class="font-mono ml-1 font-medium">{{ number_format($totalDeductions, 2) }}</span>
                        </div>
                        <div>
                            Net Pay : <span class="font-mono ml-1 text-xs font-extrabold">{{ number_format($netPay, 2) }}</span>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        @endforeach

    </div>
    @endforeach

</body>

</html>