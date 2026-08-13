<?php
// app/Http/Requests/ReviewRequest.php
// Dipakai bersama untuk approve/revisi/tolak (pustakawan & admin & atasan)

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keputusan' => ['required', Rule::in(['setuju', 'revisi', 'tolak'])],
            'catatan_revisi' => ['required_if:keputusan,revisi', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'keputusan.required' => 'Keputusan wajib diisi.',
            'keputusan.in' => 'Keputusan tidak valid.',
            'catatan_revisi.required_if' => 'Catatan revisi wajib diisi jika keputusan revisi.',
        ];
    }
}