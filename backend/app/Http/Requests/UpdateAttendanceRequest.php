<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $attendanceId = $this->route('attendance');

        return [
            'user_id' => [
                'required',
                'string',
                Rule::exists(User::class, 'id'),
                Rule::unique('attendances', 'user_id')
                    ->where(fn ($q) => $q->where('date', $this->input('date')))
                    ->ignore($attendanceId),
            ],
            'date' => ['required', 'date'],
            'status' => ['required', 'in:present,absent,half-day'],
            'overtime_hours' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
        ];
    }
}
