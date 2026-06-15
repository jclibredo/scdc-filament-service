<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Summary - {{ $employee->lastname }}</title>
    <link rel="stylesheet" href="{{ asset('css/payroll-summary.css') }}">
</head>

<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 8px 16px; background: #22c55e; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
            Print Summary
        </button>
    </div>
    <div class="header-section">
        <h1 class="company-name">{{ $companyName }}</h1>
        <h2 class="doc-title">PAYROLL ACCOUNTING SUMMARY (Period: {{ $period_code }})</h2>
    </div>
    <div class="info-grid">
        <div class="info-box">
            <h3>Employee Details</h3>
            <p><strong>ID:</strong> {{ $employee->employeeid }}</p>
            <p><strong>Name:</strong> {{ $employee->lastname }}, {{ $employee->firstname }} {{ $employee->middlename }}</p>
            <p><strong>Type:</strong> {{ $employee->empType->name }}</p>
            <p><strong>Status:</strong> {{ $employee->empStat->name }}</p>
            <p><strong>Hired Date:</strong> {{ \Carbon\Carbon::parse($employee->datehired)->format('M d, Y') }}</p>
            <p><strong>Project:</strong> {{ $employee->project->name }}</p>

        </div>
    </div>

    <h3 style="font-size: 14px; margin-bottom: 6px;">Timesheet & Attendance Logs (Daily View)
        <p style="margin: 2px 0 0 0; font-size: 11px; color: #475569;">
            <strong>Date Covered:</strong> {{ \Carbon\Carbon::parse($datePeriod->datefrom)->format('M d, Y') }}
            — {{ \Carbon\Carbon::parse($datePeriod->dateto)->format('M d, Y') }}
        </p>
    </h3>
    <table id="timesheet-table" style="width: 100%; border-collapse: collapse; font-size: 11px; line-height: 1.2;">
        <thead>
            <tr style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; text-align: center;">
                <th style="padding: 4px; text-align: left; width: 100px;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span>Date</span>
                        <button type="button"
                            onclick="openAdjustmentModal('', '08:00', '17:00')"
                            style="cursor: pointer; padding: 1px 4px; border: 1px solid #3b82f6; border-radius: 3px; background-color: #eff6ff; color: #1d4ed8; font-size: 10px; font-weight: bold; display: inline-flex; align-items: center; gap: 2px;"
                            title="Insert Time Log Pair">
                            ➕ Days
                        </button>
                    </div>
                </th>
                <th style="padding: 4px; text-align: left; width: 120px;">Pay Type</th>
                <th style="padding: 4px; width: 65px;">Time In</th>
                <th style="padding: 4px; width: 65px;">Break Out</th>
                <th style="padding: 4px; width: 65px;">Break In</th>
                <th style="padding: 4px; width: 65px;">Time Out</th>
                <th style="padding: 4px; text-align: right; width: 45px;">Hrs</th>
                <th style="padding: 4px; text-align: right; width: 40px;">OT</th>
                <th style="padding: 4px; text-align: right; width: 45px;">Late/UT</th>
                <th style="padding: 4px; text-align: right; width: 75px;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($timesheets as $dateKey => $day)
            <tr style="border-bottom: 1px solid #edf2f7; text-align: center;">
                <td style="padding: 3px 4px; text-align: left; white-space: nowrap;">
                    <div style="display: flex; align-items: center; gap: 4px;">
                        <button type="button"
                            onclick="window.openAdjustmentModal('{{ $day['date'] }}', '{{ $day['time_in'] !== '---' ? $day['time_in'] : '' }}', '{{ $day['time_out'] !== '---' ? $day['time_out'] : '' }}')"
                            style="cursor: pointer; padding: 1px 3px; border: 1px solid #cbd5e1; border-radius: 3px; background-color: #fff; font-size: 10px; color: #2563eb;"
                            title="Edit Logs">
                            📝
                        </button>

                        <form action="{{ route('attendance-logs.destroy-day') }}" method="POST" style="margin: 0; display: inline;" onsubmit="return confirm('Are you sure you want to completely clear logs for {{ $day['date'] }}?');">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="employee_id" value="{{ $employee->employeeid ?? $employee->id }}">
                            <input type="hidden" name="log_date" value="{{ \Carbon\Carbon::parse($day['date'])->format('Y-m-d') }}">
                            <button type="submit"
                                style="cursor: pointer; padding: 1px 3px; border: 1px solid #fee2e2; border-radius: 3px; background-color: #fef2f2; font-size: 10px; color: #dc2626;"
                                title="Remove Logs">
                                🗑️
                            </button>
                        </form>
                        <strong style="margin-left: 2px; color: #1e293b;">{{ $day['date'] }}</strong>
                    </div>
                </td>
                <td style="padding: 3px 4px; text-align: left;">
                    <select name="timesheet[{{ $dateKey }}][holiday_id]" onchange="calculatePayrollTable()" style="padding: 1px 4px; border: 1px solid #cbd5e1; border-radius: 3px; font-size: 11px; background-color: #fff; width: 100%; max-width: 120px; height: 22px;">
                        @foreach($holidays as $holiday)
                        <option value="{{ $holiday->id }}" data-rate="{{ $holiday->percentage }}" {{ (float)$holiday->percentage === 0.0 ? 'selected' : '' }}>
                            {{ $holiday->type }} ({{ number_format($holiday->percentage, 0) }}%)
                        </option>
                        @endforeach
                    </select>
                </td>
                <td style="padding: 3px 4px; white-space: nowrap;">{{ $day['time_in'] }}</td>
                <td style="padding: 3px 4px; white-space: nowrap; color: #4a5568;">{{ $day['break_out'] }}</td>
                <td style="padding: 3px 4px; white-space: nowrap; color: #4a5568;">{{ $day['break_in'] }}</td>
                <td style="padding: 3px 4px; white-space: nowrap;">{{ $day['time_out'] }}</td>
                <td class="font-mono row-hrs" data-hours="{{ is_numeric($day['total_hours']) ? $day['total_hours'] : 0 }}" style="padding: 3px 4px; text-align: right;">{{ $day['total_hours'] }}</td>
                <td class="font-mono row-ot" data-ot="{{ is_numeric($day['total_overtime'] ?? 0) ? ($day['total_overtime'] ?? 0) : 0 }}" style="padding: 3px 4px; text-align: right;">{{ $day['total_overtime'] ?? 0 }}</td>
                <td class="font-mono row-late" data-late="{{ is_numeric($day['late_undertime'] ?? 0) ? ($day['late_undertime'] ?? 0) : 0 }}" style="padding: 3px 4px; text-align: right;">{{ $day['late_undertime'] ?? 0 }}</td>
                <td class="font-mono computed-amount" style="padding: 3px 4px; text-align: right; font-weight: bold; color: #0f172a;">0.00</td>
            </tr>
            @empty
            <tr>
                <td colspan="10" style="text-align: center; color: #888; padding: 12px; font-size: 12px;">No processed attendance history found.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot style="background-color: #f1f5f9; border-top: 2px solid #cbd5e1; font-weight: bold; border-bottom: 2px solid #cbd5e1;">
            <tr>
                <td colspan="6" style="padding: 5px 4px; text-align: right; color: #475569;">Total Summary Cost:</td>
                <td id="foot-total-hours-cost" class="font-mono" style="padding: 5px 4px; text-align: right; color: #0f172a;" title="Total Hours Cost">0.00</td>
                <td id="foot-total-ot-cost" class="font-mono" style="padding: 5px 4px; text-align: right; color: #16a34a;" title="Total OT Cost (Rate + 20%)">0.00</td>
                <td id="foot-total-late-cost" class="font-mono" style="padding: 5px 4px; text-align: right; color: #dc2626;" title="Total Late/UT Deductions">0.00</td>
                <td id="foot-final-gross-amount" class="font-mono" style="padding: 5px 4px; text-align: right; font-size: 12px; color: #1e293b; background-color: #e2e8f0;">0.00</td>
            </tr>
        </tfoot>
    </table>
    <!-- MODAL -->
    <div id="atlogModal" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.4); justify-content: center; align-items: center;">
        <div style="background-color: #fff; padding: 16px; border-radius: 6px; width: 100%; max-width: 320px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); font-family: sans-serif;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px;">
                <h4 style="margin: 0; font-size: 13px; color: #0f172a;">Adjust Attendance Stamp</h4>
                <span onclick="window.closeAdjustmentModal();" style="cursor: pointer; font-size: 18px; color: #94a3b8; font-weight: bold; padding: 0 4px; line-height: 1;">&times;</span>
            </div>

            <form action="{{ route('attendance-logs.store-double') }}" method="POST">
                @csrf
                <input type="hidden" name="employee_id" value="{{ $employee->employeeid ?? $employee->id }}">
                <input type="hidden" name="project_code" value="{{ $project_code ?? 'MAIN' }}">
                <input type="hidden" name="period_start_raw" value="{{ \Carbon\Carbon::parse($datePeriod->datefrom)->format('Y-m-d') }}">
                <input type="hidden" name="period_end_raw" value="{{ \Carbon\Carbon::parse($datePeriod->dateto)->format('Y-m-d') }}">
                <div style="margin-bottom: 10px;">
                    <label style="display: block; font-size: 11px; font-weight: bold; margin-bottom: 3px; color: #475569;">Target Date</label>
                    <input type="date"
                        id="modal_date"
                        name="log_date"
                        required
                        min="{{ \Carbon\Carbon::parse($datePeriod->datefrom)->format('Y-m-d') }}"
                        max="{{ \Carbon\Carbon::parse($datePeriod->dateto)->format('Y-m-d') }}"
                        style="width: 100%; padding: 4px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 12px; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 10px;">
                    <label style="display: block; font-size: 11px; font-weight: bold; margin-bottom: 3px; color: #475569;">Time In Stamp</label>
                    <input type="time" id="modal_timein" name="time_in" required style="width: 100%; padding: 4px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 12px; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 11px; font-weight: bold; margin-bottom: 3px; color: #475569;">Time Out Stamp</label>
                    <input type="time" id="modal_timeout" name="time_out" required style="width: 100%; padding: 4px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 12px; box-sizing: border-box;">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 6px;">
                    <button type="button" onclick="window.closeAdjustmentModal();" style="padding: 4px 10px; border: 1px solid #cbd5e1; background: #f8fafc; border-radius: 4px; font-size: 11px; cursor: pointer;">Cancel</button>
                    <button type="submit" style="padding: 4px 12px; border: none; background: #2563eb; color: #fff; border-radius: 4px; font-size: 11px; cursor: pointer; font-weight: bold;">Save Log Pair</button>
                </div>
            </form>
        </div>
    </div>
    <div class="footer-grid">
        <div class="footer-card">
            <h4>Adjustments</h4>
            <table style="font-size: 12px; margin: 0;">
                @forelse($adjustments as $adj)
                <tr>
                    <td>TITLE : {{ $adj->adjustmentName->name }}</td>
                    <td class="text-right"><strong>{{ number_format($adj->amount, 2) }}</strong></td>
                </tr>
                @empty
                <tr>
                    <td style="color:#888;">None recorded</td>
                </tr>
                @endforelse
            </table>
        </div>
        <div class="footer-card">
            <h4>Gov. Deductions</h4>
            <table style="font-size: 12px; margin: 0;">
                @forelse($govDeductions as $gov)
                <tr>
                    <td>TITLE : {{ $gov->govDeduction->title ?? 'NO DATA' }}</td>
                    <td class="text-right"><strong>{{ number_format($gov->govDeduction->amount, 2) }}</strong></td>
                </tr>
                @empty
                <tr>
                    <td style="color:#888;">None recorded</td>
                </tr>
                @endforelse
            </table>
        </div>
        <div class="footer-card">
            <h4>Other Deductions</h4>
            <table style="font-size: 12px; margin: 0;">
                @forelse($otherDeductions as $oth)
                <tr>
                    <td>
                        TITLE : {{ $oth->otherDeduction->title ?? 'NO DATA' }}
                    </td>
                    <td class="text-right"><strong>{{ number_format($oth->amount, 2) }}</strong></td>
                </tr>
                @empty
                <tr>
                    <td style="color:#888;">None recorded</td>
                </tr>
                @endforelse
            </table>
        </div>
    </div>
</body>

</html>
<script>
    (function() {
        // 💡 Single-line evaluation safe wrapper string parsing injection
        const dailyRate = parseFloat("{{ (float)($basicPayAmount ?? 0) }}") || 0;
        const otPercentageSetting = parseFloat("{{ (float)($datePeriod->overtime_rate ?? 0) }}");
        const hourlyRate = dailyRate / 8;
        // const otBaseRate = 0;
        const otBaseRate = hourlyRate * (1 + (otPercentageSetting / 100));
        // const otBaseRate = hourlyRate * otPercentageSetting; // Hourly rate increased by 20% premium baseline
        window.calculatePayrollTable = function() {
            let globalHoursCostSummary = 0;
            let globalOtCostSummary = 0;
            let globalLateCostSummary = 0;
            let globalGrossAmountSummary = 0;
            // Loop over each row inside the table body elements
            document.querySelectorAll("#timesheet-table tbody tr").forEach(row => {
                const hrsCell = row.querySelector('.row-hrs');
                const otCell = row.querySelector('.row-ot');
                const lateCell = row.querySelector('.row-late');
                const amountCell = row.querySelector('.computed-amount');
                const selectElement = row.querySelector('select');
                if (!hrsCell || !amountCell || !selectElement) return;
                // 1. Gather raw metric input float representations
                const totalHours = parseFloat(hrsCell.getAttribute('data-hours')) || 0;
                const totalOt = parseFloat(otCell ? otCell.getAttribute('data-ot') : 0) || 0;
                const totalLate = parseFloat(lateCell ? lateCell.getAttribute('data-late') : 0) || 0;
                // 2. Identify active holiday dropdown percentage premium values
                const selectedOption = selectElement.options[selectElement.selectedIndex];
                const holidayBonusPercent = parseFloat(selectedOption.getAttribute('data-rate')) || 0;
                const holidayMultiplier = 1 + (holidayBonusPercent / 100);
                // 3. APPLY CUSTOM FORMULA SPECS PER ROW:
                const rowHoursCost = (totalHours * hourlyRate) * holidayMultiplier;
                const rowOtCost = (totalOt * otBaseRate) * holidayMultiplier;
                const rowLateCost = totalLate * hourlyRate; // Standard deduction rate
                // Row Gross Amount = Hours Cost + Overtime Cost - Deductions
                const rowFinalGross = (rowHoursCost + rowOtCost) - rowLateCost;
                // 4. Update the current row visual presentation element text
                amountCell.innerText = rowFinalGross.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                // 5. Accumulate system matrix aggregates
                globalHoursCostSummary += rowHoursCost;
                globalOtCostSummary += rowOtCost;
                globalLateCostSummary += rowLateCost;
                globalGrossAmountSummary += rowFinalGross;
            });
            // 6. UPDATE TABLE FOOTER WITH SUMMARIZED CALCULATIONS
            const formatConfig = {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            };
            document.getElementById('foot-total-hours-cost').innerText = globalHoursCostSummary.toLocaleString('en-US', formatConfig);
            document.getElementById('foot-total-ot-cost').innerText = globalOtCostSummary.toLocaleString('en-US', formatConfig);
            document.getElementById('foot-total-late-cost').innerText = globalLateCostSummary.toLocaleString('en-US', formatConfig);
            document.getElementById('foot-final-gross-amount').innerText = globalGrossAmountSummary.toLocaleString('en-US', formatConfig);
        };
        // Bootstrap execution right away once core components render onto user view ports
        document.addEventListener("DOMContentLoaded", function() {
            window.calculatePayrollTable();
        });
    })();
</script>

<script>
    window.openAdjustmentModal = function(rawDate, existingTimeIn, existingTimeOut) {
        const dateInput = document.getElementById('modal_date');
        const modalWrapper = document.getElementById('atlogModal');
        if (!modalWrapper) return;
        // 1. Handle Target Date
        if (rawDate) {
            // Fix for parsing local date strings securely
            let formattedDate = '';
            if (/^\d{4}-\d{2}-\d{2}$/.test(rawDate)) {
                formattedDate = rawDate;
            } else {
                let d = new Date(rawDate);
                if (!isNaN(d.getTime())) {
                    let year = d.getFullYear();
                    let month = String(d.getMonth() + 1).padStart(2, '0');
                    let day = String(d.getDate()).padStart(2, '0');
                    formattedDate = `${year}-${month}-${day}`;
                }
            }
            dateInput.value = formattedDate;
        } else {
            // FIX: Clear the date field completely on create instead of setting min date
            dateInput.value = '';
        }

        function convertTo24Hour(timeString) {
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
        // 2. FIX: Bind values exactly as they are. No fallback to "08:00" or "17:00"
        document.getElementById('modal_timein').value = convertTo24Hour(existingTimeIn);
        document.getElementById('modal_timeout').value = convertTo24Hour(existingTimeOut);
        // Open UI Box
        modalWrapper.style.display = 'flex';
    };
    window.closeAdjustmentModal = function() {
        const modalWrapper = document.getElementById('atlogModal');
        if (modalWrapper) {
            modalWrapper.style.display = 'none';
        }
    };
</script>