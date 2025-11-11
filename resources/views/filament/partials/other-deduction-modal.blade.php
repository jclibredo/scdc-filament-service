<div>
    <h3>Existing Deductions</h3>
    <table class="w-full border">
        <thead>
            <tr>
                <th class="border px-2 py-1">Deduction</th>
                <th class="border px-2 py-1">Amount</th>
                <th class="border px-2 py-1">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($deductionLogs as $log)
            <tr>
                <td class="border px-2 py-1">{{ $log->otherDeduction->title }}</td>
                <td class="border px-2 py-1">{{ number_format($log->amount, 2) }}</td>
                <td class="border px-2 py-1">
                    <form wire:submit.prevent="deleteDeduction({{ $log->id }})">
                        <button type="submit" class="text-red-600 hover:underline">Remove</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>