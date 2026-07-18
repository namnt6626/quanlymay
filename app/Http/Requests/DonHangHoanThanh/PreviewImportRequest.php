<?php

namespace App\Http\Requests\DonHangHoanThanh;

use App\Models\DmKenhBan;
use Illuminate\Foundation\Http\FormRequest;

class PreviewImportRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'file_excel' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
            'kenh_ban' => ['required', DmKenhBan::activeNameRule()],
        ];
    }
}
