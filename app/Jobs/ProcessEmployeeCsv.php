<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Models\EmployeeProjectHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Csv\Reader;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class ProcessEmployeeCsv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function handle(): void
    {
        $fullPath = Storage::disk('public')->path($this->filePath);
        if (!file_exists($fullPath)) {
            Log::error("CSV missing at path: " . $fullPath);
            return;
        }
        try {
            // Load CSV
            $csv = Reader::createFromPath($fullPath, 'r');
            $csv->setHeaderOffset(0);
            foreach ($csv as $row) {
                // Normalize fields
                $employeeId = trim($row['employeeid']);
                $firstname  = $this->cleanText($row['firstname']);
                $lastname   =  $this->cleanText($row['lastname']);
                $middlename = $this->cleanText(trim($row['middlename']));
                $empstatus =  $this->cleanText(trim($row['status']));
                $hiredDate  =  $this->parseDate($row['datehired']);
                $birthdate  =  $this->parseDate($row['birthdate']);
                $projectId  = trim($row['project_id']);
                $skillId    = trim($row['skill_id']);
                $employeeType =  trim($row['employeetype']);
                $Sex =  trim($row['sex']);
                $Email = $row['email'] == null ? '' : trim($row['email']);
                $Mobile =  trim($row['mobile']);
                $Address =   $this->cleanText($row['address']);

                // Create or update employee
                Employee::updateOrCreate(
                    [
                        'employeeid' => $employeeId,
                        'firstname'     => $firstname,
                        'lastname'      => $lastname,
                        'middlename'    => $middlename,
                        'datehired'     => $hiredDate,
                        'birthdate'     => $birthdate,
                        'skill_id'      => $skillId,
                        'project_id'    => $projectId,
                        'sex'           => $Sex,
                        'email'         => $Email,
                        'mobile'        => $Mobile,
                        'address'       => $Address
                    ]
                );
                // Save project history
                $prohistStatus =    EmployeeProjectHistory::create([
                    'employeeid'      => $employeeId, // employeeid = code
                    'projectid'       => $projectId,
                    'employeetype'    => $employeeType,
                    'employee_status' => $empstatus,
                    'datestarted'     => $hiredDate,
                    'status'          => 1,
                ]);
                Log::info('YOU GOT IT ' . $prohistStatus);
            }
            // ✅ Delete file after processing
            Storage::disk('public')->delete($this->filePath);

            Log::info('YOU GOT IT');
        } catch (Exception $e) {
            Log::error("CSV error: " . $e->getMessage());
            return;
        }
    }

    private function parseDate($value)
    {
        $value = trim((string) $value);

        if ($value === '' || strtolower($value) === 'n/a' || $value === '0') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (Exception $e) {
            Log::warning('Invalid date in CSV: ' . $value);
            return null;
        }
    }

    private function cleanText($value)
    {
        if ($value === null) return null;
        // ✅ Fix mojibake (ex: Ba�o → Baño)
        $value = utf8_encode($value);
        // Convert to UTF-8 from any possible encoding
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');

        // Remove invalid UTF-8 bytes
        $value = iconv('UTF-8', 'UTF-8//IGNORE', $value);

        // Remove control characters (hidden)
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);

        // ✅ Remove accents (á → a, ñ → n, ü → u)
        $value = Str::ascii($value);

        return trim($value);
    }
}
