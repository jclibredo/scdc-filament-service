<?php

namespace App\Livewire;

use App\Models\OtherDeductionLog;
use Livewire\Component;

class OtherDeductionModal extends Component
{
    public $employeeId;
    public $datePeriodId;
    public $deductionLogs = [];

    public function mount($employeeId, $datePeriodId)
    {
        $this->employeeId = $employeeId;
        $this->datePeriodId = $datePeriodId;

        $this->loadDeductions();
    }

    public function loadDeductions()
    {
        $this->deductionLogs = OtherDeductionLog::where('employee_id', $this->employeeId)
            ->where('date_period_id', $this->datePeriodId)
            ->with('otherDeduction')
            ->get();
    }

    public function deleteDeduction($id)
    {
        OtherDeductionLog::find($id)?->delete();
        $this->loadDeductions(); // refresh list
        $this->dispatchBrowserEvent('notify', ['message' => 'Deduction removed']);
    }

    public function render()
    {
        return view('livewire.other-deduction-modal');
    }
}
