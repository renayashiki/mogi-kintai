<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminEditRequest extends FormRequest
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
            'new_clock_in' => ['required', 'date_format:H:i'],
            // ルール①：退勤は出勤より後（＝出勤より前ならエラー）
            'new_clock_out' => ['required', 'date_format:H:i', 'after:new_clock_in'],

            // 休憩1
            'new_rest1_in' => ['nullable', 'date_format:H:i', 'after:new_clock_in', 'before:new_clock_out'],
            'new_rest1_out' => ['nullable', 'date_format:H:i', 'after:new_rest1_in', 'before:new_clock_out'],

            // 休憩2（休憩1の後、かつ出勤・退勤の間）
            'new_rest2_in' => ['nullable', 'date_format:H:i', 'after:new_rest1_out', 'before:new_clock_out'],
            'new_rest2_out' => ['nullable', 'date_format:H:i', 'after:new_rest2_in', 'before:new_clock_out'],

            'comment' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages()
    {
        return [
            /**
             * 1. 出勤時間が退勤時間より後になっている場合、および退勤時間が出勤時間より前になっている場合
             */
            // after:new_clock_inに違反 ＝「退勤時間が出勤時間より前」の状態を検知
            'new_clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です',

            /**
             * 2. 休憩開始時間が出勤時間より前になっている場合、及び退勤時間より後になっている場合
             */
            // after:new_clock_inに違反 ＝「休憩開始時間が出勤時間より前」を検知
            'new_rest1_in.after' => '休憩時間が不適切な値です',
            'new_rest2_in.after' => '休憩時間が不適切な値です',
            // before:new_clock_outに違反 ＝「休憩開始時間が退勤時間より後」を検知
            'new_rest1_in.before' => '休憩時間が不適切な値です',
            'new_rest2_in.before' => '休憩時間が不適切な値です',

            /**
             * 3. 休憩終了時間が退勤時間より後になっている場合
             */
            // before:new_clock_outに違反 ＝「休憩終了時間が退勤時間より後」を検知
            'new_rest1_out.before' => '休憩時間もしくは退勤時間が不適切な値です',
            'new_rest2_out.before' => '休憩時間もしくは退勤時間が不適切な値です',

            /**
             * 4. 備考欄が未入力になっている場合
             */
            'comment.required' => '備考を記入してください',
        ];
    }
}
