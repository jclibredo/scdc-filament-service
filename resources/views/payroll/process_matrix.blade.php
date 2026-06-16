<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Processing Matrix</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 p-6 text-xs font-sans">

    <div class="max-w-full mx-auto bg-white shadow-md rounded-lg overflow-hidden">

        <div class="p-4 bg-amber-50 border-l-4 border-amber-500 flex justify-between items-center">
            <div>
                <h2 class="text-sm font-bold text-gray-800">PAYROLL PROCESSING INTERFACE</h2>
                <p class="text-gray-600">Period: <span class="font-mono font-bold">{{ $period->code }}</span> ({{ \Carbon\Carbon::parse($period->datefrom)->format('M d') }} - {{ \Carbon\Carbon::parse($period->dateto)->format('M d, Y') }})</p>
            </div>
            <button type="button" onclick="window.close()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded shadow-sm transition duration-150">
                Close Tab
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-300 text-center min-w-max">
                <thead>
                    <tr class="bg-gray-50 text-gray-700 font-bold">
                        <th rowspan="2" class="border border-gray-300 p-2 text-left">Employee Name</th>
                        <th rowspan="2" class="border border-gray-300 p-2">Project</th>

                        <th colspan="{{ $earningsCategories->count() }}" class="border border-gray-300 p-1 bg-emerald-50">EARNINGS CATEGORIES</th>

                        <th colspan="{{ count($periodDates) }}" class="border border-gray-300 p-1 bg-amber-100">
                            {{ \Carbon\Carbon::parse($period->datefrom)->format('M d') }} - {{ \Carbon\Carbon::parse($period->dateto)->format('M d, Y') }}
                        </th>
                        <th rowspan="2" class="border border-gray-300 p-2 text-red-600">Overtime</th>
                        <th rowspan="2" class="border border-gray-300 p-2 text-red-600">Late/Undertime</th>
                        <th rowspan="2" class="border border-gray-300 p-2 bg-green-50">Total Earnings</th>

                        <th colspan="{{ max($deductions->count(), 1) }}" class="border border-gray-300 p-1 bg-red-50">DEDUCTIONS</th>

                        <th rowspan="2" class="border border-gray-300 p-2">ADJUSTMENT</th>
                        <th rowspan="2" class="border border-gray-300 p-2">Total Deduction</th>
                        <th rowspan="2" class="border border-gray-300 p-2">Gross Pay</th>
                        <th rowspan="2" class="border border-gray-300 p-2 bg-green-100 font-extrabold text-green-800">Net Pay</th>
                    </tr>
                    <tr class="bg-gray-50 text-gray-600 font-semibold">
                        @foreach($earningsCategories as $category)
                        <th class="border border-gray-300 p-1 px-2 text-xs font-sans">{{ $category->name }}</th>
                        @endforeach

                        @foreach($periodDates as $date)
                        <th class="border border-gray-300 w-10 uppercase">
                            {{ \Carbon\Carbon::parse($date)->shortDayName }} [{{ \Carbon\Carbon::parse($date)->format('d') }}]
                        </th>
                        @endforeach

                        @forelse($deductions as $deduction)
                        <th class="border border-gray-300 p-1 font-sans text-xs">{{ $deduction->title }}</th>
                        @empty
                        <th class="border border-gray-300 p-1 text-gray-400 font-sans italic text-xs">None found</th>
                        @endforelse
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 font-mono">
                    @foreach($employees as $employee)
                    <tr class="hover:bg-gray-50 text-gray-800">
                        <td class="border border-gray-300 p-2 text-left font-sans font-medium">
                            {{ strtoupper($employee->lastname) ?? '' }}, {{ strtoupper($employee->firstname) }} {{ strtoupper($employee->middlename) ?? '' }}
                        </td>
                        <td class="border border-gray-300 p-2 font-sans text-gray-600">
                            {{ $employee->project->name ?? '--' }}
                        </td>

                        @foreach($earningsCategories as $category)
                        @php
                        $matchedEarning = collect($employee->earningsData)
                        ->first(function($earning) use ($category, $employee) {
                        return $earning->title == $category->id
                        && $earning->employee_id == $employee->employeeid;
                        });
                        @endphp
                        <td class="border border-gray-300 p-2 text-right">
                            {{ $matchedEarning ? number_format($matchedEarning->amount, 2) : '0.00' }}
                        </td>
                        @endforeach
                        @foreach($employee->payrollReportsData as $report)
                        @php
                        // Convert the model's date entry into a clean string key (e.g., '2026-06-08')
                        $dateKey = $report->date_entry->toDateString();
                        $dayData = $employeeTimesheets[$employee->employeeid][$dateKey] ?? [
                        'display' => '0.00',
                        'class' => 'bg-yellow-50 text-amber-700 font-bold text-center'
                        ];
                        @endphp
                        <td class="border border-gray-300 p-1 {{ $dayData['class'] }}">
                            {{ $report->acquired_hours }}
                        </td>
                        @endforeach
                        <td class="border border-gray-300 p-2 text-blue-600 font-bold">
                            {{ number_format($employee->payrollReportsData->sum('overtime'), 2) }}
                        </td>
                        <td class="border border-gray-300 p-2 text-red-600 font-bold">
                            {{ number_format($employee->payrollReportsData->sum('late_undertime'), 2) }}
                        </td>
                        <td class="border border-gray-300 p-2 bg-green-50 font-bold text-gray-900">2,550.00</td>
                        @forelse($deductions as $deduction)
                        @php
                        $empDeductions = $govDeductions->get($employee->employeeid, collect());
                        $matchedDed = $empDeductions->first(fn($log) => ($log->govDeduction->name ?? '') === $deduction->title);
                        @endphp
                        <td class="border border-gray-300 p-1 text-right">
                            {{ $deduction->amount ? number_format($deduction->amount, 2) : '0.00' }}
                            <!-- {{ $matchedDed ? number_format($matchedDed->amount, 2) : '0.00' }} -->
                        </td>
                        @empty
                        <td class="border border-gray-300 p-1 bg-gray-50">asdasd</td>
                        @endforelse

                        <td class="border border-gray-300 p-1">0.00</td>
                        <td class="border border-gray-300 p-1">0.00</td>
                        <td class="border border-gray-300 p-1">2,550.00</td>
                        <td class="border border-gray-300 p-2 bg-green-100 font-bold text-green-900">2,550.00</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="p-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
            <button type="button" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded shadow">
                Save & Lock Payroll
            </button>
        </div>
    </div>
    <script>
        // Scenario 1: Captures browser "X" button, page refreshes, or tab closes.
        window.addEventListener('beforeunload', (event) => {
            // Note: Browsers ignore custom text here and show a default generic error message.
            event.preventDefault();
            event.returnValue = '';
        });

        // Scenario 2: Captures when the user clicks your UI "Close Tab" button.
        function handleCustomClose() {
            // Since window.close() doesn't automatically fire 'beforeunload' cleanly across all browsers,
            // we show a standard browser confirm dialog first.
            const confirmLeave = confirm("Are you sure you want to close this matrix? Unsaved payroll entries will be lost.");
            if (confirmLeave) {
                window.close();
            }
        }
    </script>
</body>

</html>