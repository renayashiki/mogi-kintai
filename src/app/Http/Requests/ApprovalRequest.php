<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApprovalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'application_id' => ['required', 'integer', 'exists:attendance_corrects,id'],
        ];
    }

    public function messages()
    {
        return [
            'application_id.required' => '申請IDが確認できません',
            'application_id.integer' => '申請IDは整数で入力してください',
            'application_id.exists' => '指定された申請データが存在しません',
        ];
    }
}
