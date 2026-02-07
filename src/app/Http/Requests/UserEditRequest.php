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
            'new_clock_in' => ['required', 'date_format:H:i'],
            // 仕様1：退勤が出勤より前ならエラー
            'new_clock_out' => ['required', 'date_format:H:i', 'after:new_clock_in'],

            // 休憩配列（動的な数に対応）
            // 仕様2：休憩開始が出勤より前、または退勤より後ならエラー
            'rests.*.in' => ['nullable', 'date_format:H:i', 'after:new_clock_in', 'before:new_clock_out'],
            // 仕様3：休憩終了が退勤より後ならエラー
            'rests.*.out' => ['nullable', 'date_format:H:i', 'after:rests.*.in', 'before:new_clock_out'],

            // 仕様4：備考欄
            'comment' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages()
    {
        return [
            // 1. 出勤・退勤の不整合（テストケース「出勤時間が不適切な値です」に対応）
            'new_clock_in.after' => '出勤時間もしくは退勤時間が不適切な値です',
            'new_clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です',

            // 2. 休憩開始の不整合
            'rests.*.in.after' => '休憩時間が不適切な値です',
            'rests.*.in.before' => '休憩時間が不適切な値です',

            // 3. 休憩終了の不整合
            'rests.*.out.before' => '休憩時間もしくは退勤時間が不適切な値です',

            // 4. 備考欄（仕様書の文言に完全一致）
            'comment.required' => '備考を記入してください',
        ];
    }
}
