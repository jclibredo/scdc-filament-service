<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Year-End Matrix Breakdown - {{ $period->code }}</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body class="bg-gray-100 p-4 font-sans text-xs">

    <!-- CONTROL BAR -->
    <div class="no-print mb-4 flex justify-between items-center bg-white p-3 rounded shadow border border-gray-200">
        <div>
            <h1 class="text-sm font-bold text-gray-800">13th Month Matrix Breakdown</h1>
            <p class="text-[11px] text-gray-500">
                Period: <strong>{{ $period->code }}</strong>
                ({{ \Carbon\Carbon::parse($period->datefrom)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($period->dateto)->format('M d, Y') }})
            </p>
        </div>
        <div class="flex gap-2">
            <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-3 py-1 rounded text-xs cursor-pointer">
                Print Report
            </button>
            <button onclick="window.close()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold px-3 py-1 rounded text-xs cursor-pointer">
                Close
            </button>
        </div>
    </div>

    @php
    $grandTotalEarnings = $employees->sum(fn($e) => (float) $e->thirteenthMonths->sum('earnings'));
    $grandTotalAllowance = $employees->sum(fn($e) => (float) $e->thirteenthMonths->sum('allowance'));
    $grandTotalAdj = $employees->sum(fn($e) => (float) $e->adjustmentData->sum('amount'));
    $grandTotalGov = $employees->sum(fn($e) => (float) $e->govdeductionData->sum('amount'));
    $grandTotalOther = $employees->sum(fn($e) => (float) $e->otherdeductionData->sum('amount'));
    $grandTotal13thMonth = ($grandTotalEarnings+$grandTotalAllowance)/12;
    $grandTotalNetPay = ($grandTotal13thMonth + $grandTotalAdj) - ($grandTotalGov + $grandTotalOther);
    @endphp

    <!-- TAB SUMMARY SECTION -->
    <div class="mb-4 bg-white p-4 rounded shadow border border-gray-200">
        <h2 class="text-xs font-bold text-gray-700 uppercase tracking-wide mb-3">Overall Summary</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 text-center">
            <div class="p-2 rounded bg-gray-50 border border-gray-200">
                <span class="block text-[10px] uppercase font-semibold text-gray-500">Employees</span>
                <span class="text-sm font-bold text-gray-800">{{ $employees->count() }}</span>
            </div>
            <div class="p-2 rounded bg-emerald-50 border border-emerald-200">
                <span class="block text-[10px] uppercase font-semibold text-emerald-700">Total Earnings</span>
                <span class="text-sm font-bold text-emerald-900">{{ number_format($grandTotalEarnings/12, 2) }}</span>
            </div>
            <div class="p-2 rounded bg-blue-50 border border-blue-200">
                <span class="block text-[10px] uppercase font-semibold text-blue-700">Total Allowance</span>
                <span class="text-sm font-bold text-blue-900">{{ number_format($grandTotalAllowance/12, 2) }}</span>
            </div>
            <div class="p-2 rounded bg-purple-50 border border-purple-200">
                <span class="block text-[10px] uppercase font-semibold text-purple-700">Adjustments</span>
                <span class="text-sm font-bold text-purple-900">{{ number_format($grandTotalAdj, 2) }}</span>
            </div>
            <div class="p-2 rounded bg-rose-50 border border-rose-200">
                <span class="block text-[10px] uppercase font-semibold text-rose-700">Gov Deductions</span>
                <span class="text-sm font-bold text-rose-900">{{ number_format($grandTotalGov, 2) }}</span>
            </div>
            <div class="p-2 rounded bg-orange-50 border border-orange-200">
                <span class="block text-[10px] uppercase font-semibold text-orange-700">Other Deductions</span>
                <span class="text-sm font-bold text-orange-900">{{ number_format($grandTotalOther, 2) }}</span>
            </div>
            <div class="p-2 rounded bg-green-100 border border-green-300">
                <span class="block text-[10px] uppercase font-semibold text-green-800">Net Pay</span>
                <span class="text-sm font-extrabold text-green-950">{{ number_format($grandTotalNetPay, 2) }}</span>
            </div>
        </div>
    </div>

    <!-- MAIN MATRIX TABLE -->
    <div class="overflow-x-auto custom-scrollbar bg-white shadow rounded border border-gray-300">
        <table class="w-full border-collapse border border-gray-300 text-center min-w-max">
            <thead>

                <!-- HEADER ROW 1: MONTH NAMES -->
                <tr class="bg-gray-100 text-gray-800 font-bold text-[11px]">
                    <th rowspan="3" class="border border-gray-300 p-2 min-w-[90px] bg-gray-100 sticky left-0 z-30">Employee ID</th>
                    <th rowspan="3" class="border border-gray-300 p-2 text-left min-w-[180px] bg-gray-100 sticky left-[90px] z-30">Employee Name</th>
                    <th rowspan="3" class="border border-gray-300 p-2 min-w-[120px] bg-gray-100 sticky left-[270px] z-30">Designation</th>
                    <th rowspan="3" class="border border-gray-300 p-2 min-w-[120px] bg-gray-100 sticky left-[390px] z-30">Project</th>

                    @foreach($monthsStructure as $mKey => $mInfo)
                    @php
                    $cutoffCount = count($mInfo['cutoffs']);
                    $colSpan = $cutoffCount > 0 ? ($cutoffCount * 2) : 2;
                    @endphp
                    <th colspan="{{ $colSpan }}" class="border border-gray-300 p-1.5 uppercase bg-amber-100/70 text-amber-950 font-extrabold tracking-wider">
                        {{ $mInfo['name'] }}
                    </th>
                    @endforeach

                    <th rowspan="3" class="border border-gray-300 p-2 bg-emerald-100 text-emerald-900 font-bold min-w-[90px]">Total Earnings</th>
                    <th rowspan="3" class="border border-gray-300 p-2 bg-blue-100 text-blue-900 font-bold min-w-[90px]">Total Allowance</th>
                    <th rowspan="3" class="border border-gray-300 p-2 bg-purple-100 text-purple-900 font-bold min-w-[90px]">Adjustments</th>
                    <th rowspan="3" class="border border-gray-300 p-2 bg-rose-100 text-rose-900 font-bold min-w-[90px]">Gov Deductions</th>
                    <th rowspan="3" class="border border-gray-300 p-2 bg-orange-100 text-orange-900 font-bold min-w-[90px]">Other Deductions</th>
                    <th rowspan="3" class="border border-gray-300 p-2 bg-green-200 font-extrabold text-green-950 min-w-[100px]">Net Pay</th>
                </tr>

                <!-- HEADER ROW 2: DATE RANGES -->
                <tr class="bg-gray-50 text-gray-700 font-semibold text-[10px]">
                    @foreach($monthsStructure as $mKey => $mInfo)
                    @if(count($mInfo['cutoffs']) > 0)
                    @foreach($mInfo['cutoffs'] as $cutoff)
                    <th colspan="2" class="border border-gray-300 p-1 bg-gray-100 text-gray-800">
                        {{ $cutoff['label'] }}
                    </th>
                    @endforeach
                    @else
                    <th colspan="2" class="border border-gray-300 p-1 text-gray-400 italic">No Data</th>
                    @endif
                    @endforeach
                </tr>

                <!-- HEADER ROW 3: EARNS / ALLOW SUB-COLUMNS -->
                <tr class="bg-gray-100 text-gray-600 font-bold text-[9px] uppercase">
                    @foreach($monthsStructure as $mKey => $mInfo)
                    @if(count($mInfo['cutoffs']) > 0)
                    @foreach($mInfo['cutoffs'] as $cutoff)
                    <th class="border border-gray-300 p-1 w-12 bg-emerald-50 text-emerald-800">Earns</th>
                    <th class="border border-gray-300 p-1 w-12 bg-blue-50 text-blue-800">Allow</th>
                    @endforeach
                    @else
                    <th class="border border-gray-300 p-1 w-12 bg-emerald-50 text-emerald-800">Earns</th>
                    <th class="border border-gray-300 p-1 w-12 bg-blue-50 text-blue-800">Allow</th>
                    @endif
                    @endforeach
                </tr>
            </thead>

            <!-- TABLE BODY -->
            <tbody class="divide-y divide-gray-200 font-mono text-[10px]">
                @forelse($employees as $employee)
                @php
                $empTotalEarnings = (float) $employee->thirteenthMonths->sum('earnings');
                $empTotalAllowance = (float) $employee->thirteenthMonths->sum('allowance');
                $empTotalAdj = (float) $employee->adjustmentData->sum('amount');
                $empTotalGov = (float) $employee->govdeductionData->sum('amount');
                $empTotalOther = (float) $employee->otherdeductionData->sum('amount');
                $thirteentmonth = ($empTotalEarnings + $empTotalAllowance) /12;
                $empNetPay = ($thirteentmonth + $empTotalAdj) - ($empTotalGov + $empTotalOther);
                @endphp
                <tr class="hover:bg-gray-50 text-gray-800">

                    <!-- Sticky Employee Info Columns -->
                    <td class="border border-gray-300 p-1.5 font-sans font-semibold text-gray-700 bg-white sticky left-0 z-20">
                        {{ $employee->employeeid }}
                    </td>
                    <td class="border border-gray-300 p-1.5 text-left font-sans font-medium bg-white sticky left-[90px] z-20 whitespace-nowrap">
                        {{ strtoupper($employee->lastname) }}, {{ strtoupper($employee->firstname) }}
                    </td>
                    <td class="border border-gray-300 p-1.5 font-sans text-gray-600 bg-white sticky left-[270px] z-20">
                        {{ $employee->skill->title ?? '--' }}
                    </td>
                    <td class="border border-gray-300 p-1.5 font-sans text-gray-600 bg-white sticky left-[390px] z-20">
                        {{ $employee->project->name ?? '--' }}
                    </td>

                    <!-- Cutoff Earns & Allow Cells -->
                    @foreach($monthsStructure as $mKey => $mInfo)
                    @if(count($mInfo['cutoffs']) > 0)
                    @foreach($mInfo['cutoffs'] as $cutoff)
                    @php
                    $rec = $employee->thirteenthMonths->first(function ($item) use ($cutoff) {
                    if (!$item->datestart) return false;

                    $itemStart = \Carbon\Carbon::parse($item->datestart)->startOfDay();
                    $itemEnd = $item->dateend
                    ? \Carbon\Carbon::parse($item->dateend)->endOfDay()
                    : $itemStart->copy()->endOfDay();

                    $cutoffStart = \Carbon\Carbon::parse($cutoff['datestart'])->startOfDay();
                    $cutoffEnd = \Carbon\Carbon::parse($cutoff['dateend'])->endOfDay();

                    return ($itemStart <= $cutoffEnd && $itemEnd>= $cutoffStart);
                        });

                        $earns = $rec ? (float) $rec->earnings : 0;
                        $allow = $rec ? (float) $rec->allowance : 0;
                        @endphp
                        <td class="border border-gray-300 p-1 text-right {{ $earns > 0 ? 'text-gray-900 font-bold bg-emerald-50/20' : 'text-gray-300' }}">
                            {{ number_format($earns, 2) }}
                        </td>
                        <td class="border border-gray-300 p-1 text-right {{ $allow > 0 ? 'text-gray-900 font-bold bg-blue-50/20' : 'text-gray-300' }}">
                            {{ number_format($allow, 2) }}
                        </td>
                        @endforeach
                        @else
                        <td class="border border-gray-300 p-1 text-right text-gray-300">0.00</td>
                        <td class="border border-gray-300 p-1 text-right text-gray-300">0.00</td>
                        @endif
                        @endforeach

                        <!-- Summary Columns -->
                        <td class="border border-gray-300 p-1.5 bg-emerald-50/50 font-bold text-emerald-900 text-right">
                            {{ number_format($empTotalEarnings/12, 2) }}
                        </td>
                        <td class="border border-gray-300 p-1.5 bg-blue-50/50 font-bold text-blue-900 text-right">
                            {{ number_format($empTotalAllowance/12, 2) }}
                        </td>
                        <td class="border border-gray-300 p-1.5 bg-purple-50/50 font-bold text-purple-900 text-right">
                            {{ number_format($empTotalAdj, 2) }}
                        </td>
                        <td class="border border-gray-300 p-1.5 bg-rose-50/50 font-bold text-rose-900 text-right">
                            {{ number_format($empTotalGov, 2) }}
                        </td>
                        <td class="border border-gray-300 p-1.5 bg-orange-50/50 font-bold text-orange-900 text-right">
                            {{ number_format($empTotalOther, 2) }}
                        </td>
                        <td class="border border-gray-300 p-1.5 bg-green-100 font-extrabold text-green-950 text-right">
                            {{ number_format($empNetPay, 2) }}
                        </td>
                </tr>
                @empty
                <tr>
                    <td colspan="100" class="p-6 text-center text-gray-500 italic">No employee records found.</td>
                </tr>
                @endforelse
            </tbody>

            <!-- TABLE FOOTER (GRAND TOTALS) -->
            @if($employees->count() > 0)
            <tfoot class="bg-gray-100 font-mono text-[10px] text-gray-900 font-bold border-t-2 border-gray-400">
                <tr>
                    <td class="border border-gray-300 p-2 font-sans sticky left-0 z-20 bg-gray-100">--</td>
                    <td class="border border-gray-300 p-2 text-left font-sans sticky left-[90px] z-20 bg-gray-100">TOTALS</td>
                    <td class="border border-gray-300 p-2 font-sans sticky left-[270px] z-20 bg-gray-100">--</td>
                    <td class="border border-gray-300 p-2 font-sans sticky left-[390px] z-20 bg-gray-100">--</td>

                    <!-- Dynamic Monthly Column Totals -->
                    @foreach($monthsStructure as $mKey => $mInfo)
                    @if(count($mInfo['cutoffs']) > 0)
                    @foreach($mInfo['cutoffs'] as $cutoff)
                    @php
                    $totEarns = $employees->sum(function($emp) use ($cutoff) {
                    $rec = $emp->thirteenthMonths->first(function ($item) use ($cutoff) {
                    if (!$item->datestart) return false;

                    $itemStart = \Carbon\Carbon::parse($item->datestart)->startOfDay();
                    $itemEnd = $item->dateend
                    ? \Carbon\Carbon::parse($item->dateend)->endOfDay()
                    : $itemStart->copy()->endOfDay();

                    $cutoffStart = \Carbon\Carbon::parse($cutoff['datestart'])->startOfDay();
                    $cutoffEnd = \Carbon\Carbon::parse($cutoff['dateend'])->endOfDay();

                    return ($itemStart <= $cutoffEnd && $itemEnd>= $cutoffStart);
                        });

                        return $rec ? (float) $rec->earnings : 0;
                        });

                        $totAllow = $employees->sum(function($emp) use ($cutoff) {
                        $rec = $emp->thirteenthMonths->first(function ($item) use ($cutoff) {
                        if (!$item->datestart) return false;

                        $itemStart = \Carbon\Carbon::parse($item->datestart)->startOfDay();
                        $itemEnd = $item->dateend
                        ? \Carbon\Carbon::parse($item->dateend)->endOfDay()
                        : $itemStart->copy()->endOfDay();

                        $cutoffStart = \Carbon\Carbon::parse($cutoff['datestart'])->startOfDay();
                        $cutoffEnd = \Carbon\Carbon::parse($cutoff['dateend'])->endOfDay();

                        return ($itemStart <= $cutoffEnd && $itemEnd>= $cutoffStart);
                            });

                            return $rec ? (float) $rec->allowance : 0;
                            });
                            @endphp
                            <td class="border border-gray-300 p-1 text-right bg-emerald-100/50 text-emerald-950">{{ number_format($totEarns, 2) }}</td>
                            <td class="border border-gray-300 p-1 text-right bg-blue-100/50 text-blue-950">{{ number_format($totAllow, 2) }}</td>
                            @endforeach
                            @else
                            <td class="border border-gray-300 p-1 text-right bg-emerald-100/50">0.00</td>
                            <td class="border border-gray-300 p-1 text-right bg-blue-100/50">0.00</td>
                            @endif
                            @endforeach

                            <td class="border border-gray-300 p-1.5 bg-emerald-100 text-right">{{ number_format($grandTotalEarnings/12, 2) }}</td>
                            <td class="border border-gray-300 p-1.5 bg-blue-100 text-right">{{ number_format($grandTotalAllowance/12, 2) }}</td>
                            <td class="border border-gray-300 p-1.5 bg-purple-100 text-right">{{ number_format($grandTotalAdj, 2) }}</td>
                            <td class="border border-gray-300 p-1.5 bg-rose-100 text-right">{{ number_format($grandTotalGov, 2) }}</td>
                            <td class="border border-gray-300 p-1.5 bg-orange-100 text-right">{{ number_format($grandTotalOther, 2) }}</td>
                            <td class="border border-gray-300 p-1.5 bg-green-200 font-extrabold text-green-950 text-right">{{ number_format($grandTotalNetPay, 2) }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

</body>

</html>