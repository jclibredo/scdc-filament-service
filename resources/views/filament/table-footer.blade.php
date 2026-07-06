<div>
    {{-- 1. Employee Info Banner Card --}}
    <div style='padding: 0.75rem 1rem; margin: 1rem 1rem 1.25rem 1rem; border-left: 4px solid #d97706; background-color: rgba(254, 243, 199, 0.4); border-top-right-radius: 0.5rem; border-bottom-right-radius: 0.5rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);'>
        <div style='display: flex; flex-direction: column; gap: 0.5rem;'>
            <div style='display: flex; align-items: center; gap: 0.5rem;'>
                <span style='width: 0.4rem; height: 0.4rem; background-color: #f59e0b; border-radius: 9999px;'></span>
                <h3 style='font-size: 0.9rem; font-weight: 700; color: #111827; margin: 0; font-family: system-ui, sans-serif;'>
                    {{ $empFullname }} <span style='font-family: monospace; color: #b45309; font-size: 0.85rem;'>{{ $yearendid }}</span>
                </h3>
            </div>
            <div style='display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;'>
                {!! $formattedBadges !!}
            </div>
        </div>
    </div>

    {{-- 2. Two-Column Breakdown & Calculations Section --}}
    <div style="display: flex; justify-content: space-between; padding: 0 1rem 1.5rem 1rem; font-family: system-ui, sans-serif; gap: 2rem;">

        {{-- Left Side: Itemized Lists --}}
        <div style="flex: 1; display: flex; flex-direction: column; gap: 1rem;">
            {{-- Adjustments --}}
            @if($adjustments->isNotEmpty())
            <div>
                <div style="font-weight: 700; color: #111827; margin-bottom: 0.35rem; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em;">Adjustments</div>
                @foreach ($adjustments as $item)
                <div style="display: flex; justify-content: space-between; max-width: 400px; font-size: 0.75rem; margin-bottom: 0.2rem;">
                    <div style="color: #4b5563;">{{ Str::upper($item->adjustmentName?->name ?? 'UNKNOWN') }}:</div>
                    <div style="font-family: monospace; font-weight: 700; color: #111827;">₱{{ number_format($item->amount, 2) }}</div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Gov Deductions --}}
            @if($govDeductions->isNotEmpty())
            <div>
                <div style="font-weight: 700; color: #111827; margin-bottom: 0.35rem; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em;">Gov Deductions</div>
                @foreach ($govDeductions as $item)
                <div style="display: flex; justify-content: space-between; max-width: 400px; font-size: 0.75rem; margin-bottom: 0.2rem;">
                    <div style="color: #4b5563;">{{ Str::upper($item->govDeduction?->title ?? 'UNKNOWN') }}:</div>
                    <div style="font-family: monospace; font-weight: 700; color: #111827;">₱{{ number_format($item->amount, 2) }}</div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Other Deductions --}}
            @if($otherDeductions->isNotEmpty())
            <div>
                <div style="font-weight: 700; color: #111827; margin-bottom: 0.35rem; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em;">Other Deductions</div>
                @foreach ($otherDeductions as $item)
                <div style="display: flex; justify-content: space-between; max-width: 400px; font-size: 0.75rem; margin-bottom: 0.2rem;">
                    <div style="color: #4b5563;">{{ Str::upper($item->otherDeduction?->title ?? 'UNKNOWN') }}:</div>
                    <div style="font-family: monospace; font-weight: 700; color: #111827;">₱{{ number_format($item->amount, 2) }}</div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Right Side: Summary Math Formula Blocks --}}
        <div style="flex: 1; display: flex; flex-direction: column; align-items: flex-start; gap: 0.4rem; font-size: 0.75rem; font-weight: 600; color: #111827;">
            <div style="display: flex; justify-content: space-between; width: 100; max-width: 300px;">
                <span>Total 13th month</span>
                <span style="color: #16a34a; font-family: monospace; font-weight: 700;">+₱{{ number_format($total13thMonth, 2) }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; width: 100; max-width: 300px;">
                <span>Adjustment</span>
                <span style="color: #16a34a; font-family: monospace; font-weight: 700;">+₱{{ number_format($adjustments->sum('amount'), 2) }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; width: 100; max-width: 300px;">
                <span>Gov. Deduction</span>
                <span style="color: #dc2626; font-family: monospace; font-weight: 700;">-₱{{ number_format($govDeductions->sum('amount'), 2) }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; width: 100; max-width: 300px;">
                <span>Other Deduction</span>
                <span style="color: #dc2626; font-family: monospace; font-weight: 700;">-₱{{ number_format($otherDeductions->sum('amount'), 2) }}</span>
            </div>

            <div style="margin-top: 1rem; padding-top: 0.5rem; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; width: 100; max-width: 300px; font-weight: 700;">
                <span>Grand Total</span>
                <span style="color: #16a34a; font-size: 0.9rem; font-family: monospace;">₱{{ number_format(($total13thMonth + $adjustments->sum('amount')) - ($govDeductions->sum('amount') + $otherDeductions->sum('amount')), 2) }}</span>
            </div>
        </div>

    </div>
</div>