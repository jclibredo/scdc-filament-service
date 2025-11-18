<div class="space-y-3">
    <h3 class="text-lg font-semibold">
        Active Earnings for {{ $employee->lastname }}, {{ $employee->firstname }}
    </h3>

    @if ($earnings->isEmpty())
    <p class="text-gray-600">No active earnings found.</p>
    @else
    <table class="min-w-full text-sm border-collapse border border-gray-200">
        <thead>
            <tr class="border-b">
                <th class="text-left py-2 px-2">Category</th>
                <th class="text-left py-2 px-2">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($earnings as $item)
            <tr class="border-b">
                <td class="py-2 px-2">
                    {{ DB::table('categories')->where('id', $item->category_id)->value('name') }}
                </td>
                <td class="py-2 px-2">
                    ₱{{ number_format($item->amount, 2) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>