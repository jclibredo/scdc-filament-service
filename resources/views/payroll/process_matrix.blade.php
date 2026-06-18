<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Payroll Processing Ledger Matrix</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        /* Custom scrollbar adjustments for comfortable matrix navigation */
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

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>

<body class="bg-gray-100 p-6">
    <!-- SUCCESS AND ERROR MESSAGE -->
    <div class="max-w-full mx-auto bg-white shadow-md rounded-lg overflow-hidden flex flex-col">

        <div class="p-4 bg-amber-50 border-l-4 border-amber-500 flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center">
            <div>
                <h2 class="text-sm font-bold text-gray-800 tracking-wide uppercase">Payroll Processing Interface</h2>
                <p class="text-gray-600 mt-0.5">
                    Period: <span class="font-mono font-bold bg-amber-100 px-1.5 py-0.5 rounded text-amber-900 text-[11px]">{{ $period->code }}</span>
                    <span class="block sm:inline sm:ml-2 text-gray-500">({{ \Carbon\Carbon::parse($period->datefrom)->format('M d') }} - {{ \Carbon\Carbon::parse($period->dateto)->format('M d, Y') }})</span>
                </p>
            </div>
            <button type="button" onclick="handleCustomClose()" class="w-full sm:w-auto inline-flex justify-center items-center gap-1.5 px-4 py-2 sm:py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded shadow-sm transition-colors duration-150">
                Close Tab
            </button>
        </div>
        <br>
        <!-- SESSION ALERTS (For Traditional Form Submissions) -->
        <div class="mb-4 px-4">
            @if(session('success'))
            <div class="bg-emerald-50 border-l-4 border-emerald-500 p-3 rounded shadow-sm text-emerald-800 text-xs font-medium flex items-center justify-between">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    {{ session('success') }}
                </span>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 font-bold">&times;</button>
            </div>
            @endif
            @if($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-3 rounded shadow-sm text-red-800 text-xs">
                <div class="font-bold mb-1 flex items-center gap-2">
                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    Please correct the following validation errors:
                </div>
                <ul class="list-disc pl-5 space-y-0.5 font-medium">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
        <div id="modal_js_alerts" class="mb-4 px-4 hidden"></div>
        <!--END SUCCESS AND ERROR MESSAGE -->

        <div class="overflow-x-auto custom-scrollbar w-full relative">
            <table class="w-full border-collapse border border-gray-300 text-center min-w-max isolate">
                <thead>
                    <tr class="bg-gray-50 text-gray-700 font-bold text-[10px]">
                        <th rowspan="2" class="sticky left-0 z-30 bg-gray-50 border border-gray-300 p-2 text-left shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] min-w-[240px]">Employee Name</th>
                        <th rowspan="2" class="sticky left-[240px] z-30 bg-gray-50 border border-gray-300 p-2 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] min-w-[100px]">Designation</th>
                        <th rowspan="2" class="sticky left-[240px] z-30 bg-gray-50 border border-gray-300 p-2 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] min-w-[100px]">Project</th>
                        <th colspan="{{ $earningsCategories->count() }}" class="border border-gray-300 p-1 bg-emerald-50 text-emerald-900 tracking-wider">EARNINGS CATEGORIES</th>
                        <th colspan="{{ count($periodDates) }}" class="border border-gray-300 p-1 bg-amber-100 text-amber-900 tracking-wider">Attendance Logs (Daily View Hours)</th>
                        <th rowspan="2" class="border border-gray-300 p-2 text-blue-600 bg-gray-50">OT</th>
                        <th rowspan="2" class="border border-gray-300 p-2 text-red-600 bg-gray-50">Late/Under.</th>
                        <th rowspan="2" class="border border-gray-300 p-2 bg-green-50 text-green-900">Total Earnings</th>
                        <th colspan="{{ max($deductions->count(), 1) }}" class="border border-gray-300 p-1 bg-red-50 text-red-900 tracking-wider">DEDUCTIONS</th>
                        <th rowspan="2" class="border border-gray-300 p-2 bg-gray-50">OTHR. DEDUCTIONS</th>
                        <th rowspan="2" class="border border-gray-300 p-2 bg-gray-50">ADJUSTMENT</th>
                        <th rowspan="2" class="border border-gray-300 p-2 bg-gray-50">Total Deduction</th>
                        <th rowspan="2" class="border border-gray-300 p-2 bg-gray-50">Gross Pay</th>
                        <th rowspan="2" class="border border-gray-300 p-2 bg-green-100 font-extrabold text-green-800">Net Pay</th>
                    </tr>
                    <tr class="bg-gray-50 text-gray-600 font-semibold text-[10px]">
                        @foreach($earningsCategories as $category)
                        <th class="border border-gray-300 p-1 px-2 font-sans bg-gray-50">{{ $category->name }}</th>
                        @endforeach
                        @foreach($periodDates as $date)
                        <th class="border border-gray-300 w-10 uppercase tracking-tighter bg-gray-50 text-[9px]">
                            {{ \Carbon\Carbon::parse($date)->shortDayName }}<br><span class="text-gray-400">[{{ \Carbon\Carbon::parse($date)->format('d') }}]</span>
                        </th>
                        @endforeach
                        @forelse($deductions as $deduction)
                        <th class="border border-gray-300 p-1 font-sans bg-gray-50">{{ $deduction->title }}</th>
                        @empty
                        <th class="border border-gray-300 p-1 text-gray-400 font-sans italic bg-gray-50">None found</th>
                        @endforelse
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 font-mono">
                    @foreach($employees as $employee)
                    @php
                    $empAdjustments = collect($employee->adjustmentData ?? []);
                    $empOtherDeductions = collect($employee->otherdeductionData ?? []);
                    $empGovDeductions = collect($employee->govdeductionData ?? []);
                    $empTimesheetRecords = $employeeTimesheets[$employee->employeeid] ?? [];

                    $reportMetrics = [];
                    foreach ($periodDates as $pDate) {
                    $dKey = \Carbon\Carbon::parse($pDate)->toDateString();
                    $report = collect($employee->payrollReportsData)->first(fn($r) => \Carbon\Carbon::parse($r->date_entry)->toDateString() === $dKey);
                    $dayData = $empTimesheetRecords[$dKey] ?? [];

                    $reportMetrics[$dKey] = [
                    'paytype' => $report['paytype'] ?? null,
                    'cat_id' => $report['cat_id'] ?? null,
                    'time_in' => $dayData['time_in'] ?? null,
                    'break_out' => $dayData['break_out'] ?? null,
                    'break_in' => $dayData['break_in'] ?? null,
                    'time_out' => $dayData['time_out'] ?? null,
                    'class' => $dayData['class'] ?? '',
                    'acquired_hours' => $report ? number_format($report->acquired_hours, 2, '.', '') : '0.00',
                    'overtime' => $report ? number_format($report->overtime, 2, '.', '') : '0.00',
                    'late_undertime' => $report ? number_format($report->late_undertime, 2, '.', '') : '0.00',
                    ];
                    }
                    @endphp
                    <tr class="hover:bg-gray-50 text-gray-800 text-[10px] group transition-colors">
                        <td class="sticky left-0 z-20 bg-white group-hover:bg-gray-50 border border-gray-300 p-2 text-left font-sans font-medium shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] transition-colors">
                            <div class="flex items-center justify-between gap-2">
                                <span class="truncate">{{ strtoupper($employee->lastname) }}, {{ strtoupper($employee->firstname) }} {{ strtoupper($employee->middlename) }}</span>
                                <div class="flex items-center gap-1 shrink-0 no-print">
                                    <button type="button"
                                        class="payroll-modal-trigger cursor-pointer px-1.5 py-0.5 border border-blue-200 hover:border-blue-400 rounded bg-blue-50 hover:bg-blue-100 text-blue-600 font-bold transition-all text-[10px]"
                                        data-id="{{ $employee->employeeid }}"
                                        data-skill="{{ $employee->skill->title ?? '--' }}"
                                        data-name="{{ strtoupper($employee->lastname) }}, {{ strtoupper($employee->firstname) }} {{ strtoupper($employee->middlename) }}"
                                        data-project="{{ $employee->project->name ?? '--' }}"
                                        data-timesheets="{{ json_encode($reportMetrics) }}"
                                        data-adjustments="{{ json_encode($empAdjustments) }}"
                                        data-govdeductions="{{ json_encode($empGovDeductions) }}"
                                        data-otherdeductions="{{ json_encode($empOtherDeductions) }}">
                                        📝 Open Modal
                                    </button>
                                </div>
                            </div>
                        </td>
                        <td class="sticky left-[240px] z-20 bg-white group-hover:bg-gray-50 border border-gray-300 p-2 font-sans text-gray-600 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] transition-colors">
                            {{ $employee->skill->title ?? '--' }}
                        </td>
                        <td class="sticky left-[240px] z-20 bg-white group-hover:bg-gray-50 border border-gray-300 p-2 font-sans text-gray-600 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] transition-colors">
                            {{ $employee->project->name ?? '--' }}
                        </td>

                        @foreach($earningsCategories as $category)
                        @php
                        $matchedEarning = collect($employee->earningsData)->first(fn($earning) => $earning->title == $category->id && $earning->employee_id == $employee->employeeid);
                        @endphp
                        <td class="border border-gray-300 p-2 text-right">{{ $matchedEarning ? number_format($matchedEarning->amount, 2) : '0.00' }}</td>
                        @endforeach

                        @foreach($periodDates as $pDate)
                        @php
                        $dateKey = \Carbon\Carbon::parse($pDate)->toDateString();
                        $report = collect($employee->payrollReportsData)->first(fn($r) => \Carbon\Carbon::parse($r->date_entry)->toDateString() === $dateKey);
                        @endphp
                        <td class="border border-gray-300 p-1 text-center">{{ $report ? number_format($report->acquired_hours, 2) : '0.00' }}</td>
                        @endforeach

                        <td class="border border-gray-300 p-2 text-blue-600 font-bold text-right">{{ number_format($employee->payrollReportsData->sum('overtime'), 2) }}</td>
                        <td class="border border-gray-300 p-2 text-red-600 font-bold text-right">{{ number_format($employee->payrollReportsData->sum('late_undertime'), 2) }}</td>
                        <td class="border border-gray-300 p-2 bg-green-50 font-bold text-gray-900 text-right">0.00</td>

                        @forelse($deductions as $deduction)
                        @php
                        $matchedGovLog = $empGovDeductions->first(fn($log) => $log->gov_deduction_id == $deduction->id);
                        $resolvedGovAmount = 0.00;
                        if ($matchedGovLog) {
                        $masterAmount = $matchedGovLog->govDeduction ? (float) $matchedGovLog->govDeduction->amount : 0;
                        $resolvedGovAmount = ($masterAmount > 0) ? $masterAmount : (float) $matchedGovLog->amount;
                        }
                        @endphp
                        <td class="border border-gray-300 p-1 text-right font-mono text-red-700 bg-red-50/10">
                            {{ number_format($resolvedGovAmount, 2) }}
                        </td>
                        @empty
                        <td class="border border-gray-300 p-1 bg-gray-50 text-gray-400 italic text-center">No deductions set</td>
                        @endforelse
                        <td class="border border-gray-300 p-1 text-right">
                            {{ number_format($employee->otherdeductionData->sum('amount'), 2) }}
                        </td>
                        <td class="border border-gray-300 p-1 text-right">
                            {{ number_format($employee->adjustmentData->sum('amount'), 2) }}
                        </td>
                        <td class="border border-gray-300 p-1 text-right">0.00</td>
                        <td class="border border-gray-300 p-1 text-right">0.00</td>
                        <td class="border border-gray-300 p-2 bg-green-100 font-bold text-green-900 text-right">0.00</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div id="payrollDetailModal" class="hidden fixed inset-0 z-50 bg-black/60 items-center justify-center p-4 transition-all"
        data-lookup-adjustments="{{ json_encode(\App\Models\Category::query()->where('cat', 'ADJUSTMENT')->where('status', true)->get(['id', 'name'])->toArray()) }}"
        data-lookup-gov="{{ json_encode(\App\Models\GovDeduction::query()->get(['id', 'title', 'amount'])->toArray()) }}"
        data-lookup-other="{{ json_encode(\App\Models\OtherDeduction::query()->get(['id', 'title'])->toArray()) }}">

        <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl max-h-[92vh] flex flex-col overflow-hidden">
            <div class="bg-gray-900 text-white p-4 flex justify-between items-center shrink-0">
                <div>
                    <h3 class="text-md font-bold tracking-wide"><span id="m_box_empname">-- Loading Ledger --</span></h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Employee ID : <span id="m_box_empid" class="font-mono">--</span>
                        | Project Assignment: <span id="m_box_empproject">--</span>
                        | Skill : <span id="m_box_empskill" class="font-mono">--</span> </p>
                </div>
                <button type="button" onclick="closePayrollDetailModal()" class="text-gray-400 hover:text-white font-bold text-2xl px-2">&times;</button>
            </div>

            <form action="{{ route('payroll.update-batch') }}" method="POST" id="modal_form_engine" class="flex flex-col flex-1 overflow-hidden">
                @csrf
                <input type="hidden" name="employee_id" id="modal_form_employee_id" value="">
                <input type="hidden" name="period_code" value="{{ $period->code }}">

                <div class="p-5 overflow-y-auto flex-1 space-y-6 bg-gray-50/50">

                    <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                        <h4 class="text-xs font-bold uppercase text-gray-500 tracking-wider mb-2 border-b pb-1">Adjust Cutoff Daily Work Sheets</h4>

                        <div class="max-h-100 overflow-y-auto border border-gray-200 rounded"
                            id="timesheet_table_wrapper"
                            data-lookup-holidays="{{ json_encode($holidays->toArray()) }}">

                            <table class="w-full text-left text-[11px] border-collapse">
                                <thead class="bg-gray-100 sticky top-0 border-b border-gray-200 text-gray-600 font-semibold z-10">
                                    <tr>
                                        <th class="p-2">Date Frame</th>
                                        <th class="p-1">Pay Type</th>
                                        <th class="p-1">Pay Cat</th>
                                        <th class="p-1">Time In</th>
                                        <th class="p-1">Break Out</th>
                                        <th class="p-1">Break In</th>
                                        <th class="p-1">Time Out</th>
                                        <th class="p-1 text-center">Reg Hours</th>
                                        <th class="p-1 text-center text-blue-600">OT Hours</th>
                                        <th class="p-1 text-center text-red-600">Late/UT</th>
                                    </tr>
                                </thead>
                                <tbody id="m_table_timesheet" class="divide-y divide-gray-100 font-mono">
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                        <div class="bg-white border border-gray-200 rounded-lg p-3 flex flex-col shadow-sm">
                            <div class="flex items-center justify-between border-b pb-2 mb-2">
                                <h4 class="text-xs font-bold uppercase text-amber-600 tracking-wider">Adjustments</h4>
                                <button type="button" onclick="addDynamicRow('adjustments')" class="px-2 py-0.5 bg-amber-500 hover:bg-amber-600 text-white rounded text-[10px] font-semibold transition-colors">+ Add New</button>
                            </div>
                            <div class="overflow-y-auto flex-1 max-h-48">
                                <table class="w-full text-[11px]">
                                    <tbody id="m_list_adjustments"></tbody>
                                </table>
                            </div>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-lg p-3 flex flex-col shadow-sm">
                            <div class="flex items-center justify-between border-b pb-2 mb-2">
                                <h4 class="text-xs font-bold uppercase text-emerald-600 tracking-wider">Mandatory Deductions</h4>
                                <button type="button" onclick="addDynamicRow('gov_deductions')" class="px-2 py-0.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded text-[10px] font-semibold transition-colors">+ Add New</button>
                            </div>
                            <div class="overflow-y-auto flex-1 max-h-48">
                                <table class="w-full text-[11px]">
                                    <tbody id="m_list_govdeductions"></tbody>
                                </table>
                            </div>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-lg p-3 flex flex-col shadow-sm">
                            <div class="flex items-center justify-between border-b pb-2 mb-2">
                                <h4 class="text-xs font-bold uppercase text-purple-600 tracking-wider">Other Deductions</h4>
                                <button type="button" onclick="addDynamicRow('other_deductions')" class="px-2 py-0.5 bg-purple-500 hover:bg-purple-600 text-white rounded text-[10px] font-semibold transition-colors">+ Add New</button>
                            </div>
                            <div class="overflow-y-auto flex-1 max-h-48">
                                <table class="w-full text-[11px]">
                                    <tbody id="m_list_otherdeductions"></tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="bg-gray-100 p-3 border-t border-gray-200 flex justify-between items-center shrink-0">
                    <button type="button" onclick="closePayrollDetailModal()" class="px-3 py-1.5 bg-gray-500 hover:bg-gray-600 text-white text-xs font-medium rounded shadow transition-colors">Close</button>
                    <button type="submit" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded shadow transition-all">Commit Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let systemLookups = {
            adjustments: [],
            gov_deductions: [],
            other_deductions: []
        };

        // Safely closes the modal view space instead of closing down the browser tab execution layer
        function handleCustomClose() {
            window.closePayrollDetailModal();
        }

        window.closePayrollDetailModal = function() {
            const modal = document.getElementById('payrollDetailModal');
            if (modal) {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }
        };

        function convertTo24HourTime(t) {
            if (!t || t === '---') return '';
            t = t.trim().toUpperCase();
            const pm = t.includes('PM'),
                am = t.includes('AM');
            let clean = t.replace(/[^\d:]/g, '').split(':');
            let h = parseInt(clean[0], 10),
                m = clean[1] ? parseInt(clean[1], 10) : 0;
            if (isNaN(h)) return '';
            if (pm && h < 12) h += 12;
            if (am && h === 12) h = 0;
            return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
        }

        function handleLookupAmountChange(selectElement, type) {
            const selectedId = selectElement.value;
            const row = selectElement.closest('tr');
            if (!row) return;

            const amountInput = row.querySelector('input[type="number"]');
            if (!amountInput) return;

            if (!selectedId) {
                amountInput.value = "0.00";
                return;
            }

            const dataset = systemLookups[type] || [];
            const matchedItem = dataset.find(item => String(item.id) === String(selectedId));

            if (matchedItem && matchedItem.amount !== undefined) {
                const standardAmount = parseFloat(matchedItem.amount || 0);
                amountInput.value = standardAmount.toFixed(2);
            } else {
                amountInput.value = "0.00";
            }
        }

        // UPDATED: Accepts a 6th parameter 'lookupId' to capture the true foreign key reference for existing records
        function createDynamicRowHtml(type, itemId, nameOrTitle, amountVal, isNew = false, lookupId = null) {
            // FIXED: Force new items to use the exact same array parameter group name as existing elements
            const groupKey = type;

            const lookupField = type === 'adjustments' ? 'adjustment_id' : (type === 'gov_deductions' ? 'gov_deduction_id' : 'other_deduction_id');
            const focusClass = type === 'adjustments' ? 'focus:ring-amber-500' : (type === 'gov_deductions' ? 'focus:ring-emerald-500' : 'focus:ring-purple-500');

            let descriptiveColumn = '';

            if (isNew) {
                const lookupDataset = systemLookups[type] || [];
                let options = lookupDataset.map(opt => {
                    const label = opt.name || opt.title;
                    return `<option value="${opt.id}">${label}</option>`;
                }).join('');

                if (options === '') {
                    options = `<option value="">-- No Options Configured --</option>`;
                }

                descriptiveColumn = `
                <select name="${groupKey}[${itemId}][${lookupField}]" 
                        onchange="handleLookupAmountChange(this, '${type}')" 
                        class="w-full p-1 border border-gray-300 rounded text-[11px] bg-white text-gray-800" required>
                    <option value="">-- Select --</option>
                    ${options}
                </select>
            `;
            } else {
                // FIXED: Uses the actual relational dictionary ID (lookupId) instead of fallback layout ID
                const finalLookupVal = lookupId ? lookupId : itemId;

                descriptiveColumn = `
                <span class="text-gray-700 font-medium">${nameOrTitle}</span>
                <input type="hidden" name="${groupKey}[${itemId}][id]" value="${itemId}">
                <input type="hidden" name="${groupKey}[${itemId}][${lookupField}]" value="${finalLookupVal}">
            `;
            }

            return `
            <tr class="group/row d-row transition-all border-b border-gray-100 last:border-none">
                <td class="py-2 pr-2 align-middle">${descriptiveColumn}</td>
                <td class="py-2 text-right w-24">
                    <input type="number" step="0.01" min="0" name="${groupKey}[${itemId}][amount]" value="${parseFloat(amountVal || 0).toFixed(2)}"
                        class="w-20 p-1 text-right font-bold border border-gray-300 bg-white rounded text-[11px] text-gray-900 focus:outline-none focus:ring-1 ${focusClass}">
                </td>
                <td class="py-2 pl-2 text-center w-6">
                    <button type="button" onclick="removeDynamicRow(this)" class="text-red-500 hover:text-red-700 font-bold text-sm" title="Drop entry line">&times;</button>
                </td>
            </tr>
        `;
        }

        function addDynamicRow(type) {
            let normalizedType = type;
            if (type === 'gov_ded_types' || type === 'gov_deduction' || type === 'gov_deductions') {
                normalizedType = 'gov_deductions';
            }

            const containerMapping = {
                'adjustments': 'm_list_adjustments',
                'gov_deductions': 'm_list_govdeductions',
                'other_deductions': 'm_list_otherdeductions'
            };

            const containerId = containerMapping[normalizedType];
            const targetTable = document.getElementById(containerId);

            if (!targetTable) return;

            if (targetTable.querySelector('td.italic') || targetTable.innerText.includes('No') || targetTable.innerText.includes('empty')) {
                targetTable.innerHTML = '';
            }

            const temporaryId = 'new_' + Date.now() + Math.floor(Math.random() * 100);
            const rowHtml = createDynamicRowHtml(normalizedType, temporaryId, '', 0.00, true);

            targetTable.insertAdjacentHTML('beforeend', rowHtml);
        }

        function removeDynamicRow(button) {
            const row = button.closest('tr');
            const tbody = row.parentNode;
            row.remove();

            if (tbody.children.length === 0) {
                tbody.innerHTML = `<tr><td colspan="3" class="py-2 text-gray-400 italic text-center">Entry list empty</td></tr>`;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const modalEl = document.getElementById('payrollDetailModal');
            if (modalEl) {
                systemLookups.adjustments = JSON.parse(modalEl.getAttribute('data-lookup-adjustments') || '[]');
                systemLookups.gov_deductions = JSON.parse(modalEl.getAttribute('data-lookup-gov') || '[]');
                systemLookups.other_deductions = JSON.parse(modalEl.getAttribute('data-lookup-other') || '[]');
            }

            document.addEventListener('click', function(event) {
                const trigger = event.target.closest('.payroll-modal-trigger');
                if (!trigger) return;

                const empId = trigger.getAttribute('data-id');
                const empSkill = trigger.getAttribute('data-skill');
                const empName = trigger.getAttribute('data-name');
                const project = trigger.getAttribute('data-project');

                const timesheets = JSON.parse(trigger.getAttribute('data-timesheets') || '{}');
                const adjustments = JSON.parse(trigger.getAttribute('data-adjustments') || '[]');
                const govDeductions = JSON.parse(trigger.getAttribute('data-govdeductions') || '[]');
                const otherDeductions = JSON.parse(trigger.getAttribute('data-otherdeductions') || '[]');

                if (!modalEl) return;

                document.getElementById('modal_form_employee_id').value = empId;
                document.getElementById('m_box_empid').innerText = empId;

                const skillBox = document.getElementById('m_box_empskill');
                if (skillBox) {
                    skillBox.tagName === 'INPUT' ? skillBox.value = empSkill : skillBox.innerText = empSkill;
                }

                document.getElementById('m_box_empname').innerText = empName;
                document.getElementById('m_box_empproject').innerText = project;

                // Extract reference holiday setup properties from background wrappers
                const tsWrapper = document.getElementById('timesheet_table_wrapper');
                const holidayMaster = tsWrapper ? JSON.parse(tsWrapper.getAttribute('data-lookup-holidays') || '[]') : [];

                // Bind and Load Cutoff Timesheet Logs Matrix
                const tsBody = document.getElementById('m_table_timesheet');
                tsBody.innerHTML = '';

                Object.entries(timesheets).forEach(([dateKey, log]) => {
                    console.log(log.cat_id);
                    let holidayOptions = '';

                    // Find the default/regular holiday ID (usually the one with 0% percentage or no cat_id)
                    let defaultHolidayId = '';
                    holidayMaster.forEach(holiday => {
                        if (parseFloat(holiday.percentage) === 0) {
                            defaultHolidayId = holiday.id;
                        }
                        const isSelected = (log.cat_id == holiday.id || (!log.cat_id && parseFloat(holiday.percentage) === 0));
                        holidayOptions += `
                            <option value="${holiday.id}" data-rate="${holiday.percentage}" ${isSelected ? 'selected' : ''}>
                                ${holiday.type} (${parseInt(holiday.percentage)}%)
                            </option>`;
                    });

                    // Pre-calculate clean strings for your backup values
                    const originalHolidayId = log.cat_id || defaultHolidayId;
                    const originalTimeIn = convertTo24HourTime(log.time_in);
                    const originalBreakOut = convertTo24HourTime(log.break_out);
                    const originalBreakIn = convertTo24HourTime(log.break_in);
                    const originalTimeOut = convertTo24HourTime(log.time_out);
                    const originalRegHours = parseFloat(log.acquired_hours || 0).toFixed(2);
                    const originalOtHours = parseFloat(log.overtime || 0) > 0 ? parseFloat(log.overtime).toFixed(2) : '0.00';
                    const originalLateHours = parseFloat(log.late_undertime || 0) > 0 ? parseFloat(log.late_undertime).toFixed(2) : '0.00';

                    const tr = document.createElement('tr');
                    tr.className = "hover:bg-gray-50 text-gray-700 timesheet-row border-b border-gray-100";
                    tr.dataset.date = dateKey;
                    tr.innerHTML = `
                        <td class="p-2 text-left font-semibold text-gray-900 bg-gray-50 whitespace-nowrap">${dateKey}</td>
                        <td class="p-1">
                            <select name="timesheet[${dateKey}][holiday_id]" 
                                    data-backup="${originalHolidayId}"
                                    ${log.paytype === 'A' ? 'disabled' : ''}
                                    class="bg-gray-50 border border-gray-300 rounded text-[10px] p-0.5 w-28 font-sans focus:ring-1 focus:ring-blue-500 disabled:bg-gray-100">
                                ${holidayOptions}
                            </select>
                        </td>
                        <td class="p-1">
                            <select name="timesheet[${dateKey}][pay_cat]" 
                                    onchange="handlePayCatChange(this)"
                                    class="bg-gray-50 border border-gray-300 rounded text-[10px] p-0.5 w-24 font-sans focus:ring-1 focus:ring-blue-500 font-medium">
                                <option value="R" ${log.paytype === 'R' || !log.paytype ? 'selected' : ''}>REGULAR</option>
                                <option value="A" ${log.paytype === 'A' ? 'selected' : ''}>ABSENT</option>
                                <option value="N" ${log.paytype === 'N' ? 'selected' : ''}>NOT-INCLUDED</option>
                            </select>
                        </td>
                        <td class="p-1">
                            <input type="time" name="timesheet[${dateKey}][time_in]" 
                                data-backup="${originalTimeIn}"
                                value="${log.paytype === 'A' ? '' : originalTimeIn}" 
                                ${log.paytype === 'A' ? 'disabled' : ''} 
                                class="w-full p-1 border border-gray-300 rounded text-[11px] disabled:bg-gray-100">
                        </td>
                        <td class="p-1">
                            <input type="time" name="timesheet[${dateKey}][break_out]" 
                                data-backup="${originalBreakOut}"
                                value="${log.paytype === 'A' ? '' : originalBreakOut}" 
                                ${log.paytype === 'A' ? 'disabled' : ''} 
                                class="w-full p-1 border border-gray-300 rounded text-[11px] disabled:bg-gray-100">
                        </td>
                        <td class="p-1">
                            <input type="time" name="timesheet[${dateKey}][break_in]" 
                                data-backup="${originalBreakIn}"
                                value="${log.paytype === 'A' ? '' : originalBreakIn}" 
                                ${log.paytype === 'A' ? 'disabled' : ''} 
                                class="w-full p-1 border border-gray-300 rounded text-[11px] disabled:bg-gray-100">
                        </td>
                        <td class="p-1">
                            <input type="time" name="timesheet[${dateKey}][time_out]" 
                                data-backup="${originalTimeOut}"
                                value="${log.paytype === 'A' ? '' : originalTimeOut}" 
                                ${log.paytype === 'A' ? 'disabled' : ''} 
                                class="w-full p-1 border border-gray-300 rounded text-[11px] disabled:bg-gray-100">
                        </td>
                        <td class="p-1">
                            <input type="number" step="0.01" name="timesheet[${dateKey}][regular_hours]" 
                                data-backup="${originalRegHours}"
                                value="${log.paytype === 'A' ? '0.00' : originalRegHours}" 
                                ${log.paytype === 'A' ? 'disabled' : ''} 
                                class="w-16 p-1 text-right border border-gray-300 rounded text-[11px] disabled:bg-gray-100">
                        </td>
                        <td class="p-1">
                            <input type="number" step="0.01" name="timesheet[${dateKey}][overtime_hours]" 
                                data-backup="${originalOtHours}"
                                value="${log.paytype === 'A' ? '0.00' : originalOtHours}" 
                                ${log.paytype === 'A' ? 'disabled' : ''} 
                                class="w-16 p-1 text-right border border-blue-200 rounded text-blue-600 text-[11px] disabled:bg-gray-100">
                        </td>
                        <td class="p-1">
                            <input type="number" step="0.01" name="timesheet[${dateKey}][late_undertime_hours]" 
                                data-backup="${originalLateHours}"
                                value="${log.paytype === 'A' ? '0.00' : originalLateHours}" 
                                ${log.paytype === 'A' ? 'disabled' : ''} 
                                class="w-16 p-1 text-right border border-red-200 rounded text-red-600 text-[11px] disabled:bg-gray-100">
                        </td>
                        `;
                    tsBody.appendChild(tr);
                });
                // FIXED: Loops for mapping existing records extract and forward their true master table relational foreign keys 

                // 1. Bind Adjustment Fields
                const adjBody = document.getElementById('m_list_adjustments');
                adjBody.innerHTML = adjustments.length === 0 ? '<tr><td class="py-2 text-gray-400 italic text-center">No adjustments recorded</td></tr>' : '';
                adjustments.forEach(item => {
                    const name = item.adjustment_name ? item.adjustment_name.name : 'Adjustment Entry';
                    const lookupId = item.adjustment_id;
                    adjBody.insertAdjacentHTML('beforeend', createDynamicRowHtml('adjustments', item.id, name, item.amount, false, lookupId));
                });

                // 2. Bind Statutory Contribution logs
                const govBody = document.getElementById('m_list_govdeductions');
                govBody.innerHTML = govDeductions.length === 0 ? '<tr><td class="py-2 text-gray-400 italic text-center">No collections itemized</td></tr>' : '';
                govDeductions.forEach(item => {
                    const title = item.gov_deduction ? item.gov_deduction.title : 'Statutory Deduction';
                    let masterAmount = item.gov_deduction ? parseFloat(item.gov_deduction.amount || 0) : 0;
                    let fineTunedAmount = (masterAmount > 0) ? masterAmount : parseFloat(item.amount || 0);
                    const lookupId = item.gov_deduction_id;
                    govBody.insertAdjacentHTML('beforeend', createDynamicRowHtml('gov_deductions', item.id, title, fineTunedAmount, false, lookupId));
                });

                // 3. Bind Custom Miscellaneous Loans fields
                const othBody = document.getElementById('m_list_otherdeductions');
                othBody.innerHTML = otherDeductions.length === 0 ? '<tr><td class="py-2 text-gray-400 italic text-center">No loans recorded</td></tr>' : '';
                otherDeductions.forEach(item => {
                    const title = item.other_deduction ? item.other_deduction.title : 'Company Deduction';
                    const lookupId = item.other_deduction_id;
                    othBody.insertAdjacentHTML('beforeend', createDynamicRowHtml('other_deductions', item.id, title, item.amount, false, lookupId));
                });

                modalEl.classList.remove('hidden');
                modalEl.classList.add('flex');
            });
        });

        // Call this function inside your JavaScript form submission catch/then blocks:
        function showModalAlert(type, messageOrArray) {
            const alertBox = document.getElementById('modal_js_alerts');
            if (!alertBox) return;

            alertBox.innerHTML = ''; // Reset
            alertBox.classList.remove('hidden');

            if (type === 'success') {
                alertBox.className = "mb-4 px-4";
                alertBox.innerHTML = `
            <div class="bg-emerald-50 border-l-4 border-emerald-500 p-3 rounded text-emerald-800 text-xs font-medium flex items-center justify-between">
                <span>${messageOrArray}</span>
                <button type="button" onclick="this.parentElement.parentElement.classList.add('hidden')" class="font-bold">&times;</button>
            </div>`;
            } else if (type === 'error') {
                alertBox.className = "mb-4 px-4";

                let errorsList = '';
                if (Array.isArray(messageOrArray)) {
                    errorsList = `<ul class="list-disc pl-5 mt-1 space-y-0.5 font-medium">${messageOrArray.map(e => `<li>${e}</li>`).join('')}</ul>`;
                } else {
                    errorsList = `<p class="font-medium mt-0.5">${messageOrArray}</p>`;
                }

                alertBox.innerHTML = `
            <div class="bg-red-50 border-l-4 border-red-500 p-3 rounded text-red-800 text-xs">
                <span class="font-bold">Execution Error Encountered:</span>
                ${errorsList}
            </div>`;
            }
        }

        function handlePayCatChange(selectElement) {
            const row = selectElement.closest('tr');
            if (!row) return;
            // Targets both inputs and dropdowns inside the row
            const elements = row.querySelectorAll('input, select');
            if (selectElement.value === 'A') {
                // Turning to ABSENT: Disable and clear
                elements.forEach(el => {
                    // Skip the current active dropdown so the user can change it back later
                    if (el === selectElement) return;

                    if (el.tagName === 'SELECT') {
                        // For holiday select, you can default it to your basic selection or just leave it empty
                        el.value = el.dataset.backup || el.options[0].value;
                    } else {
                        el.value = el.type === 'number' ? '0.00' : '';
                    }
                    el.disabled = true;
                });
            } else {
                // Turning back to REGULAR/Other: Re-enable and restore backup data
                elements.forEach(el => {
                    el.disabled = false;

                    // Extract and restore data from the data-backup attribute
                    if (el.dataset.backup !== undefined) {
                        el.value = el.dataset.backup;
                    }
                });
            }
        }

        function validateInput(inputElement) {
            const row = inputElement.closest('tr');
            if (!row) return;

            // Check the current status of the Pay Category dropdown in this row
            const payCatSelect = row.querySelector('select[name*="[pay_cat]"]');
            const isAbsent = payCatSelect && payCatSelect.value === 'A';

            // If it's not Absent and the user left the input completely blank
            if (!isAbsent && !inputElement.value) {
                // Add visual indicator (Red border)
                inputElement.classList.add('border-red-500', 'bg-red-50');
                inputElement.classList.remove('border-gray-300');

                // Human-readable field name from the name attribute (e.g., "break_out")
                const fieldName = inputElement.name.split('[').pop().replace(']', '').replace('_', ' ').toUpperCase();
                const dateKey = row.dataset.date || '';

                alert(`The field "${fieldName}" for ${dateKey} is required when status is REGULAR.`);

                // Optional: Put focus back onto the empty field
                setTimeout(() => inputElement.focus(), 10);
            } else {
                // Clean up red highlights if they fix it or if row is absent
                inputElement.classList.remove('border-red-500', 'bg-red-50');
                inputElement.classList.add('border-gray-300');
            }
        }
    </script>
</body>

</html>