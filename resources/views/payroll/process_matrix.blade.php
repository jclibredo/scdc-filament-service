<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Processing Matrix</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>

<body class="bg-gray-100 p-3 sm:p-6 text-xs font-sans antialiased">
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

        <div class="overflow-x-auto custom-scrollbar w-full relative">
            <table class="w-full border-collapse border border-gray-300 text-center min-w-max isolate">
                <thead>
                    <tr class="bg-gray-50 text-gray-700 font-bold text-[10px]">
                        <th rowspan="2" class="sticky left-0 z-30 bg-gray-50 border border-gray-300 p-2 text-left shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] min-w-[240px]">Employee Name</th>
                        <th rowspan="2" class="sticky left-[240px] z-30 bg-gray-50 border border-gray-300 p-2 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] min-w-[100px]">Project</th>
                        <th colspan="{{ $earningsCategories->count() }}" class="border border-gray-300 p-1 bg-emerald-50 text-emerald-900 tracking-wider">EARNINGS CATEGORIES</th>
                        <th colspan="{{ count($periodDates) }}" class="border border-gray-300 p-1 bg-amber-100 text-amber-900 tracking-wider">Attendance Logs (Daily View Hours)</th>
                        <th rowspan="2" class="border border-gray-300 p-2 text-red-600 bg-gray-50">OT</th>
                        <th rowspan="2" class="border border-gray-300 p-2 text-red-600 bg-gray-50">Late/Under.</th>
                        <th rowspan="2" class="border border-gray-300 p-2 bg-green-50 text-green-900">Total Earnings</th>
                        <th colspan="{{ max($deductions->count(), 1) }}" class="border border-gray-300 p-1 bg-red-50 text-red-900 tracking-wider">DEDUCTIONS</th>
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
                        $empAdjustments = $adjustments ?? collect(); 
                        $empOtherDeductions = $otherDeductions ?? collect();
                        $empGovDeductions = $govDeductions->get($employee->employeeid, collect());
                        $empTimesheetRecords = $employeeTimesheets[$employee->employeeid] ?? [];
                    @endphp
                    <tr class="hover:bg-gray-50 text-gray-800 text-[10px] group transition-colors">
                        <td class="sticky left-0 z-20 bg-white group-hover:bg-gray-50 border border-gray-300 p-2 text-left font-sans font-medium shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] transition-colors">
                            <div class="flex items-center justify-between gap-2">
                                <span class="truncate">
                                    {{ strtoupper($employee->lastname) }}, {{ strtoupper($employee->firstname) }}
                                </span>
                                <div class="flex items-center gap-1 shrink-0 no-print">
                                    <button type="button" 
                                        onclick="openPayrollDetailModal(
                                            '{{ $employee->employeeid }}', 
                                            '{{ strtoupper($employee->lastname) }}, {{ strtoupper($employee->firstname) }}',
                                            '{{ $employee->project->name ?? '--' }}',
                                            {{ json_encode($empTimesheetRecords) }},
                                            {{ json_encode($empAdjustments) }},
                                            {{ json_encode($empGovDeductions) }},
                                            {{ json_encode($empOtherDeductions) }}
                                        )"
                                        class="cursor-pointer px-1.5 py-0.5 border border-blue-200 hover:border-blue-400 rounded bg-blue-50 hover:bg-blue-100 text-blue-600 font-bold transition-all text-[10px]">
                                        📝 Open Modal
                                    </button>
                                </div>
                            </div>
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

                        @foreach($employee->payrollReportsData as $report)
                        @php $dateKey = $report->date_entry->toDateString(); $dayData = $empTimesheetRecords[$dateKey] ?? ['display' => '0.00', 'class' => 'bg-yellow-50 text-amber-700 font-bold text-center']; @endphp
                        <td class="border border-gray-300 p-1 {{ $dayData['class'] }}">{{ $report->acquired_hours }}</td>
                        @endforeach

                        <td class="border border-gray-300 p-2 text-blue-600 font-bold text-right">{{ number_format($employee->payrollReportsData->sum('overtime'), 2) }}</td>
                        <td class="border border-gray-300 p-2 text-red-600 font-bold text-right">{{ number_format($employee->payrollReportsData->sum('late_undertime'), 2) }}</td>
                        <td class="border border-gray-300 p-2 bg-green-50 font-bold text-gray-900 text-right">2,550.00</td>

                        @forelse($deductions as $deduction)
                        <td class="border border-gray-300 p-1 text-right">{{ $deduction->amount ? number_format($deduction->amount, 2) : '0.00' }}</td>
                        @empty <td class="border border-gray-300 p-1 bg-gray-50">--</td> @endforelse

                        <td class="border border-gray-300 p-1 text-right">0.00</td>
                        <td class="border border-gray-300 p-1 text-right">0.00</td>
                        <td class="border border-gray-300 p-1 text-right">2,550.00</td>
                        <td class="border border-gray-300 p-2 bg-green-100 font-bold text-green-900 text-right">2,550.00</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div id="payrollDetailModal" class="hidden fixed inset-0 z-[9999] bg-black/50 items-center justify-center p-4">
        <form action="{{ route('attendance-logs.update-batch') }}" method="POST" class="bg-white rounded-lg w-full max-w-5xl shadow-2xl border border-gray-200 font-sans flex flex-col max-h-[90vh]">
            @csrf
            @method('PUT')
            
            <input type="hidden" id="modal_form_employee_id" name="employee_id" value="">

            <div class="p-4 bg-gray-900 text-white flex justify-between items-center rounded-t-lg shrink-0">
                <div>
                    <h3 class="text-sm font-bold tracking-wide uppercase">Edit Employee Payroll & Timesheet Logs</h3>
                    <p class="text-[11px] text-gray-300 mt-0.5">
                        Date Covered: <span class="font-mono font-bold text-amber-300">{{ \Carbon\Carbon::parse($period->datefrom)->format('M d, Y') }} — {{ \Carbon\Carbon::parse($period->dateto)->format('M d, Y') }}</span>
                    </p>
                </div>
                <button type="button" onclick="closePayrollDetailModal();" class="text-xl text-gray-400 hover:text-white font-bold p-1 transition-colors">&times;</button>
            </div>

            <div class="p-5 overflow-y-auto space-y-6 text-xs text-gray-700">
                
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 bg-gray-50 p-3 rounded border border-gray-200">
                    <div>
                        <span class="block text-[10px] uppercase font-bold text-gray-400">Employee Identification</span>
                        <strong id="m_box_empid" class="text-gray-900 font-mono">--</strong>
                    </div>
                    <div>
                        <span class="block text-[10px] uppercase font-bold text-gray-400">Full Name</span>
                        <strong id="m_box_empname" class="text-gray-900 uppercase">--</strong>
                    </div>
                    <div>
                        <span class="block text-[10px] uppercase font-bold text-gray-400">Assigned Operational Project</span>
                        <strong id="m_box_empproject" class="text-gray-900">--</strong>
                    </div>
                </div>

                <div>
                    <h4 class="font-bold text-gray-800 border-b pb-1 mb-2 uppercase tracking-wider text-[11px] text-blue-600">Timesheet & Attendance Logs (Time Input Only View)</h4>
                    <div class="overflow-x-auto border border-gray-200 rounded">
                        <table class="w-full text-center border-collapse text-[11px]">
                            <thead>
                                <tr class="bg-gray-100 text-gray-600 font-bold border-b border-gray-200">
                                    <th class="p-2 border-r text-left w-28">Date</th>
                                    <th class="p-2 border-r min-w-[110px]">Time In</th>
                                    <th class="p-2 border-r min-w-[110px]">Break Out</th>
                                    <th class="p-2 border-r min-w-[110px]">Break In</th>
                                    <th class="p-2 border-r min-w-[110px]">Time Out</th>
                                    <th class="p-2 text-right w-20">Hrs Data</th>
                                </tr>
                            </thead>
                            <tbody id="m_table_timesheet" class="divide-y divide-gray-200 font-mono">
                                </tbody>
                        </table>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="border border-gray-200 rounded p-3 bg-white shadow-sm">
                        <h5 class="font-bold text-gray-900 border-b pb-1.5 mb-2 uppercase text-[10px] tracking-wide text-amber-600">Adjustments Ledger</h5>
                        <table class="w-full text-left font-mono text-[11px]">
                            <tbody id="m_list_adjustments" class="divide-y divide-gray-100"></tbody>
                        </table>
                    </div>

                    <div class="border border-gray-200 rounded p-3 bg-white shadow-sm">
                        <h5 class="font-bold text-gray-900 border-b pb-1.5 mb-2 uppercase text-[10px] tracking-wide text-red-600">Govt Deductions</h5>
                        <table class="w-full text-left font-mono text-[11px]">
                            <tbody id="m_list_govdeductions" class="divide-y divide-gray-100"></tbody>
                        </table>
                    </div>

                    <div class="border border-gray-200 rounded p-3 bg-white shadow-sm">
                        <h5 class="font-bold text-gray-900 border-b pb-1.5 mb-2 uppercase text-[10px] tracking-wide text-purple-600">Other Deductions</h5>
                        <table class="w-full text-left font-mono text-[11px]">
                            <tbody id="m_list_otherdeductions" class="divide-y divide-gray-100"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="p-3 bg-gray-50 border-t border-gray-200 flex justify-end gap-2 rounded-b-lg shrink-0">
                <button type="button" onclick="closePayrollDetailModal();" class="px-4 py-2 border border-gray-300 hover:bg-gray-100 rounded text-xs font-semibold tracking-wide transition-colors">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-bold shadow transition-colors">Save Structural Logs</button>
            </div>
        </form>
    </div>

    <script>
        function handleCustomClose() {
            if (confirm("Close matrix layout safely?")) { window.close(); }
        }

        window.openPayrollDetailModal = function(empId, empName, project, timesheets, adjustments, govDeductions, otherDeductions) {
            const modal = document.getElementById('payrollDetailModal');
            if (!modal) return;

            document.getElementById('modal_form_employee_id').value = empId;
            document.getElementById('m_box_empid').innerText = empId;
            document.getElementById('m_box_empname').innerText = empName;
            document.getElementById('m_box_empproject').innerText = project;

            const tsBody = document.getElementById('m_table_timesheet');
            tsBody.innerHTML = ''; 
            
            // Helper function to extract 24-hour HH:MM strings exactly as needed for native <input type="time"> fields
            function convertTo24HourTime(timeString) {
                if (!timeString || timeString === '---') return '';
                
                timeString = timeString.trim().toUpperCase();
                const hasAM = timeString.includes('AM');
                const hasPM = timeString.includes('PM');
                let cleanTime = timeString.replace(/[^\d:]/g, '');
                let [hours, minutes] = cleanTime.split(':');
                
                hours = parseInt(hours, 10);
                if (isNaN(hours) || isNaN(parseInt(minutes, 10))) return '';
                
                if (hasPM && hours < 12) hours += 12;
                if (hasAM && hours === 12) hours = 0;
                
                return String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');
            }

            const entries = Object.entries(timesheets);
            if(entries.length === 0) {
                tsBody.innerHTML = `<tr><td colspan="6" class="p-4 text-center text-gray-400 italic bg-gray-50">No structural tracking histories logs found.</td></tr>`;
            } else {
                entries.forEach(([dateKey, data]) => {
                    const tr = document.createElement('tr');
                    tr.className = "hover:bg-gray-50 text-gray-700";

                    // Extract only the HH:MM time strings
                    const timeInValue = convertTo24HourTime(data.time_in);
                    const breakOutValue = convertTo24HourTime(data.break_out);
                    const breakInValue = convertTo24HourTime(data.break_in);
                    const timeOutValue = convertTo24HourTime(data.time_out);

                    tr.innerHTML = `
                        <td class="p-2 border-r font-sans text-left font-semibold text-gray-900 bg-gray-50">${dateKey}</td>
                        <td class="p-1 border-r">
                            <input type="time" name="timesheet[${dateKey}][time_in]" value="${timeInValue}" 
                                class="w-full p-1 border border-gray-300 rounded text-[11px] bg-white text-gray-800 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        </td>
                        <td class="p-1 border-r">
                            <input type="time" name="timesheet[${dateKey}][break_out]" value="${breakOutValue}" 
                                class="w-full p-1 border border-gray-300 rounded text-[11px] bg-white text-gray-800 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        </td>
                        <td class="p-1 border-r">
                            <input type="time" name="timesheet[${dateKey}][break_in]" value="${breakInValue}" 
                                class="w-full p-1 border border-gray-300 rounded text-[11px] bg-white text-gray-800 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        </td>
                        <td class="p-1 border-r">
                            <input type="time" name="timesheet[${dateKey}][time_out]" value="${timeOutValue}" 
                                class="w-full p-1 border border-gray-300 rounded text-[11px] bg-white text-gray-800 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        </td>
                        <td class="p-2 text-right font-bold ${data.class || ''} bg-gray-50">${data.display ?? '0.00'}</td>
                    `;
                    tsBody.appendChild(tr);
                });
            }

            const formatCurrency = (val) => parseFloat(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            // Render Adjustments
            const adjBody = document.getElementById('m_list_adjustments');
            adjBody.innerHTML = adjustments.length === 0 ? '<tr><td class="py-2 text-gray-400 italic">None recorded</td></tr>' : '';
            adjustments.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td class="py-1.5 text-gray-600">${item.adjustment_name?.name ?? 'Adjustment Entry'}</td><td class="py-1.5 text-right font-bold text-gray-900">${formatCurrency(item.amount)}</td>`;
                adjBody.appendChild(tr);
            });

            // Render Government Deductions
            const govBody = document.getElementById('m_list_govdeductions');
            govBody.innerHTML = govDeductions.length === 0 ? '<tr><td class="py-2 text-gray-400 italic">None recorded</td></tr>' : '';
            govDeductions.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td class="py-1.5 text-gray-600">${item.gov_deduction?.title ?? 'Statutory Def'}</td><td class="py-1.5 text-right font-bold text-gray-900">${formatCurrency(item.amount ?? item.gov_deduction?.amount)}</td>`;
                govBody.appendChild(tr);
            });

            // Render Other Deductions
            const othBody = document.getElementById('m_list_otherdeductions');
            othBody.innerHTML = otherDeductions.length === 0 ? '<tr><td class="py-2 text-gray-400 italic">None recorded</td></tr>' : '';
            otherDeductions.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td class="py-1.5 text-gray-600">${item.other_deduction?.title ?? 'Company Loan'}</td><td class="py-1.5 text-right font-bold text-gray-900">${formatCurrency(item.amount)}</td>`;
                othBody.appendChild(tr);
            });

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        };

        window.closePayrollDetailModal = function() {
            const modal = document.getElementById('payrollDetailModal');
            if (modal) {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }
        };
    </script>
</body>

</html>