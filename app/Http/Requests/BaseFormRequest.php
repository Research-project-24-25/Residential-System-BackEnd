<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Admin; // Assuming App\Models\Admin exists

abstract class BaseFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Default: allow all. Child classes should override if needed.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Helper to check if the current user is an admin.
     */
    protected function isAdmin(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }
        // Check against 'admins' table name (common for multi-auth) or Admin model instance
        return $user->getTable() === 'admins' || $user instanceof Admin;
    }

    /**
     * Helper to check if the current user is a resident.
     */
    protected function isResident(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }
        // Check against 'residents' table name
        return $user->getTable() === 'residents';
    }


    /**
     * Helper to check if the current user is authenticated.
     */
    protected function isAuthenticated(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Helper to determine if the current request is a PUT or PATCH request.
     */
    protected function isUpdateRequest(): bool
    {
        return $this->isMethod('PUT') || $this->isMethod('PATCH');
    }

    /**
     * Determine if the current action is for filtering.
     * Relies on action method name being 'filter' or 'index'.
     */
    protected function isFilterAction(): bool
    {
        $actionMethod = $this->route() ? $this->route()->getActionMethod() : null;
        return in_array($actionMethod, ['filter', 'index']);
    }

    /**
     * Provides common validation rules for filtering, sorting, and pagination.
     * Child classes can merge these with their specific filter field rules.
     */
    protected function getCommonFilterRules(): array
    {
        return [
            'filters' => ['sometimes', 'array'],
            'filters.search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filters.created_at' => ['sometimes', 'array'],
            'filters.created_at.from' => ['sometimes', 'nullable', 'date'],
            'filters.created_at.to' => ['sometimes', 'nullable', 'date', 'after_or_equal:filters.created_at.from'],
            'filters.updated_at' => ['sometimes', 'array'],
            'filters.updated_at.from' => ['sometimes', 'nullable', 'date'],
            'filters.updated_at.to' => ['sometimes', 'nullable', 'date', 'after_or_equal:filters.updated_at.from'],
            'filters.trashed' => ['sometimes', 'nullable', Rule::in(['only', 'with', 'true', 'false', '1', '0'])],
            'sort' => ['sometimes', 'array'],
            'sort.field' => ['sometimes', 'string'],
            'sort.direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     * Provides common filter rules if it's a filter action.
     * Child classes should merge their specific rules with the result of parent::rules().
     */
    public function rules(): array
    {
        if ($this->isFilterAction()) {
            return $this->getCommonFilterRules();
        }
        return [];
    }

    /**
     * Get custom messages for validation errors.
     * Child classes can override and merge with parent::messages().
     */
    public function messages(): array
    {
        $messages = [];
        if ($this->isFilterAction()) {
            $messages = [
                'sort.direction.in' => 'The sort direction must be either "asc" or "desc".',
                'per_page.integer' => 'The items per page must be an integer.',
                'per_page.min' => 'The items per page must be at least 1.',
                'per_page.max' => 'The items per page may not be greater than 100.',
                'filters.created_at.to.after_or_equal' => 'The "created to" date must be after or equal to the "created from" date.',
                'filters.updated_at.to.after_or_equal' => 'The "updated to" date must be after or equal to the "updated from" date.',
                'filters.trashed.in' => 'The trashed filter must be one of: only, with, true, false, 1, 0.',
            ];
        }
        return $messages;
    }
}
