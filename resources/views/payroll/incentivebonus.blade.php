<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incentive Bonus Breakdown - {{ $period->code ?? $yearendid ?? 'Report' }}</title>
    <script src="{{ asset('js/tailwindcss-browser-4.js') }}"></script>
    <!-- SheetJS CDN for Excel Export -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            block-size: 8px;
            inline-size: 8px;
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
            <h1 class="text-sm font-bold text-gray-800">Incentive Bonus Matrix Breakdown</h1>
            <p class="text-[11px] text-gray-500">
                Period: <strong>{{ $period->code ?? $yearendid ?? 'N/A' }}</strong>
                @if(isset($period->datefrom) && isset($period->dateto))
                ({{ \Carbon\Carbon::parse($period->datefrom)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($period->dateto)->format('M d, Y') }})
                @endif
            </p>
        </div>
        <div class="flex gap-2">
            <!-- EXCEL EXPORT BUTTON -->
            <button onclick="exportToExcel()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-3 py-1 rounded text-xs cursor-pointer flex items-center gap-1">
                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zM6 20V4h7v5h5v11H6zm10-9h-2.5l-1.5 2.5L10.5 11H8l2.5 3.5L8 18h2.5l1.5-2.5 1.5 2.5H16l-2.5-3.5L16 11z" />
                </svg>
                Export Excel
            </button>
            <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-3 py-1 rounded text-xs cursor-pointer">
                Print Report
            </button>
            <button onclick="window.close()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold px-3 py-1 rounded text-xs cursor-pointer">
                Close
            </button>
        </div>
    </div>

    @php
    $grandTotalEarnings = $employees->sum(fn($e) => (float) ($e->incentiveBonus->earnings ?? 0));
    $grandTotalAdj = $employees->sum(fn($e) => (float) optional($e->adjustmentData)->sum('amount'));
    $grandTotalGov = $employees->sum(fn($e) => (float) optional($e->govdeductionData)->sum('amount'));
    $grandTotalOther = $employees->sum(fn($e) => (float) optional($e->otherdeductionData)->sum('amount'));
    $grandTotalDeductions = $grandTotalGov + $grandTotalOther;
    $grandTotalNetPay = ($grandTotalEarnings + $grandTotalAdj) - $grandTotalDeductions;
    @endphp

    <!-- TAB SUMMARY SECTION -->
    <div class="mb-4 bg-white p-4 rounded shadow border border-gray-200">
        <!-- Company Header Image (Centered) -->
        <div class="text-center mb-6">
            <img src="{{ asset('images/scdc_full.png') }}" alt="Company Header" class="mx-auto max-h-16 object-contain">
        </div>
        <h2 class="text-xs font-bold text-gray-700 uppercase tracking-wide mb-3">Overall Summary</h2>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 text-center">
            <div class="p-2 rounded bg-gray-50 border border-gray-200">
                <span class="block text-[10px] uppercase font-semibold text-gray-500">Employees</span>
                <span class="text-sm font-bold text-gray-800">{{ $employees->count() }}</span>
            </div>
            <div class="p-2 rounded bg-emerald-50 border border-emerald-200">
                <span class="block text-[10px] uppercase font-semibold text-emerald-700">Total Earnings</span>
                <span class="text-sm font-bold text-emerald-900">{{ number_format($grandTotalEarnings, 2) }}</span>
            </div>
            <div class="p-2 rounded bg-purple-50 border border-purple-200">
                <span class="block text-[10px] uppercase font-semibold text-purple-700">Total Adjustments</span>
                <span class="text-sm font-bold text-purple-900">{{ number_format($grandTotalAdj, 2) }}</span>
            </div>
            <div class="p-2 rounded bg-rose-50 border border-rose-200">
                <span class="block text-[10px] uppercase font-semibold text-rose-700">Total Deductions</span>
                <span class="text-sm font-bold text-rose-900">{{ number_format($grandTotalDeductions, 2) }}</span>
            </div>
            <div class="p-2 rounded bg-green-100 border border-green-300">
                <span class="block text-[10px] uppercase font-semibold text-green-800">Total Net Pay</span>
                <span class="text-sm font-extrabold text-green-950">{{ number_format($grandTotalNetPay, 2) }}</span>
            </div>
        </div>
    </div>

    <!-- PARTNER / PROJECT DISPLAY (TOP OF TABLE) -->
    @if(!empty($partner) || !empty($partners))
    <div class="mb-3 bg-indigo-50 border-l-4 border-indigo-600 p-3 rounded shadow-xs">
        <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-900">Partner / Company:</span>
        <span class="text-xs font-extrabold text-indigo-950 ml-2">
            {{ is_array($partners ?? null) ? implode(', ', $partners) : ($partner->name ?? $partner ?? 'N/A') }}
        </span>
    </div>
    @endif

    <!-- MAIN TABLE -->
    <div class="overflow-x-auto custom-scrollbar bg-white shadow rounded border border-gray-300">
        <table id="matrixTable" class="w-full border-collapse border border-gray-300 text-center min-w-max">
            <thead>
                <tr class="bg-gray-100 text-gray-800 font-bold text-[11px] uppercase">
                    <th class="border border-gray-300 p-2 min-w-[100px] bg-gray-100 sticky left-0 z-30">Employee ID</th>
                    <th class="border border-gray-300 p-2 text-left min-w-[200px] bg-gray-100 sticky left-[100px] z-30">Full Name</th>
                    <th class="border border-gray-300 p-2 min-w-[150px] bg-gray-100">Project</th>
                    <th class="border border-gray-300 p-2 bg-emerald-100 text-emerald-900 font-bold min-w-[120px]">Earnings</th>
                    <th class="border border-gray-300 p-2 bg-purple-100 text-purple-900 font-bold min-w-[120px]">Total Adjustment</th>
                    <th class="border border-gray-300 p-2 bg-rose-100 text-rose-900 font-bold min-w-[120px]">Total Deduction</th>
                    <th class="border border-gray-300 p-2 bg-green-200 font-extrabold text-green-950 min-w-[130px]">Total Net</th>
                </tr>
            </thead>
            <!-- TABLE BODY -->
            <tbody class="divide-y divide-gray-200 font-mono text-[10px]">
                @forelse($employees as $employee)
                @php
                $empEarnings = (float) ($employee->incentiveBonus->earnings ?? 0);
                $empTotalAdj = (float) optional($employee->adjustmentData)->sum('amount');
                $empTotalGov = (float) optional($employee->govdeductionData)->sum('amount');
                $empTotalOther = (float) optional($employee->otherdeductionData)->sum('amount');
                $empTotalDeduction = $empTotalGov + $empTotalOther;
                $empTotalNet = ($empEarnings + $empTotalAdj) - $empTotalDeduction;
                @endphp
                <tr class="hover:bg-gray-50 text-gray-800">
                    <td class="border border-gray-300 p-2 font-sans font-semibold text-gray-700 bg-white sticky left-0 z-20">
                        {{ $employee->employeeid }}
                    </td>
                    <td class="border border-gray-300 p-2 text-left font-sans font-medium bg-white sticky left-[100px] z-20 whitespace-nowrap">
                        {{ strtoupper($employee->lastname) }}, {{ strtoupper($employee->firstname) }} {{ $employee->middlename ? strtoupper($employee->middlename[0]) . '.' : '' }}
                    </td>
                    <td class="border border-gray-300 p-2 font-sans text-gray-600 bg-white">
                        {{ $employee->project->name ?? '--' }}
                    </td>
                    <td class="border border-gray-300 p-2 bg-emerald-50/50 font-bold text-emerald-900 text-right">
                        {{ number_format($empEarnings, 2) }}
                    </td>
                    <td class="border border-gray-300 p-2 bg-purple-50/50 font-bold text-purple-900 text-right">
                        {{ number_format($empTotalAdj, 2) }}
                    </td>
                    <td class="border border-gray-300 p-2 bg-rose-50/50 font-bold text-rose-900 text-right">
                        {{ number_format($empTotalDeduction, 2) }}
                    </td>
                    <td class="border border-gray-300 p-2 bg-green-100 font-extrabold text-green-950 text-right">
                        {{ number_format($empTotalNet, 2) }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="p-6 text-center text-gray-500 italic">No employee incentive records found.</td>
                </tr>
                @endforelse
            </tbody>
            <!-- TABLE FOOTER (GRAND TOTALS) -->
            @if($employees->count() > 0)
            <tfoot class="bg-gray-100 font-mono text-[10px] text-gray-900 font-bold border-t-2 border-gray-400">
                <tr>
                    <td class="border border-gray-300 p-2 font-sans sticky left-0 z-20 bg-gray-100">--</td>
                    <td class="border border-gray-300 p-2 text-left font-sans sticky left-[100px] z-20 bg-gray-100">TOTALS</td>
                    <td class="border border-gray-300 p-2 font-sans bg-gray-100">--</td>
                    <td class="border border-gray-300 p-2 bg-emerald-100 text-right text-emerald-950">{{ number_format($grandTotalEarnings, 2) }}</td>
                    <td class="border border-gray-300 p-2 bg-purple-100 text-right text-purple-950">{{ number_format($grandTotalAdj, 2) }}</td>
                    <td class="border border-gray-300 p-2 bg-rose-100 text-right text-rose-950">{{ number_format($grandTotalDeductions, 2) }}</td>
                    <td class="border border-gray-300 p-2 bg-green-200 font-extrabold text-green-950 text-right">{{ number_format($grandTotalNetPay, 2) }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
    <!-- Single Prepared By Section (Print Only) -->
    <div class="hidden print:block mt-8 pt-4">
        <div class="w-64">
            <span class="text-[11px] font-sans text-gray-700">Prepared by:</span>
            <div class="mt-8 text-[11px] font-sans font-bold text-gray-900 pb-1">
                {{ auth()->user()->name ?? auth()->user()->lastname . ' , ' . auth()->user()->firstname.' '. auth()->user()->middlename }}
            </div>
        </div>
    </div>
    <!-- JAVASCRIPT FOR EXCEL EXPORT -->
    <script>
        function exportToExcel() {
            var table = document.getElementById("matrixTable");
            var wb = XLSX.utils.table_to_book(table, {
                sheet: "Incentive Bonuses"
            });
            var filename = "Incentive_Bonus_Breakdown_{{ $period->code ?? $yearendid ?? 'Report' }}.xlsx";
            XLSX.writeFile(wb, filename);
        }
    </script>
</body>

</html>