<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Employee Payslips</title>
    <!-- <script src="https://cdn.tailwindcss.com"></script> -->
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
                height: 100vh;
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
                margin-bottom: 0 !important;
                /* Lock height so 4 items fit perfectly into 100vh page heights */
                height: 23vh !important;
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
        <span class="text-gray-600 font-medium">Ready to print <strong class="text-blue-600">{{ $loopData->count() }}</strong> payslip(s) <span class="text-gray-400">({{ ceil($loopData->count() / 4) }} pages total)</span></span>
        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded shadow-sm transition">
            Print All Payslips
        </button>
    </div>

    @foreach($loopData->chunk(4) as $chunk)
    <div class="print-page-wrapper max-w-5xl mx-auto space-y-4 mb-8">

        @foreach($chunk as $employee)
        <div class="payslip-card bg-white border border-black font-sans text-xs text-black shadow-sm select-none flex flex-col justify-between">
            <div class="grid grid-cols-12 divide-x divide-black h-full flex-grow">

                <div class="col-span-3 p-2.5 flex flex-col justify-between text-[10px] leading-tight">
                    <div>
                        <!-- <p class="text-justify mb-2 text-gray-900">
                            I acknowledge to have received from <strong class="font-bold">SINCERE CONSTRUCTION & DEVELOPMENT CORP.</strong> The amount stated below and have no further claims for service rendered.
                        </p> -->
                        <p class="text-justify mb-2 text-gray-900">
                            I acknowledge to have received from <strong class="font-bold">HIRO Premier JAPANESE YAKINIKU

                            </strong> The amount stated below and have no further claims for service rendered.
                        </p>
                        <div class="space-y-0.5 mt-2">
                            <div>Date Covered : <strong class="font-bold">{{ \Carbon\Carbon::parse($period->datefrom)->format('M d') }}
                                    - {{ \Carbon\Carbon::parse($period->dateto)->format('M d, Y') }}</strong></div>
                            <div>Empoyee ID : <strong class="font-bold">{{ $employee->employeeid }}</strong></div>
                            <div>Empoyee Status : <strong class="font-bold">{{ $employee->empStat?->name }}</strong></div>
                            <div>Net Pay : <strong class="font-bold">Php. {{ number_format($employee->payrollSummaryData->sum('totalnetpay'), 2) }}</strong></div>
                            <div class="truncate">Project : <strong class="font-bold">BRIDGETOWN MALL</strong></div>
                        </div>
                    </div>

                    <div class="mt-2 text-center">
                        <div class="font-bold underline uppercase tracking-wide text-[10px] truncate">{{ $employee->lastname }},{{ $employee->firstname }} {{ $employee->middlename }} </div>
                        <div class="italic text-[9px] text-gray-500 mt-0.5">Signature</div>
                    </div>
                </div>

                <div class="col-span-9 flex flex-col justify-between">
                    <div class="p-2 text-center border-b border-black">
                        <!-- <h1 class="text-sm font-extrabold tracking-wide uppercase">SINCERE CONSTRUCTION & DEVELOPMENT CORP.</h1> -->
                        <h1 class="text-sm font-extrabold tracking-wide uppercase">HIRO Premier JAPANESE YAKINIKU</h1>
                        <h2 class="text-xs font-bold tracking-wider uppercase">PAYSLIP</h2>
                        <div class="grid grid-cols-2 text-left mt-1.5 text-[10px] gap-y-0.5 font-medium">
                            <div class="truncate">Name : <span class="font-bold uppercase">
                                    {{ $employee->lastname }}, {{ $employee->firstname }} {{ $employee->middlename }} - {{ $employee->employeeid }}</span></div>
                            <div class="truncate">Assignment : <span class="font-bold uppercase">{{ $employee->project?->name }}</span></div>
                            <div class="truncate">Empoyee Status : <span class="font-bold uppercase">{{ $employee->empStat?->name }}</span></div>
                            <div class="col-span-2">Pay Period : <span class="font-bold">{{ \Carbon\Carbon::parse($period->datefrom)->format('M d') }}
                                    - {{ \Carbon\Carbon::parse($period->dateto)->format('M d, Y') }}</span></div>
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
                                    <!-- EARNINGS -->
                                    @forelse($employee->earningsData as $earning)
                                    <tr class="whitespace-nowrap">
                                        <td class="p-0.5 px-2 font-sans max-w-[100px] text-[10px] sm:text-[9px] truncate" title="{{ $earning->category->name ?? 'NODATA' }}">
                                            {{ $earning->category->name ?? 'NODATA' }}
                                        </td>
                                        <td class="p-0.5 px-2 text-center text-[10px]"></td>
                                        <td class="p-0.5 px-2 text-right font-mono text-[10px]">
                                            {{ number_format($earning->amount, 2) }}
                                        </td>
                                    </tr>
                                    @empty
                                    @endforelse
                                    <!-- ADJUSTMENT -->
                                    @forelse($employee->adjustmentData as $adjustment)
                                    <tr class="whitespace-nowrap">
                                        <td class="p-0.5 px-2 font-sans max-w-[100px] text-[10px] sm:text-[9px] truncate" title="{{ $earning->category->name ?? 'NODATA' }}">
                                            {{ $adjustment->adjustmentName->name ?? 'NODATA' }}
                                        </td>
                                        <td class="p-0.5 px-2 text-center text-[10px]"></td>
                                        <td class="p-0.5 px-2 text-right font-mono text-[10px]">
                                            {{ number_format($adjustment->amount, 2) }}
                                        </td>
                                    </tr>
                                    @empty
                                    @endforelse
                                    <!-- OVERTIME -->
                                    @forelse($employee->payrollSummaryData as $payrollsummary)
                                    @if((float)$payrollsummary->totalovertime > 0)
                                    <tr>
                                        <td class="p-0.5 px-2 font-sans">Overtime</td>
                                        <td class="p-0.5 px-2 text-center">{{number_format($payrollsummary->totalovertime, 2)}} hrs.</td>
                                        <td class="p-0.5 px-2 text-right">{{number_format(($payrollsummary->totalovertime * $employee->otratehour), 2)}}</td>
                                    </tr>
                                    @endif
                                    @empty
                                    @endforelse
                                    <!-- REQUIRED HOURS -->
                                    @forelse($employee->payrollSummaryData as $payrollsummary)
                                    @if((float)$payrollsummary->required_hours > 0)
                                    <tr>
                                        <td class="p-0.5 px-2 font-sans">Require Hours</td>
                                        <td class="p-0.5 px-2 text-center font-sans text-[9px]">
                                            {{ number_format($payrollsummary->required_hours, 0) }} hrs.
                                        </td>
                                        <td class="p-0.5 px-2 text-right">{{ number_format( $payrollsummary->required_income, 2) }}</td>
                                    </tr>
                                    @endif
                                    @empty
                                    @endforelse
                                    {{-- 1. Initialize the total variable to 0 before the loop --}}
                                    @php $totalHolidayAmount = 0; @endphp
                                    @forelse($employee->payrollReportsData as $holidaysData)
                                    @if($holidaysData->holidayData?->percentage > 0)
                                    <tr class="whitespace-nowrap">
                                        <td class="p-0.5 px-2 font-sans max-w-[100px] text-[10px] sm:text-[9px] truncate" title="{{ $holidaysData->holidayData?->type ?? 'NODATA' }}">
                                            {{ $holidaysData->holidayData?->type ?? 'NODATA' }} ,+{{number_format($holidaysData->holidayData?->percentage,0)}}%
                                        </td>
                                        <td class="p-0.5 px-2 text-center text-[10px]">{{ $holidaysData->acquired_hours }} hrs.</td>
                                        <td class="p-0.5 px-2 text-right font-mono text-[10px]">
                                            @php
                                            $computedRate = ($employee->rate_per_hour * ($holidaysData->holidayData?->percentage/100))+$employee->rate_per_hour;
                                            // 2. Calculate the row total as a raw number
                                            $rowTotal = $holidaysData->acquired_hours * $computedRate;
                                            // 3. Add it to our running total variable
                                            $totalHolidayAmount += $rowTotal;
                                            // 4. Echo the formatted version for display
                                            echo number_format($rowTotal, 2);
                                            @endphp
                                        </td>
                                    </tr>
                                    @endif
                                    @empty
                                    @endforelse
                                    <!-- TOTAL HOURS -->
                                    <!-- @if($employee->payrollSummaryData->sum('totalhours') > 0)
                                    <tr>
                                        <td class="p-0.5 px-2 font-sans">Acquired Hours</td>
                                        <td class="p-0.5 px-2 text-center font-sans text-[9px]">
                                            {{ number_format($employee->payrollSummaryData->sum('totalhours'), 0) }} hrs.
                                        </td>
                                        <td class="p-0.5 px-2 text-right">{{ number_format( ($employee->payrollSummaryData->sum('totalhours') * $employee->rate_per_hour), 2) }}</td>
                                    </tr>
                                    @endif -->
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
                                    <!-- LATE AND UNDERTIME -->
                                    @if($employee->payrollSummaryData->sum('lateundertime') > 0)
                                    <tr>
                                        <td class="p-0.5 px-2 font-sans">Late/Under.</td>
                                        <td class="p-0.5 px-2 text-center font-sans text-[9px]">
                                            {{ number_format($employee->payrollSummaryData->sum('lateundertime'), 1) }}
                                        </td>
                                        <td class="p-0.5 px-2 text-right">{{ number_format(($employee->payrollSummaryData->sum('lateundertime') * 
                                            ($employee->rate_per_hour + $employee->dailyallowanceratehour )), 2) }}</td>
                                    </tr>
                                    @endif
                                    <!-- TOTAL ABSENT -->
                                    @if($employee->payrollSummaryData->sum('totalabsent') > 0)
                                    <tr>
                                        <td class="p-0.5 px-2 font-sans">Absences</td>
                                        <td class="p-0.5 px-2 text-center font-sans text-[9px]">
                                            {{ number_format($employee->payrollSummaryData->sum('totalabsent'), 0) }}
                                        </td>
                                        <td class="p-0.5 px-2 text-right">{{ number_format(($employee->payrollSummaryData->sum('totalabsent') * 
                                            ($employee->basic_rate+$employee->dailyallowance)), 2) }}</td>
                                    </tr>
                                    @endif
                                    @forelse($employee->otherdeductionData as $otherdata)
                                    <tr class="whitespace-nowrap">
                                        <td class="p-0.5 px-2 font-sans max-w-[100px] text-[10px] sm:text-[9px] truncate" title="{{ $earning->category->name ?? 'NODATA' }}">
                                            {{ $otherdata->otherDeduction->title ?? 'NODATA' }}
                                        </td>
                                        <td class="p-0.5 px-2 text-center text-[10px]"></td>
                                        <td class="p-0.5 px-2 text-right font-mono text-[10px]">
                                            {{ number_format($otherdata->amount, 2) }}
                                        </td>
                                    </tr>
                                    @empty
                                    @endforelse
                                    @forelse($employee->govdeductionData as $govdata)
                                    <tr class="whitespace-nowrap">
                                        <td class="p-0.5 px-2 font-sans max-w-[100px] text-[10px] sm:text-[9px] truncate" title="{{ $earning->category->name ?? 'NODATA' }}">
                                            {{ $govdata->govDeduction->title ?? 'NODATA' }}
                                        </td>
                                        <td class="p-0.5 px-2 text-center text-[10px]"></td>
                                        <td class="p-0.5 px-2 text-right font-mono text-[10px]">
                                            {{ number_format($govdata->amount, 2) }}
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
                            Total Earnings : <span class="font-mono ml-1 font-medium">

                                @php
                                $totalOvertime = $payrollsummary->totalovertime * $employee->otratehour;
                                echo number_format($totalOvertime
                                + $totalHolidayAmount
                                + $employee->payrollSummaryData->sum('totaladjustment')
                                + $payrollsummary->required_income,2)
                                @endphp
                                <!-- {{
                                $totalHolidayAmount  }} -->
                            </span>
                        </div>
                        <div>
                            Total Deduction : <span class="font-mono ml-1 font-medium">
                                @php
                                $dailyRate = $employee->basic_rate;
                                $ratePerHour = $employee->rate_per_hour;
                                $computedAbsent = ($employee->payrollSummaryData->sum('totalabsent') * ($dailyRate+$employee->dailyallowance));
                                $computedLate = ($employee->payrollSummaryData->sum('lateundertime') * ($ratePerHour+ $employee->dailyallowanceratehour));
                                $finalTotaldeduction =$employee->payrollSummaryData->sum('totaldeductionn') + $computedAbsent + $computedLate ;
                                echo number_format($finalTotaldeduction, 2) ;
                                @endphp
                            </span>
                        </div>
                        <div>
                            Net Pay : <span class="font-mono ml-1 text-xs font-extrabold">{{ number_format($employee->payrollSummaryData->sum('totalnetpay'), 2) }}</span>
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