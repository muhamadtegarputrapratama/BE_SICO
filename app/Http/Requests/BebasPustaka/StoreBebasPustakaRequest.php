<?php
namespace App\Http\Requests\BebasPustaka;

use Illuminate\Foundation\Http\FormRequest;

class StoreBebasPustakaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('mahasiswa');
    }

    public function rules(): array
    {
        return [];
    }
}