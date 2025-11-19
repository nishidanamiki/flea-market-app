<?php

namespace App\Http\Requests;

use App\Models\Address;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class PurchaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'payment' => ['required', 'in:card,konbini'],
            'address_id' => ['required', 'exists:addresses,id'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $user = Auth::user();
            $addressId = $this->input('address_id');

            if (!Address::where('id', $addressId)->where('user_id', $user->id)->exists()) {
                $validator->errors()->add('address_id', '不正な住所が指定されています');
            }
        });
    }

    public function messages()
    {
        return [
            'payment.required' => '支払い方法を選択してください',
            'payment.in' => '支払い方法が不正です',
            'address_id.required' => '配送先を選択してください',
            'address_id.exists' => '指定された住所が存在しません',
        ];
    }
}
