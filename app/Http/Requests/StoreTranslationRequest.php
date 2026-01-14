<?php

namespace App\Http\Requests;

use App\Models\Locale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTranslationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $localeId = Locale::where('code', $this->input('locale'))->value('id');

        $uniqueKeyRule = Rule::unique('translations', 'key');
        if ($localeId) {
            $uniqueKeyRule = $uniqueKeyRule->where(
                fn ($query) => $query->where('locale_id', $localeId)
            );
        }

        return [
            'key' => ['required', 'string', 'max:255', $uniqueKeyRule],
            'content' => ['required', 'string'],
            'locale' => ['required', 'string', 'exists:locales,code'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:50', 'distinct'],
        ];
    }
}
