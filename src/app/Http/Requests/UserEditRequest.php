<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserEditRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'clock_in' => ['required', 'date_format:H:i'],
            'clock_out' => ['required', 'date_format:H:i', 'after:clock_in'],

            // required から nullable に変更
            'rests.*.in' => ['nullable', 'date_format:H:i', 'after:clock_in', 'before:clock_out'],
            'rests.*.out' => ['nullable', 'date_format:H:i', 'after:rests.*.in', 'before:clock_out'],

            'comment' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages()
    {
        return [
            'clock_in.required'  => '出勤時間を入力してください',
            'clock_in.date_format' => '出勤時間は、\'00:00\'形式の半角で入力して下さい',
            'clock_out.required' => '退勤時間を入力してください',
            'clock_out.date_format' => '退勤時間は、\'00:00\'形式の半角で入力して下さい',
            'rests.*.in.date_format' => '休憩開始時間は、\'00:00\'形式の半角で入力して下さい',
            'rests.*.out.date_format' => '休憩終了時間は、\'00:00\'形式の半角で入力して下さい',
            // 1. 出勤・退勤（FN029-1）
            'clock_in.after' => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です',

            // 2. 休憩開始（FN029-2）
            'rests.*.in.after' => '休憩時間が不適切な値です',
            'rests.*.in.before' => '休憩時間が不適切な値です',
            'rests.*.out.after'  => '休憩時間が不適切な値です',

            // 3. 休憩終了（FN029-3）
            'rests.*.out.before' => '休憩時間もしくは退勤時間が不適切な値です',

            // 4. 備考（FN029-4）
            'comment.required' => '備考を記入してください',
            'comment.max' => '備考は255文字以内で記入してください',
        ];
    }

    public function attributes()
    {
        return [
            'clock_in' => '出勤時間',
            'clock_out' => '退勤時間',
            'rests.*.in' => '休憩開始時間',
            'rests.*.out' => '休憩終了時間',
            'comment' => '備考',
        ];
    }
}
